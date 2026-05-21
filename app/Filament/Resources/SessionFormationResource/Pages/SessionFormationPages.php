<?php

namespace App\Filament\Resources\SessionFormationResource\Pages;

use App\Filament\Resources\SessionFormationResource;
use App\Models\AuditLog;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\ViewRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

// ── Liste ─────────────────────────────────────────────────────────────────────
class ListSessionFormations extends ListRecords
{
    protected static string $resource = SessionFormationResource::class;
    protected static ?string $title = 'Sessions de formation';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nouvelle session'),
        ];
    }
}

// ── Création ─────────────────────────────────────────────────────────────────
class CreateSessionFormation extends CreateRecord
{
    protected static string $resource = SessionFormationResource::class;
    protected static ?string $title = 'Créer une session';

    protected function afterCreate(): void
    {
        // Calcul de l'ordre sur chaque demi-journée après création
        $session = $this->record;
        $session->demiJournees()->orderBy('date')->orderBy('creneau')->each(function ($dj, $index) {
            $dj->update(['ordre' => $index + 1]);
        });

        AuditLog::journaliser('session_creee', $session, auth()->id(), [
            'intitule' => $session->intitule,
        ]);

        Notification::make()
            ->title('Session créée')
            ->body('Le lien participant a été généré automatiquement.')
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}

// ── Vue détaillée ─────────────────────────────────────────────────────────────
class ViewSessionFormation extends ViewRecord
{
    protected static string $resource = SessionFormationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            // Désactiver / réactiver le lien participant
            Actions\Action::make('toggle_lien')
                ->label(fn () => $this->record->lien_actif ? 'Désactiver le lien' : 'Réactiver le lien')
                ->icon(fn () => $this->record->lien_actif ? 'heroicon-o-no-symbol' : 'heroicon-o-link')
                ->color(fn () => $this->record->lien_actif ? 'danger' : 'success')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['lien_actif' => !$this->record->lien_actif]);
                    AuditLog::journaliser(
                        $this->record->lien_actif ? 'lien_active' : 'lien_desactive',
                        $this->record,
                        auth()->id()
                    );
                    Notification::make()
                        ->title($this->record->lien_actif ? 'Lien réactivé' : 'Lien désactivé')
                        ->success()
                        ->send();
                    $this->refreshFormData(['lien_actif']);
                }),

            // Déclarer à la CDC (déclenche la planification de la purge RGPD)
            Actions\Action::make('declarer_cdc')
                ->label('Déclarer à la CDC')
                ->icon('heroicon-o-building-library')
                ->color('warning')
                ->visible(fn () => $this->record->statut === 'terminee' && !$this->record->cdc_declare_at)
                ->requiresConfirmation()
                ->modalHeading('Déclarer cette session à la CDC ?')
                ->modalDescription(fn () => "Cette action planifiera la purge automatique des NIR dans {$this->record->purge_delai_jours} jours.")
                ->action(function () {
                    $this->record->planifierPurge();
                    AuditLog::journaliser('cdc_declaree', $this->record, auth()->id());
                    Notification::make()
                        ->title('Déclaration CDC enregistrée')
                        ->body("Purge des NIR planifiée dans {$this->record->purge_delai_jours} jours.")
                        ->success()
                        ->send();
                }),

            // Exports
            Actions\ActionGroup::make([
                Actions\Action::make('export_pdf')
                    ->label('Export PDF (émargements)')
                    ->icon('heroicon-o-document-text')
                    ->url(fn () => route('exports.pdf', $this->record))
                    ->openUrlInNewTab(),

                Actions\Action::make('export_excel')
                    ->label('Export Excel (CDC)')
                    ->icon('heroicon-o-table-cells')
                    ->url(fn () => route('exports.excel', $this->record))
                    ->openUrlInNewTab()
                    ->visible(fn () => auth()->user()->isAdmin()),
            ])->label('Exporter')->icon('heroicon-o-arrow-down-tray'),
        ];
    }
}

// ── Édition ──────────────────────────────────────────────────────────────────
class EditSessionFormation extends EditRecord
{
    protected static string $resource = SessionFormationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()->visible(fn () => auth()->user()->isAdmin()),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
