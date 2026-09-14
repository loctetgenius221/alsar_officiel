<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8" />
    </head>
    <body style="font-family: sans-serif; color: #0e1a2b; line-height: 1.6;">
        <p style="color: #b8863a; text-transform: uppercase; font-size: 12px; letter-spacing: 1px;">
            ALSAR — Alliance pour la Synergie des Actions Républicaines
        </p>

        <h1 style="font-size: 20px;">Bienvenue, {{ $member->fullName() }} !</h1>

        <p>
            Votre adhésion à ALSAR est confirmée. Vous trouverez votre carte de membre numérique
            (avec QR code vérifiable) en pièce jointe de cet email.
        </p>

        <p>
            <strong>Code d'adhésion :</strong> {{ $member->membership_code }}<br />
            <strong>Section :</strong> {{ ucfirst($member->section) }}
        </p>

        <p>Vous pouvez vérifier publiquement votre adhésion à tout moment via ce lien :</p>
        <p><a href="{{ route('verifier', $member->membership_code) }}">{{ route('verifier', $member->membership_code) }}</a></p>

        <p style="margin-top: 32px; color: #6e5f4a; font-size: 12px;">
            Alliance pour la Synergie des Actions Républicaines — Podor, région de Saint-Louis, Sénégal
        </p>
    </body>
</html>
