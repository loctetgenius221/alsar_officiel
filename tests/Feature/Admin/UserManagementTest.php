<?php

/*
| Gestion des comptes du back-office : création, rôle, mot de passe, blocage.
|
| Ce qui est verrouillé ici, ce ne sont pas les écrans mais les quelques manipulations qui
| peuvent rendre le back-office inutilisable (se bloquer soi-même, rétrograder le dernier
| administrateur) et celles qui doivent avoir un effet IMMÉDIAT (un blocage ferme la session
| en cours, il n'attend pas la prochaine connexion).
*/

use App\Models\User;
use App\Support\AdminRoles;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RolesSeeder::class);

    $this->admin = User::factory()->create(['name' => 'Racine Admin']);
    $this->admin->assignRole(AdminRoles::SUPER_ADMIN);
});

/**
 * Repasse en visiteur après une action d'administration.
 *
 * Indispensable avant d'éprouver une vraie connexion : la route POST /admin/login porte le
 * middleware `guest`, un test resté authentifié se ferait rediriger vers le dashboard sans
 * qu'aucun identifiant ne soit vérifié — et l'assertion passerait pour la mauvaise raison.
 */
function backToGuest(): void
{
    auth()->logout();
    test()->flushSession();
}

function userWithRole(string $role, array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $user->assignRole($role);

    return $user;
}

it('crée un compte avec son rôle, utilisable immédiatement', function () {
    $this->actingAs($this->admin)
        ->post('/admin/utilisateurs', [
            'name' => 'Awa Rédactrice',
            'email' => 'awa@alsar.test',
            'role' => AdminRoles::WRITER,
            'password' => 'motdepasse2026',
            'password_confirmation' => 'motdepasse2026',
        ])
        ->assertRedirect('/admin/utilisateurs')
        ->assertSessionHas('success');

    $created = User::where('email', 'awa@alsar.test')->first();

    expect($created)->not->toBeNull()
        ->and($created->hasRole(AdminRoles::WRITER))->toBeTrue()
        ->and(Hash::check('motdepasse2026', $created->password))->toBeTrue()
        ->and($created->isBlocked())->toBeFalse();

    // Le compte doit pouvoir se connecter sans étape intermédiaire (pas de vérification
    // d'email à faire : l'adresse a été saisie par un administrateur).
    backToGuest();
    $this->post('/admin/login', ['email' => 'awa@alsar.test', 'password' => 'motdepasse2026'])
        ->assertRedirect('/admin/dashboard');
    $this->assertAuthenticatedAs($created);
});

it('refuse un email déjà utilisé par un autre compte', function () {
    $this->actingAs($this->admin)
        ->from('/admin/utilisateurs/nouveau')
        ->post('/admin/utilisateurs', [
            'name' => 'Doublon',
            'email' => $this->admin->email,
            'role' => AdminRoles::WRITER,
            'password' => 'motdepasse2026',
            'password_confirmation' => 'motdepasse2026',
        ])
        ->assertSessionHasErrors('email');

    expect(User::where('name', 'Doublon')->exists())->toBeFalse();
});

it('refuse un mot de passe trop court ou non confirmé', function (array $payload, string $field) {
    $this->actingAs($this->admin)
        ->from('/admin/utilisateurs/nouveau')
        ->post('/admin/utilisateurs', [
            'name' => 'Nouveau',
            'email' => 'nouveau@alsar.test',
            'role' => AdminRoles::WRITER,
            ...$payload,
        ])
        ->assertSessionHasErrors($field);

    expect(User::where('email', 'nouveau@alsar.test')->exists())->toBeFalse();
})->with([
    'trop court' => [['password' => 'court1', 'password_confirmation' => 'court1'], 'password'],
    'sans chiffre' => [['password' => 'quecheslettres', 'password_confirmation' => 'quecheslettres'], 'password'],
    'confirmation différente' => [['password' => 'motdepasse2026', 'password_confirmation' => 'autrechose2026'], 'password'],
]);

it('refuse un rôle absent du catalogue', function () {
    $this->actingAs($this->admin)
        ->from('/admin/utilisateurs/nouveau')
        ->post('/admin/utilisateurs', [
            'name' => 'Pirate',
            'email' => 'pirate@alsar.test',
            'role' => 'Trésorier',
            'password' => 'motdepasse2026',
            'password_confirmation' => 'motdepasse2026',
        ])
        ->assertSessionHasErrors('role');
});

