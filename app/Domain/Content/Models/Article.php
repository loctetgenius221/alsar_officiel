<?php

namespace App\Domain\Content\Models;

use App\Models\User;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'category_id', 'author_id', 'title', 'slug', 'excerpt', 'body',
    'cover_image', 'seo_title', 'seo_description', 'published_at',
])]
class Article extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    protected static function newFactory(): Factory
    {
        return ArticleFactory::new();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Un seul champ `published_at` dérive les 3 états (pas d'enum status séparé à
     * garder synchronisé) : NULL = brouillon, date future = programmé, sinon publié.
     */
    public function publicationStatus(): string
    {
        if ($this->published_at === null) {
            return 'draft';
        }

        return $this->published_at->isFuture() ? 'scheduled' : 'published';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    /**
     * Forme partagée par les 3 endroits publics qui affichent un article (teaser accueil,
     * liste /actualites, en-tête du détail) — évite de répéter le même mapping 3 fois.
     * Suppose `category` déjà chargée (with('category')), sinon N+1.
     */
    public function toPublicArray(): array
    {
        return [
            'slug' => $this->slug,
            'category' => $this->category->name,
            'date' => $this->published_at?->translatedFormat('j F Y'),
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'image' => $this->coverImageUrl(),
        ];
    }

    /**
     * Les chemins commençant par `/` sont des assets publics statiques hérités (articles
     * migrés depuis l'ancien contenu en dur, ex. `/images/slider-img-1.webp`, jamais passés
     * par un upload) et passent inchangés ; les chemins relatifs au disque `public` (upload
     * normal depuis l'admin) sont résolus via Storage::url() — même principe que
     * PageSectionSchemas::resolveUrl(). Sans cette distinction, un chemin absolu hérité se
     * retrouvait préfixé de `/storage/` et pointait vers un fichier inexistant.
     */
    public function coverImageUrl(): ?string
    {
        if (! $this->cover_image) {
            return null;
        }

        return str_starts_with($this->cover_image, '/')
            ? $this->cover_image
            : Storage::disk('public')->url($this->cover_image);
    }
}
