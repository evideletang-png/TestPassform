<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'is_active'      => 'boolean',
        'last_login_at'  => 'datetime',
        'password'       => 'hashed',
    ];

    // ── Rôles ────────────────────────────────────────────────────────────────
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isFormateur(): bool
    {
        return $this->role === 'formateur';
    }

    // ── Relations ────────────────────────────────────────────────────────────
    public function sessions(): HasMany
    {
        return $this->hasMany(SessionFormation::class);
    }

    // ── Filament : limite l'accès au panel admin ──────────────────────────────
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }

    // ── Filament : un formateur ne voit que ses propres sessions ─────────────
    public function sessionsAccessibles()
    {
        if ($this->isAdmin()) {
            return SessionFormation::query();
        }
        return $this->sessions();
    }
}
