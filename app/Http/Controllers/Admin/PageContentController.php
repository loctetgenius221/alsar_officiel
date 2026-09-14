<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Content\Actions\UpdatePageContent;
use App\Domain\Content\Models\PageContent;
use App\Domain\Content\PageSectionSchemas;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePageContentRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class PageContentController extends Controller
{
    public function index(): InertiaResponse
    {
        $labels = PageSectionSchemas::all();

        $pages = PageContent::query()
            ->get(['id', 'slug', 'label', 'updated_at'])
            ->sortBy(fn (PageContent $page) => array_search($page->slug, array_keys($labels)))
            ->values()
            ->map(fn (PageContent $page) => [
                'slug' => $page->slug,
                'label' => $page->label,
                'updatedAt' => $page->updated_at->translatedFormat('d M Y, H:i'),
            ]);

        return Inertia::render('Admin/PageContent/Index', [
            'pages' => $pages,
        ]);
    }

    public function edit(PageContent $pageContent): InertiaResponse
    {
        return Inertia::render('Admin/PageContent/Edit', [
            'page' => [
                'slug' => $pageContent->slug,
                'label' => $pageContent->label,
                'sections' => PageSectionSchemas::resolveImagesForDisplay($pageContent->slug, $pageContent->sections ?? []),
                'seoTitle' => $pageContent->seo_title,
                'seoDescription' => $pageContent->seo_description,
            ],
            'schema' => PageSectionSchemas::for($pageContent->slug),
        ]);
    }

    public function update(UpdatePageContentRequest $request, PageContent $pageContent, UpdatePageContent $action): RedirectResponse
    {
        $validated = $request->validated();

        $action->handle(
            $pageContent,
            $validated['sections'] ?? [],
            $validated['seo_title'] ?? null,
            $validated['seo_description'] ?? null,
        );

        return back()->with('success', 'Contenu mis à jour.');
    }
}
