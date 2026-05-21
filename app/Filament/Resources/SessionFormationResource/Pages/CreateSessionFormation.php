<?php

namespace App\Filament\Resources\SessionFormationResource\Pages;

use App\Filament\Resources\SessionFormationResource;
use App\Models\AuditLog;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateSessionFormation extends CreateRecord
{
    protected static string $resource = SessionFormationResource::class;
    protected static ?string $title = 'Créer une session';

    protected function afterCreate(): void
    {
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
