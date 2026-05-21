<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Participant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'session_formation_id',
        'prenom',
        'nom',
        'nom_naissance',
        'nir_encrypted',
        'nir_refuse',
        'code_identification',
        'code_consulte',
        'ip_inscription',
        'inscrit_at',
    ];

    /**
     * Le cast 'encrypted' utilise APP_KEY via Laravel Encrypter (AES-256-CBC).
     * Le NIR est automatiquement chiffré à l'écriture et déchiffré à la lecture.
     * En base, la colonne nir_encrypted ne contient que du texte opaque.
     */
    protected $casts = [
        'nir_encrypted' => 'encrypted',
        'nir_refuse'    => 'boolean',
        'code_consulte' => 'boolean',
        'inscrit_at'    => 'datetime',
    ];

    // Le NIR est accessible via ->nir (alias du champ chiffré)
    public function getNirAttribute(): ?string
    {
        return $this->nir_encrypted;
    }

    public function setNirAttribute(?string $value): void
    {
        $this->nir_encrypted = $value;
    }

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
    public function getNomCompletAttribute(): string
    {
        return $this->prenom . ' ' . strtoupper($this->nom);
    }

    public function getNirMasqueAttribute(): string
    {
        if ($this->nir_refuse || !$this->nir_encrypted) {
            return 'Non communiqué';
        }
        $nir = $this->nir;
        return substr($nir, 0, 1) . str_repeat('*', 10) . substr($nir, -2);
    }

    // ── Validation NIR ───────────────────────────────────────────────────────
    public static function validerNir(string $nir): bool
    {
        // 13 chiffres exactement, commence par 1 ou 2
        if (!preg_match('/^[12][0-9]{12}$/', $nir)) {
            return false;
        }
        // Clé de contrôle (modulo 97)
        $base = (int) substr($nir, 0, 13);
        $cle  = 97 - ($base % 97);
        return $cle >= 1 && $cle <= 97;
    }

    // ── Méthodes ─────────────────────────────────────────────────────────────
    public function marquerCodeConsulte(int $userId): void
    {
        $this->update(['code_consulte' => true]);
        AuditLog::journaliser('code_consulte', $this, $userId, [
            'participant' => $this->nom_complet,
        ]);
    }

    public function getEmargementPour(DemiJournee $dj): ?Emargement
    {
        return $this->emargements()->where('demi_journee_id', $dj->id)->first();
    }
}
