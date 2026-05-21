<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon  = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Formateurs';
    protected static ?string $modelLabel      = 'Formateur';
    protected static ?string $pluralModelLabel = 'Formateurs';
    protected static ?int    $navigationSort  = 2;

    // Seul l'admin accède à cette ressource
    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identité')->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nom complet')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('email')
                    ->label('Adresse e-mail')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                Forms\Components\Select::make('role')
                    ->label('Rôle')
                    ->options(['admin' => 'Administrateur', 'formateur' => 'Formateur'])
                    ->required()
                    ->default('formateur'),

                Forms\Components\Toggle::make('is_active')
                    ->label('Compte actif')
                    ->default(true),
            ])->columns(2),

            Forms\Components\Section::make('Mot de passe')->schema([
                Forms\Components\TextInput::make('password')
                    ->label('Mot de passe')
                    ->password()
                    ->revealable()
                    ->dehydrateStateUsing(fn ($s) => Hash::make($s))
                    ->dehydrated(fn ($s) => filled($s))
                    ->required(fn (string $operation) => $operation === 'create')
                    ->minLength(10)
                    ->helperText('Minimum 10 caractères. Laisser vide pour conserver l\'actuel lors d\'une modification.'),

                Forms\Components\TextInput::make('password_confirmation')
                    ->label('Confirmation')
                    ->password()
                    ->revealable()
                    ->dehydrated(false)
                    ->same('password')
                    ->required(fn (string $operation) => $operation === 'create'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('role')
                    ->label('Rôle')
                    ->colors(['warning' => 'admin', 'primary' => 'formateur'])
                    ->formatStateUsing(fn ($s) => $s === 'admin' ? 'Administrateur' : 'Formateur'),

                Tables\Columns\TextColumn::make('sessions_count')
                    ->label('Sessions')
                    ->counts('sessions')
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('Dernière connexion')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Jamais')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options(['admin' => 'Administrateur', 'formateur' => 'Formateur']),
                Tables\Filters\TernaryFilter::make('is_active')->label('Actif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('desactiver')
                    ->label(fn (User $u) => $u->is_active ? 'Désactiver' : 'Réactiver')
                    ->icon(fn (User $u) => $u->is_active ? 'heroicon-o-no-symbol' : 'heroicon-o-check-circle')
                    ->color(fn (User $u) => $u->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(fn (User $u) => $u->update(['is_active' => !$u->is_active])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
