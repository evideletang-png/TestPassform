<?php

namespace App\Filament\Widgets;

use App\Models\SessionFormation;
use App\Models\Participant;
use App\Models\Emargement;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = auth()->user();

        $sessionsQuery = $user->isAdmin()
            ? SessionFormation::query()
            : SessionFormation::where('user_id', $user->id);

        $sessionsActives  = (clone $sessionsQuery)->whereIn('statut', ['planifiee', 'en_cours'])->count();
        $sessionsTerminees = (clone $sessionsQuery)->where('statut', 'terminee')->count();

        // Participants sur les sessions accessibles
        $sessionIds   = (clone $sessionsQuery)->pluck('id');
        $nbParticipants = Participant::whereIn('session_formation_id', $sessionIds)->count();

        // Taux global d'émargement (signatures / total attendu)
        $totalAttendu = 0;
        $totalSigne   = 0;
        (clone $sessionsQuery)
            ->with(['demiJournees.emargements', 'participants'])
            ->whereIn('statut', ['en_cours', 'terminee'])
            ->get()
            ->each(function (SessionFormation $s) use (&$totalAttendu, &$totalSigne) {
                $nbP = $s->participants->count();
                foreach ($s->demiJournees as $dj) {
                    $totalAttendu += $nbP;
                    $totalSigne   += $dj->emargements->whereNotNull('signature')->count();
                }
            });

        $tauxGlobal = $totalAttendu > 0 ? round(($totalSigne / $totalAttendu) * 100, 1) : 0;

        // Purges en attente (sessions déclarées CDC, purge non effectuée)
        $purgesEnAttente = $user->isAdmin()
            ? SessionFormation::whereNotNull('purge_at')
                ->where('purge_effectuee', false)
                ->where('purge_at', '>', now())
                ->count()
            : 0;

        $stats = [
            Stat::make('Sessions actives', $sessionsActives)
                ->description("dont {$sessionsTerminees} terminée(s)")
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('primary'),

            Stat::make('Participants inscrits', $nbParticipants)
                ->description('Toutes sessions confondues')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Taux d\'émargement', $tauxGlobal . ' %')
                ->description('Signatures / total attendu')
                ->descriptionIcon('heroicon-m-pencil')
                ->color($tauxGlobal >= 90 ? 'success' : ($tauxGlobal >= 60 ? 'warning' : 'danger')),
        ];

        if ($user->isAdmin() && $purgesEnAttente > 0) {
            $stats[] = Stat::make('Purges RGPD planifiées', $purgesEnAttente)
                ->description('NIR à supprimer prochainement')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('warning');
        }

        return $stats;
    }

    // Rafraîchissement automatique toutes les 30 secondes
    protected static ?string $pollingInterval = '30s';
}
