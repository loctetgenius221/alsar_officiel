<?php

namespace App\Http\Controllers\Site;

use App\Domain\Content\Models\Article;
use App\Domain\Content\Models\PageContent;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class HomeController extends Controller
{
    public function index(): InertiaResponse
    {
        $articles = Article::published()
            ->with('category')
            ->orderByDesc('published_at')
            ->take(3)
            ->get()
            ->map(fn (Article $article) => $article->toPublicArray());

        $page = PageContent::where('slug', 'home')->firstOrFail();

        return Inertia::render('Site/Home', [
            'articles' => $articles,
            ...$page->toPublicArray(),
        ]);
    }
}
