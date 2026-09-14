<?php

namespace App\Exceptions;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use RuntimeException;

/**
 * Levée quand un fichier envoyé depuis l'administration n'a pas pu être écrit sur le disque.
 *
 * Pourquoi une exception dédiée plutôt qu'un simple `return false` : le disque `public` est
 * configuré en `throw => false` (cf. config/filesystems.php), donc `UploadedFile::store()`
 * renvoie `false` au lieu de lever quoi que ce soit. Sans vérification explicite, ce `false`
 * était enregistré tel quel comme chemin d'image — l'illustration disparaissait de la page
 * sans message et sans ligne de journal.
 *
 * Causes réelles en production (hébergement mutualisé) : dossier `storage/app/public` non
 * inscriptible par l'utilisateur du serveur web, quota disque atteint, ou lien symbolique
 * `public/storage` absent après un déploiement par upload de fichiers.
 *
 * `render()` transforme l'exception en redirection avec message flashé : l'utilisateur voit
 * une notification compréhensible plutôt qu'une page 500, et Laravel la journalise quand même
 * (une exception dotée de `render()` reste rapportée).
 */
class FileStorageFailed extends RuntimeException
{
    public static function for(UploadedFile $file, string $directory): self
    {
        return new self(sprintf(
            'Écriture impossible sur le disque "public" : %s vers "%s" (taille : %d octets). '
            .'Vérifier les droits d\'écriture sur storage/app/public et l\'espace disque disponible.',
            $file->getClientOriginalName(),
            $directory,
            $file->getSize() ?: 0,
        ));
    }

    /**
     * Message destiné à l'utilisateur de l'admin : il ne peut rien corriger lui-même, donc on
     * lui dit surtout ce qui N'A PAS été perdu et vers qui se tourner.
     */
    public function render(Request $request)
    {
        return back()->with(
            'error',
            "Le fichier n'a pas pu être enregistré sur le serveur. Vos textes n'ont pas été modifiés et "
            ."l'image précédente est conservée. Il s'agit d'un problème de stockage sur l'hébergement : "
            .'signalez-le à votre administrateur technique.'
        );
    }
}
