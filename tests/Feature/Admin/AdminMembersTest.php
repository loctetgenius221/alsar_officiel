<?php

use App\Domain\Members\Models\Member;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('Super-admin', 'web');
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Super-admin');
});

it('filtre les adhérents par région et par statut', function () {
    Member::factory()->create(['region' => 'Dakar', 'status' => 'pending', 'first_name' => 'Awa']);
    Member::factory()->active()->create(['region' => 'Dakar', 'first_name' => 'Moussa']);
    Member::factory()->active()->create(['region' => 'Thiès', 'first_name' => 'Fatou']);

    $this->actingAs($this->admin)
        ->get('/admin/adherents?region=Dakar&status=active')
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Members/Index')
            ->has('members.data', 1)
            ->where('members.data.0.fullName', fn ($name) => str_contains($name, 'Moussa'))
        );
});

it('bascule le statut d\'un adhérent actif vers inactif et journalise l\'action', function () {
    $member = Member::factory()->active()->create();

    $this->actingAs($this->admin)
        ->patch("/admin/adherents/{$member->getRouteKey()}/statut")
        ->assertRedirect();

    expect($member->fresh()->status)->toBe('inactive');

    expect(Activity::where('subject_id', $member->id)->where('subject_type', Member::class)->count())->toBe(1);
    expect(Activity::first()->causer_id)->toBe($this->admin->id);
});

it('enregistre une note interne sur la fiche adhérent', function () {
    $member = Member::factory()->create();

    $this->actingAs($this->admin)
        ->patch("/admin/adherents/{$member->getRouteKey()}/note", ['note' => 'À suivre : relancer par téléphone.'])
        ->assertRedirect();

    expect($member->fresh()->note)->toBe('À suivre : relancer par téléphone.');
});

it('empêche un invité d\'accéder à la fiche adhérent', function () {
    $member = Member::factory()->create();

    $this->get("/admin/adherents/{$member->getRouteKey()}")->assertRedirect('/admin/login');
});
