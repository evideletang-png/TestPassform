<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\SessionFormation;
use Illuminate\Console\Command;

class PurgerDonneesRgpd extends Command
{
    protected $signature   = 'rgpd:purger {--dry-run : Simulation sans suppression}';
    protected $description = 'Purge automatique des données sensibles (NIR, codes) et des logs > 12 mois';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $this->info($dryRun ? '[DRY-RUN] Simulation — aucune donnée ne sera supprimée.' : 'Lancement de la purge RGPD...');

        // ── 1. Sessions dont la purge est planifiée et échue ─────────────────
        $sessions = SessionFormation::where('purge_effectuee', false)
            ->whereNotNull('purge_at')
            ->where('purge_at', '<=', now())
            ->get();

        $this->info("{$sessions->count()} session(s) à purger.");

        foreach ($sessions as $session) {
            $this->line("  → Session #{$session->id} : {$session->intitule}");
            $nbParticipants = $session->participants()->whereNotNull('nir_encrypted')->count();
            $this->line("    NIR à effacer : {$nbParticipants}");

            if (!$dryRun) {
                $session->purgerDonneesSensibles();
                $this->info("    ✓ Purge effectuée.");
            }
        }

        // ── 2. Logs d'audit > 12 mois ─────────────────────────────────────────
        $nbLogsAnciens = AuditLog::where('created_at', '<', now()->subMonths(12))->count();
        $this->info("{$nbLogsAnciens} log(s) d'audit à supprimer (> 12 mois).");

        if (!$dryRun && $nbLogsAnciens > 0) {
            $supprime = AuditLog::purgerAnciensLogs();
            $this->info("  ✓ {$supprime} log(s) supprimé(s).");
        }

        // ── 3. Résumé ─────────────────────────────────────────────────────────
        $this->newLine();
        $this->info($dryRun
            ? '[DRY-RUN] Simulation terminée. Aucune modification.'
            : "Purge RGPD terminée. {$sessions->count()} session(s) traitée(s), {$nbLogsAnciens} log(s) supprimé(s)."
        );

        return self::SUCCESS;
    }
}
