<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Donne au modèle un identifiant public opaque, utilisé À LA PLACE de la clé primaire dans
 * les URLs (`/admin/utilisateurs/9b1deb4d-.../modifier` au lieu de `/admin/utilisateurs/3`).
 *
 * Ce que cela règle concrètement :
 *   - une URL ne révèle plus combien de comptes, d'articles ou de paiements existent, ni dans
 *     quel ordre ils ont été créés — un identifiant auto-incrémenté dit les deux ;
 *   - on ne peut plus parcourir les fiches en incrémentant un nombre dans la barre d'adresse.
 *     L'autorisation reste bien sûr portée par les middlewares (`can:`), pas par l'opacité de
 *     l'URL : c'est une couche de plus, jamais la seule.
 *
 * UUID v4 et non ULID : un ULID est trié par date de création, et son préfixe laisse donc
 * encore deviner l'ancienneté d'une fiche. Un UUID v4 ne dit rien du tout.
 *
 * La colonne est nullable en base (compatibilité des anciennes lignes et de SQLite en test),
 * mais ce crochet `creating` garantit qu'aucune ligne créée par Eloquent n'en est dépourvue.
 * Un `DB::table()->insert()` brut y échapperait — il n'y en a pas dans ce projet.
 */
trait HasPublicId
{
    public static function bootHasPublicId(): void
    {
        static::creating(function (Model $model) {
            if (blank($model->public_id)) {
                $model->public_id = (string) Str::uuid();
            }
        });
    }

    /** C'est cette méthode qui fait résoudre `{user}`, `{article}`… par l'identifiant public. */
    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
