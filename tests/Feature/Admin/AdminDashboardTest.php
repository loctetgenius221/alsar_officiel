<?php

use App\Domain\Members\Models\Member;
use App\Domain\Payments\Models\MembershipPayment;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('Super-admin', 'web');
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Super-admin');
});

it('calcule des KPIs corrects à partir de fixtures connues', function () {
    Member::factory()->count(2)->active()->create(['region' => 'Dakar']);
    Member::factory()->count(1)->inactive()->create(['region' => 'Thiès']);
    Member::factory()->count(3)->create(['region' => 'Dakar']); // pending

    $activeMembers = Member::where('status', 'active')->get();
    MembershipPayment::factory()->success()->create(['member_id' => $activeMembers[0]->id, 'amount' => 5000]);
    MembershipPayment::factory()->success()->create(['member_id' => $activeMembers[1]->id, 'amount' => 15000]);

    $response = $this->actingAs($this->admin)->get('/admin/dashboard');

    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Dashboard')
        ->where('kpis.totalMembers', 6)
        ->where('kpis.newMembers30d', 6)
        ->where('kpis.totalCollected', 20000)
        ->where('kpis.paidMembershipRate', (int) round((2 / 6) * 100))
        ->has('regionBreakdown', 2)
        ->where('regionBreakdown.0.region', 'Dakar')
        ->where('regionBreakdown.0.total', 5)
    );
});

it("affiche un dashboard vide sans erreur quand aucun adhérent n'existe", function () {
    $this->actingAs($this->admin)
        ->get('/admin/dashboard')
        ->assertInertia(fn ($page) => $page
            ->where('kpis.totalMembers', 0)
            ->where('kpis.paidMembershipRate', 0)
            ->where('kpis.totalCollected', 0)
        );
});
