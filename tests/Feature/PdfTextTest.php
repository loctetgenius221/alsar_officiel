<?php

/*
| Ajustement du texte du verso de la carte de membre.
|
| Le verso coupe au ras de la boîte (`overflow: hidden`). Sans ajustement sur la largeur réelle,
| une adresse un peu longue était tranchée en plein milieu d'une lettre, sans rien indiquer que
| l'information était incomplète — sur une carte officielle, c'est un défaut visible.
|
| Ces tests verrouillent le point qui a motivé l'existence de PdfText : une limite en NOMBRE de
| caractères ne suffit pas, la largeur d'un caractère variant du simple au double selon la casse.
*/

use App\Support\PdfText;
use Dompdf\Dompdf;

/** Largeur réelle d'un texte, en millimètres, avec les métriques de dompdf. */
function textWidthMm(string $text, float $sizePt = 7.2): float
{
    $metrics = (new Dompdf([
        'fontDir' => storage_path('fonts'),
        'fontCache' => storage_path('fonts'),
    ]))->getFontMetrics();

    return $metrics->getTextWidth($text, $metrics->getFont('helvetica', 'normal'), $sizePt) * 25.4 / 72;
}

// Largeur utile de la colonne des valeurs sur le verso (85,6 - 22 - 3,4).
const COLONNE_MM = 60.2;

it('laisse intact un texte qui tient déjà dans la colonne', function (string $texte) {
    expect(PdfText::fit($texte, COLONNE_MM, 7.2))->toBe($texte);
})->with([
    'SY',
    '+221 77 123 45 67',
    'Chef d’entreprise',
    'Quartier Thioffy, Podor',
]);

it('ramène un texte trop long sous la largeur disponible', function (string $texte) {
    $ajuste = PdfText::fit($texte, COLONNE_MM, 7.2);

    expect($ajuste)->not->toBe($texte)
        ->and($ajuste)->toEndWith('…')
        ->and(textWidthMm($ajuste))->toBeLessThanOrEqual(COLONNE_MM);
})->with([
    'adresse longue' => 'Rue 10 x Boulevard du Centenaire, Grand Dakar, Dakar',
    // Le cas que ne couvre AUCUNE limite en nombre de caractères : en capitales, 34 caractères
    // occupent déjà 64 mm là où la même longueur en bas de casse en prend 38.
    'capitales larges' => 'MAMADOU WAWWA MAMADOU WAWWA MAMADOU WAWWA',
    'sans espace' => str_repeat('W', 60),
]);

it('ne laisse pas de ponctuation orpheline avant les points de suspension', function () {
    // Couper « …Dakar, » puis ajouter « … » donnerait « Dakar,… », qui se lit mal.
    $ajuste = PdfText::fit('Cité Keur Gorgui, Villa 234, Dakar, Sénégal, Afrique', COLONNE_MM, 7.2);

    expect($ajuste)->not->toContain(',…')
        ->and($ajuste)->not->toContain(' …');
});

it('renvoie une chaîne vide pour une valeur absente', function () {
    expect(PdfText::fit(null, COLONNE_MM, 7.2))->toBe('')
        ->and(PdfText::fit('   ', COLONNE_MM, 7.2))->toBe('');
});
