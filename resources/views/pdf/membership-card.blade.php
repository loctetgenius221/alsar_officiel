<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8" />
        <style>
            /*
             * Carte de membre ALSAR — format ISO/IEC 7810 ID-1 (85,60 x 53,98 mm).
             *
             * Refonte v5 : maquette imprimeur (fond gris très clair, vagues jaunes montant vers
             * la droite, titre condensé, photo en bas à gauche, QR dans un cadre jaune), avec les
             * proportions revues à la demande du client — photo, logo, titre d'en-tête, titre
             * « Carte de membre » et numéro réduits ; en-tête du verso centré et identique à
             * celui du recto ; valeurs du verso alignées sur une colonne commune.
             *
             * Choix techniques (contraintes dompdf) :
             * - Les vagues sont des PNG 300 dpi (public/images/card-bg-front.png / -back.png)
             *   posés en plein cadre : dompdf ne rend ni dégradé CSS ni courbe libre, et un
             *   raster à la taille EXACTE de la carte ne peut pas créer de page fantôme.
             * - Tout est en position:absolute à l'intérieur de .card (aucun élément ne dépasse
             *   verticalement la carte).
             * - Police titre : Anton (SIL OFL, public/fonts/Anton-Regular.ttf, licence dans
             *   public/fonts/Anton-OFL.txt). Repli sur DejaVu Sans (embarquée dans dompdf) si le
             *   fichier manque — la carte reste lisible, seulement moins fidèle.
             *   dompdf met en cache les métriques dans storage/fonts : ce dossier DOIT exister et
             *   être inscriptible, sinon la génération échoue.
             * - Photo : cadre à taille fixe + image à hauteur fixe centrée dans un conteneur
             *   très large (marge négative) → recadrage centré sans object-fit ni
             *   background-size, tous deux peu fiables dans dompdf.
             * - Les abscisses (départ des filets, centrage de l'en-tête du verso) sont calées sur
             *   des largeurs de texte MESURÉES via Dompdf\FontMetrics, pas estimées à l'œil.
             */
            @font-face {
                font-family: 'Anton';
                font-style: normal;
                font-weight: normal;
                src: url('{{ public_path('fonts/Anton-Regular.ttf') }}') format('truetype');
            }

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
                font-family: 'DejaVu Sans', sans-serif;
                color: #14233a;
            }

            p {
                margin: 0;
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
                top: 0;
                left: 0;
                width: 85.6mm;
                height: 53.98mm;
            }

            .display {
                font-family: 'Anton', 'DejaVu Sans', sans-serif;
                font-weight: normal;
                text-transform: uppercase;
            }

            /* dompdf calcule la hauteur de ligne sur la boîte englobante de la police (très
               haute pour Anton) et ignore line-height : chaque ligne est donc placée à sa
               propre coordonnée verticale. */
            .line {
                position: absolute;
                white-space: nowrap;
                line-height: 1;
            }

            /* Bloc titre commun au recto et au verso (« reprendre le même titre ») : même
               contenu, mêmes corps, mêmes coordonnées — cf. les règles .header-* plus bas. */
            .party-name {
                font-size: 9.6pt;
                letter-spacing: 0.1pt;
                color: #2b3038;
                text-align: center;
            }

            .tagline {
                text-align: center;
                font-family: Helvetica, Arial, sans-serif;
                font-size: 6.2pt;
                letter-spacing: 0.35pt;
                color: #3a4048;
            }

            /* En-tête, identique et centré sur les deux faces : le groupe logo (12 mm) +
               gouttière (2 mm) + bloc titre (41 mm) fait 55 mm, d'où une marge de
               (85,6 - 55) / 2 = 15,3 mm de chaque côté. Les trois lignes du bloc tiennent dans
               41 mm (largeurs mesurées : 40,61 / 40,58 / 39,50 mm), le centrage est donc exact. */
            .header-logo {
                position: absolute;
                top: 1.8mm;
                left: 15.3mm;
                width: 12mm;
            }

            .header-title {
                left: 29.3mm;
                width: 41mm;
            }

            .header-title.l1 {
                top: 2.2mm;
            }

            .header-title.l2 {
                top: 6.4mm;
            }

            .header-tagline {
                top: 11.6mm;
                left: 29.3mm;
                width: 41mm;
            }

            /* ============ RECTO ============ */

            /* Centrés sur la LARGEUR TOTALE de la carte (et non sur l'espace restant à droite
               de la photo) : `left: 0; right: 0` aligne l'axe du texte sur celui de la carte. */
            .card-title {
                top: 15.2mm;
                left: 0;
                right: 0;
                text-align: center;
                font-size: 16pt;
                letter-spacing: 0.2pt;
                color: #0d0d0d;
            }

            .card-code {
                top: 24.2mm;
                left: 0;
                right: 0;
                text-align: center;
                font-family: Helvetica, Arial, sans-serif;
                font-size: 8.6pt;
                font-weight: bold;
                letter-spacing: 0.3pt;
                color: #0d0d0d;
            }

            /* Photo encadrée comme le QR (liseré jaune + réserve blanche). Le bord BAS reste
               calé sur celui du QR (50 mm) : la réduction se fait donc vers le haut, ce qui
               préserve la respiration en pied de carte et dégage en prime le titre « Carte de
               membre » (1,6 mm entre le cadre et le « C », contre 0,9 mm auparavant).
               Épaisseurs reprises du QR : 1,05 mm de jaune, puis 0,6 mm de blanc.
               Image utile 15,7 x 19,7 mm, soit une proportion portrait proche du 4:5. */
            .photo-yellow {
                position: absolute;
                left: 2.8mm;
                top: 27mm;
                width: 19mm;
                height: 23mm;
                background-color: #f2d62a;
            }

            .photo-white {
                position: absolute;
                left: 3.85mm;
                top: 28.05mm;
                width: 16.9mm;
                height: 20.9mm;
                background-color: #ffffff;
            }

            .photo-frame {
                position: absolute;
                left: 4.45mm;
                top: 28.65mm;
                width: 15.7mm;
                height: 19.7mm;
                overflow: hidden;
            }

            /* Conteneur 80 mm centré sur le cadre : l'image (hauteur fixe, largeur
               proportionnelle) est centrée dedans, le cadre coupe le surplus des deux côtés.
               Marge = -(80 - 15,7) / 2. */
            .photo-strip {
                width: 80mm;
                margin-left: -32.15mm;
                text-align: center;
                line-height: 0;
            }

            .photo-strip img {
                height: 19.7mm;
                width: auto;
            }

            /* QR dans un cadre jaune, en bas à droite (boîtes absolues indépendantes :
               dompdf place mal une boîte imbriquée dans un parent à padding). */
            .qr-yellow {
                position: absolute;
                left: 67.3mm;
                top: 34.5mm;
                width: 15.5mm;
                height: 15.5mm;
                background-color: #f2d62a;
            }

            .qr-white {
                position: absolute;
                left: 68.35mm;
                top: 35.55mm;
                width: 13.4mm;
                height: 13.4mm;
                background-color: #ffffff;
            }

            .qr {
                position: absolute;
                left: 68.95mm;
                top: 36.15mm;
                width: 12.2mm;
                height: 12.2mm;
            }

            /* Petit logo au centre du QR : n'activer ($qrWithLogo) que si le QR est généré
               avec la correction d'erreur H, sinon il devient illisible. */
            .qr-logo-bg {
                position: absolute;
                left: 73.3mm;
                top: 40.5mm;
                width: 3.5mm;
                height: 3.5mm;
                background-color: #ffffff;
            }

            .qr-logo {
                position: absolute;
                left: 73.55mm;
                top: 40.75mm;
                width: 3mm;
            }

            /* ============ VERSO ============ */
            /* Lignes à remplir : 7 rangées. Le filet part juste après son libellé (comme sur la
               maquette) ; les valeurs, elles, démarrent toutes à la même abscisse pour former une
               colonne alignée. */
            .rule {
                position: absolute;
                right: 2.8mm;
                height: 0;
                border-top: 0.32mm solid #14233a;
            }

            .field {
                left: 3mm;
                right: 2.8mm;
                overflow: hidden;
            }

            .f-label {
                position: absolute;
                left: 3mm;
                font-family: Helvetica, Arial, sans-serif;
                font-size: 7.2pt;
                font-weight: bold;
                color: #14233a;
            }

            /* Helvetica comme le libellé (et non DejaVu) : deux polices dans la même ligne
               donnent à dompdf une boîte de ligne bâtarde, et le filet tombait alors au milieu
               du texte au lieu de la ligne de base. Helvetica couvre les accents français. */
            /* Valeurs alignées à droite : toutes se terminent sur la même verticale, au ras
               de la marge. `left: 22mm` (et non 41 mm, après le libellé le plus long) laisse
               60 mm utiles au lieu de 41 — de quoi loger une adresse sénégalaise complète sans
               la tronquer. Seul « NUMÉRO D'IDENTIFICATION : » s'étend jusqu'à 39,4 mm, mais ses
               valeurs sont des numéros courts : aucun recouvrement en pratique. */
            .f-value {
                position: absolute;
                left: 22mm;
                right: 3.4mm;
                text-align: right;
                font-family: Helvetica, Arial, sans-serif;
                font-size: 7.2pt;
                color: #0e1a2b;
                overflow: hidden;
            }
        </style>
    </head>
    <body>
        {{-- ======================= RECTO ======================= --}}
        <div class="card">
            <img class="bg" src="{{ public_path('images/card-bg-front.png') }}" alt="" />

            <img class="header-logo" src="{{ public_path('images/logo-alsar.png') }}" alt="" />

            <p class="line display party-name header-title l1">Alliance pour la synergie des</p>
            <p class="line display party-name header-title l2">Actions républicaines (ALSAR)</p>
            <p class="line tagline header-tagline">ACTION-ENGAGEMENT-SOLIDARITÉ</p>

            <p class="line display card-title">Carte de membre</p>
            <p class="line card-code">N&deg; {{ $member->membership_code }}</p>

            {{-- Les trois boîtes du cadre sont sous le @if : sans photo, un rectangle jaune
                 vide resterait affiché en bas à gauche. --}}
            @if ($photo)
                <div class="photo-yellow"></div>
                <div class="photo-white"></div>
                <div class="photo-frame">
                    <div class="photo-strip"><img src="{{ $photo }}" alt="" /></div>
                </div>
            @endif

            <div class="qr-yellow"></div>
            <div class="qr-white"></div>
            <img class="qr" src="{{ $qrCode }}" alt="QR de vérification" />
            @if ($qrWithLogo ?? false)
                <div class="qr-logo-bg"></div>
                <img class="qr-logo" src="{{ public_path('images/logo-alsar.png') }}" alt="" />
            @endif
        </div>

        {{-- ======================= VERSO ======================= --}}
        <div class="card">
            <img class="bg" src="{{ public_path('images/card-bg-back.png') }}" alt="" />

            <img class="header-logo" src="{{ public_path('images/logo-alsar.png') }}" alt="" />

            <p class="line display party-name header-title l1">Alliance pour la synergie des</p>
            <p class="line display party-name header-title l2">Actions républicaines (ALSAR)</p>
            <p class="line tagline header-tagline">ACTION-ENGAGEMENT-SOLIDARITÉ</p>

            @php
                // Libellé, valeur, et abscisse de départ du filet = 3 mm (marge) + largeur du
                // libellé MESURÉE en Helvetica gras 7,2 pt + 1,2 mm de respiration.
                // `cellule` n'existe pas sur Member : la ligne reste vide, à remplir à la main.
                $rows = [
                    ['NOM :', mb_strtoupper($member->last_name ?? ''), 11.7],
                    ['PRÉNOM :', $member->first_name ?? '', 16.9],
                    ['PROFESSION :', $member->profession ?? '', 22.4],
                    ['CELLULE', $member->cellule ?? '', 15.9],
                    ['ADRESSE :', $member->address ?? '', 18.0],
                    ['TÉLÉPHONE :', $member->phone ?? '', 21.3],
                    ['NUMÉRO D’IDENTIFICATION :', $member->id_document_number ?? '', 40.6],
                ];
                $firstTop = 16.4;
                $pitch = 5.1;
                // Décalage du filet sous le haut de la rangée, calé sur la ligne de base des
                // glyphes. Valeur propre à une ligne 100 % Helvetica 7,2 pt : mélanger deux
                // polices dans la même ligne rendrait la boîte plus haute et décalerait le trait.
                $ruleDrop = 2.5;
            @endphp

            @foreach ($rows as $i => [$label, $value, $ruleLeft])
                @php
                    $top = $firstTop + $i * $pitch;
                    // Les valeurs disposent de 60,2 mm (85,6 - 22 - 3,4). Au-delà,
                    // `overflow: hidden` trancherait net au milieu d'une lettre : on coupe donc
                    // sur la largeur RÉELLE du texte, pas sur un nombre de caractères (cf.
                    // App\Support\PdfText).
                    $value = \App\Support\PdfText::fit($value, 60.2, 7.2);
                @endphp
                <p class="line f-label" style="top: {{ number_format($top, 2) }}mm">{{ $label }}</p>
                <p class="line f-value" style="top: {{ number_format($top, 2) }}mm">{{ $value }}</p>
                <div
                    class="rule"
                    style="top: {{ number_format($top + $ruleDrop, 2) }}mm; left: {{ number_format($ruleLeft, 1) }}mm"
                ></div>
            @endforeach
        </div>
    </body>
</html>
