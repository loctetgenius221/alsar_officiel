<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force les URLs générées (asset(), route(), url()) à toujours utiliser APP_URL,
        // même quand le Host header réel diffère (ex. tunnel ngrok qui réécrit le Host vers
        // le vhost Apache local pour le routage, alors que l'URL publique est différente).
        if ($appUrl = config('app.url')) {
            URL::forceRootUrl($appUrl);
            URL::forceScheme(str_starts_with($appUrl, 'https://') ? 'https' : 'http');

            // Le paginator construit ses liens ?page=N à partir de request()->url(), pas de
            // URL::forceRootUrl() — même souci que les assets, il faut le forcer séparément.
            Paginator::currentPathResolver(
                fn () => rtrim($appUrl, '/').request()->getPathInfo(),
            );
        }
    }
}
