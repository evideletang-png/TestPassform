<?php

use App\Http\Controllers\ParticipantController;
use App\Http\Controllers\FormateurPublicController;
use App\Http\Controllers\ExportController;
use Illuminate\Support\Facades\Route;

Route::get('/healthz', function () {
    return response()->json([
        'ok' => true,
        'app' => config('app.name'),
        'env' => app()->environment(),
    ]);
});

Route::get('/health', fn () => redirect('/healthz'));

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
