<?php

use App\Domain\Content\Models\PageContent;
use Database\Seeders\PageContentSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->seed(PageContentSeeder::class);
});

it('affiche le contenu seedé du Parti', function () {
    $this->get('/le-parti')->assertInertia(fn ($page) => $page
        ->component('Site/Parti')
        ->where('sections.header.title', "Vision, histoire et organisation d'ALSAR")
        ->where('sections.vision.values.0.label', 'Travail (Liggey) et mérite')
    );
});

it('affiche le contenu seedé du Président', function () {
    $this->get('/le-president')->assertInertia(fn ($page) => $page
        ->component('Site/President')
        ->where('sections.header.title', 'Mamadou Racine Sy')
    );
});

it('affiche le contenu seedé du Programme (6 axes)', function () {
    $this->get('/programme')->assertInertia(fn ($page) => $page
        ->component('Site/Programme')
        ->has('sections.axes.axes', 6)
        ->where('sections.axes.axes.0.title', "Pouvoir d'achat et emploi")
    );
});

it('affiche le contenu seedé des Régions', function () {
    $this->get('/regions')->assertInertia(fn ($page) => $page
        ->component('Site/Regions')
        ->where('sections.region_info.regions.0.region_name', 'Dakar')
    );
});

it('affiche le contenu seedé de la Médiathèque', function () {
    $this->get('/mediatheque')->assertInertia(fn ($page) => $page
        ->component('Site/Mediatheque')
        ->has('sections.gallery.photos', 7)
    );
});

it('reflète une modification du contenu Accueil sur la page publique', function () {
    $home = PageContent::where('slug', 'home')->first();
    $sections = $home->sections;
    $sections['hero']['headline'] = 'Titre modifié par un admin';
    $home->update(['sections' => $sections]);

    $this->get('/')->assertInertia(fn ($page) => $page
        ->component('Site/Home')
        ->where('sections.hero.headline', 'Titre modifié par un admin')
    );
});

it('partage le même en-tête/pied de page sur des pages différentes', function () {
    $home = $this->get('/');
    $parti = $this->get('/le-parti');

    $home->assertInertia(fn ($page) => $page
        ->where('siteSettings.sections.header.nav_items.0.label', 'Le Parti')
    );
    $parti->assertInertia(fn ($page) => $page
        ->where('siteSettings.sections.header.nav_items.0.label', 'Le Parti')
    );
});
