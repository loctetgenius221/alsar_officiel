<?php

namespace App\Domain\Members\Actions;

use App\Domain\Members\Models\Member;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

/**
 * QR de vérification publique (/verifier/{code}), avec le logo ALSAR au centre. Partagée
 * par AdhesionController::confirmation()/::card() et MembershipConfirmed — jusqu'ici
 * dupliquée à l'identique aux 3 endroits.
 */
class BuildMembershipQrCode
{
    public function handle(Member $member, int $size = 260): string
    {
        return (new Builder(
            writer: new PngWriter(),
            data: route('verifier', $member->membership_code),
            size: $size,
            margin: 8,
            logoPath: public_path('images/logo-alsar.png'),
            logoResizeToWidth: (int) round($size * 0.22),
            logoPunchoutBackground: true,
        ))->build()->getDataUri();
    }
}
