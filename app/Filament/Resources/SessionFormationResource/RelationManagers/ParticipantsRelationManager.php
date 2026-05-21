<?php

namespace App\Filament\Resources\SessionFormationResource\RelationManagers;

use App\Models\Participant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class ParticipantsRelationManager extends RelationManager
{
    protected static string $relationship = 'participants';
    protected static ?string $title = 'Participants et codes';

    public function form(Form $form): Form
    {
        // Formulaire d'ajout manuel (rare — normalement via portail)
        return $form->schema([
            Forms\Components\TextInput::make('prenom')->label('Prénom')->required(),
            Forms\Components\TextInput::make('nom')->label('Nom')->required(),
            Forms\Components\TextInput::make('nom_naissance')->label('Nom de naissance')->nullable(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nom_complet')
            ->columns([
                Tables\Columns\TextColumn::make('code_identification')
                    ->label('Code')
                    ->badge()
                    ->color('primary')
                    ->weight('bold')
                    ->width('60px'),

                Tables\Columns\TextColumn::make('prenom')
                    ->label('Participant')
                    ->getStateUsing(fn (Participant $p) => $p->nom_complet)
                    ->searchable(['prenom', 'nom'])
                    ->weight('bold')
                    ->description(fn (Participant $p) => $p->nom_naissance ? 'Naissance : ' . $p->nom_naissance : null),

                Tables\Columns\TextColumn::make('nom_naissance')
                    ->label('Nom de naissance')
                    ->placeholder('—')
                    ->toggleable(),

                // NIR masqué (déchiffré côté serveur, affiché masqué)
                Tables\Columns\TextColumn::make('nir_masque')
                    ->label('NIR')
                    ->getStateUsing(fn (Participant $p) => $p->nir_masque)
                    ->toggleable(isToggledHiddenByDefault: true),

                // Signatures par demi-journée (calculé dynamiquement)
                Tables\Columns\TextColumn::make('nb_signatures')
                    ->label('Signatures')
                    ->getStateUsing(function (Participant $p) {
                        $total  = $this->getOwnerRecord()->demiJournees()->count();
                        $signes = $p->emargements()->whereNotNull('signature')->count();
                        return "{$signes} / {$total}";
                    })
                    ->badge()
                    ->color(function (Participant $p) {
                        $total = max(1, $this->getOwnerRecord()->demiJournees()->count());
                        $signes = $p->emargements()->whereNotNull('signature')->count();
                        $ratio = $signes / $total;

                        return $ratio >= 1 ? 'success' : ($ratio > 0 ? 'warning' : 'danger');
                    })
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('inscrit_at')
                    ->label('Inscrit le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('inscrit_at', 'asc')
            ->filters([])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Ajouter un participant')
                    ->mutateFormDataUsing(function (array $data) {
                        $session = $this->getOwnerRecord();
                        $data['code_identification'] = $session->genererCodeUnique();
                        $data['inscrit_at']          = now();
                        $data['ip_inscription']      = request()->ip();
                        return $data;
                    }),
            ])
            ->actions([
                // Révéler le code en clair (journalisé)
                Tables\Actions\Action::make('voir_code')
                    ->label('Voir code')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->modalHeading('Code d\'identification')
                    ->modalContent(fn (Participant $p) => new \Illuminate\Support\HtmlString(
                        '<div style="text-align:center;font-size:3rem;font-weight:700;letter-spacing:.5rem;color:#1D9E75;padding:1rem">'
                        . $p->code_identification
                        . '</div><p style="text-align:center;color:#888">Participant : ' . $p->nom_complet . '</p>'
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fermer')
                    ->action(function (Participant $p) {
                        // Journaliser la consultation du code
                        $p->marquerCodeConsulte(auth()->id());
                    }),

                // Voir le détail des émargements du participant
                Tables\Actions\Action::make('emargements')
                    ->label('Suivi')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->modalHeading(fn (Participant $p) => 'Émargements de ' . $p->nom_complet)
                    ->modalContent(function (Participant $p) {
                        $session = $this->getOwnerRecord();
                        $rows = '';
                        foreach ($session->demiJournees as $dj) {
                            $ema   = $p->getEmargementPour($dj);
                            $signé = $ema && $ema->est_signe;
                            $icon  = $signé ? '✅' : '⬜';
                            $heure = $signé ? $ema->signe_at->format('d/m H:i') : 'Non signé';
                            $rows .= "<tr><td style='padding:6px 8px'>{$dj->libelle}</td><td style='padding:6px 8px;text-align:center'>{$icon}</td><td style='padding:6px 8px;color:#888'>{$heure}</td></tr>";
                        }
                        return new \Illuminate\Support\HtmlString(
                            "<table style='width:100%;border-collapse:collapse'><thead><tr>
                            <th style='text-align:left;padding:6px 8px;border-bottom:1px solid #eee'>Demi-journée</th>
                            <th style='padding:6px 8px;border-bottom:1px solid #eee'>Signé</th>
                            <th style='padding:6px 8px;border-bottom:1px solid #eee'>Horodatage</th>
                            </tr></thead><tbody>{$rows}</tbody></table>"
                        );
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fermer'),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()->isAdmin()),
            ])
            ->emptyStateHeading('Aucun participant')
            ->emptyStateDescription('Les participants apparaîtront ici après inscription via le lien public.');
    }
}
