<?php

namespace App\Filament\Resources\SessionFormationResource\Pages;

use App\Filament\Resources\SessionFormationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

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
