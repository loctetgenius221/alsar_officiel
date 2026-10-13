{{--
    Balises de référencement et de partage, rendues par PHP.

    Indispensable : les robots de Facebook, WhatsApp et consorts n'exécutent pas JavaScript —
    des balises posées par `<Head>` côté Vue leur resteraient invisibles. Cf. App\Support\SiteSeo.

    `$seo` est à null sur tout ce qui ne doit pas être indexé (back-office, pages liées à une
    personne) : on émet alors un `noindex` et rien d'autre.
--}}
@if ($seo === null)
    <title inertia>{{ config('app.name', 'ALSAR') }}</title>
    <meta name="robots" content="noindex, nofollow" />
@else
    {{-- L'attribut `inertia` laisse le client reprendre la main sur le titre lors des
         navigations SPA, sans priver les robots du bon titre au premier chargement. --}}
    <title inertia>{{ $seo['title'] }}</title>
    <meta name="description" content="{{ $seo['description'] }}" />

    <meta property="og:type" content="{{ $seo['type'] }}" />
    <meta property="og:site_name" content="{{ \App\Support\SiteSeo::SITE_NAME }}" />
    <meta property="og:locale" content="fr_SN" />
    <meta property="og:title" content="{{ $seo['title'] }}" />
    <meta property="og:description" content="{{ $seo['description'] }}" />
    {{-- URL absolue exigée par Open Graph : les robots ne résolvent pas les chemins relatifs. --}}
    <meta property="og:image" content="{{ url($seo['image']) }}" />
    <meta property="og:url" content="{{ url()->current() }}" />

    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="{{ $seo['title'] }}" />
    <meta name="twitter:description" content="{{ $seo['description'] }}" />
    <meta name="twitter:image" content="{{ url($seo['image']) }}" />

    <link rel="canonical" href="{{ url()->current() }}" />
@endif
