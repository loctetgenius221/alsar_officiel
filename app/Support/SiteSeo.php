<?php

namespace App\Support;

/**
 * Métadonnées de partage (title / description / Open Graph) des pages publiques.
 *
 * Pourquoi côté SERVEUR et non dans les composants Vue : les robots de Facebook, WhatsApp,
 * LinkedIn et Twitter n'exécutent pas JavaScript. Les balises posées par `<Head>` d'Inertia
 * sont injectées après le chargement, donc invisibles pour eux — un lien partagé n'affichait
 * ni vignette, ni titre, ni résumé. Or au Sénégal, le partage par WhatsApp et Facebook est le
 * premier canal de diffusion : ces balises doivent être dans le HTML rendu par PHP.
 *
 * Le titre reste rendu avec l'attribut `inertia` : le serveur donne le bon au premier
 * chargement (ce que voient les robots), et le client continue de le mettre à jour lors des
 * navigations SPA, sans que les deux se contredisent.
 */
final class SiteSeo
{
    public const SITE_NAME = 'ALSAR';

    /** Visuel de partage par défaut — une vraie photo de terrain, jamais le logo seul. */
    public const DEFAULT_IMAGE = '/images/slider-img-1.webp';

    private const DEFAULT_DESCRIPTION = 'ALSAR — Alliance pour la Synergie des Actions Républicaines. '
        .'Un parti sénégalais porté par une troisième voie pragmatique, tournée vers des résultats concrets.';

    /**
     * Métadonnées d'une route publique, ou `null` pour tout ce qui ne doit pas être indexé
     * (back-office, pages de confirmation personnelles).
     *
     * @return array{title: string, description: string, image: string, type: string}|null
     */
    public static function forRoute(?string $routeName): ?array
    {
        if ($routeName === null || str_starts_with($routeName, 'admin.')) {
            return null;
        }

        // Pages liées à une personne (confirmation d'adhésion, carte, vérification d'un code) :
        // elles n'ont rien à faire dans un index de moteur de recherche.
        if (str_starts_with($routeName, 'adhesion.confirmation')
            || str_starts_with($routeName, 'adhesion.card')
            || $routeName === 'verifier') {
            return null;
        }

        $page = self::pages()[$routeName] ?? null;

        if ($page === null) {
            return null;
        }

        return [
            'title' => $page['title'],
            'description' => $page['description'] ?? self::DEFAULT_DESCRIPTION,
            'image' => $page['image'] ?? self::DEFAULT_IMAGE,
            'type' => $page['type'] ?? 'website',
        ];
    }

    /**
     * Routes publiques indexables, dans l'ordre d'importance.
     *
     * Même liste que celle des métadonnées, et c'est volontaire : une page qui mérite un titre
     * et une description mérite d'être dans le sitemap, et réciproquement. Une seule liste à
     * tenir à jour, donc pas de dérive entre les deux (cf. Site\SitemapController).
     *
     * @return list<string>
     */
    public static function indexableRouteNames(): array
    {
        return array_keys(self::pages());
    }

    /**
     * Descriptions volontairement spécifiques et sous les ~160 caractères utiles : une
     * description générique répétée sur toutes les pages n'apporte rien, ni au partage ni au
     * référencement.
     *
     * @return array<string, array{title: string, description?: string, image?: string, type?: string}>
     */
    private static function pages(): array
    {
        return [
            'home' => [
                'title' => 'ALSAR — La force du changement, l\'énergie du progrès',
                'description' => 'Alliance pour la Synergie des Actions Républicaines : un parti sénégalais '
                    .'porté par Mamadou Racine Sy, tourné vers des résultats concrets pour le Sénégal.',
            ],
            'parti' => [
                'title' => 'Le Parti — vision, histoire et organisation | ALSAR',
                'description' => 'D\'ALPAR à ALSAR : la trajectoire, les valeurs et l\'organisation du parti, '
                    .'de la mairie de Podor à la scène nationale.',
            ],
            'president' => [
                'title' => 'Mamadou Racine Sy — le Président d\'ALSAR',
                'description' => 'Entrepreneur hôtelier, maire de Podor depuis 2022, fondateur d\'ALPAR puis '
                    .'d\'ALSAR : le parcours de Mamadou Racine Sy.',
            ],
            'programme' => [
                'title' => 'Le programme — six axes pour transformer le Sénégal | ALSAR',
                'description' => 'Emploi, souveraineté alimentaire, désenclavement, foncier, droits des femmes '
                    .'et gouvernance : les six axes du programme d\'ALSAR.',
            ],
            'actualites' => [
                'title' => 'Actualités — la vie du parti au jour le jour | ALSAR',
                'description' => 'Vie du parti, actions à Podor, vie nationale et solidarité : le fil des '
                    .'communiqués et des faits marquants d\'ALSAR.',
                'image' => '/images/slider-img-2.webp',
            ],
            'mediatheque' => [
                'title' => 'Médiathèque — photos, vidéos et kit presse | ALSAR',
                'description' => 'Photos de terrain, vidéos et ressources à destination de la presse et des '
                    .'militants d\'ALSAR.',
            ],
            'regions' => [
                'title' => 'Régions — une présence nationale | ALSAR',
                'description' => 'ALSAR est présent dans les 14 régions du Sénégal. Coordinations locales et '
                    .'implantation du parti, région par région.',
            ],
            'adhesion.create' => [
                'title' => 'Rejoignez-nous — adhérer à ALSAR',
                'description' => 'Adhésion en ligne, cotisation et carte de membre numérique : devenez '
                    .'adhérent, bénévole ou relais local d\'ALSAR.',
            ],
            'mentions-legales' => [
                'title' => 'Mentions légales | ALSAR',
                'description' => 'Éditeur, hébergeur et conditions d\'utilisation du site d\'ALSAR.',
            ],
            'confidentialite' => [
                'title' => 'Politique de confidentialité | ALSAR',
                'description' => 'Traitement des données personnelles des adhérents et des visiteurs du site '
                    .'d\'ALSAR.',
            ],
        ];
    }
}
