<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

function createAdmin(string $password = 'password-super-secret'): User
{
    Role::findOrCreate('Super-admin', 'web');

    $admin = User::factory()->create(['password' => Hash::make($password)]);
    $admin->assignRole('Super-admin');

    return $admin;
}

it('redirige un invité vers la connexion admin sur une route protégée', function () {
    $this->get('/admin/dashboard')->assertRedirect('/admin/login');
});

it('connecte un admin avec les bons identifiants', function () {
    $admin = createAdmin('mon-mot-de-passe');

    $response = $this->post('/admin/login', [
        'email' => $admin->email,
        'password' => 'mon-mot-de-passe',
    ]);

    $response->assertRedirect('/admin/dashboard');
    $this->assertAuthenticatedAs($admin);
});

it('rejette une connexion avec un mauvais mot de passe', function () {
    $admin = createAdmin('bon-mot-de-passe');

    $response = $this->from('/admin/login')->post('/admin/login', [
        'email' => $admin->email,
        'password' => 'mauvais-mot-de-passe',
    ]);

    $response->assertRedirect('/admin/login');
    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('limite les tentatives de connexion répétées', function () {
    $admin = createAdmin();

    for ($i = 0; $i < 5; $i++) {
        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'faux']);
    }

    $response = $this->post('/admin/login', ['email' => $admin->email, 'password' => 'faux']);

    $response->assertStatus(429);
});

it('déconnecte un admin authentifié', function () {
    $admin = createAdmin();

    $this->actingAs($admin)->post('/admin/logout')->assertRedirect('/admin/login');
    $this->assertGuest();
});
