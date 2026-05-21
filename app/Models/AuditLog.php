<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'action',
        'entite_type',
        'entite_id',
        'user_id',
        'participant_code',
        'ip',
        'user_agent',
        'contexte',
        'created_at',
    ];

    protected $casts = [
        'contexte'   => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Méthode centrale de journalisation — à appeler partout dans l'app.
     *
     * @param string     $action      Ex: 'signature', 'export', 'purge'
     * @param Model|null $entite      Le modèle concerné
     * @param int|null   $userId      ID du formateur/admin connecté (null si participant)
     * @param array      $contexte    Données complémentaires libres
     */
    public static function journaliser(
        string $action,
        ?Model $entite = null,
        ?int $userId = null,
        array $contexte = []
    ): void {
        static::create([
            'action'      => $action,
            'entite_type' => $entite ? get_class($entite) : null,
            'entite_id'   => $entite?->getKey(),
            'user_id'     => $userId,
            'ip'          => request()->ip(),
            'user_agent'  => request()->userAgent(),
            'contexte'    => $contexte ?: null,
            'created_at'  => now(),
        ]);
    }

    /**
     * Purge des logs de plus de 12 mois (à appeler via Scheduler).
     */
    public static function purgerAnciensLogs(): int
    {
        return static::where('created_at', '<', now()->subMonths(12))->delete();
    }
}
