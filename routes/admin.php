<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\PageContentController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\UserController;
use App\Support\AdminRoles;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('login', [AuthenticatedSessionController::class, 'store'])
            ->middleware('throttle:5,1');
    });

    // `role:` liste les rôles du back-office : un compte sans rôle (ou avec un rôle futur
    // destiné à l'espace adhérent) n'entre pas ici, même authentifié. Les droits fins, eux,
    // sont posés par les `can:` ci-dessous — cf. app/Support/AdminRoles.php.
    Route::middleware(['auth', 'not-blocked', 'role:'.implode('|', AdminRoles::names())])->group(function () {
        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

        Route::get('/', fn () => redirect()->route('admin.dashboard'));
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Son propre compte : accessible à tous les rôles, sans permission particulière —
        // c'est le seul endroit où un rédacteur peut changer le mot de passe que
        // l'administrateur lui a communiqué.
        Route::get('mon-compte', [AccountController::class, 'edit'])->name('account.edit');
        Route::patch('mon-compte', [AccountController::class, 'update'])->name('account.update');
        Route::patch('mon-compte/mot-de-passe', [AccountController::class, 'updatePassword'])
            ->name('account.password');

        Route::middleware('can:'.AdminRoles::MANAGE_ARTICLES)->group(function () {
            Route::get('actualites', [ArticleController::class, 'index'])->name('articles.index');
            Route::get('actualites/nouveau', [ArticleController::class, 'create'])->name('articles.create');
            Route::post('actualites', [ArticleController::class, 'store'])->name('articles.store');
            Route::get('actualites/{article}/modifier', [ArticleController::class, 'edit'])->name('articles.edit');
            Route::patch('actualites/{article}', [ArticleController::class, 'update'])->name('articles.update');
            Route::delete('actualites/{article}', [ArticleController::class, 'destroy'])->name('articles.destroy');
        });

        Route::middleware('can:'.AdminRoles::MANAGE_PAGE_CONTENT)->group(function () {
            Route::get('contenu-des-pages', [PageContentController::class, 'index'])->name('page-content.index');
            Route::get('contenu-des-pages/{pageContent:slug}/modifier', [PageContentController::class, 'edit'])
                ->name('page-content.edit');
            Route::patch('contenu-des-pages/{pageContent:slug}', [PageContentController::class, 'update'])
                ->name('page-content.update');
        });

        Route::middleware('can:'.AdminRoles::MANAGE_MEMBERS)->group(function () {
            Route::get('adherents', [MemberController::class, 'index'])->name('members.index');
            Route::get('adherents-export', [MemberController::class, 'export'])->name('members.export');
            Route::get('adherents/{member}', [MemberController::class, 'show'])->name('members.show');
            Route::patch('adherents/{member}/statut', [MemberController::class, 'toggleStatus'])
                ->name('members.toggle-status');
            Route::patch('adherents/{member}/note', [MemberController::class, 'updateNote'])
                ->name('members.update-note');
            Route::post('adherents/{member}/renvoyer-carte', [MemberController::class, 'resendCard'])
                ->name('members.resend-card');
        });

        Route::middleware('can:'.AdminRoles::MANAGE_PAYMENTS)->group(function () {
            Route::get('paiements', [PaymentController::class, 'index'])->name('payments.index');
            Route::post('paiements/{payment}/confirmer', [PaymentController::class, 'confirmManually'])
                ->name('payments.confirm');
        });

        Route::middleware('can:'.AdminRoles::MANAGE_USERS)->group(function () {
            Route::get('utilisateurs', [UserController::class, 'index'])->name('users.index');
            Route::get('utilisateurs/nouveau', [UserController::class, 'create'])->name('users.create');
            Route::post('utilisateurs', [UserController::class, 'store'])->name('users.store');
            Route::get('utilisateurs/{user}/modifier', [UserController::class, 'edit'])->name('users.edit');
            Route::patch('utilisateurs/{user}', [UserController::class, 'update'])->name('users.update');
            Route::patch('utilisateurs/{user}/mot-de-passe', [UserController::class, 'updatePassword'])
                ->name('users.password');
            Route::patch('utilisateurs/{user}/blocage', [UserController::class, 'toggleBlock'])
                ->name('users.toggle-block');
        });

        Route::middleware('can:'.AdminRoles::VIEW_ACTIVITY_LOG)->group(function () {
            Route::get('journal-audit', [ActivityLogController::class, 'index'])->name('activity-log.index');
        });
    });
});
