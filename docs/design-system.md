# Design system ALSAR — Phase 0

Référence technique du design system mis en place lors du bootstrap du projet. Complète (ne remplace
pas) `CLAUDE.md` §3. Toute nouvelle page doit consommer ces tokens plutôt que des valeurs codées en dur.

## Où sont les tokens

`resources/css/app.css` — bloc `@theme` (Tailwind v4, CSS-first). C'est la seule source de vérité.

## Couleurs

| Token | Valeur | Usage |
|---|---|---|
| `bg-paper` / `text-paper` | `#FAF7F1` | Fond principal (jamais blanc pur) |
| `bg-paper-dim` | `#F2EDE2` | Fond de section alterné |
| `bg-ink` / `text-ink` | `#0E1A2B` | Texte principal, fonds sombres |
| `text-ink-soft` | `#26364D` | Texte secondaire sur fond clair |
| `sand-50` → `sand-900` | échelle grise chaude | Neutres, bordures, textes tertiaires |
| `gold-100/300/500/600/700` | base `#C9962C` / `#B8863A` | Accent primaire — **parcimonieux** : CTA, soulignages, détails. Jamais en grand aplat. |
| `terracotta-300/500/600/700` | base `#A8481F` | Accent secondaire — CTA alternatifs, éviter le "tout doré" |
| `success` / `warning` / `danger` | — | États de paiement/formulaire uniquement |

## Typographie

- **Display** (`font-display`) : Fraunces Variable, self-hosted via `@fontsource-variable/fraunces`. Titres,
  chiffres clés. Poids/optical size gérés par la variable font (pas de fichiers statiques par poids).
- **Texte courant** (`font-sans`) : Public Sans Variable, self-hosted via `@fontsource-variable/public-sans`.
  Choisi plutôt qu'Inter/Poppins par défaut : humaniste, institutionnel, non générique — cohérent avec le
  positionnement "banque privée / cabinet premium" de CLAUDE.md §3.6. **Remplaçable** par General Sans ou
  Satoshi (licence Fontshare) si le client fournit les fichiers de police — il n'existe pas de package npm
  self-hosted pour ces deux polices, contrairement à Fraunces/Public Sans qui sont sur Fontsource.
- Aucune requête tierce (Google Fonts CDN, Bunny Fonts) : tout est servi depuis `public/build`, meilleur pour
  la vie privée, la performance et les connexions mobiles sénégalaises instables (CLAUDE.md §2.3).
- Échelle éditoriale à 4 paliers max : `text-display-1` (4.5rem) → `text-display-4` (1.375rem), plus
  `text-numeral` (5.5rem) dédié aux compteurs animés GSAP. Ne pas ajouter un 5e palier de titre.
- Corps de texte : line-height 1.7 (`--text-body--line-height`), à appliquer via `leading-[var(--text-body--line-height)]`.

## Accessibilité

- Focus visible doré (`outline: 2px solid var(--color-gold-500)`) sur tout élément focusable — ne pas
  supprimer les outlines par défaut sans le réintroduire.
- `prefers-reduced-motion: reduce` coupe globalement les animations/transitions CSS (voir `app.css`) et est
  aussi vérifié manuellement dans `useLenis`/`useReducedMotion`/`app.ts` avant toute animation GSAP/Lenis.

## Structure `resources/js/`

```
Pages/Site/      pages publiques (vitrine, adhésion...)
Pages/Admin/      pages back-office
Components/ui/    primitives (Reka UI + Tailwind), sans logique métier
Components/site/  composants vitrine (Hero, Timeline, ImpactCounter...)
Components/admin/ composants back-office
Composables/      logique réutilisable (useLenis, useReducedMotion, à venir : useGsapReveal, usePaymentStatus)
lib/              utilitaires purs (cn(), schémas zod partagés, formatters)
types/            déclarations globales (route(), etc.)
```

`app.ts` résout les pages via `import.meta.glob('./Pages/**/*.vue')` — toute nouvelle page doit donc être
créée sous `Pages/Site/*.vue` ou `Pages/Admin/*.vue` et rendue côté Laravel via `Inertia::render('Site/Xxx')`.

## Ce qui est déjà branché

Inertia.js v2 (Laravel + Vue 3), TypeScript strict, Tailwind v4, GSAP + ScrollTrigger, Lenis (smooth scroll,
désactivé si reduced-motion), SplitType, Reka UI, `@lucide/vue`, VueUse, Pinia, vee-validate + zod (zod épinglé
en v3 tant que `@vee-validate/zod` n'a pas de release compatible v4), Ziggy (routes nommées côté JS), ESLint
(flat config) + Prettier (+ tri des classes Tailwind), Vitest + Vue Test Utils, Pest.

## Ce qui n'est PAS encore installé (phases suivantes, cf. CLAUDE.md §11)

Passerelles de paiement (PayTech/CinetPay/Wave/Stripe), spatie/laravel-permission,
spatie/laravel-medialibrary, spatie/laravel-pdf, spatie/laravel-backup, spatie/laravel-activitylog,
Turnstile, Sentry, Meilisearch/Scout, Playwright. À ajouter au moment de la phase correspondante, pas avant.

## Page de vérification

`resources/js/Pages/Site/Home.vue` est un **scaffold de vérification des tokens** (palette, échelle
typographique, compteur GSAP, boutons), pas la vraie page d'accueil du sitemap. À remplacer par le vrai
gabarit "Accueil" (CLAUDE.md §4, page 1) lors du démarrage de la Phase 1 — ne pas construire les autres
pages vitrine avant validation de la direction artistique dessus (règle de la Phase 0, CLAUDE.md §11).
