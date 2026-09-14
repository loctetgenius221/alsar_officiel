<?php

/*
| Que se passe-t-il quand le serveur n'arrive PAS à écrire un fichier envoyé depuis l'admin.
|
| Ce cas ne se produit jamais en local (le disque est inscriptible) mais c'est la panne la plus
| courante en hébergement mutualisé : droits manquants sur storage/app/public, quota atteint,
| dossier absent après un déploiement par upload de fichiers.
|
| Le disque `public` est configuré en `throw => false` : `UploadedFile::store()` renvoie alors
| `false` au lieu de lever une exception. Avant correction, ce `false` était enregistré tel quel
| comme chemin d'image APRÈS que l'ancienne image ait déjà été supprimée — l'illustration était
| donc définitivement perdue, sans message pour l'utilisateur ni ligne de journal.
|
| Ces tests verrouillent le comportement attendu : échouer bruyamment, et ne rien détruire.
*/

use App\Domain\Content\Actions\CreateArticle;
use App\Domain\Content\Actions\UpdateArticle;
use App\Domain\Content\Actions\UpdatePageContent;
use App\Domain\Content\Models\Article;
use App\Domain\Content\Models\Category;
use App\Domain\Content\Models\PageContent;
use App\Domain\Members\Actions\RegisterMember;
use App\Exceptions\FileStorageFailed;
use App\Models\User;
use Database\Seeders\PageContentSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

/**
 * Un fichier envoyé dont l'écriture sur le disque échoue. On simule le retour `false` de
 * `store()` plutôt que de rendre un dossier non inscriptible, ce qui ne serait pas reproductible
 * entre Windows (dev) et Linux (CI/prod).
 */
function failingUpload(): UploadedFile
{
    $file = Mockery::mock(UploadedFile::class);
    $file->shouldReceive('store')->andReturn(false);
    $file->shouldReceive('getClientOriginalName')->andReturn('photo.jpg');
    $file->shouldReceive('getSize')->andReturn(2048);

    return $file;
}

beforeEach(function () {
    Storage::fake('public');
});

it("conserve l'image existante d'une page quand l'écriture du nouveau fichier échoue", function () {
    $this->seed(PageContentSeeder::class);

    $page = PageContent::where('slug', 'home')->first();
    $oldPath = $page->sections['hero']['slides'][0]['image']['path'];
    Storage::disk('public')->assertExists($oldPath);

    $sections = $page->sections;
    $sections['hero']['slides'][0]['image'] = [
        'path' => $oldPath,
        'alt' => 'Inchangé',
        'remove' => false,
        'file' => failingUpload(),
    ];

    expect(fn () => (new UpdatePageContent)->handle($page, $sections, null, null))
        ->toThrow(FileStorageFailed::class);

    // Le point essentiel : l'ancienne image survit, sur le disque comme en base.
    Storage::disk('public')->assertExists($oldPath);
    expect($page->fresh()->sections['hero']['slides'][0]['image']['path'])->toBe($oldPath);
});

it("conserve la couverture existante d'un article quand l'écriture échoue", function () {
    $editor = User::factory()->create();
    $existing = UploadedFile::fake()->image('ancienne.jpg')->store('articles/covers', 'public');

    $article = Article::factory()->create([
        'category_id' => Category::factory()->create()->id,
        'cover_image' => $existing,
    ]);

    expect(fn () => (new UpdateArticle)->handle(
        $article,
        [
            'category_id' => $article->category_id,
            'title' => $article->title,
            'slug' => $article->slug,
            'cover_image' => failingUpload(),
        ],
        $editor,
    ))->toThrow(FileStorageFailed::class);

    Storage::disk('public')->assertExists($existing);
    expect($article->fresh()->cover_image)->toBe($existing);
});

it("n'enregistre jamais un article dont la couverture n'a pas pu être écrite", function () {
    $author = User::factory()->create();

    expect(fn () => (new CreateArticle)->handle(
        [
            'category_id' => Category::factory()->create()->id,
            'title' => 'Article avec image cassée',
            'cover_image' => failingUpload(),
        ],
        $author,
    ))->toThrow(FileStorageFailed::class);

    // Aucun article « fantôme » avec un chemin d'image invalide.
    expect(Article::where('title', 'Article avec image cassée')->exists())->toBeFalse();
});

it("enregistre quand même l'adhésion si la photo ne peut pas être écrite, mais le journalise", function () {
    // Une adhésion ne doit pas être refusée pour un problème de stockage côté serveur : c'est
    // l'arbitrage inverse de celui retenu côté administration.
    Log::shouldReceive('error')->once()->withArgs(
        fn (string $message) => str_contains($message, "Photo d'adhérent non enregistrée")
    );

    $member = (new RegisterMember)->handle([
        'first_name' => 'Aminata',
        'last_name' => 'Diallo',
        'gender' => 'femme',
        'phone' => '+221 770000000',
        'email' => 'aminata.diallo@example.sn',
        'region' => 'Dakar',
        'commune' => 'Plateau',
        'section' => 'standard',
        'photo' => failingUpload(),
    ], null, null);

    expect($member->exists)->toBeTrue()
        ->and($member->photo_path)->toBeNull();
});

it("présente un message compréhensible à l'utilisateur plutôt qu'une page d'erreur", function () {
    // Vérifie le câblage de FileStorageFailed::render() : l'exception doit se transformer en
    // redirection avec message flashé (donc en notification à l'écran), pas en 500.
    Role::findOrCreate('Super-admin', 'web');
    $admin = User::factory()->create();
    $admin->assignRole('Super-admin');

    $this->seed(PageContentSeeder::class);
    $page = PageContent::where('slug', 'programme')->first();

    $this->mock(UpdatePageContent::class)
        ->shouldReceive('handle')
        ->andThrow(FileStorageFailed::for(UploadedFile::fake()->image('x.jpg'), 'pages/programme'));

    $response = $this->actingAs($admin)->patch('/admin/contenu-des-pages/programme', [
        'sections' => $page->sections,
        'seo_title' => null,
        'seo_description' => null,
    ]);

    $response->assertRedirect();
    expect(session('error'))
        ->toContain("n'a pas pu être enregistré")
        ->toContain('administrateur technique');
});
