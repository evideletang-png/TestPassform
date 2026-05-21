<?php

namespace App\Filament\Pages;

use App\Models\Parametre;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class Parametres extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?string $navigationLabel = 'Paramètres';
    protected static ?string $title           = 'Paramètres de l\'application';
    protected static ?int    $navigationSort  = 10;
    protected static string  $view            = 'filament.pages.parametres';

    // Seul l'admin accède aux paramètres
    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'organisme_nom'                   => Parametre::get('organisme_nom'),
            'organisme_siret'                 => Parametre::get('organisme_siret'),
            'nda'                             => Parametre::get('nda'),
            'purge_delai_jours_defaut'        => Parametre::get('purge_delai_jours_defaut', 30),
            'lien_expiration_jours'           => Parametre::get('lien_expiration_jours', 30),
            'max_tentatives_code'             => Parametre::get('max_tentatives_code', 5),
            'blocage_duree_minutes'           => Parametre::get('blocage_duree_minutes', 15),
            'signature_tolerance_avant_minutes' => Parametre::get('signature_tolerance_avant_minutes', 15),
            'signature_tolerance_apres_minutes' => Parametre::get('signature_tolerance_apres_minutes', 30),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Section::make('Organisme de formation')
                ->schema([
                    Forms\Components\TextInput::make('organisme_nom')
                        ->label('Nom de l\'organisme')
                        ->required(),
                    Forms\Components\TextInput::make('organisme_siret')
                        ->label('SIRET')
                        ->mask('999 999 999 99999'),
                    Forms\Components\TextInput::make('nda')
                        ->label('Numéro de Déclaration d\'Activité (NDA)'),
                ])->columns(3),

            Forms\Components\Section::make('Conformité RGPD')
                ->schema([
                    Forms\Components\TextInput::make('purge_delai_jours_defaut')
                        ->label('Délai de purge NIR par défaut')
                        ->numeric()
                        ->suffix('jours après déclaration CDC')
                        ->minValue(1)
                        ->maxValue(365)
                        ->helperText('Peut être surchargé session par session.'),

                    Forms\Components\TextInput::make('lien_expiration_jours')
                        ->label('Expiration du lien participant')
                        ->numeric()
                        ->suffix('jours après fin de session')
                        ->minValue(1),
                ])->columns(2),

            Forms\Components\Section::make('Sécurité — Portail participant')
                ->schema([
                    Forms\Components\TextInput::make('max_tentatives_code')
                        ->label('Tentatives max avant blocage')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(20)
                        ->suffix('tentatives'),

                    Forms\Components\TextInput::make('blocage_duree_minutes')
                        ->label('Durée du blocage')
                        ->numeric()
                        ->minValue(1)
                        ->suffix('minutes'),

                    Forms\Components\TextInput::make('signature_tolerance_avant_minutes')
                        ->label('Tolérance avant début demi-journée')
                        ->numeric()
                        ->suffix('minutes'),

                    Forms\Components\TextInput::make('signature_tolerance_apres_minutes')
                        ->label('Tolérance après fin demi-journée')
                        ->numeric()
                        ->suffix('minutes'),
                ])->columns(2),

        ])->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Enregistrer les paramètres')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        foreach ($data as $cle => $valeur) {
            Parametre::set($cle, $valeur);
        }

        Notification::make()
            ->title('Paramètres enregistrés')
            ->success()
            ->send();
    }
}
