<?php

use App\Domain\Content\Models\PageContent;
use App\Domain\Content\PageSectionSchemas;
use App\Models\User;
use Database\Seeders\PageContentSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('Super-admin', 'web');
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Super-admin');

    // Storage::fake évite que le seeder écrive de vrais fichiers dans storage/app/public
    // pendant les tests ; le seeder est nécessaire ici car PageContent n'a pas de factory —
    // ses 7 lignes n'existent que via ce seeder (cf. plan : "créées uniquement par le seeder").
    Storage::fake('public');
    $this->seed(PageContentSeeder::class);
});

/**
 * Reconstruit, à partir des `sections` actuellement en base, un payload de soumission valide
 * (mêmes clés que prepareFormSections() côté Vue : chaque champ image devient
 * {path, alt, remove}, sans 'url' qui est une valeur d'affichage uniquement).
 */
function pageContentPayload(string $slug, array $sections): array
{
    $payload = [];
    foreach (PageSectionSchemas::for($slug) as $sectionKey => $section) {
        $payload[$sectionKey] = preparePayloadFields($section['fields'], $sections[$sectionKey] ?? []);
    }

    return $payload;
}

function preparePayloadFields(array $fields, array $values): array
{
    $result = [];
    foreach ($fields as $key => $field) {
        $value = $values[$key] ?? null;
        if ($field['type'] === 'image') {
            $result[$key] = ['path' => $value['path'] ?? null, 'alt' => $value['alt'] ?? '', 'remove' => false];
        } elseif ($field['type'] === 'repeater') {
            $result[$key] = array_map(
                fn (array $item) => preparePayloadFields($field['fields'], $item),
                $value ?? [],
            );
        } else {
            $result[$key] = $value ?? '';
        }
    }

    return $result;
}

it('permet à un admin de modifier le contenu d\'une page', function () {
    $page = PageContent::where('slug', 'home')->first();
    $sections = pageContentPayload('home', $page->sections);
    $sections['hero']['headline'] = 'Nouveau titre de test';

    $this->actingAs($this->admin)
        ->patch('/admin/contenu-des-pages/home', [
            'sections' => $sections,
            'seo_title' => null,
            'seo_description' => null,
        ])
        ->assertRedirect();

    expect($page->fresh()->sections['hero']['headline'])->toBe('Nouveau titre de test');
});

it('remplace une image et supprime l\'ancienne du disque', function () {
    $page = PageContent::where('slug', 'home')->first();
    $oldPath = $page->sections['hero']['slides'][0]['image']['path'];
    Storage::disk('public')->assertExists($oldPath);

    $sections = pageContentPayload('home', $page->sections);
    $sections['hero']['slides'][0]['image']['file'] = UploadedFile::fake()->image('new-slide.jpg');

    $this->actingAs($this->admin)
        ->patch('/admin/contenu-des-pages/home', [
            'sections' => $sections,
            'seo_title' => null,
            'seo_description' => null,
        ])
        ->assertRedirect();

    $newPath = $page->fresh()->sections['hero']['slides'][0]['image']['path'];
    expect($newPath)->not->toBe($oldPath);
    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($newPath);
});

it('nettoie l\'image orpheline après suppression d\'une ligne de répéteur', function () {
    $page = PageContent::where('slug', 'mediatheque')->first();
    $removedPath = $page->sections['gallery']['photos'][0]['image']['path'];
    Storage::disk('public')->assertExists($removedPath);

    $sections = pageContentPayload('mediatheque', $page->sections);
    array_shift($sections['gallery']['photos']);

    $this->actingAs($this->admin)
        ->patch('/admin/contenu-des-pages/mediatheque', [
            'sections' => $sections,
            'seo_title' => null,
            'seo_description' => null,
        ])
        ->assertRedirect();

    Storage::disk('public')->assertMissing($removedPath);
});

