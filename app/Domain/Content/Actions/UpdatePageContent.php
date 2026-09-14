<?php

namespace App\Domain\Content\Actions;

use App\Domain\Content\Models\PageContent;
use App\Domain\Content\PageSectionSchemas;
use App\Exceptions\FileStorageFailed;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Fusionne les sections soumises sur les sections existantes plutôt que de les remplacer en
 * bloc : un champ absent ou explicitement `null` dans la requête conserve sa valeur actuelle,
 * seul un champ réellement présent (chaîne vide comprise — un texte vidé volontairement est
 * une modification, pas une absence) est appliqué. Corrige un incident de prod où une requête
 * incomplète avait écrasé tout le contenu d'une page avec des valeurs vides.
 *
 * `UpdatePageContentRequest` rejette déjà en amont une requête totalement vide (aucune valeur
 * non nulle nulle part dans `sections`) ; cette Action se protège quand même une seconde fois
 * champ par champ, au cas où une requête partiellement vide (certaines clés absentes, d'autres
 * non) passerait ce premier filtre.
 *
 * Pas de paramètre `$editor`/journalisation manuelle ici contrairement à `CreateArticle`/
 * `UpdateArticle` : `PageContent` porte le trait `LogsActivity` (avant/après automatiques,
 * causeur résolu depuis `Auth::user()`), ce que `Article` ne fait pas encore.
 */
class UpdatePageContent
{
    public function handle(PageContent $page, array $validatedSections, ?string $seoTitle, ?string $seoDescription): PageContent
    {
        $oldSections = $page->sections ?? [];
        $schema = PageSectionSchemas::for($page->slug);

        $resolvedSections = $this->resolveSections($schema, $oldSections, $validatedSections, "pages/{$page->slug}");

        $oldPaths = PageSectionSchemas::collectImagePaths($page->slug, $oldSections);
        $newPaths = PageSectionSchemas::collectImagePaths($page->slug, $resolvedSections);
        $this->deletePaths(array_diff($oldPaths, $newPaths));

        $page->update([
            'sections' => $resolvedSections,
            'seo_title' => $seoTitle,
            'seo_description' => $seoDescription,
        ]);

        if ($page->slug === 'site-settings') {
            Cache::forget('site-settings');
        }

        return $page;
    }

    /**
     * Parcourt le schéma (pas les valeurs brutes) pour savoir exactement où se trouvent les
     * champs image/répéteur, à toute profondeur. `$oldValues`/`$newValues` avancent en
     * parallèle : chaque champ décide lui-même, selon son type, comment se rabattre sur
     * l'ancienne valeur quand la nouvelle est absente.
     */
    private function resolveSections(array $schemaSections, array $oldValues, array $newValues, string $pathPrefix): array
    {
        $resolved = [];
        foreach ($schemaSections as $sectionKey => $section) {
            $resolved[$sectionKey] = $this->resolveFields(
                $section['fields'],
                $this->asArray($oldValues[$sectionKey] ?? null),
                $this->asArray($newValues[$sectionKey] ?? null),
                "{$pathPrefix}/{$sectionKey}",
            );
        }

        return $resolved;
    }

    private function resolveFields(array $fields, array $oldValues, array $newValues, string $pathPrefix): array
    {
        $resolved = [];
        foreach ($fields as $key => $field) {
            // `array_key_exists` (pas `??`) : une valeur `null` explicitement présente doit être
            // traitée exactement comme une clé absente (conserver l'ancienne valeur), ce que `??`
            // fait déjà naturellement puisqu'il ne distingue pas les deux — mais on le nomme ici
            // pour que l'intention (absent === null === "ne pas toucher") soit explicite.
            $newValue = array_key_exists($key, $newValues) ? $newValues[$key] : null;
            $oldValue = $oldValues[$key] ?? null;

            if ($field['type'] === 'image') {
                $resolved[$key] = $this->resolveImage(
                    $this->asArray($oldValue),
                    $newValue === null ? null : $this->asArray($newValue),
                    "{$pathPrefix}/{$key}",
                );
            } elseif ($field['type'] === 'repeater') {
                $resolved[$key] = $this->resolveRepeater($field['fields'], $this->asArray($oldValue), $newValue, "{$pathPrefix}/{$key}");
            } else {
                // Texte/texte long/richtext/icône : `null` (absent ou explicitement nul) conserve
                // l'ancienne valeur ; toute autre valeur (chaîne vide comprise) est appliquée telle
                // quelle — une chaîne vide est une modification volontaire, pas une absence.
                $resolved[$key] = $newValue !== null ? $newValue : $oldValue;
            }
        }

        return $resolved;
    }

