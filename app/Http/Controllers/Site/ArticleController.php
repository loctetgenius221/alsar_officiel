<?php

namespace App\Http\Controllers\Site;

use App\Domain\Content\Models\Article;
use App\Domain\Content\Models\Category;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ArticleController extends Controller
{
    public function index(): InertiaResponse
    {
        $articles = Article::published()
            ->with('category')
            ->orderByDesc('published_at')
            ->get()
            ->map(fn (Article $article) => $article->toPublicArray());

        return Inertia::render('Site/Actualites', [
            'articles' => $articles,
            'categories' => Category::orderBy('name')->pluck('name'),
        ]);
    }

    public function show(string $slug): InertiaResponse
    {
        $article = Article::published()->with('category')->where('slug', $slug)->first();

        return Inertia::render('Site/ActualiteDetail', [
            'article' => $article ? [
                ...$article->toPublicArray(),
                'body' => $article->body,
                'seoTitle' => $article->seo_title,
                'seoDescription' => $article->seo_description,
                // URL absolue exigée par Open Graph (les scrapers Facebook/Twitter ne résolvent
                // pas les chemins relatifs) — le logo sert de repli si l'article n'a pas de
                // couverture, jamais nu/vide sur un partage social. `url()` complète un chemin
                // racine hérité (`/images/...`) avec le domaine ; une URL déjà absolue (cas
                // normal, résolue par Storage::url()) traverse `url()` inchangée.
                'ogImage' => url($article->coverImageUrl() ?? '/images/logo-alsar.png'),
            ] : null,
        ]);
    }
}
