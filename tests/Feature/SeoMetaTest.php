<?php

/*
| Métadonnées de partage rendues par le serveur.
|
| Le point de départ : les balises Open Graph existaient déjà, mais posées par `<Head>` côté
| Vue — donc injectées après le chargement, par JavaScript. Les robots de Facebook, WhatsApp
| et LinkedIn n'en exécutent pas : un lien partagé s'affichait sans vignette, sans titre et
| sans résumé. Au Sénégal, où ces deux applications sont le premier canal de diffusion, c'était
| une perte sèche à chaque partage.
|
| Ces tests regardent donc le HTML BRUT renvoyé par PHP, exactement ce que voit un robot — pas
| le DOM après hydratation.
*/

use App\Domain\Content\Models\Article;
use App\Domain\Content\Models\Category;
use App\Models\User;
use App\Support\AdminRoles;
use App\Support\SiteSeo;
use Database\Seeders\PageContentSeeder;
use Database\Seeders\RolesSeeder;

// Les pages vitrine lisent leur contenu en base : sans ce contenu, elles répondent 404 et on
// ne testerait plus rien de ce qu'on croit tester.
beforeEach(fn () => $this->seed(PageContentSeeder::class));

it('rend les balises Open Graph de la page d’accueil dans le HTML servi', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('property="og:title"', false)
        ->assertSee('property="og:description"', false)
        ->assertSee('property="og:type" content="website"', false)
        ->assertSee('name="twitter:card" content="summary_large_image"', false)
        // URL absolue : un chemin relatif n'est pas résolu par les robots.
        ->assertSee('property="og:image" content="'.url(SiteSeo::DEFAULT_IMAGE).'"', false)
        ->assertSee('rel="canonical"', false);
});

it('donne à chaque page publique son propre titre et sa propre description', function (string $url, string $fragment) {
    $this->get($url)
        ->assertOk()
        ->assertSee($fragment, false);
})->with([
    ['/programme', 'Le programme — six axes pour transformer le Sénégal'],
    ['/le-president', 'Mamadou Racine Sy — le Président'],
    ['/regions', 'une présence nationale'],
    ['/rejoignez-nous', 'adhérer à ALSAR'],
]);

it('utilise le titre, le résumé et la couverture de l’article sur son détail', function () {
    $article = Article::factory()->for(Category::factory())->create([
        'title' => 'Mobilisation à Podor',
        'excerpt' => 'Une délégation reçue au siège du parti.',
        'cover_image' => '/images/slider-img-2.webp',
        'published_at' => now()->subDay(),
    ]);

    $this->get("/actualites/{$article->slug}")
        ->assertOk()
        ->assertSee('property="og:type" content="article"', false)
        ->assertSee('Mobilisation à Podor | ALSAR', false)
        ->assertSee('Une délégation reçue au siège du parti.', false)
        ->assertSee('property="og:image" content="'.url('/images/slider-img-2.webp').'"', false);
});

it('retombe sur le visuel du site quand un article n’a pas de couverture', function () {
    // Un partage sans vignette passe beaucoup moins bien dans un fil WhatsApp.
    $article = Article::factory()->for(Category::factory())->create([
        'cover_image' => null,
        'published_at' => now()->subDay(),
    ]);

    $this->get("/actualites/{$article->slug}")
        ->assertSee('property="og:image" content="'.url(SiteSeo::DEFAULT_IMAGE).'"', false);
});

it('interdit l’indexation du back-office', function () {
    $this->get('/admin/login')
        ->assertOk()
        ->assertSee('name="robots" content="noindex, nofollow"', false)
        ->assertDontSee('property="og:title"', false);
});

it('interdit l’indexation des pages liées à une personne', function () {
    // Carte de membre, confirmation d'adhésion, vérification d'un code : ces URLs contiennent
    // un identifiant d'adhérent, elles n'ont rien à faire dans un index.
    expect(SiteSeo::forRoute('adhesion.confirmation'))->toBeNull()
        ->and(SiteSeo::forRoute('verifier'))->toBeNull()
        ->and(SiteSeo::forRoute('admin.dashboard'))->toBeNull();
});

it('n’indexe pas un article introuvable', function () {
    $this->get('/actualites/slug-qui-nexiste-pas')
        ->assertOk()
        ->assertSee('name="robots" content="noindex, nofollow"', false);
});

it('garde le back-office fonctionnel malgré le compositeur de vue', function () {
    // Le compositeur s'applique au gabarit racine, partagé par la vitrine et l'admin : une
    // erreur sur les routes admin (où `$seo` est null) casserait tout le back-office.
    $this->seed(RolesSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole(AdminRoles::SUPER_ADMIN);

    $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
});
