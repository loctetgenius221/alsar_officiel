<?php

use App\Domain\Members\Models\Member;
use App\Domain\Payments\Models\MembershipPayment;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('Super-admin', 'web');
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Super-admin');
});

it('confirme manuellement un paiement en attente et active l\'adhérent', function () {
    Mail::fake();

    $member = Member::factory()->create(['status' => 'pending']);
    $payment = MembershipPayment::factory()->create(['member_id' => $member->id, 'status' => 'pending']);

    $this->actingAs($this->admin)
        ->post("/admin/paiements/{$payment->id}/confirmer")
        ->assertRedirect();

    expect($payment->fresh()->status)->toBe('success');
    expect($member->fresh()->status)->toBe('active');
});

it('refuse de reconfirmer un paiement déjà traité', function () {
    $member = Member::factory()->active()->create();
    $payment = MembershipPayment::factory()->success()->create(['member_id' => $member->id]);

    $response = $this->actingAs($this->admin)->post("/admin/paiements/{$payment->id}/confirmer");

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

it('empêche un invité d\'accéder au journal d\'audit', function () {
    $this->get('/admin/journal-audit')->assertRedirect('/admin/login');
});

it('affiche le journal d\'audit à un admin authentifié', function () {
    $this->actingAs($this->admin)
        ->get('/admin/journal-audit')
        ->assertInertia(fn ($page) => $page->component('Admin/ActivityLog/Index'));
});
