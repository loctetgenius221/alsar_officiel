{{--
    Version texte de l'email de confirmation.

    Un message envoyé uniquement en HTML est pénalisé par les filtres anti-spam, qui attendent
    les deux parties d'un message multipart. C'est aussi ce que voient les clients configurés en
    texte seul. Elle doit donc porter la même information, sans rien perdre.
--}}
ALSAR — Alliance pour la Synergie des Actions Républicaines

Bienvenue, {{ $member->fullName() }}.

Votre adhésion est enregistrée. Votre carte de membre numérique est jointe à cet
email, au format PDF.

Code d'adhésion : {{ $member->membership_code }}
Section : {{ ucfirst($member->section) }}
@if ($member->joined_at)
Membre depuis : {{ $member->joined_at->translatedFormat('d F Y') }}
@endif
@if ($payment)
Cotisation : {{ ucfirst($payment->tier) }} — {{ number_format($payment->amount, 0, ',', ' ') }} {{ $payment->currency }}
@endif

Vérifier publiquement votre adhésion, à tout moment :
{{ route('verifier', $member->membership_code) }}

Conservez cet email : votre carte peut être présentée depuis votre téléphone lors
des rencontres du parti, et son QR code permet d'en confirmer la validité.

--
Alliance pour la Synergie des Actions Républicaines
Cité Keur Gorgui, immeuble Hermès 2, Dakar — Sénégal
{{ url('/') }}
