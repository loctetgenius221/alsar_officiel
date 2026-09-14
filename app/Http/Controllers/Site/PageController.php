<?php

namespace App\Http\Controllers\Site;

use App\Domain\Content\Models\PageContent;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class PageController extends Controller
{
    public function parti(): InertiaResponse
    {
        return $this->render('le-parti', 'Site/Parti');
    }

    public function president(): InertiaResponse
    {
        return $this->render('le-president', 'Site/President');
    }

    public function programme(): InertiaResponse
    {
        return $this->render('programme', 'Site/Programme');
    }

    public function regions(): InertiaResponse
    {
        return $this->render('regions', 'Site/Regions');
    }

    public function mediatheque(): InertiaResponse
    {
        return $this->render('mediatheque', 'Site/Mediatheque');
    }

    private function render(string $slug, string $component): InertiaResponse
    {
        $page = PageContent::where('slug', $slug)->firstOrFail();

        return Inertia::render($component, $page->toPublicArray());
    }
}
