<?php

use App\Http\Controllers\Site\AdhesionController;
use App\Http\Controllers\Site\ArticleController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\PageController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/le-parti', [PageController::class, 'parti'])->name('parti');

Route::get('/le-president', [PageController::class, 'president'])->name('president');

Route::get('/programme', [PageController::class, 'programme'])->name('programme');

Route::get('/actualites', [ArticleController::class, 'index'])->name('actualites');
Route::get('/actualites/{slug}', [ArticleController::class, 'show'])->name('actualites.show');

Route::get('/mediatheque', [PageController::class, 'mediatheque'])->name('mediatheque');

Route::get('/regions', [PageController::class, 'regions'])->name('regions');

Route::get('/mentions-legales', function () {
    return Inertia::render('Site/MentionsLegales');
})->name('mentions-legales');

Route::get('/politique-de-confidentialite', function () {
    return Inertia::render('Site/Confidentialite');
})->name('confidentialite');

Route::get('/rejoignez-nous', [AdhesionController::class, 'create'])->name('adhesion.create');
Route::post('/rejoignez-nous', [AdhesionController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('adhesion.store');
Route::post('/rejoignez-nous/verifier-email', [AdhesionController::class, 'checkEmail'])
    ->middleware('throttle:10,1')
    ->name('adhesion.check-email');
Route::get('/adhesion/confirmation/{membershipCode}', [AdhesionController::class, 'confirmation'])
    ->name('adhesion.confirmation');
Route::get('/adhesion/carte/{membershipCode}', [AdhesionController::class, 'card'])
    ->name('adhesion.card');
Route::post('/paiement/webhook', [AdhesionController::class, 'webhook'])->name('paiement.webhook');
Route::get('/verifier/{membershipCode}', [AdhesionController::class, 'verify'])->name('verifier');
