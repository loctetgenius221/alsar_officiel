<?php

namespace App\Http\Requests\Admin;

use App\Domain\Content\Models\PageContent;
use App\Domain\Content\PageSectionSchemas;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePageContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var PageContent $page */
        $page = $this->route('pageContent');

        return [
            ...PageSectionSchemas::validationRules($page->slug),
            'seo_title' => ['nullable', 'string'],
            'seo_description' => ['nullable', 'string'],
        ];
    }

    /**
     * Noms de champs lisibles dérivés du schéma (« Héros → Titre ») : sans ça, un message
     * d'erreur afficherait « Le champ sections.hero.headline est obligatoire » à un
     * utilisateur non technique.
     */
    public function attributes(): array
    {
        /** @var PageContent $page */
        $page = $this->route('pageContent');

        return PageSectionSchemas::attributeLabels($page->slug);
    }

    /**
     * Garde-fou contre l'incident de prod où une requête vide a écrasé tout le contenu d'une
     * page : `UpdatePageContent` fusionne désormais les champs réellement soumis sur l'existant
     * (une valeur absente/`null` ne remplace plus rien), mais si la requête entière ne contient
     * strictement aucune valeur non nulle, il n'y a rien à fusionner — mieux vaut le refuser
     * explicitement que d'enregistrer un `update()` qui ne change rien silencieusement (et masque
     * ainsi un vrai problème côté client : formulaire mal envoyé, session expirée, etc.).
     */
    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            // `$this->all()` (pas `input()`) : inclut les fichiers uploadés, qui comptent comme
            // une valeur soumise au même titre qu'un texte.
            $sections = $this->all()['sections'] ?? null;

            if (! is_array($sections) || self::isEmptyOfValues($sections)) {
                $validator->errors()->add(
                    'sections',
                    "Aucune donnée exploitable n'a été reçue : le contenu existant n'a pas été modifié pour éviter de l'écraser par erreur. Réessayez, et si le problème persiste, rechargez la page.",
                );
            }
        });
    }

    /**
     * Vrai si `$value` ne contient, à quelque profondeur que ce soit, strictement aucune valeur
     * non nulle — une chaîne vide `''` OU un fichier uploadé COMPTENT comme une valeur (`""` est
     * une modification volontaire, cf. UpdatePageContent), seul un vrai `null`/tableau vide à
     * tous les niveaux déclenche le rejet.
     */
    private static function isEmptyOfValues(array $value): bool
    {
        foreach ($value as $item) {
            if (is_array($item)) {
                if (! self::isEmptyOfValues($item)) {
                    return false;
                }
            } elseif ($item !== null) {
                return false;
            }
        }

        return true;
    }
}