    /**
     * Un répéteur absent/`null` conserve la liste actuelle intacte. Un répéteur présent (même
     * vide `[], ce qui correspond à "l'admin a supprimé toutes les lignes", une action légitime)
     * remplace la liste — chaque ligne restante est quand même fusionnée avec la ligne existante
     * au même index (si elle existe) pour rester protégé même si un champ précis à l'intérieur
     * d'une ligne soumise arrivait `null` par accident.
     */
    private function resolveRepeater(array $itemFields, array $oldItems, mixed $newValue, string $pathPrefix): array
    {
        if ($newValue === null) {
            return $oldItems;
        }

        $newItems = is_array($newValue) ? $newValue : [];
        $resolved = [];
        foreach ($newItems as $i => $item) {
            $resolved[] = $this->resolveFields($itemFields, $this->asArray($oldItems[$i] ?? null), $this->asArray($item), "{$pathPrefix}/{$i}");
        }

        return $resolved;
    }

    /**
     * `path`/`alt` reflètent la valeur soumise (le client réécrit toujours l'état courant d'un
     * champ image non modifié, y compris après un réordonnancement de répéteur où l'index seul
     * ne suffit pas à identifier la bonne image) — inchangé par rapport au comportement
     * précédent. La seule différence : si le champ image est absent de la requête (`$newValue
     * === null`), on renvoie l'ancienne valeur telle quelle, fichier compris, au lieu de la
     * réduire à un objet vide qui aurait été interprété comme "pas de photo".
     */
    private function resolveImage(array $oldValue, ?array $newValue, string $storagePath): array
    {
        if ($newValue === null) {
            return ['path' => $oldValue['path'] ?? null, 'alt' => $oldValue['alt'] ?? ''];
        }

        $currentPath = $newValue['path'] ?? null;
        $alt = $newValue['alt'] ?? '';

        if (! empty($newValue['remove'])) {
            $this->deletePaths([$currentPath]);

            return ['path' => null, 'alt' => $alt];
        }

        if (($newValue['file'] ?? null) instanceof UploadedFile) {
            // Ordre critique : écrire la nouvelle image AVANT de supprimer l'ancienne.
            // L'ordre inverse (celui d'origine) faisait perdre l'illustration existante dès
            // que l'écriture échouait — et comme le disque est en `throw => false`,
            // `store()` renvoie `false` sans rien signaler : le `false` était enregistré
            // comme chemin, l'ancienne image était déjà effacée, et rien n'apparaissait dans
            // le journal. Cas typique en production sur hébergement mutualisé (droits
            // d'écriture, quota).
            $newPath = $newValue['file']->store($storagePath, 'public');

            if (! is_string($newPath) || $newPath === '') {
                throw FileStorageFailed::for($newValue['file'], $storagePath);
            }

            $this->deletePaths([$currentPath]);

            return ['path' => $newPath, 'alt' => $alt];
        }

        return ['path' => $currentPath, 'alt' => $alt];
    }

    private function asArray(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    /**
     * Les chemins commençant par `/` sont des assets publics statiques (hérités), jamais des
     * fichiers du disque `public` géré par Storage — ne jamais tenter de les supprimer.
     */
    private function deletePaths(array $paths): void
    {
        $diskPaths = array_values(array_filter(
            $paths,
            fn (?string $path) => $path && ! str_starts_with($path, '/'),
        ));

        if ($diskPaths !== []) {
            Storage::disk('public')->delete($diskPaths);
        }
    }
}
