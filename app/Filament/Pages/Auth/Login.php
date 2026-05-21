<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;

class Login extends BaseLogin
{
    public function getHeading(): string
    {
        return 'Connexion';
    }

    public function getSubheading(): string
    {
        return 'Accédez à l’espace sécurisé d’émargement BR Code.';
    }
}
