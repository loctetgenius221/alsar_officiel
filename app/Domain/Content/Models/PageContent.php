<?php

namespace App\Domain\Content\Models;

use App\Domain\Content\PageSectionSchemas;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[Fillable(['slug', 'label', 'sections', 'seo_title', 'seo_description'])]
class PageContent extends Model
{
    use LogsActivity;

    protected function casts(): array
    {
        return ['sections' => 'array'];
    }

    /**
     * Trace anciennes/nouvelles valeurs (`old`/`attributes` dans `activity_log.properties`,
     * déjà affichées telles quelles par Admin/ActivityLog/Index.vue) pour permettre une
     * restauration manuelle après un écrasement accidentel. `logOnlyDirty()` évite une entrée
     * bruyante quand `update()` est appelé sans changement réel (ex. merge qui retombe sur les
     * mêmes valeurs) ; `dontSubmitEmptyLogs()` supprime l'entrée si, après ça, il ne reste
     * justement plus rien de dirty à journaliser.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['slug', 'label', 'sections', 'seo_title', 'seo_description'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'updated' => 'Contenu de page modifié',
                'created' => 'Page de contenu créée',
                default => "Page de contenu {$eventName}",
            });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Résout chaque champ `image` de `sections` (chemin relatif au disque `public`) en URL
     * absolue, en suivant le schéma de la page plutôt que le JSON brut — un champ image peut
     * être imbriqué dans un répéteur à profondeur variable. Même principe qu'Article::toPublicArray().
     */
    public function toPublicArray(): array
    {
        return [
            'sections' => PageSectionSchemas::resolveImagesForDisplay($this->slug, $this->sections ?? []),
            'seoTitle' => $this->seo_title,
            'seoDescription' => $this->seo_description,
        ];
    }
}
