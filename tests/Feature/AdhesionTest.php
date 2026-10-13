<?php

use App\Domain\Members\Models\Member;
use App\Mail\MembershipConfirmed;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * Le débit (throttle:5,1) n'est pas ce que ces tests vérifient — on le désactive ici pour ne
 * pas coupler le nombre de tests à la limite de la route de soumission.
 *
 * `withoutDefer()` : l'email de confirmation part par `defer()` (cf. SendMembershipEmail),
 * donc APRÈS la réponse HTTP, au moment où le middleware `InvokeDeferredCallbacks` vide la
 * pile. Dans un test, cette phase n'a pas lieu — sans cela, aucune assertion sur l'envoi ne
 * pourrait aboutir. `withoutDefer()` exécute les rappels immédiatement.
 */
beforeEach(function () {
    $this->withoutMiddleware(ThrottleRequests::class);
    $this->withoutDefer();
});

function validAdhesionPayload(array $overrides = []): array
{
    return array_merge([
        'website' => '',
        'first_name' => 'Awa',
        'last_name' => 'Diallo',
        'birth_date' => '1990-05-12',
        'gender' => 'femme',
        'phone' => '+221 77 543 60 81',
        'email' => 'awa.diallo@example.com',
        'region' => 'Dakar',
        'commune' => 'Guédiawaye',
        'address' => 'Cité Keur Gorgui, villa 12',
        'profession' => 'Enseignante',
        'id_document_number' => null,
        'section' => 'femmes',
        'referral_source' => 'Réseaux sociaux',
        'consent' => true,
        'tier' => null,
    ], $overrides);
}

it('active le membre immédiatement lors de la soumission du wizard (cotisation désactivée)', function () {
    Mail::fake();

    $response = $this->post('/rejoignez-nous', validAdhesionPayload());

    $member = Member::first();

    expect($member)->not->toBeNull()
        ->and($member->status)->toBe('active')
        ->and($member->joined_at)->not->toBeNull()
        ->and($member->payments)->toHaveCount(0);

    $response->assertRedirect(route('adhesion.confirmation', $member->membership_code));

    Mail::assertSent(MembershipConfirmed::class);
});

it('active le membre et confirme le paiement simulé quand une cotisation est choisie', function () {
    Mail::fake();

    $response = $this->post('/rejoignez-nous', validAdhesionPayload([
        'email' => 'soutien@example.com',
        'tier' => 'soutien',
    ]));

    $member = Member::where('email', 'soutien@example.com')->first();

    expect($member->status)->toBe('active')
        ->and($member->payments)->toHaveCount(1);

    $payment = $member->payments->first();

    expect($payment->status)->toBe('success')
        ->and($payment->gateway_reference)->not->toBeNull();

    $response->assertRedirect(route('adhesion.confirmation', $member->membership_code));

    Mail::assertSent(MembershipConfirmed::class);
});

it('calcule toujours le montant depuis la configuration serveur, jamais depuis le client', function () {
    $this->post('/rejoignez-nous', validAdhesionPayload([
        'email' => 'anti-tampering@example.com',
        'tier' => 'bienfaiteur',
        // Tentative de manipulation du montant : ce champ n'existe dans aucune règle de
        // validation ni dans RegisterMember, il doit être totalement ignoré.
        'amount' => 1,
    ]));

    $member = Member::where('email', 'anti-tampering@example.com')->first();
    $payment = $member->payments->first();

    expect($payment->amount)->toBe(config('adhesion.tiers.bienfaiteur.amount'))
        ->and($payment->amount)->not->toBe(1);
});

