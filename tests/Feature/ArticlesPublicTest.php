<?php

use App\Domain\Content\Models\Article;
use App\Domain\Content\Models\Category;

beforeEach(function () {
    $this->category = Category::factory()->create(['name' => 'Vie du parti']);
});

it('affiche uniquement les articles publiés sur la liste publique', function () {
    Article::factory()->for($this->category, 'category')->create(['title' => 'Article publié']);
    Article::factory()->for($this->category, 'category')->draft()->create(['title' => 'Article brouillon']);
    Article::factory()->for($this->category, 'category')->scheduled()->create(['title' => 'Article programmé']);

    $this->get('/actualites')->assertInertia(fn ($page) => $page
        ->component('Site/Actualites')
        ->has('articles', 1)
        ->where('articles.0.title', 'Article publié')
    );
});

it('affiche le détail d\'un article publié', function () {
    $article = Article::factory()->for($this->category, 'category')->create([
        'title' => 'Un vrai article',
        'body' => '<p>Contenu réel.</p>',
    ]);

    $this->get("/actualites/{$article->slug}")->assertInertia(fn ($page) => $page
        ->component('Site/ActualiteDetail')
        ->where('article.title', 'Un vrai article')
        ->where('article.body', '<p>Contenu réel.</p>')
    );
});

it('ne montre pas un article brouillon sur sa page de détail publique', function () {
    $article = Article::factory()->for($this->category, 'category')->draft()->create();

    $this->get("/actualites/{$article->slug}")->assertInertia(fn ($page) => $page
        ->component('Site/ActualiteDetail')
        ->where('article', null)
    );
});

it('renvoie un article nul pour un slug inconnu, sans erreur serveur', function () {
    $this->get('/actualites/ce-slug-n-existe-pas')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('article', null));
});
