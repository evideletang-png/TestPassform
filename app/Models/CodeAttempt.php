<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CodeAttempt extends Model
{
    protected $fillable = [
        'session_formation_id',
        'ip',
        'tentatives',
        'bloque_jusqu_at',
    ];

    public $timestamps = false;

    protected $casts = [
        'bloque_jusqu_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(SessionFormation::class, 'session_formation_id');
    }
}
