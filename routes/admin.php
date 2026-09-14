<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\PageContentController;
use App\Http\Controllers\Admin\PaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('login', [AuthenticatedSessionController::class, 'store'])
            ->middleware('throttle:5,1');
    });

    Route::middleware(['auth', 'role:Super-admin'])->group(function () {
        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

        Route::get('/', fn () => redirect()->route('admin.dashboard'));
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('adherents', [MemberController::class, 'index'])->name('members.index');
        Route::get('adherents-export', [MemberController::class, 'export'])->name('members.export');
        Route::get('adherents/{member}', [MemberController::class, 'show'])->name('members.show');
        Route::patch('adherents/{member}/statut', [MemberController::class, 'toggleStatus'])
            ->name('members.toggle-status');
        Route::patch('adherents/{member}/note', [MemberController::class, 'updateNote'])
            ->name('members.update-note');
        Route::post('adherents/{member}/renvoyer-carte', [MemberController::class, 'resendCard'])
            ->name('members.resend-card');

        Route::get('actualites', [ArticleController::class, 'index'])->name('articles.index');
        Route::get('actualites/nouveau', [ArticleController::class, 'create'])->name('articles.create');
        Route::post('actualites', [ArticleController::class, 'store'])->name('articles.store');
        Route::get('actualites/{article}/modifier', [ArticleController::class, 'edit'])->name('articles.edit');
        Route::patch('actualites/{article}', [ArticleController::class, 'update'])->name('articles.update');
        Route::delete('actualites/{article}', [ArticleController::class, 'destroy'])->name('articles.destroy');

        Route::get('contenu-des-pages', [PageContentController::class, 'index'])->name('page-content.index');
        Route::get('contenu-des-pages/{pageContent:slug}/modifier', [PageContentController::class, 'edit'])
            ->name('page-content.edit');
        Route::patch('contenu-des-pages/{pageContent:slug}', [PageContentController::class, 'update'])
            ->name('page-content.update');

        Route::get('paiements', [PaymentController::class, 'index'])->name('payments.index');
        Route::post('paiements/{payment}/confirmer', [PaymentController::class, 'confirmManually'])
            ->name('payments.confirm');

        Route::get('journal-audit', [ActivityLogController::class, 'index'])->name('activity-log.index');
    });
});
