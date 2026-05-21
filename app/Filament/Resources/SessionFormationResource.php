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
use Illuminate\Support\HtmlString;

class SessionFormationResource extends Resource
{
    protected static ?string $model = SessionFormation::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Sessions';
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

            Forms\Components\Section::make('Informations générales')
                ->schema([
                    Forms\Components\TextInput::make('intitule')
                        ->label('Intitulé de la formation')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('description')
                        ->label('Description')
                        ->rows(3)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('lieu')
                        ->label('Lieu de formation')
                        ->maxLength(255),

                    Forms\Components\Select::make('user_id')
                        ->label('Formateur responsable')
                        ->options(User::where('is_active', true)->pluck('name', 'id'))
                        ->searchable()
                        ->required()
                        ->default(fn () => auth()->id())
                        ->disabled(fn () => auth()->user()->isFormateur()),
                ])->columns(2),

            Forms\Components\Section::make('Demi-journées')
                ->description('Définissez chaque demi-journée de la session. L\'ordre est automatique.')
                ->schema([
                    Forms\Components\Repeater::make('demiJournees')
                        ->relationship()
                        ->label('')
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
                        ->addActionLabel('Ajouter une demi-journée')
                        ->reorderable()
                        ->reorderableWithButtons()
                        ->mutateRelationshipDataBeforeCreateUsing(function (array $data, $livewire): array {
                            // L'ordre est calculé après le repeater
                            return $data;
                        })
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Paramètres RGPD')
                ->schema([
                    Forms\Components\TextInput::make('purge_delai_jours')
                        ->label('Délai de purge NIR (jours après déclaration CDC)')
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
            ->columns([
                Tables\Columns\TextColumn::make('intitule')
                    ->label('Formation')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (SessionFormation $s) => $s->lieu),

                Tables\Columns\TextColumn::make('formateur.name')
                    ->label('Formateur')
                    ->sortable()
                    ->hidden(fn () => auth()->user()->isFormateur()),

                Tables\Columns\TextColumn::make('demiJournees_count')
                    ->label('Demi-journées')
                    ->counts('demiJournees')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('participants_count')
                    ->label('Participants')
                    ->counts('participants')
                    ->alignCenter(),

                // Taux de complétion avec barre de progression
                Tables\Columns\TextColumn::make('taux_completion')
                    ->label('Complétion')
                    ->getStateUsing(fn (SessionFormation $s) => $s->taux_completion . ' %')
                    ->alignCenter(),

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
                    ->label('Détail')
                    ->icon('heroicon-o-eye')
                    ->url(fn (SessionFormation $s) => static::getUrl('view', ['record' => $s])),

                Tables\Actions\Action::make('copier_lien')
                    ->label('Lien participant')
                    ->icon('heroicon-o-clipboard-document')
                    ->action(fn () => null) // Le JS copy est géré côté vue
                    ->extraAttributes(fn (SessionFormation $s) => [
                        'x-on:click' => "navigator.clipboard.writeText('{$s->url_participant}'); \$dispatch('notify', {message: 'Lien copié !'})",
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
            ->emptyStateDescription('Créez votre première session de formation.')
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
            Infolists\Components\Section::make('Informations')
                ->schema([
                    Infolists\Components\TextEntry::make('intitule')->label('Formation')->weight('bold'),
                    Infolists\Components\TextEntry::make('formateur.name')->label('Formateur'),
                    Infolists\Components\TextEntry::make('lieu')->label('Lieu')->placeholder('Non renseigné'),
                    Infolists\Components\BadgeEntry::make('statut')
                        ->label('Statut')
                        ->colors([
                            'gray'    => 'planifiee',
                            'warning' => 'en_cours',
                            'success' => 'terminee',
                        ]),
                ])->columns(2),

            Infolists\Components\Section::make('Liens de session')
                ->schema([
                    Infolists\Components\TextEntry::make('url_participant')
                        ->label('Lien participant (QR code en salle)')
                        ->copyable()
                        ->copyMessage('Lien copié !')
                        ->fontFamily('mono'),

                    Infolists\Components\TextEntry::make('url_formateur')
                        ->label('Lien formateur (privé)')
                        ->copyable()
                        ->copyMessage('Lien copié !')
                        ->fontFamily('mono'),

                    Infolists\Components\TextEntry::make('lien_expire_at')
                        ->label('Expiration du lien')
                        ->dateTime('d/m/Y à H\hi')
                        ->placeholder('Calculée à la fin de session'),
                ])->columns(1),
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
