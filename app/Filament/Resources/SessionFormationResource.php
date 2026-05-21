<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SessionFormationResource\Pages;
use App\Filament\Resources\SessionFormationResource\RelationManagers;
use App\Models\SessionFormation;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;

class SessionFormationResource extends Resource
{
    protected static ?string $model = SessionFormation::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Émargement';
    protected static ?string $navigationLabel = 'Pilotage sessions';
    protected static ?string $modelLabel = 'Session de formation';
    protected static ?string $pluralModelLabel = 'Sessions de formation';
    protected static ?int $navigationSort = 1;

    // ── Scope : un formateur ne voit que ses sessions ─────────────────────────
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user  = auth()->user();

        if ($user->isFormateur()) {
            $query->where('user_id', $user->id);
        }

        return $query->with(['formateur', 'demiJournees', 'participants']);
    }

    // ── Formulaire de création / édition ──────────────────────────────────────
    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Section::make('Session')
                ->description('Les informations visibles par l’équipe de formation et utilisées dans les exports.')
                ->icon('heroicon-o-academic-cap')
                ->schema([
                    Forms\Components\TextInput::make('intitule')
                        ->label('Formation')
                        ->placeholder('Ex. Passeport de prévention - session initiale')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('description')
                        ->label('Description')
                        ->rows(3)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('lieu')
                        ->label('Lieu de formation')
                        ->placeholder('Salle, site, ville')
                        ->maxLength(255),

                    Forms\Components\Select::make('user_id')
                        ->label('Formateur responsable')
                        ->options(User::where('is_active', true)->pluck('name', 'id'))
                        ->searchable()
                        ->required()
                        ->default(fn () => auth()->id())
                        ->disabled(fn () => auth()->user()->isFormateur()),
                ])
                ->columns(2),

            Forms\Components\Section::make('Demi-journées')
                ->description('Créez le planning d’émargement. Le formateur ouvrira chaque demi-journée le moment venu.')
                ->icon('heroicon-o-calendar-days')
                ->schema([
                    Forms\Components\Repeater::make('demiJournees')
                        ->relationship()
                        ->label('Planning')
                        ->schema([
                            Forms\Components\DatePicker::make('date')
                                ->label('Date')
                                ->required()
                                ->displayFormat('d/m/Y')
                                ->native(false),

                            Forms\Components\Select::make('creneau')
                                ->label('Créneau')
                                ->options([
                                    'matin'      => 'Matin',
                                    'apres_midi' => 'Après-midi',
                                ])
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    if ($state === 'matin') {
                                        $set('heure_debut', '08:30');
                                        $set('heure_fin', '12:00');
                                    } else {
                                        $set('heure_debut', '13:00');
                                        $set('heure_fin', '17:00');
                                    }
                                }),

                            Forms\Components\TimePicker::make('heure_debut')
                                ->label('Début')
                                ->seconds(false)
                                ->default('08:30'),

                            Forms\Components\TimePicker::make('heure_fin')
                                ->label('Fin')
                                ->seconds(false)
                                ->default('12:00'),
                        ])
                        ->columns(4)
                        ->defaultItems(1)
                        ->addActionLabel('Ajouter un créneau')
                        ->reorderable()
                        ->reorderableWithButtons()
                        ->itemLabel(fn (array $state): ?string => filled($state['date'] ?? null)
                            ? ($state['date'] . ' · ' . (($state['creneau'] ?? 'matin') === 'matin' ? 'Matin' : 'Après-midi'))
                            : 'Créneau à planifier')
                        ->mutateRelationshipDataBeforeCreateUsing(function (array $data, $livewire): array {
                            // L'ordre est calculé après le repeater
                            return $data;
                        })
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Paramètres RGPD')
                ->description('À ajuster seulement si la politique de conservation de la session diffère du réglage global.')
                ->icon('heroicon-o-shield-check')
                ->schema([
                    Forms\Components\TextInput::make('purge_delai_jours')
                        ->label('Délai de purge NIR')
                        ->numeric()
                        ->default(30)
                        ->minValue(1)
                        ->maxValue(365)
                        ->suffix('jours')
                        ->helperText('Les NIR seront automatiquement supprimés ce nombre de jours après que vous ayez déclaré la session à la CDC.'),
                ])
                ->collapsible()
                ->collapsed(),
        ]);
    }

    // ── Table (liste des sessions) ────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([
                Tables\Columns\BadgeColumn::make('statut')
                    ->label('Statut')
                    ->colors([
                        'gray'    => 'planifiee',
                        'warning' => 'en_cours',
                        'success' => 'terminee',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'planifiee' => 'Planifiée',
                        'en_cours'  => 'En cours',
                        'terminee'  => 'Terminée',
                        default     => $state,
                    }),

                Tables\Columns\TextColumn::make('intitule')
                    ->label('Formation')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (SessionFormation $s) => collect([
                        $s->lieu,
                        $s->formateur?->name ? 'Formateur : ' . $s->formateur->name : null,
                    ])->filter()->join(' · '))
                    ->wrap(),

                Tables\Columns\TextColumn::make('prochain_creneau')
                    ->label('Prochain créneau')
                    ->getStateUsing(function (SessionFormation $s) {
                        $dj = $s->demiJournees
                            ->where('statut_emargement', '!=', 'cloture')
                            ->sortBy(fn ($dj) => $dj->date->format('Ymd') . str_pad((string) $dj->ordre, 3, '0', STR_PAD_LEFT))
                            ->first();

                        if (!$dj) {
                            return 'Aucun créneau ouvert';
                        }

                        return $dj->date->format('d/m/Y') . ' · '
                            . ($dj->creneau === 'matin' ? 'Matin' : 'Après-midi')
                            . ' · ' . $dj->heure_debut . '-' . $dj->heure_fin;
                    })
                    ->icon('heroicon-o-calendar')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('demiJournees_count')
                    ->label('Créneaux')
                    ->counts('demiJournees')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('participants_count')
                    ->label('Participants')
                    ->counts('participants')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('taux_completion')
                    ->label('Signatures')
                    ->getStateUsing(fn (SessionFormation $s) => $s->taux_completion . ' %')
                    ->badge()
                    ->color(fn (SessionFormation $s) => $s->taux_completion >= 90 ? 'success' : ($s->taux_completion >= 60 ? 'warning' : 'danger'))
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('lien_actif')
                    ->label('Lien')
                    ->boolean()
                    ->trueIcon('heroicon-o-link')
                    ->falseIcon('heroicon-o-no-symbol')
                    ->alignCenter(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('statut')
                    ->label('Statut')
                    ->options([
                        'planifiee' => 'Planifiée',
                        'en_cours'  => 'En cours',
                        'terminee'  => 'Terminée',
                    ]),
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Formateur')
                    ->options(User::where('is_active', true)->pluck('name', 'id'))
                    ->hidden(fn () => auth()->user()->isFormateur()),
            ])
            ->actions([
                Tables\Actions\Action::make('voir')
                    ->label('Piloter')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->url(fn (SessionFormation $s) => static::getUrl('view', ['record' => $s])),

                Tables\Actions\Action::make('copier_lien')
                    ->label('Participant')
                    ->icon('heroicon-o-clipboard-document')
                    ->action(fn () => null) // Le JS copy est géré côté vue
                    ->extraAttributes(fn (SessionFormation $s) => [
                        'x-on:click' => "navigator.clipboard.writeText('{$s->url_participant}'); \$dispatch('notify', {message: 'Lien copié !'})",
                    ]),

                Tables\Actions\Action::make('copier_lien_formateur')
                    ->label('Formateur')
                    ->icon('heroicon-o-key')
                    ->action(fn () => null)
                    ->extraAttributes(fn (SessionFormation $s) => [
                        'x-on:click' => "navigator.clipboard.writeText('{$s->url_formateur}'); \$dispatch('notify', {message: 'Lien formateur copié !'})",
                    ]),

                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->isAdmin()),
                ]),
            ])
            ->emptyStateHeading('Aucune session')
            ->emptyStateDescription('Créez une session, planifiez ses créneaux, puis partagez le lien participant.')
            ->emptyStateActions([
                Tables\Actions\Action::make('create')
                    ->label('Créer une session')
                    ->url(static::getUrl('create'))
                    ->icon('heroicon-o-plus'),
            ]);
    }

    // ── Infolist (page de détail de la session) ───────────────────────────────
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Vue d’ensemble')
                ->icon('heroicon-o-presentation-chart-bar')
                ->schema([
                    Infolists\Components\BadgeEntry::make('statut')
                        ->label('Statut')
                        ->colors([
                            'gray'    => 'planifiee',
                            'warning' => 'en_cours',
                            'success' => 'terminee',
                        ]),
                    Infolists\Components\TextEntry::make('intitule')
                        ->label('Formation')
                        ->weight('bold'),
                    Infolists\Components\TextEntry::make('formateur.name')
                        ->label('Formateur'),
                    Infolists\Components\TextEntry::make('lieu')
                        ->label('Lieu')
                        ->placeholder('Non renseigné'),
                    Infolists\Components\TextEntry::make('participants_count')
                        ->label('Participants')
                        ->state(fn (SessionFormation $s) => $s->participants()->count()),
                    Infolists\Components\TextEntry::make('demi_journees_count')
                        ->label('Créneaux')
                        ->state(fn (SessionFormation $s) => $s->demiJournees()->count()),
                    Infolists\Components\TextEntry::make('taux_completion')
                        ->label('Signatures')
                        ->state(fn (SessionFormation $s) => $s->taux_completion . ' %')
                        ->badge()
                        ->color(fn (SessionFormation $s) => $s->taux_completion >= 90 ? 'success' : ($s->taux_completion >= 60 ? 'warning' : 'danger')),
                ])
                ->columns(3),

            Infolists\Components\Section::make('Liens de session')
                ->icon('heroicon-o-link')
                ->schema([
                    Infolists\Components\TextEntry::make('url_participant')
                        ->label('Participants')
                        ->copyable()
                        ->copyMessage('Lien copié !')
                        ->fontFamily('mono'),

                    Infolists\Components\TextEntry::make('url_formateur')
                        ->label('Formateur')
                        ->copyable()
                        ->copyMessage('Lien copié !')
                        ->fontFamily('mono'),

                    Infolists\Components\TextEntry::make('lien_expire_at')
                        ->label('Expiration')
                        ->dateTime('d/m/Y à H\hi')
                        ->placeholder('Calculée à la fin de session'),
                ])
                ->columns(1),
        ]);
    }

    // ── Relation managers (onglets dans la page de détail) ───────────────────
    public static function getRelationManagers(): array
    {
        return [
            RelationManagers\DemiJourneesRelationManager::class,
            RelationManagers\ParticipantsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSessionFormations::route('/'),
            'create' => Pages\CreateSessionFormation::route('/create'),
            'view'   => Pages\ViewSessionFormation::route('/{record}'),
            'edit'   => Pages\EditSessionFormation::route('/{record}/edit'),
        ];
    }
}
