<?php

/*
| Écran « Mon compte ».
|
| Son existence est la contrepartie de la création de comptes par un administrateur : le mot de
| passe initial est connu de deux personnes, il faut que l'intéressé puisse le remplacer seul —
| y compris les rôles qui n'ont aucun accès à /admin/utilisateurs.
*/

use App\Models\User;
use App\Support\AdminRoles;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RolesSeeder::class);

    $this->writer = User::factory()->create(['password' => Hash::make('ancien-mot2026')]);
    $this->writer->assignRole(AdminRoles::WRITER);
});

it('met à jour ses propres informations', function () {
    $this->actingAs($this->writer)
        ->patch('/admin/mon-compte', ['name' => 'Awa Diop', 'email' => 'awa.diop@alsar.test'])
        ->assertSessionHas('success');

    expect($this->writer->refresh()->name)->toBe('Awa Diop')
        ->and($this->writer->email)->toBe('awa.diop@alsar.test');
});

it('refuse une adresse email déjà prise par un autre compte', function () {
    $other = User::factory()->create();

    $this->actingAs($this->writer)
        ->from('/admin/mon-compte')
        ->patch('/admin/mon-compte', ['name' => $this->writer->name, 'email' => $other->email])
        ->assertSessionHasErrors('email');
});

it('change son mot de passe en fournissant l’actuel', function () {
    $this->actingAs($this->writer)
        ->patch('/admin/mon-compte/mot-de-passe', [
            'current_password' => 'ancien-mot2026',
            'password' => 'nouveau-mot2026',
            'password_confirmation' => 'nouveau-mot2026',
        ])
        ->assertSessionHas('success');

    expect(Hash::check('nouveau-mot2026', $this->writer->refresh()->password))->toBeTrue();
});

it('refuse le changement de mot de passe sans le mot de passe actuel correct', function () {
    $this->actingAs($this->writer)
        ->from('/admin/mon-compte')
        ->patch('/admin/mon-compte/mot-de-passe', [
            'current_password' => 'je-ne-sais-plus',
            'password' => 'nouveau-mot2026',
            'password_confirmation' => 'nouveau-mot2026',
        ])
        ->assertSessionHasErrors('current_password');

    expect(Hash::check('ancien-mot2026', $this->writer->refresh()->password))->toBeTrue();
});

it('refuse de réutiliser le mot de passe actuel', function () {
    $this->actingAs($this->writer)
        ->from('/admin/mon-compte')
        ->patch('/admin/mon-compte/mot-de-passe', [
            'current_password' => 'ancien-mot2026',
            'password' => 'ancien-mot2026',
            'password_confirmation' => 'ancien-mot2026',
        ])
        ->assertSessionHasErrors('password');
});

it('ne permet pas de modifier le compte de quelqu’un d’autre depuis cet écran', function () {
    // L'écran n'expose aucun identifiant : il agit toujours sur $request->user(). Ce test
    // documente cette garantie — un rédacteur ne peut pas détourner la route.
    $victim = User::factory()->create(['name' => 'Président']);

    $this->actingAs($this->writer)
        ->patch('/admin/mon-compte', ['name' => 'Usurpé', 'email' => 'usurpe@alsar.test']);

    expect($victim->refresh()->name)->toBe('Président')
        ->and($this->writer->refresh()->name)->toBe('Usurpé');
});
