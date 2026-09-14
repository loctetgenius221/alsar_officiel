# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

> Les sections numérotées 0 à 14 ci-dessous sont le **brief produit/design** (la cible à atteindre). L'avant-propos qui suit décrit l'**état réel du code** (as-built) : en cas de contradiction entre le brief et le code, le code fait foi pour ce qui existe déjà, le brief fait foi pour ce qui reste à construire. En cas de doute sur une décision : sobriété premium > effets gratuits, sécurité > rapidité, cohérence du design system > improvisation page par page.

---

## Avant-propos — état du code & commandes (as-built)

### A. Commandes

```bash
composer setup          # install + .env + key:generate + migrate + npm install + build
composer dev            # serve + queue:listen + pail (logs) + vite, en parallèle (concurrently)
composer test           # config:clear puis artisan test (Pest)

php artisan test                                   # toute la suite PHP
php artisan test tests/Feature/AdhesionTest.php    # un fichier
php artisan test --filter="peut adherer"           # un test par son nom
php artisan migrate:fresh --seed                   # reset DB + rôles + compte admin
php artisan ziggy:generate                         # REGÉNÉRER resources/js/ziggy.js après toute modif de routes

npm run dev             # Vite (le site n'a PAS de serveur Node à part : Laravel sert les pages)
npm run build           # vue-tsc -b && vite build
npm run type-check      # vue-tsc -b seul
npm run lint            # eslint --fix
npm run format          # prettier --write resources/js
npm run test            # vitest run (specs = resources/js/**/*.spec.ts uniquement)
npx vitest run resources/js/lib/utils.spec.ts      # un spec front
vendor/bin/pint         # formatage PHP
```

