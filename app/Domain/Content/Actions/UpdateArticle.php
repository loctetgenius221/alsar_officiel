<?php

namespace App\Domain\Content\Actions;

use App\Domain\Content\Actions\Concerns\GeneratesUniqueSlug;
use App\Domain\Content\Models\Article;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UpdateArticle
{
    use GeneratesUniqueSlug;

    public function handle(Article $article, array $data, User $editor): Article
    {
        $coverPath = $article->cover_image;

        if (isset($data['cover_image']) && $data['cover_image'] instanceof UploadedFile) {
            if ($coverPath) {
                Storage::disk('public')->delete($coverPath);
            }
            $coverPath = $data['cover_image']->store('articles/covers', 'public');
        } elseif (! empty($data['remove_cover_image'])) {
            if ($coverPath) {
                Storage::disk('public')->delete($coverPath);
            }
            $coverPath = null;
        }

        $title = $data['title'] ?: '(Sans titre)';

        $article->update([
            'category_id' => $data['category_id'],
            'title' => $title,
            'slug' => $this->uniqueArticleSlug($data['slug'] ?? null, $title, $article->id),
            'excerpt' => $data['excerpt'] ?? null,
            'body' => $data['body'] ?? null,
            'cover_image' => $coverPath,
            'seo_title' => $data['seo_title'] ?? null,
            'seo_description' => $data['seo_description'] ?? null,
            'published_at' => $data['published_at'] ?? null,
        ]);

        activity()->causedBy($editor)->performedOn($article)->log('Article modifié');

        return $article;
    }
}
