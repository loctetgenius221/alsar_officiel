<?php

/*
| Résolution des fichiers des gabarits PDF.
|
| Motivation : en production, les fonds à vagues et le logo de la carte de membre sortaient en
| « croix grise » (marqueur d'image introuvable de dompdf) alors que tout s'affichait en local.
| Sur la MÊME page, la photo et le QR — embarqués en base64 — s'affichaient correctement : le
| problème n'était donc pas le fichier mais le fait de le référencer par un chemin.
|
| Ces tests verrouillent les deux garanties qui règlent ça : trouver le fichier même quand
| `public_path()` ne pointe pas sur la vraie racine web, et l'embarquer en data URI pour que
| dompdf n'ait plus jamais à ouvrir le disque.
*/

use App\Support\PdfAsset;
use Illuminate\Support\Facades\Log;

/** Vide le cache statique de résolution entre deux scénarios. */
function resetPdfAssetCache(): void
{
    $property = (new ReflectionClass(PdfAsset::class))->getProperty('resolved');
    $property->setAccessible(true);
    $property->setValue(null, []);
}

beforeEach(fn () => resetPdfAssetCache());

it('trouve un fichier présent dans le dossier public', function () {
    expect(PdfAsset::path('images/logo-alsar.png'))
        ->toBeString()
        ->and(is_readable(PdfAsset::path('images/logo-alsar.png')))->toBeTrue();
});

it('embarque une image en data URI plutôt que de renvoyer un chemin', function () {
    $uri = PdfAsset::dataUri('images/logo-alsar.png');

    // C'est LE point qui règle la panne de production : dompdf ne touche plus au disque.
    expect($uri)->toStartWith('data:image/png;base64,')
        ->and(base64_decode(substr($uri, strlen('data:image/png;base64,')), true))->not->toBeFalse();
});

it('trouve quand même le fichier quand public_path() ne pointe pas sur la racine web', function () {
    // Reproduit l'hébergement mutualisé où la racine publique est à côté du projet
    // (`public_html`) et non dedans : `public_path()` désigne alors un dossier inexistant.
    app()->usePublicPath('/chemin/qui/nexiste/pas/public');
    resetPdfAssetCache();

    expect(PdfAsset::path('images/logo-alsar.png'))->toBeString();
});

it('renvoie null et journalise les chemins essayés quand le fichier est absent', function () {
    // Sans cette trace, un fichier oublié au déploiement ne laissait qu'une croix grise muette.
    Log::shouldReceive('error')->once()->withArgs(
        fn (string $message, array $context) => str_contains($message, 'images/inexistant.png')
            && isset($context['chemins_essayes'])
            && count($context['chemins_essayes']) >= 3
    );

    expect(PdfAsset::dataUri('images/inexistant.png'))->toBeNull();
});

it('déduit le type MIME de l’extension', function (string $file, string $expected) {
    // `finfo` n'est pas garanti présent en mutualisé, d'où la déduction par extension.
    $method = (new ReflectionClass(PdfAsset::class))->getMethod('mimeType');
    $method->setAccessible(true);

    expect($method->invoke(null, $file))->toBe($expected);
})->with([
    ['logo.png', 'image/png'],
    ['photo.JPG', 'image/jpeg'],
    ['photo.jpeg', 'image/jpeg'],
    ['anim.gif', 'image/gif'],
    ['visuel.webp', 'image/webp'],
]);
