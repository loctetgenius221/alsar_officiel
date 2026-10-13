<?php

namespace App\Http\Controllers\Site;

use App\Domain\Content\Models\Article;
use App\Domain\Content\Models\Category;
use App\Http\Controllers\Controller;
use App\Support\SiteSeo;
use Illuminate\Support\Str;
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
            ] : null,
        ])
            // Métadonnées de partage rendues par PHP, pas par `<Head>` côté Vue : les robots
            // de Facebook et WhatsApp n'exécutent pas JavaScript (cf. App\Support\SiteSeo).
            // Elles remplacent ici le défaut déduit de la route, l'article connaissant son
            // vrai titre, son vrai résumé et sa vraie couverture.
            ->withViewData(['seo' => $article ? [
                'title' => ($article->seo_title ?: $article->title).' | '.SiteSeo::SITE_NAME,
                'description' => $article->seo_description
                    ?: $article->excerpt
                    ?: Str::limit(strip_tags((string) $article->body), 155),
                // Repli sur le visuel du site : un partage sans vignette passe beaucoup moins
                // bien dans un fil WhatsApp qu'un partage illustré.
                'image' => $article->coverImageUrl() ?: SiteSeo::DEFAULT_IMAGE,
                'type' => 'article',
            ] : null]);
    }
}
