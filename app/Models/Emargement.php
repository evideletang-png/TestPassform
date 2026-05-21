<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Emargement extends Model
{
    protected $fillable = [
        'participant_id',
        'demi_journee_id',
        'signature',
        'signe_at',
        'ip_signature',
        'present',
    ];

    protected $casts = [
        'signe_at' => 'datetime',
        'present'  => 'boolean',
    ];

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function demiJournee(): BelongsTo
    {
        return $this->belongsTo(DemiJournee::class);
    }

    public function getEstSigneAttribute(): bool
    {
        return $this->signature !== null;
    }

    /**
     * Enregistre la signature avec horodatage et IP.
     */
    public function signer(string $signatureBase64, string $ip): void
    {
        $this->update([
            'signature'    => $signatureBase64,
            'signe_at'     => now(),
            'ip_signature' => $ip,
            'present'      => true,
        ]);
        AuditLog::journaliser('signature', $this, null, [
            'participant_code' => $this->participant->code_identification,
            'demi_journee'     => $this->demiJournee->libelle,
        ]);
    }
}
