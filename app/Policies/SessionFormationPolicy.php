<?php

namespace App\Policies;

use App\Models\SessionFormation;
use App\Models\User;

class SessionFormationPolicy
{
    /**
     * Un admin voit tout. Un formateur ne voit que ses propres sessions.
     */
    public function viewAny(User $user): bool
    {
        return true; // Le scope est appliqué dans la Resource
    }

    public function view(User $user, SessionFormation $session): bool
    {
        return $user->isAdmin() || $session->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true; // Admin et formateur peuvent créer
    }

    public function update(User $user, SessionFormation $session): bool
    {
        return $user->isAdmin() || $session->user_id === $user->id;
    }

    public function delete(User $user, SessionFormation $session): bool
    {
        return $user->isAdmin(); // Seul l'admin peut supprimer
    }

    public function ouvrirEmargement(User $user, SessionFormation $session): bool
    {
        return $user->isAdmin() || $session->user_id === $user->id;
    }

    public function cloturerEmargement(User $user, SessionFormation $session): bool
    {
        return $user->isAdmin() || $session->user_id === $user->id;
    }

    public function exporter(User $user, SessionFormation $session): bool
    {
        return $user->isAdmin() || $session->user_id === $user->id;
    }
}
