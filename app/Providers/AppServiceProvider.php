<?php

namespace App\Providers;

use App\Models\User;
use App\Support\AdminRoles;
use App\Support\SiteSeo;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        // Le Super-admin passe toutes les autorisations, sans dépendre de la table des
        // permissions. C'est une sécurité d'exploitation autant qu'une règle métier : si une
        // permission est ajoutée au code et que le `db:seed` n'est pas rejoué en production,
        // l'administrateur doit malgré tout pouvoir accéder à l'écran correspondant — sinon
        // plus personne ne peut rétablir les droits, y compris ceux des autres comptes.
        // `null` (et non `false`) quand ce n'est pas un Super-admin : on laisse la main aux
        // autres vérifications, à commencer par celle de spatie sur les permissions.
        Gate::before(fn (User $user) => $user->hasRole(AdminRoles::SUPER_ADMIN) ? true : null);

        // Exigence unique pour tous les mots de passe du back-office (création d'un compte,
        // réinitialisation par un administrateur, changement par l'intéressé) : déclarée ici
        // pour qu'elle ne puisse pas diverger d'un formulaire à l'autre. Longueur plutôt que
        // zoo de caractères spéciaux — c'est ce qui résiste réellement, et ce qui se retient.
        Password::defaults(fn () => Password::min(10)->letters()->numbers());

        // Métadonnées de partage injectées dans le gabarit racine (cf. App\Support\SiteSeo pour
        // le « pourquoi côté serveur »). Un contrôleur peut les remplacer au cas par cas avec
        // `Inertia::render(...)->withViewData(['seo' => [...]])` — c'est ce que fait le détail
        // d'un article, qui connaît son vrai titre et sa vraie couverture ; `getData()` est lu
        // en premier pour que cette valeur-là gagne sur le défaut déduit de la route.
        View::composer('app', function (ViewContract $view) {
            $view->with('seo', $view->getData()['seo'] ?? SiteSeo::forRoute(Route::currentRouteName()));
        });

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