Environnement local : **Laragon** (Apache + MySQL), base `alsar`, `APP_URL` pointe sur un tunnel **ngrok** (d'où `trustProxies('*')` dans `bootstrap/app.php` — ne pas retirer). `MAIL_MAILER=log` : les emails transactionnels partent dans `storage/logs`, pas en vrai.

Tests : Pest, `RefreshDatabase` appliqué à tout `tests/Feature` (`tests/Pest.php`), base **sqlite en mémoire** (`phpunit.xml`) — donc jamais la base MySQL de dev.

### B. Écarts entre le brief et le code réel

| Brief (§2) | Réalité du dépôt |
|---|---|
| Laravel 12 / Inertia v2 | **Laravel 13**, `inertiajs/inertia-laravel` v3, `@inertiajs/vue3` v3 |
| PostgreSQL préféré | **MySQL** (`DB_CONNECTION=mysql`) |
| `lucide-vue-next` | **`@lucide/vue`** (nouveau package) |
| `spatie/laravel-pdf` | **`barryvdh/laravel-dompdf`** + `endroid/qr-code` |
| Zod (dernier) | **zod v3 épinglé** (`@vee-validate/zod` pas compatible v4) |
| medialibrary, Scout, Turnstile, Sentry, backup, Playwright | **pas installés** (phases ultérieures). Anti-bot actuel = champ **honeypot** `website` dans `StoreMemberRequest` |
| Entités Region/Department/Commune | **pas de tables** : `region` et `commune` sont des colonnes texte sur `members`, la liste des 14 régions vit dans `config/adhesion.php` **et** `resources/js/lib/senegal-regions.ts` (à garder synchronisées) |
| CMS actualités/médiathèque | **pas de CMS** : le contenu éditorial est en dur dans `resources/js/lib/articles.ts` ; les pages non livrées affichent `Components/ui/ComingSoon.vue` plutôt que du faux contenu |
| Rôles Éditeur/Trésorier/CM | **un seul rôle**, `Super-admin` (`RolesSeeder`), exigé par toutes les routes `/admin` |
| `docs/ALSAR_Audit_et_Dossier_Complet.docx` | le .docx est à la **racine** ; `docs/` ne contient que `design-system.md` |

Fichiers `vite.config.js` / `vitest.config.js` / `*.d.ts` à la racine : **artefacts générés** par `vue-tsc -b`, ne pas les éditer — la source est le `.ts`.

### C. Architecture

**Monolithe Laravel + Inertia**, un seul build Vite pour la vitrine et l'admin.

- **Routing** : `routes/web.php` (vitrine, public) et `routes/admin.php` (préfixe `/admin`, noms `admin.*`), ce dernier étant monté par le callback `then:` de `bootstrap/app.php`. Comme les routes admin sont nommées `admin.login`, `bootstrap/app.php` redéfinit explicitement `redirectGuestsTo`/`redirectUsersTo` et enregistre à la main les alias `role`/`permission` de spatie (Laravel 11+ ne le fait plus).
- **Pages vitrine statiques** : rendues par des closures `Inertia::render('Site/Xxx')` dans `web.php`, sans contrôleur — n'en créer un que quand il y a de la donnée serveur.
- **Résolution des pages** : `resources/js/app.ts` fait `import.meta.glob('./Pages/**/*.vue')` ; toute page doit donc exister sous `Pages/Site/` ou `Pages/Admin/` avec le nom exact passé à `Inertia::render`.
- **Props partagées** (`HandleInertiaRequests`) : `auth.user`, `auth.roles`, `flash.success`, `flash.error`.
- **Domaine métier** : `app/Domain/{Members,Payments}/` (Models + Actions), pas `app/Models/` — sauf `User`. Les modèles hors `App\Models` doivent déclarer `newFactory()` explicitement (Laravel ne devine pas la factory).
- **Actions** : une classe = une opération métier (`RegisterMember`, `ConfirmMembershipPayment`, `BuildMembershipQrCode`, `ToggleMemberStatus`, `ResendMembershipCard`), injectées dans les contrôleurs.

### D. Parcours d'adhésion (le cœur du produit)

Front : wizard 3 étapes (`Components/adhesion/Step*.vue`) piloté par le store Pinia `stores/adhesion.ts`, persisté en `localStorage` via VueUse `useStorage`, validé par les schémas zod de `lib/adhesion-schemas.ts`.

Back : `StoreMemberRequest` → `RegisterMember` (crée `Member` en `pending` + `MembershipPayment` en `pending`) → `SimulationGateway::charge()` → `ConfirmMembershipPayment` → membre `active` + email + carte PDF/QR.

Règles à ne jamais casser :
- **Le montant ne vient jamais du client** : la requête ne porte que la clé de palier (`tier`), le montant est relu dans `config('adhesion.tiers')` côté serveur.
- **`ConfirmMembershipPayment` est idempotente** (sort immédiatement si `status === 'success'`) — c'est le point d'entrée commun du gateway simulé et du futur vrai webhook.
- Toute nouvelle passerelle s'ajoute en implémentant `PaymentGatewayInterface` (`charge` + `handleWebhook` avec vérification de signature), sans toucher au contrôleur. `SimulationGateway` confirme toujours en succès et est **à remplacer avant toute mise en production**.
- Validation en double, front (zod) **et** back (Form Request) : le format du téléphone sénégalais existe aux deux endroits, les garder alignés.
- Les routes publiques d'écriture sont throttlées (`throttle:5,1` sur l'adhésion, `10,1` sur la vérification d'email) ; `id_document_number` est casté `encrypted`, `Member` est en `SoftDeletes`.

### E. Frontend — conventions

- Tokens de design : bloc `@theme` de `resources/css/app.css` (Tailwind v4 CSS-first). **Aucune couleur/taille en dur** dans les composants ; détail des tokens dans `docs/design-system.md`.
- Polices auto-hébergées via Fontsource (Fraunces + Public Sans), aucune requête tierce.
- Motion : GSAP + ScrollTrigger enregistrés une seule fois dans `app.ts`, Lenis via `useLenis`. Toute animation passe par un composable de `Composables/` (`useGsapReveal`, `useCountUp`, `useParallax`, `useScrollReveal`) et **doit** court-circuiter sur `useReducedMotion()`.
- `cn()` (`lib/utils.ts`, clsx + tailwind-merge) pour composer les classes conditionnelles.
- Routes côté JS via Ziggy (`route('adhesion.create')`, global déclaré dans ESLint) — d'où l'obligation de regénérer `ziggy.js`.
- Prettier : 4 espaces, quotes simples, largeur 110, tri des classes Tailwind automatique. ESLint impose `<script lang="ts">` dans tous les `.vue`.

---

## 0. Résumé en une phrase

Un site vitrine + plateforme d'adhésion pour un parti politique sénégalais (ALSAR), construit en **Laravel + Inertia.js + Vue 3 + TypeScript + Tailwind CSS + GSAP**, avec formulaire d'adhésion en ligne, paiement (mobile money + carte bancaire), et back-office complet — le tout pensé pour être réutilisable comme **thème premium** (whitelabel) pour d'autres partis/organisations.

---

## 1. Vision & contexte

- **Client** : ALSAR (And Liggey Sénégal ak Racine), parti politique sénégalais dirigé par Mamadou Racine Sy. Voir `docs/ALSAR_Audit_et_Dossier_Complet.docx` pour le contenu de fond (histoire, programme, biographie, ton éditorial).
- **Objectif produit** : un site qui **inspire confiance** dès la première seconde (comme un site institutionnel, une banque premium ou un cabinet d'avocats haut de gamme) — pas un site de campagne « bricolé WordPress », pas un template générique visiblement fait par une IA.
- **Double public** :
  1. Grand public / électeurs / militants → partie vitrine.
  2. Adhérents qui s'inscrivent et paient une cotisation en ligne → parcours d'adhésion.
  3. Équipe ALSAR (community manager, trésorier, secrétariat) → back-office.
- **Contrainte forte de design** : rien ne doit "sentir" le générique IA. Voir section 3 : c'est la partie la plus critique de ce document, à relire avant toute implémentation UI.

---

## 2. Stack technique imposée

### 2.1 Architecture globale

**Monolithe Laravel + Inertia.js**, pas de SPA découplée avec API REST séparée (sauf si une app mobile est envisagée plus tard — dans ce cas, exposer une API Sanctum en plus, sans changer l'archi web).

Pourquoi Inertia : un seul repo, une seule stack Vue pour la vitrine ET le back-office, auth Laravel classique (sessions, pas de JWT à gérer côté front), routing géré côté Laravel, SEO plus simple à maîtriser (SSR possible via `@inertiajs/vue3` + `laravel-vite-plugin` + Node SSR).

| Couche | Choix | Notes |
|---|---|---|
| Backend | **Laravel 12** (PHP 8.3+) | Dernière version stable |
| Pont back/front | **Inertia.js v2** (adapter Vue 3) | Pas d'API REST à maintenir en double |
| Frontend | **Vue 3** (Composition API + `<script setup>`) | TypeScript obligatoire |
| Styles | **Tailwind CSS v4** (config CSS-first `@theme`) | Design tokens custom, voir section 3 |
| Animation | **GSAP 3** + `ScrollTrigger` + **Lenis** (smooth scroll) | Cf. 3.5 |
| Composants headless | **Reka UI** (ex radix-vue) + composants custom au-dessus | Jamais de composants Tailwind UI non modifiés tels quels |
| Icônes | **Lucide (lucide-vue-next)** | Cohérent, pas de mélange de styles d'icônes |
| State/utils front | **VueUse**, Pinia si besoin d'un store global (panier de dons, wizard d'adhésion multi-étapes) | |
| Formulaires | **vee-validate** + **zod** (schémas partagés front/back autant que possible) | |
| Base de données | **PostgreSQL** (préféré) ou MySQL 8 | |
| Admin back-office | Vue/Inertia custom (voir 7), pas de Filament/Nova — on garde 100% Vue pour la cohérence du thème revendable | |
| Rôles & permissions | **spatie/laravel-permission** | |
| Media | **spatie/laravel-medialibrary** | Conversions d'images automatiques (thumbnails, WebP) |
| PDF | **spatie/laravel-pdf** ou **dompdf** | Génération carte de membre, reçu de paiement, programme téléchargeable |
| Recherche | **Laravel Scout** + driver Meilisearch (actualités, programme) | Optionnel phase 2 |
| File d'attente | **Laravel Queue** (DB ou Redis) | Envoi d'emails, génération PDF, webhooks paiement |
| Tests | **Pest** (backend), **Vitest** + **Vue Test Utils** (frontend), **Playwright** (E2E parcours adhésion/paiement) | |
| CI/CD | GitHub Actions (lint, tests, build) | |
| Hébergement cible | VPS (Forge/Ploi) ou équivalent, avec Redis + queue worker | |

### 2.2 Paiement en ligne (spécifique contexte sénégalais)

Le public cible paie majoritairement via **mobile money**. Prévoir une **abstraction `PaymentGatewayInterface`** dans Laravel pour ne pas coupler le code à un seul prestataire.

| Prestataire | Type | Pourquoi |
|---|---|---|
| **PayTech.sn** ou **CinetPay** | Agrégateur (Orange Money, Wave, Free Money, Wizall + cartes Visa/Mastercard) | Choix principal, couverture Sénégal quasi complète |
| **Wave Business API** | Direct | Alternative/complément si volumes Wave importants (frais plus bas) |
| **Stripe** | Carte bancaire internationale | Pour la diaspora (dons/cotisations en EUR/USD) |

Le back-end doit gérer : création de transaction → redirection/checkout → **callback/webhook signé** → mise à jour idempotente du statut → notification (email + éventuellement SMS) → génération du reçu PDF. Ne jamais valider un paiement uniquement côté redirection navigateur : **le webhook fait foi**.

### 2.3 Décisions à ne pas remettre en question sans raison

- Pas de jQuery, pas de Bootstrap.
- Pas de builder no-code (Elementor etc.) — le site précédent en souffrait déjà (voir audit).
- Mobile-first obligatoire (la majorité du trafic sénégalais est mobile).
- Accessibilité WCAG AA minimum (contrastes, focus visibles, navigation clavier).

---

## 3. Direction artistique — le design ne doit PAS ressembler à du "généré par IA"

C'est le point le plus important de ce brief. Un rendu "IA générique" se reconnaît à : dégradés violet→bleu/rose, glassmorphism partout, cartes en `rounded-2xl` avec ombre portée identique partout, illustrations 3D blob génériques, polices Inter/Poppins par défaut sans hiérarchie, tout centré, boutons pilule fluo, icônes mélangées, sections toutes identiques (icône + titre + texte, x3, en boucle).

**On fait l'inverse : un design éditorial, sobre, avec du caractère et de l'asymétrie maîtrisée — inspiré de sites institutionnels premium, cabinets d'avocats, banques privées, fondations, et de références Awwwards côté typographie/motion (sans jamais copier un site en particulier).**

### 3.1 Palette de couleurs

Reprendre et raffiner l'identité ALSAR (navy + or) avec une palette plus riche et moins "corporate low-cost" :

- **Fond principal** : blanc cassé chaud, jamais blanc pur (`#FAF7F1` ou proche) — donne un rendu "papier premium".
- **Encre / texte** : bleu-nuit très profond, presque noir (`#0E1A2B`), pas un bleu vif.
- **Accent primaire (or/bronze)** : `#B8863A` à `#C9962C` — utilisé avec parcimonie (CTA, soulignages, détails), jamais en grand aplat.
- **Accent secondaire (terre/terracotta)** : une teinte chaude type terre de Podor (`#A8481F` desaturé) pour varier les CTA secondaires et éviter le "tout doré".
- **Neutres** : une échelle de gris chauds (pas de gris froid bleuté générique).
- Interdiction : dégradés arc-en-ciel, glow néon, glassmorphism comme élément principal.

### 3.2 Typographie — le levier n°1 de crédibilité

- **Police d'affichage (titres, grands chiffres)** : une serif éditoriale avec du caractère — ex. **Fraunces**, **Libre Caslon Display**, ou **Canela**-like (via Fontshare/Google Fonts, licence vérifiée). Donne un ton "institution sérieuse", pas "startup".
- **Police texte courant** : une sans-serif humaniste et non générique — ex. **General Sans**, **Satoshi** ou **Inter** *seulement* si retravaillée avec un tracking/hiérarchie soignés (éviter Inter par défaut sans travail dessus).
- Hiérarchie stricte : jamais plus de 4 tailles de titres, échelle typographique modulaire (ratio ~1.25–1.333), line-height généreux sur le corps de texte (1.6–1.7).
- Chiffres clés (ex. "10 ans d'engagement", "44 communes touchées") en grande serif display, avec animation de comptage GSAP.

### 3.3 Grille & composition

- Grille éditoriale **asymétrique** (colonnes inégales, marges variables), pas tout centré en conteneur 1200px.
- Alterner pleine largeur (photos documentaires réelles, pas de stock générique) et colonnes de texte étroites façon magazine.
- Espacement généreux (whitespace comme signal de premium), pas de sections compressées.
- Un motif graphique discret inspiré de motifs textiles sénégalais (bogolan/wax stylisé, en trait fin, jamais figuratif ni kitsch) utilisable en filigrane sur fonds de section ou séparateurs — **à valider avec le client avant usage extensif**.

### 3.4 Photographie & imagerie

- Uniquement des photos réelles du parti/président/terrain (déjà disponibles sur alsar.sn + à collecter en HD), traitées avec un grain léger et une colorimétrie cohérente (LUT unique appliquée à toutes les images).
- Aucune photo stock "hommes d'affaires souriants génériques".
- Portraits recadrés de façon éditoriale (cadrages serrés, asymétriques), pas de vignettes rondes identiques partout.

### 3.5 Motion design (GSAP)

- **Lenis** pour le smooth scroll global.
- **GSAP ScrollTrigger** pour : reveal de texte au scroll (découpage ligne par ligne via **SplitType**, alternative libre à SplitText), parallax subtil sur images (pas d'excès), timeline de la frise chronologique (section Histoire), compteurs animés (chiffres clés), transitions de page douces via Inertia (`router.on('start'/'finish')` + timeline GSAP).
- Micro-interactions : underline animée sur les liens, magnetic buttons légers sur les CTA principaux, curseur custom discret sur desktop uniquement (pas obligatoire, "nice to have").
- Règle d'or : le motion appuie la lecture, il ne doit jamais retarder l'accès à l'information (durées courtes, easing naturel type `power2.out`, `prefers-reduced-motion` toujours respecté).

### 3.6 Inspirations (esprit, pas copie)

Sites de fondations/institutions/cabinets premium, portfolios d'agences Awwwards orientés édito/typographie, sites de banques privées ou d'universités haut de gamme. Objectif : un visiteur doit se dire *"c'est du sur-mesure, ça a coûté cher"*, jamais *"ça sort d'un générateur"*.

---

## 4. Architecture de l'information — sitemap complet

**Nombre de pages recommandé : 22 à 26 gabarits de pages** (certains gabarits génèrent plusieurs pages, ex. articles, membres de l'organigramme).

| # | Page | Type | Sections principales |
|---|---|---|---|
| 1 | **Accueil** | Statique riche | Hero (vidéo/photo + accroche + CTA adhésion), bandeau chiffres clés animés, bloc "Le Président", 3 piliers du programme, actualités à la une (3), carte des régions teaser, bloc "Rejoignez-nous" avec CTA fort, bloc presse/logos médias |
| 2 | **Le Parti — Vision & Histoire** | Statique | Vision, positionnement, frise chronologique interactive (GSAP), valeurs |
| 3 | **Le Parti — Organisation** | Statique + data dynamique | Organigramme (bureau exécutif), sections régionales, mouvement des jeunes, mouvement des femmes |
| 4 | **Le Parti — Statuts & Transparence** | Statique + docs | Statuts (PDF), rapports d'activité, mentions légales |
| 5 | **Le Président — Biographie** | Statique riche | Portrait, frise parcours entrepreneurial/politique, citations |
| 6 | **Le Président — Discours & Interventions** | Liste + détail | Vidéos, transcriptions, revue de presse |
| 7 | **Programme — Vue d'ensemble** | Statique | Les 6 axes, téléchargement PDF global |
| 8–13 | **Programme — page par axe** (6 pages) | Statique | Économie/emploi, Agriculture, Infrastructures, Foncier, Femmes/Famille, Gouvernance — chaque page : constat, engagements, indicateurs de suivi |
| 14 | **Actualités — Liste** | Liste filtrable | Filtres par catégorie (Vie du parti / Action à Podor / Vie nationale / Solidarité), pagination, recherche |
| 15 | **Actualité — Détail** | Détail (CMS) | Article riche, partage social, articles liés |
| 16 | **Médiathèque** | Galerie | Photos (albums), vidéos, podcasts, kit presse téléchargeable |
| 17 | **Régions / Implantations** | Carte interactive | Carte du Sénégal (SVG ou Mapbox), sections locales, contact par région |
| 18 | **Rejoignez-nous — Adhésion** | Formulaire multi-étapes + paiement | Voir section 6 |
| 19 | **Faire un don** | Formulaire + paiement | Montant libre/prédéfini, récurrent ou ponctuel |
| 20 | **Espace Adhérent** (login) | Dashboard membre | Carte de membre numérique (QR code), historique cotisations, mise à jour profil |
| 21 | **Contact & Presse** | Formulaire | Contact général + contact presse dédié, coordonnées, réseaux sociaux |
| 22 | **Recherche globale** | Résultats | Recherche transverse (actus, programme, pages) |
| 23 | **Mentions légales / CGU / Politique de confidentialité / CGV paiement** | Statique légal | Obligatoire pour la collecte de données + paiement en ligne |
| 24 | **404 / Erreur** | Utilitaire | Design cohérent, liens de retour |
| — | **Back-office** (voir section 7) | ~10-12 écrans | Non indexé, `/admin` |

**Multilingue** : structure i18n dès le départ (Laravel `lang/` + clés Vue), même si seul le français est livré en v1 — prévoir wolof en v2 sur les pages 1, 7, 18.

---

## 5. Formulaire d'adhésion & paiement — flow détaillé

Wizard en **3 à 4 étapes** (composant Vue avec Pinia store pour l'état, validation zod à chaque étape) :

1. **Identité** : nom, prénom, date de naissance, sexe, téléphone (format sénégalais validé), email, région/département/commune (select hiérarchique), profession.
2. **Engagement** : section souhaitée (jeunes/femmes/aucune), commune de rattachement militant, comment avez-vous connu ALSAR (analytics utile), acceptation des statuts + politique de confidentialité (case à cocher obligatoire, horodatée en base — conformité loi sénégalaise sur la protection des données personnelles, cf. section 9).
3. **Cotisation** : montant (fixe ou paliers : standard / soutien / bienfaiteur), choix du mode de paiement (Orange Money / Wave / Free Money / Carte bancaire via l'agrégateur choisi).
4. **Paiement & confirmation** : redirection vers le checkout du prestataire → retour sur une page de statut → email de confirmation avec reçu PDF + (si succès) génération automatique de la **carte de membre numérique avec QR code** vérifiable publiquement (page `/verifier/{code}`).

Points UX critiques :
- Sauvegarde de progression (si l'utilisateur quitte à l'étape 3, ne pas tout perdre — `localStorage` ou session serveur).
- Gestion explicite des états : paiement en attente / échoué / réussi (jamais d'ambiguïté à l'écran).
- Auto-formatage du numéro de téléphone, sélection du pays par défaut Sénégal (+221) mais ouvert à la diaspora.
- Un adhérent doit pouvoir **rejoindre sans payer immédiatement** si le parti le souhaite (paramétrable) — mais le cas nominal est adhésion + cotisation en un seul geste.

---

## 6. Back-office (admin) — écrans et fonctionnalités

Construit avec le **même stack Inertia/Vue/Tailwind** que la vitrine (design différent, plus dense, mais même design system de base) — accessible via `/admin`, protégé par auth + rôles.

| Écran | Fonctionnalités clés |
|---|---|
| **Dashboard** | KPIs : nouveaux adhérents (période), montant collecté, taux de conversion du formulaire, dernières transactions, carte de répartition géographique des adhérents |
| **Adhérents (liste)** | Recherche, filtres (région, statut de paiement, section, date d'adhésion), export CSV/Excel, vue détail par adhérent, actions (relancer paiement, générer carte, désactiver) |
| **Fiche Adhérent** | Toutes les infos, historique des paiements, note interne, réenvoi de la carte de membre par email |
| **Paiements / Transactions** | Historique complet, statut, prestataire, réconciliation, remboursements |
| **Actualités (CMS)** | CRUD articles, éditeur riche (Tiptap recommandé, plus léger et mieux intégré Vue que TinyMCE), catégories, programmation de publication, SEO par article (title/description/OG image) |
| **Médiathèque** | Upload (drag&drop), albums, gestion vidéos (embed YouTube/Vimeo ou upload), génération auto de kit presse (zip) |
| **Pages statiques / Programme** | Édition des blocs de contenu des pages "Le Parti", "Programme" (par axe), sans devoir toucher au code — via un éditeur de blocs simple (pas un page-builder complexe, juste des champs structurés par section) |
| **Organisation / Organigramme** | Gestion des membres du bureau exécutif, sections régionales, responsables locaux |
| **Messages de contact** | Boîte de réception des formulaires contact/presse, statut traité/non traité |
| **Newsletter** | Liste des abonnés, export, intégration éventuelle avec un ESP (Mailchimp/Brevo) |
| **Utilisateurs & rôles admin** | Super-admin, Éditeur (actus/médiathèque), Trésorier (paiements/adhérents), Community manager — via spatie/laravel-permission |
| **Paramètres du site** | Coordonnées, réseaux sociaux, clés API paiement (chiffrées), textes légaux, mode maintenance |
| **Journal d'activité (audit log)** | Qui a fait quoi et quand (spatie/laravel-activitylog) — important pour un site politique sensible |

---

## 7. Modèle de données (entités principales)

```
User (admin)            id, name, email, password, role(s) [spatie]
Member (adhérent)       id, first_name, last_name, birth_date, gender, phone, email,
                        region_id, department_id, commune_id, profession, section (enum jeunes|femmes|standard),
                        status (pending|active|inactive), joined_at, referral_source, consent_at
Membership_Payment      id, member_id, amount, currency, gateway, gateway_reference,
                        status (pending|success|failed|refunded), tier, paid_at, receipt_path
Donation                id, donor_name, email, phone, amount, currency, gateway, status, is_recurring
Region / Department / Commune   hiérarchie territoriale sénégalaise (référentiel)
Section                id, name (ex. "ALSAR Sagatta"), region_id, leader_name, description
Article (actualité)    id, title, slug, excerpt, body, category_id, cover_image, published_at, author_id, seo_*
Category               id, name, slug
MediaAlbum / MediaItem id, type (photo|video|podcast), file/url, caption, album_id
Page (bloc CMS)        id, slug, sections (JSON structuré), seo_*
OrgMember (organigramme) id, name, role_title, photo, region_id (nullable), order
ContactMessage         id, name, email, subject, message, type (general|presse), handled_at
NewsletterSubscriber   id, email, subscribed_at
Setting                key, value (JSON) — config générale du site
ActivityLog            (via package) — audit trail
```

Points d'attention :
- Champ `consent_at` obligatoire (horodatage du consentement RGPD-like) sur `Member`.
- `Membership_Payment` doit être **idempotent** vis-à-vis des webhooks (clé unique sur `gateway_reference`).
- Prévoir un **soft delete** partout où des données personnelles sont stockées (traçabilité + droit à l'oubli géré manuellement).

---

## 8. Sécurité & conformité

- Chiffrement au repos des données sensibles (numéro de pièce d'identité si collecté).
- Respect de la **loi sénégalaise sur la protection des données à caractère personnel** (déclaration/autorisation CDP à prévoir côté client, pas côté code, mais le code doit permettre l'export/suppression des données d'un adhérent sur demande).
- Rate limiting strict sur : formulaire d'adhésion, formulaire de contact, endpoints de paiement (Laravel `throttle`).
- Vérification de signature sur **tous** les webhooks de paiement (ne jamais faire confiance à un simple retour de redirection).
- 2FA recommandé pour les comptes admin (rôle Super-admin et Trésorier au minimum).
- Sauvegardes automatiques (DB + fichiers médias) — `spatie/laravel-backup`.
- Headers de sécurité (CSP, HSTS), captcha/anti-bot (Cloudflare Turnstile de préférence à reCAPTCHA) sur les formulaires publics.

---

## 9. Idées UX différenciantes (au-delà du strict nécessaire)

- **Frise chronologique interactive** du parti et du président, scrollable horizontalement ou en scroll-driven vertical (GSAP ScrollTrigger + pin de section).
- **Carte du Sénégal interactive** (SVG des régions) sur la page "Régions" : survol = nombre d'adhérents/actions dans la zone, clic = détail de la section locale.
- **Compteur d'impact en temps réel** sur l'accueil (nombre d'adhérents, régions couvertes) — alimenté par une vraie requête, pas un chiffre statique figé.
- **Carte de membre numérique** avec QR code vérifiable publiquement (`/verifier/{code}`) — utile en meeting/événement pour contrôler les adhésions, et donne un sentiment de sérieux/officialité.
- **Mode "revue de presse"** : agrégation automatique (ou saisie manuelle) des articles de presse externes mentionnant le parti, avec vignettes des logos médias — renforce la crédibilité perçue.
- **Générateur de reçu/attestation d'adhésion en PDF** téléchargeable depuis l'espace adhérent.
- **Partage social natif** sur chaque article avec image Open Graph générée dynamiquement (titre + photo).
- **Formulaire d'adhésion en mode "progressive disclosure"** : on ne montre que 3-4 champs à la fois, jamais un formulaire de 20 champs d'un coup — réduit drastiquement l'abandon.
- **Accessibilité réelle** : mode contraste élevé, respect `prefers-reduced-motion`, navigation clavier testée sur le wizard d'adhésion en particulier.
- **Vitesse perçue** : squelettes de chargement (skeletons) plutôt que spinners génériques, préchargement des routes Inertia probables (survol de lien = prefetch).

---

## 10. Structure de dossiers (proposition)

```
app/
  Domain/
    Members/        (Models, Actions, Services)
    Payments/        (Gateways/, PaymentGatewayInterface, Actions)
    Content/         (Article, Page, MediaAlbum...)
    Organization/     (Region, Section, OrgMember)
  Http/
    Controllers/
      Site/          (contrôleurs vitrine)
      Admin/          (contrôleurs back-office)
    Requests/
    Middleware/
resources/
  js/
    Pages/
      Site/           (Home.vue, Programme/*.vue, Actualites/*.vue, ...)
      Admin/          (Dashboard.vue, Members/Index.vue, ...)
    Components/
      ui/             (composants de base : Button, Input, Card, Modal — Reka UI + Tailwind)
      site/           (Hero, Timeline, ImpactCounter, MembershipWizard, ...)
      admin/
    Composables/       (useGsapReveal, usePaymentStatus, ...)
    lib/                (validation zod schemas partagés, formatters)
  css/
    app.css            (@theme Tailwind v4, tokens de design)
routes/
  web.php
  admin.php
docs/
  CLAUDE.md            (ce fichier)
  ALSAR_Audit_et_Dossier_Complet.docx
  design-system.md     (à générer en phase 0, cf. section 11)
```

---

## 11. Plan de développement par phases

1. **Phase 0 — Fondations & design system** : setup Laravel+Inertia+Vue+Tailwind v4, tokens de design (couleurs/typo/spacing) validés sur 2-3 pages statiques (Accueil, Programme) avant de coder le reste. **Ne pas avancer sur les autres pages tant que la direction artistique n'est pas validée sur l'Accueil.**
2. **Phase 1 — Vitrine statique complète** : toutes les pages de contenu (sitemap section 4), CMS pour actualités/programme/organigramme.
3. **Phase 2 — Adhésion & paiement** : wizard, intégration passerelle(s), webhooks, génération carte/reçu, emails transactionnels.
4. **Phase 3 — Back-office complet** : dashboard, gestion adhérents/paiements/contenus, rôles.
5. **Phase 4 — Polish** : motion design GSAP avancé, performance (Lighthouse ≥ 90 partout), SEO technique, accessibilité, tests E2E du parcours adhésion.
6. **Phase 5 — Durcissement thème revendable** : variables de marque centralisées (logo, couleurs, textes) pour permettre un ré-habillage rapide du thème pour un autre client.

---

## 12. Definition of Done (par page/fonctionnalité)

- [ ] Responsive testé sur mobile (360px), tablette, desktop, grand écran.
- [ ] Aucune animation ne bloque la lecture du contenu ; `prefers-reduced-motion` respecté.
- [ ] Lighthouse Performance/Accessibilité/SEO ≥ 90.
- [ ] Formulaires : validation front (zod) + back (Form Requests Laravel) alignées.
- [ ] Paiement : testé en sandbox pour succès, échec, et abandon.
- [ ] Aucune donnée personnelle affichée en clair dans les logs.
- [ ] Revue design : "est-ce que ça pourrait sortir d'un générateur IA ?" → si oui, retravailler avant merge.

---

## 13. Variables d'environnement à prévoir

```
APP_NAME, APP_URL, APP_LOCALE=fr
DB_* (connexion PostgreSQL/MySQL)
QUEUE_CONNECTION=redis / database
MAIL_* (transactionnel : confirmation adhésion, reçu, contact)
PAYTECH_API_KEY, PAYTECH_API_SECRET   (ou CINETPAY_*)
WAVE_API_KEY (si intégration directe complémentaire)
STRIPE_KEY, STRIPE_SECRET (dons diaspora, optionnel)
TURNSTILE_SITE_KEY, TURNSTILE_SECRET_KEY (anti-bot formulaires)
MEDIA_DISK=s3 (ou local en dev)
SENTRY_DSN (monitoring erreurs, recommandé pour un site politique sensible)
```

---

## 14. Ce que Claude Code doit systématiquement faire

- Relire la **section 3 (direction artistique)** avant d'écrire du HTML/Vue pour une nouvelle page — refuser les patterns "IA générique" listés.
- Utiliser les **tokens Tailwind définis dans `resources/css/app.css`**, ne jamais coder des couleurs/tailles en dur.
- Pour toute nouvelle intégration de paiement ou de donnée personnelle, appliquer la section 8 (sécurité) sans exception.
- Proposer, à chaque nouvelle page vitrine, une variante de mise en page (pas un copier-coller de la page précédente) pour éviter l'effet "sections toutes identiques" typique des sites IA.
