<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;
    protected static ?string $title   = 'Formateurs';

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Nouveau formateur')];
    }
}

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
    protected static ?string $title   = 'Créer un formateur';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function (Actions\DeleteAction $action) {
                    // Empêcher la suppression de son propre compte
                    if ($this->record->id === auth()->id()) {
                        \Filament\Notifications\Notification::make()
                            ->title('Impossible de supprimer votre propre compte.')
                            ->danger()
                            ->send();
                        $action->cancel();
                    }
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
