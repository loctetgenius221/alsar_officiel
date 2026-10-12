{{--
    Page 403 — rendue quand un rôle ouvre une URL qui ne lui est pas ouverte (lien gardé en
    favori, adresse recopiée, menu d'une ancienne session). Les écrans hors de sa portée ne
    figurent pas dans son menu, mais l'adresse directe reste tapable : sans cette page, il
    tombait sur le gabarit brut de Laravel, sans un mot en français ni aucun retour possible.

    Volontairement autonome (styles en ligne, aucun asset) : une page d'erreur doit s'afficher
    même quand le build Vite est absent ou périmé, situation où le reste du site ne s'affiche
    déjà plus. Les couleurs reprennent les tokens de resources/css/app.css (CLAUDE.md §3.1).
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Accès non autorisé — ALSAR</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: #faf7f1;
            color: #0e1a2b;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
            line-height: 1.6;
        }
        .box { max-width: 34rem; border: 1px solid #e7ddcb; background: #fffdf8; padding: 2.5rem; }
        .eyebrow { margin: 0; font-size: .75rem; letter-spacing: .08em; text-transform: uppercase; color: #b8863a; }
        h1 { margin: .75rem 0 0; font-family: Georgia, "Times New Roman", serif; font-size: 1.75rem; font-weight: 400; }
        p { margin: 1rem 0 0; color: #4a5565; }
        .actions { margin-top: 2rem; display: flex; flex-wrap: wrap; gap: .75rem; }
        a {
            display: inline-block;
            padding: .7rem 1.25rem;
            font-size: .875rem;
            font-weight: 500;
            text-decoration: none;
            color: #0e1a2b;
            background: #c9962c;
        }
        a.secondary { background: transparent; border: 1px solid #e7ddcb; }
    </style>
</head>
<body>
    <main class="box">
        <p class="eyebrow">Accès non autorisé</p>
        <h1>Cette page ne fait pas partie de votre espace</h1>
        <p>
            Votre compte n'a pas les droits nécessaires pour consulter cet écran. Ce n'est pas une
            erreur de votre part : chaque rôle n'accède qu'aux parties du back-office qui le concernent.
        </p>
        <p>
            Si vous pensez devoir y accéder, demandez à un administrateur de modifier votre rôle.
        </p>
        <div class="actions">
            <a href="{{ route('admin.dashboard') }}">Retour au tableau de bord</a>
            <a class="secondary" href="{{ url('/') }}">Voir le site public</a>
        </div>
    </main>
</body>
</html>
