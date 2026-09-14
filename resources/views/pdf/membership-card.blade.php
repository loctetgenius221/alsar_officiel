<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8" />
        <style>
            /*
             * Format carte bancaire ISO/IEC 7810 ID-1 (85,60 x 53,98 mm) — la seule taille
             * qu'un adhérent peut réellement imprimer, découper et glisser dans un
             * portefeuille.
             *
             * Refonte 2026 (v2, retour utilisateur) : fond clair recto ET verso (l'aplat encre
             * du premier jet a été jugé trop sombre) ; le filet or plein cadre en bord gauche
             * est retiré (jugé trop présent) et remplacé par un motif de "vagues" superposées
             * (3 cercles de couleurs et opacités différentes, cf. `.wave-*`) posé en bas de
             * carte — signature de marque plus douce, partagée recto/verso, qui comble aussi
             * le vide en bas du verso plutôt que de laisser un aplat blanc après le tableau des
             * champs. Un seul wrapper `.content` porte le padding intérieur (recto et verso en
             * manquaient totalement — le contenu touchait presque les bords).
             *
             * Contraintes dompdf déjà éprouvées sur ce gabarit :
             * - Pas de dégradé CSS (`linear-gradient` etc.) : ne se rend pas.
             * - `<table>` qui suit un frère en `position:absolute` a besoin de son propre
             *   `position:relative` explicite, sinon dompdf la mal-positionne.
             * - Cercles/formes : `border-radius: 50%` sur un carré fonctionne, testé et vérifié
             *   par rendu réel (voir les vagues ci-dessous).
             * - Photo adhérent : `object-fit: cover` sur un <img> s'est révélé encore étirer
             *   la photo pour certains fichiers (rendu incohérent selon le format/les
             *   dimensions source — support object-fit de dompdf récent et incomplet).
             *   Recadrage fiable obtenu via `background-image` + `background-size: cover` sur
             *   le cadre lui-même (chemin de rendu plus ancien et mieux supporté par dompdf).
             */
            @page {
                margin: 0;
                size: 85.6mm 53.98mm;
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                padding: 0;
                font-family: sans-serif;
                color: #0e1a2b;
            }

            .card {
                position: relative;
                width: 85.6mm;
                height: 53.98mm;
                overflow: hidden;
                page-break-after: always;
            }

            .card:last-child {
                page-break-after: avoid;
            }

            .bg {
                position: absolute;
                inset: 0;
                background-color: #faf7f1;
            }

            /* Vagues superposées — signature de marque commune recto/verso, posée en bas de
               carte. Chaque vague est une boîte ENTIÈREMENT contenue dans ses propres
               dimensions (bottom: 0, jamais de décalage négatif) : sur un rectangle large et
               bas, un rayon de coin à 50% donne un arc très aplati (silhouette de colline)
               sans qu'aucun élément ne déborde verticalement de la carte. Un premier essai
               avec de grands cercles décalés en `bottom` négatif (pour ne montrer qu'une
               calotte) a fait ajouter des pages fantômes par dompdf — il ne respecte pas
               `overflow:hidden` pour son calcul de pagination, seulement pour l'affichage. Ne
               jamais réintroduire un élément dont la boîte dépasse la carte verticalement. */
            .wave {
                position: absolute;
                bottom: 0;
                border-radius: 50% 50% 0 0;
            }

            .wave-1 {
                left: -6mm;
                width: 92mm;
                height: 17mm;
                background-color: #e6ddcc;
            }

            .wave-2 {
                left: 19mm;
                width: 58mm;
                height: 12mm;
                background-color: #dcb466;
                opacity: 0.55;
            }

            .wave-3 {
                left: 53mm;
                width: 36mm;
                height: 7.5mm;
                background-color: #c47a52;
                opacity: 0.45;
            }

            /* Verso : vagues plus discrètes (2 collines, pas 3) pour ne pas concurrencer le
               tableau de champs — juste assez pour combler le bas de carte sans le charger. */
            .wave-back-1 {
                left: -4mm;
                width: 80mm;
                height: 13mm;
                background-color: #f2ede2;
            }

            .wave-back-2 {
                left: 44mm;
                width: 44mm;
                height: 8mm;
                background-color: #dcb466;
                opacity: 0.35;
            }

            /* Contenu : porte tout le padding intérieur (absent avant, contenu au ras des
               bords) — les vagues et le fond restent en plein cadre derrière lui. */
            .content {
                position: relative;
                padding: 3.4mm 3.6mm;
            }

            /* Recto */
            .header-table {
                width: 100%;
                border-collapse: collapse;
            }

            .header-table td {
                vertical-align: middle;
            }

            .header-table .logo-cell {
                width: 9mm;
            }

            .header-table .logo-cell img {
                width: 8mm;
            }

            .brand-cell {
                padding-left: 2.4mm;
            }

            .party-name {
                font-size: 6.4px;
                font-weight: bold;
                letter-spacing: 0.3px;
                line-height: 1.35;
                color: #0e1a2b;
                margin: 0;
            }

            .tagline {
                font-size: 5px;
                font-weight: bold;
                letter-spacing: 1.4px;
                text-transform: uppercase;
                color: #8f6720;
                margin: 1mm 0 0;
            }

            .rule {
                margin: 2.2mm 0 0;
                border-top: 0.25mm solid #d3c5ac;
            }

            .body-table {
                width: 100%;
                border-collapse: collapse;
            }

            .body-table td {
                vertical-align: top;
                padding-top: 2.8mm;
            }

            .photo-cell {
                width: 19mm;
            }

            .photo-frame {
                width: 16mm;
                height: 20mm;
                background-color: #e6ddcc;
                border: 0.4mm solid #d3c5ac;
                border-radius: 0.8mm;
                overflow: hidden;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            /* Recadrage par hauteur fixe + largeur auto (garde les proportions natives de la
               photo) et débordement horizontal coupé par le cadre — ni object-fit ni
               background-size, les deux s'étant révélés peu fiables selon la photo source. */
            .photo-frame img {
                height: 20mm;
                width: auto;
                max-width: none;
                flex-shrink: 0;
            }

            .info-cell {
                padding-left: 2.4mm;
                padding-right: 1.5mm;
            }

            .eyebrow {
                font-size: 5.2px;
                font-weight: bold;
                letter-spacing: 1.4px;
                text-transform: uppercase;
                color: #8f6720;
                margin: 0;
            }

            .member-name {
                font-family: Georgia, 'Times New Roman', serif;
                font-size: 11px;
                font-weight: bold;
                line-height: 1.2;
                color: #0e1a2b;
                margin: 1.4mm 0 0;
            }

            .member-code {
                font-family: 'Courier New', monospace;
                font-size: 6.6px;
                letter-spacing: 0.8px;
                color: #b8863a;
                margin: 2mm 0 0;
            }

            .qr-cell {
                width: 17mm;
                text-align: center;
            }

            .qr-frame {
                background-color: #faf7f1;
                border: 0.25mm solid #e6ddcc;
                padding: 0.8mm;
                border-radius: 0.6mm;
                display: inline-block;
            }

            .qr-cell img {
                width: 12.5mm;
                height: 12.5mm;
            }

            .qr-caption {
                font-size: 4.1px;
                letter-spacing: 0.3px;
                color: #8f7d64;
                margin: 0.9mm 0 0;
            }

            /* Verso */
            .back-header-table {
                width: 100%;
                border-collapse: collapse;
            }

            .back-header-table td {
                vertical-align: middle;
            }

            .back-header-table .logo-cell {
                width: 8mm;
            }

            .back-header-table .logo-cell img {
                width: 6.5mm;
            }

            .movement-name {
                font-size: 5.8px;
                font-weight: bold;
                letter-spacing: 1px;
                text-transform: uppercase;
                color: #8f6720;
                padding-left: 2.2mm;
            }

            .fields-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 1.8mm;
            }

            .fields-table td {
                vertical-align: bottom;
                line-height: 1;
                padding: 1.5mm 0 1.5mm;
                border-bottom: 0.25mm solid #d3c5ac;
            }

            .fields-table tr:last-child td {
                border-bottom: none;
            }

            .f-label {
                width: 19mm;
                font-size: 5.5px;
                font-weight: bold;
                letter-spacing: 1px;
                text-transform: uppercase;
                color: #8f6720;
            }

            .f-value {
                font-size: 9px;
                color: #0e1a2b;
                padding-left: 2mm;
            }

            .verify-footer {
                position: absolute;
                left: 3.6mm;
                right: 3.6mm;
                bottom: 3mm;
                font-size: 4.6px;
                color: #6e5f4a;
            }
        </style>
    </head>
    <body>
        {{-- Recto --}}
        <div class="card">
            <div class="bg"></div>
            <div class="wave wave-1"></div>
            <div class="wave wave-2"></div>
            <div class="wave wave-3"></div>

            <div class="content">
                <table class="header-table">
                    <tr>
                        <td class="logo-cell">
                            <img src="{{ public_path('images/logo-alsar.png') }}" />
                        </td>
                        <td class="brand-cell">
                            <p class="party-name">ALLIANCE POUR LA SYNERGIE DES ACTIONS RÉPUBLICAINES</p>
                            <p class="tagline">Action &bull; Engagement &bull; Solidarité</p>
                        </td>
                    </tr>
                </table>

                <div class="rule"></div>

                <table class="body-table">
                    <tr>
                        <td class="photo-cell">
                            <div class="photo-frame">
                                @if ($photo)
                                    <img src="{{ $photo }}" alt="" />
                                @endif
                            </div>
                        </td>
                        <td class="info-cell">
                            <p class="eyebrow">Carte de membre</p>
                            <p class="member-name">
                                {{ $member->first_name }}<br />{{ mb_strtoupper($member->last_name) }}
                            </p>
                            <p class="member-code">N&deg; {{ $member->membership_code }}</p>
                        </td>
                        <td class="qr-cell">
                            <div class="qr-frame">
                                <img src="{{ $qrCode }}" alt="QR de vérification" />
                            </div>
                            <p class="qr-caption">Scanner pour vérifier</p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- Verso --}}
        <div class="card">
            <div class="bg"></div>
            <div class="wave wave-back-1"></div>
            <div class="wave wave-back-2"></div>

            <div class="content">
                <table class="back-header-table">
                    <tr>
                        <td class="logo-cell">
                            <img src="{{ public_path('images/logo-alsar.png') }}" />
                        </td>
                        <td class="movement-name">Parti politique sénégalais</td>
                    </tr>
                </table>

                <table class="fields-table">
                    <tr>
                        <td class="f-label">Nom</td>
                        <td class="f-value">{{ $member->last_name }}</td>
                    </tr>
                    <tr>
                        <td class="f-label">Prénom</td>
                        <td class="f-value">{{ $member->first_name }}</td>
                    </tr>
                    <tr>
                        <td class="f-label">Profession</td>
                        <td class="f-value">{{ $member->profession ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="f-label">Téléphone</td>
                        <td class="f-value">{{ $member->phone }}</td>
                    </tr>
                    <tr>
                        <td class="f-label">Adresse</td>
                        <td class="f-value">{{ $member->address ?? '—' }}</td>
                    </tr>
                    @if ($member->id_document_number)
                        <tr>
                            <td class="f-label">N&deg; pièce</td>
                            <td class="f-value">{{ $member->id_document_number }}</td>
                        </tr>
                    @endif
                </table>

                {{-- Domaine public fixe (pas route()/APP_URL) : la carte doit toujours afficher
                     le vrai domaine de production, pas l'hôte de l'environnement où elle est
                     générée (local, staging...). --}}
                <p class="verify-footer">Vérifiable sur alsar.sn/verifier/{{ $member->membership_code }}</p>
            </div>
        </div>
    </body>
</html>
