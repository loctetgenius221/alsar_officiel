<?php

namespace App\Http\Middleware;

use App\Domain\Content\Models\PageContent;
use App\Support\AdminRoles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user()?->only('id', 'name', 'email'),
                'roles' => $request->user()?->getRoleNames(),
                // Permissions effectives, pour que le menu et les boutons n'affichent que ce
                // que le serveur autorisera réellement (lib/permissions.ts). Le Super-admin
                // reçoit tout le catalogue : ses droits viennent du `Gate::before` de
                // AppServiceProvider, pas de la table, donc `getAllPermissions()` pourrait
                // être vide alors qu'il a accès à tout.
                'permissions' => fn () => $this->permissions($request),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            // En-tête/pied de page (édition CLAUDE.md §6 : "site content editable from admin") :
            // partagé sur toutes les pages plutôt que refetché par chaque contrôleur, mis en
            // cache car lu sur chaque requête — invalidé par UpdatePageContent quand le slug
            // 'site-settings' est modifié.
            'siteSettings' => fn () => $this->siteSettings(),
        ];
    }

    /**
     * @return list<string>
     */
    private function permissions(Request $request): array
    {
        $user = $request->user();

        if (! $user) {
            return [];
        }

        return $user->hasRole(AdminRoles::SUPER_ADMIN)
            ? AdminRoles::permissions()
            : $user->getAllPermissions()->pluck('name')->values()->all();
    }

    /**
     * Ne met en cache "pour toujours" que lorsque la ligne existe réellement — sinon (avant
     * le premier seed, ou si elle est supprimée par erreur) le fallback vide serait sinon
     * verrouillé en cache indéfiniment, sans jamais se corriger tout seul une fois la ligne
     * disponible.
     */
    private function siteSettings(): array
    {
        $cached = Cache::get('site-settings');
        if ($cached !== null) {
            return $cached;
        }

        $page = PageContent::where('slug', 'site-settings')->first();
        if (! $page) {
            return ['sections' => []];
        }

        $settings = $page->toPublicArray();
        Cache::forever('site-settings', $settings);

        return $settings;
    }
}
