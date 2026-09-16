<?php

namespace App\Domain\Members\Actions;

use App\Domain\Members\Models\Member;
use App\Support\PdfAsset;
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
        // Le logo central est facultatif : résolu via PdfAsset (qui cherche dans plusieurs
        // racines et journalise son absence), et simplement omis s'il reste introuvable —
        // mieux vaut un QR sans logo qu'une génération de carte qui échoue.
        $logoPath = PdfAsset::path('images/logo-alsar.png');

        return (new Builder(
            writer: new PngWriter,
            data: route('verifier', $member->membership_code),
            size: $size,
            margin: 8,
            logoPath: $logoPath,
            logoResizeToWidth: $logoPath ? (int) round($size * 0.22) : null,
            logoPunchoutBackground: true,
        ))->build()->getDataUri();
    }
}
