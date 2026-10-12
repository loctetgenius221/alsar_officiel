<?php

use App\Domain\Content\Models\Article;
use App\Domain\Content\Models\Category;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('Super-admin', 'web');
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Super-admin');
    $this->category = Category::factory()->create(['name' => 'Vie du parti']);
});

function validArticlePayload(int $categoryId, array $overrides = []): array
{
    return array_merge([
        'category_id' => $categoryId,
        'title' => 'Un titre de test',
        'slug' => 'un-titre-de-test',
        'excerpt' => 'Un extrait de test.',
        'body' => '<p>Corps de test.</p>',
        'seo_title' => null,
        'seo_description' => null,
        'published_at' => null,
    ], $overrides);
}

it('crée un article et journalise l\'action', function () {
    Storage::fake('public');

    $this->actingAs($this->admin)
        ->post('/admin/actualites', validArticlePayload($this->category->id, [
            'cover_image' => UploadedFile::fake()->image('cover.jpg'),
        ]))
        ->assertRedirect();

    $article = Article::first();
    expect($article)->not->toBeNull();
    expect($article->title)->toBe('Un titre de test');
    expect($article->author_id)->toBe($this->admin->id);
    expect($article->cover_image)->not->toBeNull();
    Storage::disk('public')->assertExists($article->cover_image);

    expect(Activity::where('subject_id', $article->id)->where('subject_type', Article::class)->count())->toBe(1);
});

it('dé-doublonne automatiquement le slug au lieu de bloquer l\'enregistrement', function () {
    Article::factory()->for($this->category, 'category')->create(['slug' => 'deja-pris']);

    $this->actingAs($this->admin)
        ->post('/admin/actualites', validArticlePayload($this->category->id, ['slug' => 'deja-pris']))
        ->assertRedirect()
        ->assertSessionDoesntHaveErrors();

    expect(Article::where('slug', 'deja-pris')->count())->toBe(1);
    expect(Article::where('slug', 'deja-pris-2')->count())->toBe(1);
});

it('dérive le statut brouillon/programmé/publié depuis published_at', function () {
    $draft = Article::factory()->for($this->category, 'category')->draft()->create();
    $scheduled = Article::factory()->for($this->category, 'category')->scheduled()->create();
    $published = Article::factory()->for($this->category, 'category')->create(['published_at' => now()->subDay()]);

    expect($draft->publicationStatus())->toBe('draft');
    expect($scheduled->publicationStatus())->toBe('scheduled');
    expect($published->publicationStatus())->toBe('published');
});

it('modifie un article existant', function () {
    $article = Article::factory()->for($this->category, 'category')->create(['title' => 'Ancien titre']);

    $this->actingAs($this->admin)
        ->patch("/admin/actualites/{$article->getRouteKey()}", validArticlePayload($this->category->id, [
            'title' => 'Nouveau titre',
            'slug' => $article->slug,
        ]))
        ->assertRedirect();

    expect($article->fresh()->title)->toBe('Nouveau titre');
});

it('supprime (soft delete) un article', function () {
    $article = Article::factory()->for($this->category, 'category')->create();

    $this->actingAs($this->admin)
        ->delete("/admin/actualites/{$article->getRouteKey()}")
        ->assertRedirect('/admin/actualites');

    expect(Article::find($article->id))->toBeNull();
    expect(Article::withTrashed()->find($article->id))->not->toBeNull();
});

it('filtre les articles par statut', function () {
    Article::factory()->for($this->category, 'category')->draft()->create(['title' => 'Un brouillon']);
    Article::factory()->for($this->category, 'category')->create(['title' => 'Un publié']);

    $this->actingAs($this->admin)
        ->get('/admin/actualites?status=draft')
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Articles/Index')
            ->has('articles.data', 1)
            ->where('articles.data.0.title', 'Un brouillon')
        );
});

it('empêche un invité d\'accéder aux articles admin', function () {
    $this->get('/admin/actualites')->assertRedirect('/admin/login');
});
