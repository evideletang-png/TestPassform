<?php

namespace App\Filament\Resources\SessionFormationResource\Pages;

use App\Filament\Resources\SessionFormationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

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
