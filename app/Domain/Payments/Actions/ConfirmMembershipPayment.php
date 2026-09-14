<?php

namespace App\Domain\Payments\Actions;

use App\Domain\Payments\Models\MembershipPayment;
use App\Mail\MembershipConfirmed;
use Illuminate\Support\Facades\Mail;

/**
 * Action idempotente partagée par la passerelle simulée ET le futur vrai webhook : appelée
 * deux fois avec la même `gateway_reference`, elle ne rejoue rien la seconde fois
 * (CLAUDE.md §7 : "Membership_Payment doit être idempotent vis-à-vis des webhooks").
 */
class ConfirmMembershipPayment
{
    public function handle(MembershipPayment $payment): MembershipPayment
    {
        if ($payment->status === 'success') {
            return $payment;
        }

        $payment->forceFill([
            'status' => 'success',
            'paid_at' => now(),
        ])->save();

        $member = $payment->member;
        $member->forceFill([
            'status' => 'active',
            'joined_at' => $member->joined_at ?? now(),
        ])->save();

        Mail::to($member->email)->queue(new MembershipConfirmed($member, $payment));

        return $payment;
    }
}