it("réinitialise le mot de passe d'un compte", function () {
    $target = userWithRole(AdminRoles::WRITER, ['password' => Hash::make('ancien-mot-de-passe')]);

    $this->actingAs($this->admin)
        ->patch("/admin/utilisateurs/{$target->getRouteKey()}/mot-de-passe", [
            'password' => 'nouveau-mot2026',
            'password_confirmation' => 'nouveau-mot2026',
        ])
        ->assertSessionHas('success');

    expect(Hash::check('nouveau-mot2026', $target->refresh()->password))->toBeTrue();

    // L'ancien doit cesser de fonctionner — sinon « réinitialiser » ne protège de rien.
    backToGuest();
    $this->from('/admin/login')
        ->post('/admin/login', ['email' => $target->email, 'password' => 'ancien-mot-de-passe'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('change le rôle d’un compte', function () {
    $target = userWithRole(AdminRoles::WRITER);

    $this->actingAs($this->admin)
        ->patch("/admin/utilisateurs/{$target->getRouteKey()}", [
            'name' => $target->name,
            'email' => $target->email,
            'role' => AdminRoles::CONTENT_MANAGER,
        ])
        ->assertSessionHas('success');

    expect($target->refresh()->hasRole(AdminRoles::CONTENT_MANAGER))->toBeTrue()
        ->and($target->hasRole(AdminRoles::WRITER))->toBeFalse();
});

it('bloque un compte et ferme immédiatement sa session en cours', function () {
    $target = userWithRole(AdminRoles::CONTENT_MANAGER);

    $this->actingAs($this->admin)
        ->patch("/admin/utilisateurs/{$target->getRouteKey()}/blocage")
        ->assertSessionHas('success');

    expect($target->refresh()->isBlocked())->toBeTrue();

    // Le cœur de la fonctionnalité : sans ce comportement, bloquer quelqu'un n'aurait aucun
    // effet tant qu'il garde son onglet ouvert.
    $this->actingAs($target)
        ->get('/admin/dashboard')
        ->assertRedirect('/admin/login');

    $this->assertGuest();
});

it('empêche un compte bloqué de se reconnecter, même avec le bon mot de passe', function () {
    $target = userWithRole(AdminRoles::WRITER, [
        'password' => Hash::make('mot-de-passe-valide'),
        'blocked_at' => now(),
    ]);

    $this->from('/admin/login')
        ->post('/admin/login', ['email' => $target->email, 'password' => 'mot-de-passe-valide'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it("rétablit l'accès d'un compte bloqué", function () {
    $target = userWithRole(AdminRoles::WRITER, [
        'password' => Hash::make('mot-de-passe-valide'),
        'blocked_at' => now(),
    ]);

    $this->actingAs($this->admin)->patch("/admin/utilisateurs/{$target->getRouteKey()}/blocage");

    expect($target->refresh()->isBlocked())->toBeFalse();

    backToGuest();
    $this->post('/admin/login', ['email' => $target->email, 'password' => 'mot-de-passe-valide'])
        ->assertRedirect('/admin/dashboard');
    $this->assertAuthenticatedAs($target);
});

it('refuse de bloquer son propre compte', function () {
    $this->actingAs($this->admin)
        ->patch("/admin/utilisateurs/{$this->admin->getRouteKey()}/blocage")
        ->assertSessionHas('error');

    expect($this->admin->refresh()->isBlocked())->toBeFalse();
});

it('refuse de changer son propre rôle', function () {
    $this->actingAs($this->admin)
        ->from("/admin/utilisateurs/{$this->admin->getRouteKey()}/modifier")
        ->patch("/admin/utilisateurs/{$this->admin->getRouteKey()}", [
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'role' => AdminRoles::WRITER,
        ])
        ->assertSessionHasErrors('role');

    expect($this->admin->refresh()->hasRole(AdminRoles::SUPER_ADMIN))->toBeTrue();
});

it('refuse de bloquer le dernier administrateur actif', function () {
    // Un second administrateur fait l'action, pour écarter la règle « pas sur soi-même » et
    // n'éprouver que celle du dernier administrateur.
    $other = userWithRole(AdminRoles::SUPER_ADMIN);
    $other->forceFill(['blocked_at' => now()])->save();

    $acting = userWithRole(AdminRoles::CONTENT_MANAGER);
    $acting->givePermissionTo(AdminRoles::MANAGE_USERS);

    $this->actingAs($acting)
        ->patch("/admin/utilisateurs/{$this->admin->getRouteKey()}/blocage")
        ->assertSessionHas('error');

    expect($this->admin->refresh()->isBlocked())->toBeFalse();
});

it('refuse de rétrograder le dernier administrateur actif', function () {
    $acting = userWithRole(AdminRoles::CONTENT_MANAGER);
    $acting->givePermissionTo(AdminRoles::MANAGE_USERS);

    $this->actingAs($acting)
        ->from("/admin/utilisateurs/{$this->admin->getRouteKey()}/modifier")
        ->patch("/admin/utilisateurs/{$this->admin->getRouteKey()}", [
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'role' => AdminRoles::WRITER,
        ])
        ->assertSessionHasErrors('role');

    expect($this->admin->refresh()->hasRole(AdminRoles::SUPER_ADMIN))->toBeTrue();
});

it('autorise la rétrogradation quand un autre administrateur actif existe', function () {
    $second = userWithRole(AdminRoles::SUPER_ADMIN);

    $this->actingAs($this->admin)
        ->patch("/admin/utilisateurs/{$second->getRouteKey()}", [
            'name' => $second->name,
            'email' => $second->email,
            'role' => AdminRoles::CONTENT_MANAGER,
        ])
        ->assertSessionHasNoErrors();

    expect($second->refresh()->hasRole(AdminRoles::CONTENT_MANAGER))->toBeTrue();
});
