<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Content\Actions\CreateArticle;
use App\Domain\Content\Actions\UpdateArticle;
use App\Domain\Content\Models\Article;
use App\Domain\Content\Models\Category;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreArticleRequest;
use App\Http\Requests\Admin\UpdateArticleRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ArticleController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $articles = $this->filteredQuery($request)
            ->with('category')
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Article $article) => [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'category' => $article->category->name,
                'status' => $article->publicationStatus(),
                'publishedAt' => $article->published_at?->translatedFormat('d M Y, H:i'),
                'updatedAt' => $article->updated_at->translatedFormat('d M Y'),
            ]);

        return Inertia::render('Admin/Articles/Index', [
            'articles' => $articles,
            'filters' => $request->only('search', 'category', 'status'),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('Admin/Articles/Create', [
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreArticleRequest $request, CreateArticle $createArticle): RedirectResponse
    {
        $article = $createArticle->handle($request->validated(), Auth::user());

        return redirect()
            ->route('admin.articles.edit', $article)
            ->with('success', 'Article créé.');
    }

    public function edit(Article $article): InertiaResponse
    {
        return Inertia::render('Admin/Articles/Edit', [
            'article' => [
                'id' => $article->id,
                'categoryId' => $article->category_id,
                'title' => $article->title,
                'slug' => $article->slug,
                'excerpt' => $article->excerpt,
                'body' => $article->body,
                'coverImageUrl' => $article->cover_image ? Storage::disk('public')->url($article->cover_image) : null,
                'seoTitle' => $article->seo_title,
                'seoDescription' => $article->seo_description,
                'publishedAt' => $article->published_at?->format('Y-m-d\TH:i'),
                'status' => $article->publicationStatus(),
            ],
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateArticleRequest $request, Article $article, UpdateArticle $updateArticle): RedirectResponse
    {
        $updateArticle->handle($article, $request->validated(), Auth::user());

        return back()->with('success', 'Article mis à jour.');
    }

    public function destroy(Article $article): RedirectResponse
    {
        activity()->causedBy(Auth::user())->performedOn($article)->log('Article supprimé');

        $article->delete();

        return redirect()->route('admin.articles.index')->with('success', 'Article supprimé.');
    }

    private function filteredQuery(Request $request): Builder
    {
        return Article::query()
            ->when(
                $request->string('search')->trim()->isNotEmpty(),
                fn (Builder $query) => $query->where('title', 'like', '%'.$request->string('search')->trim().'%'),
            )
            ->when(
                $request->filled('category'),
                fn (Builder $query) => $query->where('category_id', $request->integer('category')),
            )
            ->when($request->filled('status'), function (Builder $query) use ($request) {
                match ($request->string('status')->value()) {
                    'draft' => $query->whereNull('published_at'),
                    'scheduled' => $query->where('published_at', '>', now()),
                    'published' => $query->whereNotNull('published_at')->where('published_at', '<=', now()),
                    default => null,
                };
            });
    }
}
