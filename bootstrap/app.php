<?php

use App\Http\Middleware\EnsureUserIsNotBlocked;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Le tunnel ngrok fait office de reverse proxy TLS devant Apache (qui reçoit du
        // http en clair sur le port 80) : sans ça, Laravel croit que la requête est en
        // http, génère des URLs/assets en http:// (mixed content bloqué par le navigateur)
        // et ne marque pas les cookies de session comme secure. L'IP ngrok changeant, on
        // fait confiance à tous les proxies ('*') plutôt qu'à une IP fixe.
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Les routes admin sont nommées admin.* (donc admin.login, pas "login"), il faut
        // donc dire explicitement au middleware auth où rediriger un invité plutôt que de
        // compter sur la convention par défaut de Laravel (route nommée "login").
        $middleware->redirectGuestsTo(fn () => route('admin.login'));

        // Symétrique : si un admin déjà connecté revisite /admin/login (middleware "guest"),
        // le renvoyer vers son dashboard plutôt que vers route('home') (qui n'existe même
        // pas dans ce sens pour un admin — "home" est la page vitrine publique).
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));

        // spatie/laravel-permission n'enregistre pas ces alias automatiquement avec le
        // nouveau système de middleware de Laravel 11+ (bootstrap/app.php) — à faire soi-même.
        $middleware->alias([
            'not-blocked' => EnsureUserIsNotBlocked::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
