<?php

namespace App\Support;

use Dompdf\Dompdf;
use Dompdf\FontMetrics;

/**
 * Ajustement de texte pour les gabarits PDF (carte de membre).
 *
 * Pourquoi pas un simple `Str::limit()` : une limite en NOMBRE DE CARACTÈRES ne garantit rien,
 * la largeur d'un caractère variant du simple au double selon la casse. Mesuré en Helvetica
 * 7,2 pt : « i » fait 0,64 mm, « W » 2,57 mm. Une adresse de 34 caractères en capitales
 * dépasse encore 64 mm là où la même longueur en bas de casse en occupe 38.
 *
 * Or le verso de la carte coupe au ras de la boîte (`overflow: hidden`) : sans ajustement, un
 * texte trop long est tranché en plein milieu d'une lettre, sans le moindre signe que
 * l'information est incomplète. On coupe donc sur la largeur réelle, avec points de suspension.
 */
class PdfText
{
    private static ?FontMetrics $metrics = null;

    /**
     * Tronque `$text` pour qu'il tienne dans `$widthMm`, en ajoutant « … » si nécessaire.
     */
    public static function fit(
        ?string $text,
        float $widthMm,
        float $sizePt,
        string $family = 'helvetica',
        string $style = 'normal',
    ): string {
        $text = trim((string) $text);

        if ($text === '') {
            return '';
        }

        $metrics = self::metrics();
        $font = $metrics->getFont($family, $style);
        $width = fn (string $value): float => $metrics->getTextWidth($value, $font, $sizePt) * 25.4 / 72;

        if ($width($text) <= $widthMm) {
            return $text;
        }

        // Retrait caractère par caractère : les chaînes concernées sont courtes (une adresse),
        // une recherche dichotomique n'apporterait rien de mesurable.
        $length = mb_strlen($text);
        while ($length > 1 && $width(mb_substr($text, 0, $length).'…') > $widthMm) {
            $length--;
        }

        return rtrim(mb_substr($text, 0, $length), " \t\n\r\0\x0B,;:-").'…';
    }

    /**
     * Les métriques sont coûteuses à initialiser (chargement du registre de polices) et
     * identiques pour toute la requête : une seule instance suffit.
     */
    private static function metrics(): FontMetrics
    {
        return self::$metrics ??= (new Dompdf([
            'fontDir' => storage_path('fonts'),
            'fontCache' => storage_path('fonts'),
        ]))->getFontMetrics();
    }
}
