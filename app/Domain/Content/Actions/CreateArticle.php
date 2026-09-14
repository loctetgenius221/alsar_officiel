<?php

namespace App\Domain\Content\Actions;

use App\Domain\Content\Actions\Concerns\GeneratesUniqueSlug;
use App\Domain\Content\Models\Article;
use App\Exceptions\FileStorageFailed;
use App\Models\User;
use Illuminate\Http\UploadedFile;

/**
 * Crée un article à partir des données déjà validées (StoreArticleRequest). L'auteur vient
 * toujours de l'utilisateur connecté, jamais de la requête (cf. même principe que le montant
 * de cotisation dans RegisterMember : ne jamais faire confiance au client pour une donnée
 * qu'on peut déduire côté serveur).
 */
class CreateArticle
{
    use GeneratesUniqueSlug;

    public function handle(array $data, User $author): Article
    {
        $coverPath = null;

        if (isset($data['cover_image']) && $data['cover_image'] instanceof UploadedFile) {
            // `store()` renvoie `false` (et non une exception) quand l'écriture échoue, le
            // disque `public` étant en `throw => false` : sans ce contrôle, l'article était
            // créé avec `false` comme chemin d'image, sans erreur ni ligne de journal.
            $coverPath = $data['cover_image']->store('articles/covers', 'public');

            if (! is_string($coverPath) || $coverPath === '') {
                throw FileStorageFailed::for($data['cover_image'], 'articles/covers');
            }
        }

        $title = $data['title'] ?: '(Sans titre)';

        $article = Article::create([
            'category_id' => $data['category_id'],
            'author_id' => $author->id,
            'title' => $title,
            'slug' => $this->uniqueArticleSlug($data['slug'] ?? null, $title),
            'excerpt' => $data['excerpt'] ?? null,
            'body' => $data['body'] ?? null,
            'cover_image' => $coverPath,
            'seo_title' => $data['seo_title'] ?? null,
            'seo_description' => $data['seo_description'] ?? null,
            'published_at' => $data['published_at'] ?? null,
        ]);

        activity()->causedBy($author)->performedOn($article)->log('Article créé');

        return $article;
    }
}
