<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * Résolution des fichiers (images, polices) utilisés par les gabarits PDF.
 *
 * ── Le problème constaté en production ─────────────────────────────────────────────────────
 * Sur la carte de membre, les fonds à vagues et le logo sortaient en « croix grise » (le
 * marqueur d'image introuvable de dompdf) alors que tout s'affichait en local. Or, sur la MÊME
 * page, la photo de l'adhérent et le QR s'affichaient correctement.
 *
 * La différence n'est pas le fichier, c'est le MODE de référencement : photo et QR sont
 * embarqués en `data:image/...;base64,...`, tandis que le fond et le logo étaient référencés
 * par un chemin de fichier absolu (`public_path(...)`). Dompdf, lui, refuse d'ouvrir un fichier
 * local dans deux cas (Options::validateLocalUri) :
 *   - `realpath()` ne résout pas le chemin (fichier absent, ou `public_path()` qui ne pointe
 *     pas sur la vraie racine web — fréquent en mutualisé où le projet est à côté de
 *     `public_html` et non dedans) ;
 *   - le chemin réel sort du « chroot », comparé par préfixe APRÈS résolution des liens
 *     symboliques : un `public_html` symlinké suffit à faire échouer la comparaison.
 *
 * ── La parade ──────────────────────────────────────────────────────────────────────────────
 * On cherche le fichier dans plusieurs racines plausibles, puis on l'embarque en base64.
 * Dompdf ne touche alors plus au disque pour les images : ni chroot, ni lien symbolique, ni
 * disposition d'hébergement ne peuvent l'en empêcher.
 *
 * Et si le fichier est réellement absent, on JOURNALISE tous les chemins essayés — sans quoi
 * le diagnostic se résume à une croix grise muette.
 */
class PdfAsset
{
    /** @var array<string, string|null> */
    private static array $resolved = [];

    /**
     * Chemin absolu du fichier, ou `null` s'il est introuvable.
     *
     * À réserver aux cas où un chemin est indispensable — typiquement une police, dompdf ne
     * sachant pas charger un `@font-face` depuis une data URI. Pour une image, préférer
     * `dataUri()`.
     */
    public static function path(string $relativePath): ?string
    {
        $relativePath = ltrim($relativePath, '/\\');

        if (array_key_exists($relativePath, self::$resolved)) {
            return self::$resolved[$relativePath];
        }

        $tried = [];

        foreach (self::candidateRoots() as $root) {
            $candidate = rtrim($root, '/\\').DIRECTORY_SEPARATOR.$relativePath;
            $tried[] = $candidate;

            $real = realpath($candidate);
            if ($real !== false && is_readable($real)) {
                return self::$resolved[$relativePath] = $real;
            }
        }

        Log::error("Fichier absent des gabarits PDF : {$relativePath}", ['chemins_essayes' => $tried]);

        return self::$resolved[$relativePath] = null;
    }

    /**
     * Image embarquée en `data:<mime>;base64,...`, ou `null` si le fichier est introuvable.
     * Les appelants doivent gérer ce `null` (ne pas émettre la balise) plutôt que de laisser
     * dompdf afficher sa croix grise.
     */
    public static function dataUri(string $relativePath): ?string
    {
        $path = self::path($relativePath);

        if ($path === null) {
            return null;
        }

        $contents = @file_get_contents($path);

        if ($contents === false) {
            Log::error("Fichier des gabarits PDF illisible : {$path}");

            return null;
        }

        return 'data:'.self::mimeType($path).';base64,'.base64_encode($contents);
    }

    /**
     * Racines où chercher, de la plus normale à la plus improbable. `DOCUMENT_ROOT` est la
     * racine web réellement servie : c'est elle qui rattrape les hébergements où la racine
     * publique se trouve à côté du projet (`public_html`) et non dedans.
     *
     * @return list<string>
     */
    private static function candidateRoots(): array
    {
        $roots = [
            public_path(),
            base_path('public'),
            base_path('public_html'),
            base_path(),
        ];

        $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? null;
        if (is_string($documentRoot) && $documentRoot !== '') {
            array_splice($roots, 1, 0, [$documentRoot]);
        }

        return array_values(array_unique(array_filter($roots)));
    }

    /**
     * `finfo` n'est pas garanti présent en mutualisé : on déduit le type de l'extension, qui
     * suffit ici puisque les fichiers concernés sont les nôtres, pas des envois d'utilisateurs.
     */
    private static function mimeType(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            default => 'image/png',
        };
    }
}
