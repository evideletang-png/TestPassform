<?php

namespace App\Filament\Resources\SessionFormationResource\Pages;

use App\Filament\Resources\SessionFormationResource;
use App\Models\AuditLog;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewSessionFormation extends ViewRecord
{
    protected static string $resource = SessionFormationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

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
