<?php

namespace App\Domain\Content\Actions\Concerns;

use App\Domain\Content\Models\Article;
use Illuminate\Support\Str;

/**
 * Le slug n'est plus jamais bloquant côté validation (Form Request) : on le nettoie et on le
 * rend unique automatiquement ici plutôt que de renvoyer une erreur de format/doublon à
 * l'enregistrement — la colonne `articles.slug` reste `unique()` en base (URL publique), donc
 * l'unicité doit être garantie quelque part, juste plus jamais au prix d'un blocage pour l'admin.
 */
trait GeneratesUniqueSlug
{
    private function uniqueArticleSlug(?string $requested, string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($requested ?: $title) ?: 'article';
        $base = Str::limit($base, 240, '');

        $slug = $base;
        $suffix = 2;
        while (
            Article::withTrashed()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
