<?php

/*
| Garde-fou sur lang/fr/validation.php.
|
| Dès qu'un fichier de langue local existe pour une locale, Laravel ne retombe plus sur ses
| messages anglais pour les clés absentes : il affiche la clé brute. Une règle ajoutée par une
| future version du framework produirait donc un « validation.nouvelle_regle » en pleine page,
| sous les yeux d'un utilisateur non technique — un échec silencieux qu'aucun autre test
| n'attraperait. D'où cette comparaison directe avec le fichier de référence du framework.
*/

use App\Domain\Content\PageSectionSchemas;

function flattenTranslationKeys(array $messages, string $prefix = ''): array
{
    $keys = [];

    foreach ($messages as $key => $value) {
        // 'custom' et 'attributes' sont propres à chaque application : leur contenu n'a aucune
        // raison de suivre celui du framework.
        if ($prefix === '' && in_array($key, ['custom', 'attributes'], true)) {
            continue;
        }

        if (is_array($value)) {
            $keys = [...$keys, ...flattenTranslationKeys($value, "{$prefix}{$key}.")];

            continue;
        }

        $keys[] = $prefix.$key;
    }

    return $keys;
}

it('traduit en français toutes les règles de validation connues de Laravel', function () {
    $reference = require base_path('vendor/laravel/framework/src/Illuminate/Translation/lang/en/validation.php');
    $french = require lang_path('fr/validation.php');

    $missing = array_diff(flattenTranslationKeys($reference), flattenTranslationKeys($french));

    expect($missing)->toBeEmpty(
        'Clés absentes de lang/fr/validation.php (elles s\'afficheraient telles quelles) : '
        .implode(', ', $missing)
    );
});

it('affiche les messages de validation en français aux utilisateurs', function () {
    // Test de bout en bout du dispositif : locale + fichier de langue + libellé lisible du
    // champ. Le honeypot `website` est laissé vide, sans quoi la requête serait rejetée avant.
    $this->post('/rejoignez-nous', ['email' => 'pas-un-email'])
        ->assertSessionHasErrors([
            'first_name' => 'Le champ prénom est obligatoire.',
            'email' => 'Le champ adresse email doit contenir une adresse email valide (exemple : nom@domaine.sn).',
            'consent' => 'Vous devez accepter les statuts et la politique de confidentialité pour adhérer.',
        ]);
});

it("nomme lisiblement tous les champs validés de l'éditeur de contenu", function (string $slug) {
    // Chaque chemin susceptible de porter une erreur doit avoir son libellé « Section → Champ ».
    // Sans ça, l'utilisateur verrait apparaître « sections.hero.headline » dans le message.
    $paths = array_keys(PageSectionSchemas::validationRules($slug));
    $labels = PageSectionSchemas::attributeLabels($slug);

    $unnamed = array_values(array_diff($paths, array_keys($labels)));

    expect($unnamed)->toBeEmpty('Chemins sans libellé lisible : '.implode(', ', $unnamed));
})->with(array_keys(PageSectionSchemas::all()));
