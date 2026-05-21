<?php

// routes/console.php  (Laravel 11+)
// Ce fichier remplace App\Console\Kernel pour la planification des tâches.

use App\Console\Commands\PurgerDonneesRgpd;
use App\Models\SessionFormation;
use Illuminate\Support\Facades\Schedule;

// ── Purge RGPD : NIR + codes + logs anciens — tous les jours à 2h du matin ───
Schedule::command(PurgerDonneesRgpd::class)
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('Purge RGPD exécutée avec succès.');
    })
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Échec de la purge RGPD — vérifier les logs.');
    });

// ── Mise à jour automatique du statut des sessions ────────────────────────────
// Passe "planifiée" → "en cours" quand la 1ère demi-journée commence.
// Passe "en cours" → "terminée" quand toutes les demi-journées sont clôturées.
Schedule::call(function () {
    // Planifiée → En cours
    SessionFormation::where('statut', 'planifiee')
        ->whereHas('demiJournees', function ($q) {
            $q->where('date', '<=', today())
              ->where('statut_emargement', '!=', 'ferme');
        })
        ->update(['statut' => 'en_cours']);

    // En cours → Terminée (toutes DJ clôturées ou dates passées)
    SessionFormation::where('statut', 'en_cours')
        ->whereDoesntHave('demiJournees', function ($q) {
            $q->where('date', '>=', today());
        })
        ->each(function (SessionFormation $session) {
            $session->update(['statut' => 'terminee']);
            $session->planifierExpirationLien();
        });
})->hourly()->name('mise-a-jour-statuts-sessions')->withoutOverlapping();
