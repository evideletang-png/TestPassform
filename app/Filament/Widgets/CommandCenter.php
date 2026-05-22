<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Parametres;
use App\Filament\Resources\SessionFormationResource;
use App\Filament\Resources\UserResource;
use App\Models\SessionFormation;
use Filament\Widgets\Widget;

class CommandCenter extends Widget
{
    protected static string $view = 'filament.widgets.command-center';
    protected static ?int $sort = 0;
    protected int | string | array $columnSpan = 'full';

    public function getViewData(): array
    {
        $user = auth()->user();

        $sessionsQuery = $user->isAdmin()
            ? SessionFormation::query()
            : SessionFormation::where('user_id', $user->id);

        $sessionEnCours = (clone $sessionsQuery)
            ->where('statut', 'en_cours')
            ->latest()
            ->first();

        return [
            'name' => $user->name,
            'isAdmin' => $user->isAdmin(),
            'sessionEnCours' => $sessionEnCours,
            'sessionsEnCoursCount' => (clone $sessionsQuery)->where('statut', 'en_cours')->count(),
            'sessionsPlanifieesCount' => (clone $sessionsQuery)->where('statut', 'planifiee')->count(),
            'createSessionUrl' => SessionFormationResource::getUrl('create'),
            'sessionsUrl' => SessionFormationResource::getUrl('index'),
            'usersUrl' => $user->isAdmin() ? UserResource::getUrl('index') : null,
            'parametresUrl' => $user->isAdmin() ? Parametres::getUrl() : null,
        ];
    }
}
