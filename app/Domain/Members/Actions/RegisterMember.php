<?php

namespace App\Domain\Members\Actions;

use App\Domain\Members\Models\Member;
use App\Domain\Payments\Models\MembershipPayment;
use App\Mail\MembershipConfirmed;
use Illuminate\Support\Facades\Mail;

/**
 * Crée le membre à partir des données déjà validées (StoreMemberRequest). Le montant de la
 * cotisation n'est JAMAIS pris depuis la requête : il est recalculé depuis
 * config('adhesion.tiers') par l'appelant (AdhesionController), cf. plan Phase 2 sécurité.
 *
 * La cotisation est désactivée pour le moment (décision produit) : le wizard public
 * n'envoie plus jamais de `tier`, donc la branche `else` ci-dessous est désormais le seul
 * chemin emprunté en pratique. La branche `$tier !== null` reste intacte (code mort mais
 * fonctionnel) pour pouvoir réactiver la cotisation plus tard sans tout reconstruire.
 */
class RegisterMember
{
    public function handle(array $data, ?string $tier, ?int $amount): Member
    {
        $photoPath = isset($data['photo'])
            ? $data['photo']->store('members/photos', 'public')
            : null;

        $member = Member::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'birth_date' => $data['birth_date'] ?? null,
            'gender' => $data['gender'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'region' => $data['region'],
            'commune' => $data['commune'],
            'address' => $data['address'] ?? null,
            'profession' => $data['profession'] ?? null,
            'photo_path' => $photoPath,
            'id_document_number' => $data['id_document_number'] ?? null,
            'section' => $data['section'] ?? 'standard',
            'referral_source' => $data['referral_source'] ?? null,
            'status' => 'pending',
            'consent_at' => now(),
        ]);

        if ($tier !== null && $amount !== null) {
            MembershipPayment::create([
                'member_id' => $member->id,
                'tier' => $tier,
                'amount' => $amount,
                'currency' => config('adhesion.currency'),
                'gateway' => 'simulation',
                'status' => 'pending',
            ]);
        } else {
            // Adhésion sans cotisation (désactivée pour le moment) : le membre est actif
            // immédiatement, sans étape de paiement ni de validation admin — rien ne
            // justifierait de le laisser "pending" en l'absence de cotisation à attendre.
            $member->forceFill(['status' => 'active', 'joined_at' => now()])->save();

            Mail::to($member->email)->queue(new MembershipConfirmed($member));
        }

        return $member->fresh('payments');
    }
}
