<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SessionFormation extends Model
{
    use SoftDeletes;

    protected $table = 'sessions_formation';

    protected $fillable = [
        'user_id',
        'intitule',
        'description',
        'lieu',
        'token_participant',
        'token_formateur',
        'lien_actif',
        'lien_expire_at',
        'statut',
        'purge_delai_jours',
        'cdc_declare_at',
        'purge_at',
        'purge_effectuee',
    ];

    protected $casts = [
        'lien_actif'       => 'boolean',
        'lien_expire_at'   => 'datetime',
        'cdc_declare_at'   => 'datetime',
        'purge_at'         => 'datetime',
        'purge_effectuee'  => 'boolean',
    ];

    // ── Boot : génération automatique des tokens UUID ────────────────────────
    protected static function booted(): void
    {
        static::creating(function (self $session) {
            $session->token_participant = $session->token_participant ?? Str::uuid()->toString();
            $session->token_formateur   = $session->token_formateur   ?? Str::uuid()->toString();
        });
    }

    // ── Relations ────────────────────────────────────────────────────────────
    public function formateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function demiJournees(): HasMany
    {
        return $this->hasMany(DemiJournee::class)->orderBy('ordre');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    // ── Scopes ───────────────────────────────────────────────────────────────
    public function scopeActives($query)
    {
        return $query->whereIn('statut', ['planifiee', 'en_cours']);
    }

    public function scopeLienValide($query)
    {
        return $query->where('lien_actif', true)
                     ->where(function ($q) {
                         $q->whereNull('lien_expire_at')
                           ->orWhere('lien_expire_at', '>', now());
                     });
    }

    // ── Accesseurs ───────────────────────────────────────────────────────────
    public function getUrlParticipantAttribute(): string
    {
        return route('participant.session', $this->token_participant);
    }

    public function getUrlFormateurAttribute(): string
    {
        return route('formateur.session', $this->token_formateur);
    }

    public function getTauxCompletionAttribute(): float
    {
        $total = 0;
        $signe = 0;
        foreach ($this->demiJournees as $dj) {
            $nbParticipants = $this->participants()->count();
            $total += $nbParticipants;
            $signe += $dj->emargements()->where('present', true)->whereNotNull('signature')->count();
        }
        return $total > 0 ? round(($signe / $total) * 100, 1) : 0;
    }

    public function getDemiJourneeEnCoursAttribute(): ?DemiJournee
    {
        return $this->demiJournees
            ->where('statut_emargement', 'ouvert')
            ->first();
    }

    // ── Méthodes ─────────────────────────────────────────────────────────────

    /**
     * Génère un code à 3 chiffres (100-999) garanti unique dans la session.
     */
    public function genererCodeUnique(): string
    {
        $tentatives = 0;
        do {
            $code = (string) random_int(100, 999);
            $existe = $this->participants()->where('code_identification', $code)->exists();
            $tentatives++;
        } while ($existe && $tentatives < 900);

        return $code;
    }

    /**
     * Planifie l'expiration du lien participant après la fin de session.
     */
    public function planifierExpirationLien(int $joursApresSession = 30): void
    {
        $derniereDJ = $this->demiJournees()->orderByDesc('date')->orderByDesc('heure_fin')->first();
        if ($derniereDJ) {
            $finSession = \Carbon\Carbon::parse($derniereDJ->date->format('Y-m-d') . ' ' . $derniereDJ->heure_fin);
            $this->update(['lien_expire_at' => $finSession->addDays($joursApresSession)]);
        }
    }

    /**
     * Planifie la purge RGPD après déclaration CDC.
     */
    public function planifierPurge(): void
    {
        $this->update([
            'cdc_declare_at' => now(),
            'purge_at'       => now()->addDays($this->purge_delai_jours),
        ]);
    }

    /**
     * Exécute la purge des données sensibles (NIR, codes).
     */
    public function purgerDonneesSensibles(): void
    {
        $this->participants()->update([
            'nir_encrypted'   => null,
            'code_identification' => '***',
        ]);
        $this->update(['purge_effectuee' => true]);
        AuditLog::journaliser('purge', $this, null, ['session' => $this->intitule]);
    }
}
