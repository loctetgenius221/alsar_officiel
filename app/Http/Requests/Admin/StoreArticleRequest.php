<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validations volontairement minimales (demande explicite : l'admin doit pouvoir enregistrer
 * son contenu sans accroc, à sa convenance). Ne restent que les garde-fous qu'on ne peut pas
 * retirer sans provoquer une vraie casse plus loin dans la chaîne plutôt qu'un simple message
 * d'erreur :
 * - `category_id` reste obligatoire : la colonne DB est une clé étrangère NOT NULL
 *   (`constrained()` sans `nullable()`), et `Article::toPublicArray()` appelle
 *   `$this->category->name` sans vérification de nullité — la retirer ferait planter
 *   l'affichage public, pas juste échouer poliment à l'enregistrement.
 * - `cover_image` garde la vérification "est-ce une vraie image" (résiste au faux fichier
 *   déguisé) mais avec un plafond de taille large et davantage de formats acceptés — un vrai
 *   contrôle de sécurité, pas un confort qu'on retire.
 * - `published_at` garde un contrôle de format : une date illisible ferait planter le cast
 *   `datetime` du modèle au moment de l'enregistrement, pas juste refuser proprement.
 * Le slug n'est plus validé ici : `CreateArticle`/`UpdateArticle` le nettoient et le
 * dé-doublonnent automatiquement (jamais d'erreur de doublon, jamais de format à respecter).
 */
class StoreArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'title' => ['nullable', 'string'],
            'slug' => ['nullable', 'string'],
            'excerpt' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'image', 'max:20480', 'mimes:jpg,jpeg,png,webp,gif,bmp'],
            'seo_title' => ['nullable', 'string'],
            'seo_description' => ['nullable', 'string'],
            'published_at' => ['nullable', 'date'],
        ];
    }
}
