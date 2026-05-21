<?php

namespace App\Filament\Resources\SessionFormationResource\RelationManagers;

use App\Models\AuditLog;
use App\Models\DemiJournee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class DemiJourneesRelationManager extends RelationManager
{
    protected static string $relationship = 'demiJournees';
    protected static ?string $title = 'Demi-journées';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('date')
                ->label('Date')
                ->required()
                ->displayFormat('d/m/Y')
                ->native(false),

            Forms\Components\Select::make('creneau')
                ->label('Créneau')
                ->options(['matin' => 'Matin', 'apres_midi' => 'Après-midi'])
                ->required()
                ->live()
                ->afterStateUpdated(function ($state, Forms\Set $set) {
                    $set('heure_debut', $state === 'matin' ? '08:30' : '13:00');
                    $set('heure_fin',   $state === 'matin' ? '12:00' : '17:00');
                }),

            Forms\Components\TimePicker::make('heure_debut')->label('Début')->seconds(false)->default('08:30'),
            Forms\Components\TimePicker::make('heure_fin')->label('Fin')->seconds(false)->default('12:00'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('libelle')
            ->reorderable('ordre')
            ->columns([
                Tables\Columns\TextColumn::make('ordre')
                    ->label('N°')
                    ->width('40px'),

                Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('creneau')
                    ->label('Créneau')
                    ->formatStateUsing(fn ($s) => $s === 'matin' ? 'Matin' : 'Après-midi')
                    ->colors(['primary' => 'matin', 'success' => 'apres_midi']),

                Tables\Columns\TextColumn::make('heure_debut')
                    ->label('Horaire')
                    ->formatStateUsing(fn ($s, DemiJournee $r) => $r->heure_debut . ' – ' . $r->heure_fin),

                // Taux de signature des participants
                Tables\Columns\TextColumn::make('taux_signature')
                    ->label('Signatures')
                    ->getStateUsing(fn (DemiJournee $dj) => $dj->taux_signature . ' %')
                    ->alignCenter(),

                // Statut de l'émargement avec icône
                Tables\Columns\BadgeColumn::make('statut_emargement')
                    ->label('Émargement')
                    ->colors([
                        'gray'    => 'ferme',
                        'success' => 'ouvert',
                        'danger'  => 'cloture',
                    ])
                    ->formatStateUsing(fn ($s) => match ($s) {
                        'ferme'   => 'Fermé',
                        'ouvert'  => 'Ouvert',
                        'cloture' => 'Clôturé',
                        default   => $s,
                    }),

                Tables\Columns\IconColumn::make('signature_formateur')
                    ->label('Formateur')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-clock')
                    ->alignCenter(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Ajouter une demi-journée')
                    ->mutateFormDataUsing(function (array $data) {
                        // Calcul automatique de l'ordre
                        $maxOrdre = $this->getOwnerRecord()->demiJournees()->max('ordre') ?? 0;
                        $data['ordre'] = $maxOrdre + 1;
                        return $data;
                    }),
            ])
            ->actions([
                // Ouvrir l'émargement
                Tables\Actions\Action::make('ouvrir')
                    ->label('Ouvrir')
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->visible(fn (DemiJournee $dj) => $dj->statut_emargement === 'ferme')
                    ->requiresConfirmation()
                    ->action(function (DemiJournee $dj) {
                        $dj->ouvrir(auth()->id());
                        Notification::make()->title('Émargement ouvert')->success()->send();
                    }),

                // Clôturer l'émargement
                Tables\Actions\Action::make('cloturer')
                    ->label('Clôturer')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->visible(fn (DemiJournee $dj) => $dj->statut_emargement === 'ouvert')
                    ->requiresConfirmation()
                    ->modalHeading('Clôturer cet émargement ?')
                    ->modalDescription('Les participants ne pourront plus signer. Vous pourrez rouvrir si nécessaire.')
                    ->action(function (DemiJournee $dj) {
                        $dj->cloturer(auth()->id());
                        Notification::make()->title('Émargement clôturé')->success()->send();
                    }),

                // Rouvrir l'émargement (retardataire)
                Tables\Actions\Action::make('rouvrir')
                    ->label('Rouvrir')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (DemiJournee $dj) => $dj->statut_emargement === 'cloture')
                    ->requiresConfirmation()
                    ->action(function (DemiJournee $dj) {
                        $dj->ouvrir(auth()->id());
                        Notification::make()->title('Émargement rouvert')->warning()->send();
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()->isAdmin()),
            ]);
    }
}
