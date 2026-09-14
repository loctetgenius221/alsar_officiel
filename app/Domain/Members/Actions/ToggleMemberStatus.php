<?php

namespace App\Domain\Members\Actions;

use App\Domain\Members\Models\Member;
use App\Models\User;

/**
 * Bascule un adhérent actif <-> inactif (CLAUDE.md §6, action "désactiver" sur la fiche
 * adhérent). Un membre "pending" n'est pas concerné : son activation passe par la
 * confirmation d'un paiement (App\Http\Controllers\Admin\PaymentController::confirmManually).
 */
class ToggleMemberStatus
{
    public function handle(Member $member, User $causedBy): Member
    {
        $newStatus = match ($member->status) {
            'active' => 'inactive',
            'inactive' => 'active',
            default => $member->status,
        };

        if ($newStatus !== $member->status) {
            $previousStatus = $member->status;
            $member->forceFill(['status' => $newStatus])->save();

            activity()
                ->causedBy($causedBy)
                ->performedOn($member)
                ->withProperties(['from' => $previousStatus, 'to' => $newStatus])
                ->log("Statut de l'adhérent changé : {$previousStatus} → {$newStatus}");
        }

        return $member;
    }
}
