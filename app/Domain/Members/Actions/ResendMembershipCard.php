<?php

namespace App\Domain\Members\Actions;

use App\Domain\Members\Models\Member;
use App\Mail\MembershipConfirmed;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

/**
 * Renvoie l'email de confirmation (carte + reçu PDF) à un adhérent déjà actif — réutilise le
 * Mailable de la Phase 2, aucun nouveau template. N'a de sens que pour un membre actif : il
 * possède alors forcément un paiement "success" (seul chemin vers le statut actif, cf.
 * ConfirmMembershipPayment).
 */
class ResendMembershipCard
{
    public function handle(Member $member, User $causedBy): void
    {
        $payment = $member->payments()->where('status', 'success')->latest('paid_at')->first();

        if ($member->status !== 'active' || ! $payment) {
            throw new RuntimeException("Impossible de renvoyer la carte : l'adhérent n'est pas actif.");
        }

        Mail::to($member->email)->queue(new MembershipConfirmed($member, $payment));

        activity()
            ->causedBy($causedBy)
            ->performedOn($member)
            ->log('Carte de membre renvoyée par email');
    }
}
