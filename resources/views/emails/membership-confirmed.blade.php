{{--
    Email de confirmation d'adhésion.

    Un email n'est pas une page web : Outlook rend le HTML avec le moteur de Word, Gmail
    supprime la plupart des feuilles de style, et rien ne garantit flexbox, grid, les variables
    CSS ni les polices distantes. D'où les choix ci-dessous, qui paraîtront datés hors contexte
    mais sont les seuls fiables :
      - mise en page en <table> imbriquées, jamais en div flottantes ;
      - styles ÉCRITS EN LIGNE sur chaque élément, le bloc <style> du <head> ne servant qu'au
        responsive (les clients qui l'ignorent retombent sur une colonne de 600 px, lisible) ;
      - polices système (Georgia pour les titres, Helvetica pour le texte) : Fraunces et Public
        Sans ne se chargent pas dans un client mail, autant assumer un équivalent propre ;
      - couleurs en hexadécimal littéral, puisque les tokens Tailwind n'existent pas ici. Elles
        reprennent exactement la palette du site (CLAUDE.md §3.1).

    Images bloquées par défaut (Outlook, nombreux clients) : le logo est accompagné du mot
    « ALSAR » en texte, et aucune information n'est portée par une image seule.
--}}
@php
    $verifyUrl = route('verifier', $member->membership_code);
    $joinedAt = $member->joined_at?->translatedFormat('d F Y');

    // Logo INTÉGRÉ au message (pièce jointe en ligne) plutôt que chargé depuis le site :
    //   - il s'affiche même quand le client bloque les images distantes, ce que font Outlook
    //     et de nombreux clients d'entreprise par défaut ;
    //   - il ne dépend pas de la joignabilité du domaine au moment où l'email est ouvert.
    // On prend le fichier 192 px (32 Ko) et non logo-alsar.png (152 Ko) : affiché à 40 px, il
    // est amplement suffisant, et un email n'a pas à transporter 150 Ko de décoration.
    // `PdfAsset::path()` cherche dans plusieurs racines (même logique que la carte PDF) et
    // renvoie null si le fichier manque — on retombe alors sur l'URL distante.
    $logoFile = \App\Support\PdfAsset::path('images/favicon-192.png');
    $logoSrc = $logoFile ? $message->embed($logoFile) : url('/images/favicon-192.png');
@endphp
<!DOCTYPE html>
<html lang="fr" xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="x-apple-disable-message-reformatting" />
        <meta name="color-scheme" content="light" />
        <meta name="supported-color-schemes" content="light" />
        <title>Votre adhésion à ALSAR est confirmée</title>
        <style>
            /* Seul usage du <style> : réduire les marges sur petit écran. Tout le reste est en
               ligne, pour les clients qui suppriment cette balise. */
            @media only screen and (max-width: 620px) {
                .alsar-pad { padding-left: 24px !important; padding-right: 24px !important; }
                .alsar-title { font-size: 26px !important; }
            }
        </style>
    </head>

    <body style="margin: 0; padding: 0; background-color: #f2ede2; -webkit-font-smoothing: antialiased;">
        {{-- Texte d'aperçu : ce que le client mail affiche à côté de l'objet dans la liste.
             Sans lui, il y afficherait les premiers mots de l'en-tête, c'est-à-dire « ALSAR ». --}}
        <div style="display: none; max-height: 0; overflow: hidden; mso-hide: all;">
            Votre carte de membre numérique est en pièce jointe, avec son QR code vérifiable.
        </div>

        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f2ede2;">
            <tr>
                <td align="center" style="padding: 32px 12px;">
                    {{-- Outlook (moteur Word) ignore `max-width` : sans cette table
                         conditionnelle, le message s'y afficherait sur toute la largeur de la
                         fenêtre. Les autres clients ne voient que la table fluide, qui se
                         réduit correctement sur téléphone. --}}
                    <!--[if mso]><table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"><tr><td><![endif]-->
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width: 100%; max-width: 600px;">

                        {{-- En-tête : bandeau encre, logo + mot-symbole. Le texte porte le sens,
                             l'image ne fait que l'accompagner. --}}
                        <tr>
                            <td class="alsar-pad" style="background-color: #0e1a2b; padding: 28px 40px;">
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                    <tr>
                                        <td style="padding-right: 12px; vertical-align: middle;">
                                            <img
                                                src="{{ $logoSrc }}"
                                                width="40"
                                                height="40"
                                                alt=""
                                                style="display: block; width: 40px; height: 40px; border: 0;"
                                            />
                                        </td>
                                        <td style="vertical-align: middle;">
                                            <span style="font-family: Georgia, 'Times New Roman', serif; font-size: 20px; color: #faf7f1; letter-spacing: 0.5px;">ALSAR</span>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        {{-- Filet or : la seule touche décorative, reprise du site. --}}
                        <tr>
                            <td style="background-color: #c9962c; font-size: 0; line-height: 0; height: 3px;">&nbsp;</td>
                        </tr>

                        <tr>
                            <td class="alsar-pad" style="background-color: #faf7f1; padding: 40px;">

                                <p style="margin: 0 0 6px; font-family: Helvetica, Arial, sans-serif; font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; color: #b8863a;">
                                    Adhésion confirmée
                                </p>

                                <h1 class="alsar-title" style="margin: 0 0 20px; font-family: Georgia, 'Times New Roman', serif; font-size: 30px; line-height: 1.25; font-weight: normal; color: #0e1a2b;">
                                    Bienvenue, {{ $member->fullName() }}.
                                </h1>

                                <p style="margin: 0 0 28px; font-family: Helvetica, Arial, sans-serif; font-size: 16px; line-height: 1.65; color: #26364d;">
                                    Votre adhésion à l'Alliance pour la Synergie des Actions Républicaines est
                                    enregistrée. Votre carte de membre numérique est jointe à cet email, au
                                    format PDF.
                                </p>

                                {{-- Bloc « carte » : les informations qu'un adhérent cherchera à relire plus
                                     tard. Encadré plutôt que fondu dans le texte, pour qu'on le retrouve d'un
                                     coup d'œil en rouvrant l'email dans six mois. --}}
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f2ede2; border: 1px solid #e6ddcc;">
                                    <tr>
                                        <td style="padding: 24px 28px;">
                                            <p style="margin: 0 0 4px; font-family: Helvetica, Arial, sans-serif; font-size: 11px; letter-spacing: 1.2px; text-transform: uppercase; color: #8f7d64;">
                                                Code d'adhésion
                                            </p>
                                            <p style="margin: 0 0 20px; font-family: Georgia, 'Times New Roman', serif; font-size: 26px; letter-spacing: 2px; color: #0e1a2b;">
                                                {{ $member->membership_code }}
                                            </p>

                                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                                <tr>
                                                    <td style="padding-top: 16px; border-top: 1px solid #e6ddcc; font-family: Helvetica, Arial, sans-serif; font-size: 14px; color: #26364d;">
                                                        <strong style="color: #0e1a2b;">Section</strong><br />
                                                        {{ ucfirst($member->section) }}
                                                    </td>
                                                    @if ($joinedAt)
                                                        <td align="right" style="padding-top: 16px; border-top: 1px solid #e6ddcc; font-family: Helvetica, Arial, sans-serif; font-size: 14px; color: #26364d;">
                                                            <strong style="color: #0e1a2b;">Membre depuis</strong><br />
                                                            {{ $joinedAt }}
                                                        </td>
                                                    @endif
                                                </tr>
                                                @if ($payment)
                                                    <tr>
                                                        <td colspan="2" style="padding-top: 16px; font-family: Helvetica, Arial, sans-serif; font-size: 14px; color: #26364d;">
                                                            <strong style="color: #0e1a2b;">Cotisation</strong><br />
                                                            {{ ucfirst($payment->tier) }} —
                                                            {{ number_format($payment->amount, 0, ',', ' ') }} {{ $payment->currency }}
                                                        </td>
                                                    </tr>
                                                @endif
                                            </table>
                                        </td>
                                    </tr>
                                </table>

                                {{-- Bouton « à l'épreuve des balles » : une cellule de tableau colorée, pas un
                                     <a> stylé — Outlook ignore padding et background sur un lien. --}}
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin: 32px 0 0;">
                                    <tr>
                                        <td align="center" bgcolor="#c9962c" style="background-color: #c9962c;">
                                            <a
                                                href="{{ $verifyUrl }}"
                                                style="display: inline-block; padding: 14px 28px; font-family: Helvetica, Arial, sans-serif; font-size: 15px; font-weight: bold; color: #0e1a2b; text-decoration: none;"
                                            >
                                                Vérifier mon adhésion
                                            </a>
                                        </td>
                                    </tr>
                                </table>

                                {{-- L'URL en clair sous le bouton : certains clients d'entreprise suppriment
                                     les liens, et un QR code ne se recopie pas à la main. --}}
                                <p style="margin: 16px 0 0; font-family: Helvetica, Arial, sans-serif; font-size: 13px; line-height: 1.6; color: #6e5f4a;">
                                    Vérifiable publiquement, à tout moment :<br />
                                    <a href="{{ $verifyUrl }}" style="color: #8f6720;">{{ $verifyUrl }}</a>
                                </p>

                                <p style="margin: 28px 0 0; padding-top: 24px; border-top: 1px solid #e6ddcc; font-family: Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.65; color: #26364d;">
                                    Conservez cet email : votre carte peut être présentée depuis votre téléphone
                                    lors des rencontres du parti, et son QR code permet d'en confirmer la validité.
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <td class="alsar-pad" style="background-color: #faf7f1; border-top: 1px solid #e6ddcc; padding: 24px 40px 32px;">
                                <p style="margin: 0 0 6px; font-family: Helvetica, Arial, sans-serif; font-size: 12px; line-height: 1.6; color: #8f7d64;">
                                    <strong style="color: #6e5f4a;">Alliance pour la Synergie des Actions Républicaines</strong><br />
                                    Cité Keur Gorgui, immeuble Hermès 2, Dakar — Sénégal
                                </p>
                                <p style="margin: 0; font-family: Helvetica, Arial, sans-serif; font-size: 12px; color: #8f7d64;">
                                    <a href="{{ url('/') }}" style="color: #8f6720;">{{ parse_url(url('/'), PHP_URL_HOST) }}</a>
                                </p>
                            </td>
                        </tr>
                    </table>
                    <!--[if mso]></td></tr></table><![endif]-->
                </td>
            </tr>
        </table>
    </body>
</html>