it('accepte un nom d\'icône hors liste blanche (repli côté public, jamais bloquant en admin)', function () {
    // La validation ne restreint plus les noms d'icônes (demande explicite de suppression des
    // validations de confort) — seul le <select> de l'admin continue de ne proposer que des
    // icônes réelles en usage normal ; resolveIcon() (front public) retombe sur une icône par
    // défaut si jamais un nom inconnu est enregistré, plutôt que de planter.
    $page = PageContent::where('slug', 'programme')->first();
    $sections = pageContentPayload('programme', $page->sections);
    $sections['axes']['axes'][0]['icon'] = 'NotARealIcon';

    $this->actingAs($this->admin)
        ->patch('/admin/contenu-des-pages/programme', [
            'sections' => $sections,
            'seo_title' => null,
            'seo_description' => null,
        ])
        ->assertRedirect()
        ->assertSessionDoesntHaveErrors();

    expect($page->fresh()->sections['axes']['axes'][0]['icon'])->toBe('NotARealIcon');
});

it('empêche un invité d\'accéder à l\'édition du contenu', function () {
    $this->get('/admin/contenu-des-pages')->assertRedirect('/admin/login');
});

it('rejette une requête sections vide plutôt que d\'écraser le contenu existant', function () {
    // Incident de prod reproduit : un formulaire soumis vide (ici `sections: []`, mais
    // l'absence totale de la clé produit le même rejet, cf. assertion finale) ne doit plus
    // jamais être traité comme "tout remettre à zéro".
    $page = PageContent::where('slug', 'home')->first();
    $originalSections = $page->sections;

    $this->actingAs($this->admin)
        ->patch('/admin/contenu-des-pages/home', [
            'sections' => [],
            'seo_title' => null,
            'seo_description' => null,
        ])
        ->assertSessionHasErrors('sections');

    expect($page->fresh()->sections)->toBe($originalSections);

    // Même résultat quand `sections` est totalement absente de la requête, pas seulement vide.
    $this->actingAs($this->admin)
        ->patch('/admin/contenu-des-pages/home', ['seo_title' => null])
        ->assertSessionHasErrors('sections');

    expect($page->fresh()->sections)->toBe($originalSections);
});

it('conserve les champs non soumis lors d\'une modification partielle', function () {
    $page = PageContent::where('slug', 'home')->first();
    $originalHero = $page->sections['hero'];
    $originalPresidentTeaser = $page->sections['president_teaser'];

    // Requête volontairement partielle : un seul champ d'une seule section est envoyé, tout le
    // reste (autres champs de `hero`, toutes les autres sections) est totalement absent.
    $this->actingAs($this->admin)
        ->patch('/admin/contenu-des-pages/home', [
            'sections' => [
                'hero' => ['kicker' => 'Nouveau kicker'],
            ],
        ])
        ->assertRedirect()
        ->assertSessionDoesntHaveErrors();

    $fresh = $page->fresh()->sections;

    expect($fresh['hero']['kicker'])->toBe('Nouveau kicker')
        ->and($fresh['hero']['headline'])->toBe($originalHero['headline'])
        ->and($fresh['hero']['body'])->toBe($originalHero['body'])
        ->and($fresh['hero']['slides'])->toBe($originalHero['slides'])
        ->and($fresh['president_teaser'])->toBe($originalPresidentTeaser);
});

it('journalise les modifications de contenu avec les anciennes et nouvelles valeurs', function () {
    $page = PageContent::where('slug', 'home')->first();
    $sections = pageContentPayload('home', $page->sections);
    $sections['hero']['headline'] = 'Titre journalisé';

    $this->actingAs($this->admin)
        ->patch('/admin/contenu-des-pages/home', [
            'sections' => $sections,
            'seo_title' => null,
            'seo_description' => null,
        ])
        ->assertRedirect();

    $activity = \Spatie\Activitylog\Models\Activity::where('subject_id', $page->id)
        ->where('subject_type', PageContent::class)
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->description)->toBe('Contenu de page modifié')
        ->and($activity->properties['attributes']['sections']['hero']['headline'])->toBe('Titre journalisé')
        ->and($activity->properties['old']['sections']['hero']['headline'])->toBe($page->sections['hero']['headline']);
});