it('ne retraite pas un webhook rejoué avec la même référence (idempotence)', function () {
    Mail::fake();

    $this->post('/rejoignez-nous', validAdhesionPayload([
        'email' => 'webhook@example.com',
        'tier' => 'standard',
    ]));

    $member = Member::where('email', 'webhook@example.com')->first();
    $payment = $member->payments->first();
    $reference = $payment->gateway_reference;

    $this->postJson('/paiement/webhook', ['gateway_reference' => $reference])->assertNoContent();
    $this->postJson('/paiement/webhook', ['gateway_reference' => $reference])->assertNoContent();

    // Confirmé une seule fois au total (par la passerelle simulée), les deux rejeux du
    // webhook ne doivent pas déclencher un second email ni un second traitement.
    Mail::assertSent(MembershipConfirmed::class, 1);

    $member->refresh();
    $payment->refresh();

    expect($payment->status)->toBe('success')
        ->and($member->status)->toBe('active');
});

it('vérifie un code d\'adhésion valide et rejette un code inconnu', function () {
    $this->post('/rejoignez-nous', validAdhesionPayload([
        'email' => 'verif@example.com',
        'tier' => 'standard',
    ]));

    $member = Member::where('email', 'verif@example.com')->first();

    $this->get("/verifier/{$member->membership_code}")
        ->assertInertia(fn ($page) => $page
            ->component('Site/Verifier')
            ->where('result.valid', true)
            ->where('result.status', 'active')
        );

    $this->get('/verifier/CODE-INCONNU')
        ->assertInertia(fn ($page) => $page
            ->component('Site/Verifier')
            ->where('result', null)
        );
});

it('stocke la photo de l\'adhérent quand elle est fournie', function () {
    Storage::fake('public');

    $photo = UploadedFile::fake()->image('portrait.jpg', 400, 500);

    $this->post('/rejoignez-nous', validAdhesionPayload([
        'email' => 'avec-photo@example.com',
        'photo' => $photo,
    ]));

    $member = Member::where('email', 'avec-photo@example.com')->first();

    expect($member->photo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($member->photo_path);
});

it('reste fonctionnelle sans photo (champ optionnel)', function () {
    $this->post('/rejoignez-nous', validAdhesionPayload([
        'email' => 'sans-photo@example.com',
    ]))->assertRedirect();

    $member = Member::where('email', 'sans-photo@example.com')->first();

    expect($member)->not->toBeNull()
        ->and($member->photo_path)->toBeNull();
});

it('chiffre le numéro de pièce d\'identité au repos', function () {
    $this->post('/rejoignez-nous', validAdhesionPayload([
        'email' => 'piece-identite@example.com',
        'id_document_number' => '1234567890123',
    ]));

    $member = Member::where('email', 'piece-identite@example.com')->first();

    // La colonne brute en base ne doit jamais contenir la valeur en clair.
    $raw = DB::table('members')->where('id', $member->id)->value('id_document_number');
    expect($raw)->not->toBe('1234567890123');

    // Mais le modèle la déchiffre correctement via le cast `encrypted`.
    expect($member->fresh()->id_document_number)->toBe('1234567890123');
});

it('rejette un numéro de téléphone qui n\'est pas au format sénégalais', function () {
    $response = $this->post('/rejoignez-nous', validAdhesionPayload([
        'email' => 'mauvais-numero@example.com',
        'phone' => '+33 6 12 34 56 78',
    ]));

    $response->assertSessionHasErrors('phone');
    expect(Member::where('email', 'mauvais-numero@example.com')->exists())->toBeFalse();
});

it('accepte un numéro sénégalais valide avec ou sans indicatif +221', function () {
    $this->post('/rejoignez-nous', validAdhesionPayload([
        'email' => 'sans-indicatif@example.com',
        'phone' => '77 543 60 81',
    ]))->assertRedirect();

    expect(Member::where('email', 'sans-indicatif@example.com')->exists())->toBeTrue();
});

it('indique si un email est disponible via la vérification en direct', function () {
    $this->post('/rejoignez-nous', validAdhesionPayload(['email' => 'deja-pris@example.com']));

    $this->postJson('/rejoignez-nous/verifier-email', ['email' => 'deja-pris@example.com'])
        ->assertOk()
        ->assertJson(['available' => false]);

    $this->postJson('/rejoignez-nous/verifier-email', ['email' => 'jamais-vu@example.com'])
        ->assertOk()
        ->assertJson(['available' => true]);
});
