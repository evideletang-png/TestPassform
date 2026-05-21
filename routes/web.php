<?php

use App\Http\Controllers\ParticipantController;
use App\Http\Controllers\FormateurPublicController;
use App\Http\Controllers\ExportController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/healthz', function () {
    return response()->json([
        'ok' => true,
        'app' => config('app.name'),
        'env' => app()->environment(),
    ]);
});

Route::get('/health', fn () => redirect('/healthz'));

Route::get('/_db-check', function () {
    $tables = [
        'users',
        'sessions',
        'cache',
        'parametres',
        'sessions_formation',
        'demi_journees',
        'participants',
        'emargements',
        'audit_logs',
        'code_attempts',
    ];

    $tableStatus = [];

    foreach ($tables as $table) {
        $tableStatus[$table] = Schema::hasTable($table);
    }

    return response()->json([
        'ok' => true,
        'connection' => config('database.default'),
        'host_set' => filled(config('database.connections.mysql.host')),
        'database_set' => filled(config('database.connections.mysql.database')),
        'tables' => $tableStatus,
        'users_count' => Schema::hasTable('users') ? DB::table('users')->count() : null,
        'admin_exists' => Schema::hasTable('users')
            ? DB::table('users')->where('email', 'admin@passform.local')->exists()
            : false,
    ]);
});

// ── Portail Participant (accès public via token UUID) ─────────────────────────
Route::prefix('s/{token}')->name('participant.')->group(function () {
    // Affiche la page d'émargement (routage automatique demi-journée en cours)
    Route::get('/', [ParticipantController::class, 'index'])->name('session');

    // Première inscription (formulaire complet + signature)
    Route::post('/inscrire', [ParticipantController::class, 'inscrire'])->name('inscrire');

    // Émargement via code à 3 chiffres (sessions suivantes)
    Route::post('/signer', [ParticipantController::class, 'signer'])->name('signer');

    // Inscription tardive (arrivée en cours de formation)
    Route::post('/inscrire-retard', [ParticipantController::class, 'inscrireRetard'])->name('inscrire_retard');

    // Vérification AJAX du code (pour UX temps réel)
    Route::post('/verifier-code', [ParticipantController::class, 'verifierCode'])->name('verifier_code');
});

// ── Signature Formateur (accès via token formateur distinct) ──────────────────
Route::prefix('f/{token}')->name('formateur.')->group(function () {
    Route::get('/', [FormateurPublicController::class, 'index'])->name('session');
    Route::post('/signer/{demiJourneeId}', [FormateurPublicController::class, 'signer'])->name('signer');
});

// ── Exports (protégés par auth Filament, appelés depuis le back-office) ───────
Route::middleware(['auth'])->prefix('exports')->name('exports.')->group(function () {
    Route::get('/pdf/{session}', [ExportController::class, 'pdf'])->name('pdf');
    Route::get('/excel/{session}', [ExportController::class, 'excel'])->name('excel');
    Route::get('/cdc/{session}', [ExportController::class, 'cdc'])->name('cdc');
});

// ── Page d'accueil ────────────────────────────────────────────────────────────
Route::get('/', function () {
    return redirect('/admin');
});
