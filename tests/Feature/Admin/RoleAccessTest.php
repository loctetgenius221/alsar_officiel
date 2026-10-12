<?php

/*
| Matrice d'accès des rôles du back-office.
|
| C'est le filet de sécurité de la fonctionnalité « chaque rôle ne voit que ce qui le concerne » :
| un écran ajouté plus tard sans middleware `can:` tombera ici, et non en production.
|
| Le pendant visuel (entrées de menu filtrées) vit dans AdminLayout.vue et lit les mêmes
| permissions via les props Inertia — cf. resources/js/lib/permissions.ts.
*/

use App\Models\User;
use App\Support\AdminRoles;
use Database\Seeders\RolesSeeder;

beforeEach(fn () => $this->seed(RolesSeeder::class));

/** @return array<string, string> URL => rôle minimal attendu */
function adminScreens(): array
{
    return [
        '/admin/actualites' => AdminRoles::MANAGE_ARTICLES,
        '/admin/contenu-des-pages' => AdminRoles::MANAGE_PAGE_CONTENT,
        '/admin/adherents' => AdminRoles::MANAGE_MEMBERS,
        '/admin/paiements' => AdminRoles::MANAGE_PAYMENTS,
        '/admin/utilisateurs' => AdminRoles::MANAGE_USERS,
        '/admin/journal-audit' => AdminRoles::VIEW_ACTIVITY_LOG,
    ];
}

function actingWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('donne accès à tous les écrans au Super-admin', function () {
    $admin = actingWithRole(AdminRoles::SUPER_ADMIN);

    foreach (array_keys(adminScreens()) as $url) {
        $this->actingAs($admin)->get($url)->assertOk();
    }
});

it('n’ouvre à un rôle que les écrans couverts par ses permissions', function (string $role) {
    $user = actingWithRole($role);
    $granted = AdminRoles::permissionsFor($role);

    foreach (adminScreens() as $url => $permission) {
        $expected = in_array($permission, $granted, true) ? 200 : 403;

        expect($this->actingAs($user)->get($url)->getStatusCode())
            ->toBe($expected, "{$role} sur {$url}");
    }
})->with([
    AdminRoles::CONTENT_MANAGER,
    AdminRoles::WRITER,
]);

it('laisse le dashboard et « Mon compte » ouverts à tous les rôles', function (string $role) {
    $user = actingWithRole($role);

    $this->actingAs($user)->get('/admin/dashboard')->assertOk();
    $this->actingAs($user)->get('/admin/mon-compte')->assertOk();
})->with([
    AdminRoles::SUPER_ADMIN,
    AdminRoles::CONTENT_MANAGER,
    AdminRoles::WRITER,
]);

it('explique le refus en français plutôt que d’afficher le gabarit brut de Laravel', function () {
    // Les écrans hors de portée ne sont pas dans le menu, mais l'URL reste tapable (favori,
    // lien recopié) : ce qui s'affiche alors doit rester compréhensible et offrir une sortie.
    $writer = actingWithRole(AdminRoles::WRITER);

    $this->actingAs($writer)
        ->get('/admin/adherents')
        ->assertForbidden()
        ->assertSee('Cette page ne fait pas partie de votre espace')
        ->assertSee('/admin/dashboard');
});

it('ferme le back-office à un compte authentifié sans rôle', function () {
    // Cas réel : un compte créé hors de cet écran (seeder de démo, future espace adhérent).
    // Authentifié ne veut pas dire administrateur.
    $this->actingAs(User::factory()->create())
        ->get('/admin/dashboard')
        ->assertForbidden();
});

it("n'envoie au dashboard que les blocs autorisés", function () {
    $writer = actingWithRole(AdminRoles::WRITER);

    // Les chiffres d'adhésion ne sont pas masqués côté front : ils ne sont pas transmis.
    $this->actingAs($writer)
        ->get('/admin/dashboard')
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Dashboard')
            ->has('contentKpis')
            ->missing('kpis')
            ->missing('regionBreakdown')
        );

    $admin = actingWithRole(AdminRoles::SUPER_ADMIN);

    $this->actingAs($admin)
        ->get('/admin/dashboard')
        ->assertInertia(fn ($page) => $page->has('kpis')->has('contentKpis'));
});

it('expose à chaque rôle la liste de ses permissions', function () {
    $manager = actingWithRole(AdminRoles::CONTENT_MANAGER);

    $this->actingAs($manager)
        ->get('/admin/dashboard')
        ->assertInertia(fn ($page) => $page
            ->where('auth.permissions', AdminRoles::permissionsFor(AdminRoles::CONTENT_MANAGER))
        );
});

it('expose au Super-admin tout le catalogue de permissions', function () {
    // Ses droits viennent du Gate::before, pas de la table : la liste partagée doit malgré
    // tout être complète, sinon son menu serait vide.
    $admin = actingWithRole(AdminRoles::SUPER_ADMIN);

    $this->actingAs($admin)
        ->get('/admin/dashboard')
        ->assertInertia(fn ($page) => $page->where('auth.permissions', AdminRoles::permissions()));
});

it('accorde au Super-admin une permission absente de la table', function () {
    // Garde-fou d'exploitation : un `db:seed` oublié après l'ajout d'une permission ne doit
    // pas enfermer l'administrateur dehors de son propre back-office.
    $admin = actingWithRole(AdminRoles::SUPER_ADMIN);

    expect($admin->can('permission.jamais.semee'))->toBeTrue();
});
