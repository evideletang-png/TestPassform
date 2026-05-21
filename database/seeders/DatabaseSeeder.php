<?php

namespace Database\Seeders;

use App\Models\Parametre;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Administrateur par défaut ─────────────────────────────────────────
        User::firstOrCreate(
            ['email' => 'admin@passform.local'],
            [
                'name'      => 'Administrateur',
                'password'  => Hash::make('PassForm2025!'),  // À changer au 1er login
                'role'      => 'admin',
                'is_active' => true,
            ]
        );

        // ── Paramètres globaux ────────────────────────────────────────────────
        $parametres = [
            [
                'cle'         => 'purge_delai_jours_defaut',
                'valeur'      => '30',
                'description' => 'Délai de purge RGPD (jours) après déclaration CDC',
            ],
            [
                'cle'         => 'lien_expiration_jours',
                'valeur'      => '30',
                'description' => 'Durée de validité du lien participant après fin de session',
            ],
            [
                'cle'         => 'max_tentatives_code',
                'valeur'      => '5',
                'description' => 'Nombre de tentatives avant blocage temporaire du code',
            ],
            [
                'cle'         => 'blocage_duree_minutes',
                'valeur'      => '15',
                'description' => 'Durée du blocage après trop de tentatives (minutes)',
            ],
            [
                'cle'         => 'signature_tolerance_avant_minutes',
                'valeur'      => '15',
                'description' => 'Minutes avant le début d\'une demi-journée où la signature est acceptée',
            ],
            [
                'cle'         => 'signature_tolerance_apres_minutes',
                'valeur'      => '30',
                'description' => 'Minutes après la fin d\'une demi-journée où la signature reste acceptée',
            ],
            [
                'cle'         => 'organisme_nom',
                'valeur'      => 'Mon Organisme de Formation',
                'description' => 'Nom de l\'organisme (affiché sur les exports PDF)',
            ],
            [
                'cle'         => 'organisme_siret',
                'valeur'      => '',
                'description' => 'SIRET de l\'organisme de formation',
            ],
            [
                'cle'         => 'nda',
                'valeur'      => '',
                'description' => 'Numéro Déclaration d\'Activité (NDA)',
            ],
        ];

        foreach ($parametres as $p) {
            \App\Models\Parametre::firstOrCreate(['cle' => $p['cle']], $p);
        }
    }
}
