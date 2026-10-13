<?php

namespace App\Http\Controllers\Site;

use App\Domain\Content\Models\Article;
use App\Http\Controllers\Controller;
use App\Support\SiteSeo;
use Illuminate\Http\Response;

/**
 * Sitemap XML servi sur /sitemap.xml, référencé par public/robots.txt.
 *
 * Généré à la demande plutôt que déposé en fichier statique : les actualités sont publiées
 * depuis le back-office, un fichier figé serait périmé dès le premier article. Le volume ne
 * justifie aucun cache (une dizaine de pages et quelques dizaines d'articles).
 *
 * Toutes les URLs passent par `route()`, donc par APP_URL : le sitemap annonce exactement le
 * même domaine que les balises canoniques et Open Graph. Aucun domaine n'est écrit en dur.
 */
class SitemapController extends Controller
{
    /** Pages de fond : elles bougent peu, mais restent les plus importantes du site. */
    private const PAGE_PRIORITY = [
        'home' => '1.0',
        'adhesion.create' => '0.9',
        'programme' => '0.8',
        'actualites' => '0.8',
        'president' => '0.7',
        'parti' => '0.7',
    ];

    public function __invoke(): Response
    {
        $urls = [];

        foreach (SiteSeo::indexableRouteNames() as $name) {
            $urls[] = [
                'loc' => route($name),
                'priority' => self::PAGE_PRIORITY[$name] ?? '0.5',
                'changefreq' => 'monthly',
                'lastmod' => null,
            ];
        }

        // `lastmod` tiré de la vraie date de modification : c'est le signal qui dit à un moteur
        // qu'un article a été corrigé depuis son passage précédent.
        foreach (Article::published()->orderByDesc('published_at')->get() as $article) {
            $urls[] = [
                'loc' => route('actualites.show', $article->slug),
                'priority' => '0.6',
                'changefreq' => 'yearly',
                'lastmod' => $article->updated_at?->toAtomString(),
            ];
        }

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }
}
