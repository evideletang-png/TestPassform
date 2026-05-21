<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DemiJournee extends Model
{
    protected $table = 'demi_journees';

    protected $fillable = [
        'session_formation_id',
        'date',
        'creneau',
        'heure_debut',
        'heure_fin',
        'ordre',
        'statut_emargement',
        'emargement_ouvert_at',
        'emargement_cloture_at',
        'signature_formateur',
        'formateur_signe_at',
        'formateur_sign_ip',
    ];

    protected $casts = [
        'date'                  => 'date',
        'emargement_ouvert_at'  => 'datetime',
        'emargement_cloture_at' => 'datetime',
        'formateur_signe_at'    => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────────────────────
    public function session(): BelongsTo
    {
        return $this->belongsTo(SessionFormation::class, 'session_formation_id');
    }

    public function emargements(): HasMany
    {
        return $this->hasMany(Emargement::class);
    }

    // ── Accesseurs ───────────────────────────────────────────────────────────
    public function getLibelleAttribute(): string
    {
        $label = $this->creneau === 'matin' ? 'Matin' : 'Après-midi';
        return "DJ{$this->ordre} · {$label} · " . $this->date->format('d/m/Y')
             . " · {$this->heure_debut}-{$this->heure_fin}";
    }

    public function getEstEnCoursAttribute(): bool
    {
        if ($this->statut_emargement !== 'ouvert') return false;
        $now = now();
        $debut = \Carbon\Carbon::parse($this->date->format('Y-m-d') . ' ' . $this->heure_debut);
        $fin   = \Carbon\Carbon::parse($this->date->format('Y-m-d') . ' ' . $this->heure_fin);
        // Tolérance : 15 min avant le début et 30 min après la fin
        return $now->between($debut->subMinutes(15), $fin->addMinutes(30));
    }

    public function getTauxSignatureAttribute(): float
    {
        $total = $this->session->participants()->count();
        if ($total === 0) return 0;
        $signe = $this->emargements()->whereNotNull('signature')->where('present', true)->count();
        return round(($signe / $total) * 100, 1);
    }

    // ── Méthodes ─────────────────────────────────────────────────────────────
    public function ouvrir(int $userId): void
    {
        $this->update([
            'statut_emargement'    => 'ouvert',
            'emargement_ouvert_at' => now(),
        ]);
        AuditLog::journaliser('emargement_ouvert', $this, $userId);
    }

    public function cloturer(int $userId): void
    {
        $this->update([
            'statut_emargement'     => 'cloture',
            'emargement_cloture_at' => now(),
        ]);
        AuditLog::journaliser('emargement_cloture', $this, $userId);
    }

    public function signerFormateur(string $signatureBase64, string $ip): void
    {
        $this->update([
            'signature_formateur' => $signatureBase64,
            'formateur_signe_at'  => now(),
            'formateur_sign_ip'   => $ip,
        ]);
        AuditLog::journaliser('signature_formateur', $this, null, ['ip' => $ip]);
    }
}
