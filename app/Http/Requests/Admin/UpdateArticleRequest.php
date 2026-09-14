<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Voir StoreArticleRequest pour le raisonnement complet — mêmes règles minimales, même
 * traitement du slug (nettoyé/dé-doublonné dans UpdateArticle, jamais validé ici).
 */
class UpdateArticleRequest extends FormRequest
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
            'remove_cover_image' => ['nullable', 'boolean'],
            'seo_title' => ['nullable', 'string'],
            'seo_description' => ['nullable', 'string'],
            'published_at' => ['nullable', 'date'],
        ];
    }
}
