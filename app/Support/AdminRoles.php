<?php

namespace App\Support;

/**
 * Catalogue des rôles et permissions du back-office — source d'autorité UNIQUE.
 *
 * Trois consommateurs lisent cette classe, et aucun ne redéclare la liste de son côté :
 *   - RolesSeeder (crée les rôles/permissions en base et les resynchronise) ;
 *   - routes/admin.php (middleware `can:` posé sur chaque groupe d'écrans) ;
 *   - AdminLayout.vue / UserController (menu et formulaire de compte, via les props Inertia).
 *
 * Pourquoi des permissions et pas seulement des rôles : une route protégée par `role:Rédacteur`
 * devrait être retouchée à chaque nouveau rôle, alors qu'une route protégée par
 * `can:articles.manage` reste juste — on ajoute le rôle, on lui donne la permission, rien
 * d'autre ne bouge. Les rôles restent la seule chose que l'utilisateur voit et choisit.
 *
 * Le Super-admin ne dépend PAS de ces lignes pour accéder au back-office : AppServiceProvider
 * lui accorde tout via `Gate::before`. C'est volontaire — un `db:seed` oublié après l'ajout
 * d'une permission ne doit jamais pouvoir enfermer l'administrateur dehors de son propre
 * back-office. Les permissions lui sont malgré tout attribuées en base pour que l'interface
 * (qui lit `auth.permissions`) affiche la même chose que ce que le serveur autorise.
 */
final class AdminRoles
{
    public const SUPER_ADMIN = 'Super-admin';

    public const CONTENT_MANAGER = 'Gestionnaire de contenu';

    public const WRITER = "Rédacteur d'articles";

    public const MANAGE_MEMBERS = 'members.manage';

    public const MANAGE_PAYMENTS = 'payments.manage';

    public const MANAGE_ARTICLES = 'articles.manage';

    public const MANAGE_PAGE_CONTENT = 'page-content.manage';

    public const MANAGE_USERS = 'users.manage';

    public const VIEW_ACTIVITY_LOG = 'activity-log.view';

    /**
     * Toutes les permissions existantes, dans l'ordre d'apparition dans le menu.
     *
     * @return list<string>
     */
    public static function permissions(): array
    {
        return [
            self::MANAGE_ARTICLES,
            self::MANAGE_PAGE_CONTENT,
            self::MANAGE_MEMBERS,
            self::MANAGE_PAYMENTS,
            self::MANAGE_USERS,
            self::VIEW_ACTIVITY_LOG,
        ];
    }

    /**
     * Rôle => permissions accordées.
     *
     * @return array<string, list<string>>
     */
    public static function map(): array
    {
        return [
            self::SUPER_ADMIN => self::permissions(),
            // "Gestion de contenu" au sens large : les actualités ET les textes/photos des
            // pages du site. C'est le profil d'un community manager.
            self::CONTENT_MANAGER => [
                self::MANAGE_ARTICLES,
                self::MANAGE_PAGE_CONTENT,
            ],
            // Un rédacteur écrit des articles, un point c'est tout : il ne doit pas pouvoir
            // modifier l'accueil ou les pages institutionnelles par mégarde.
            self::WRITER => [
                self::MANAGE_ARTICLES,
            ],
        ];
    }

    /** @return list<string> */
    public static function names(): array
    {
        return array_keys(self::map());
    }

    /** @return list<string> */
    public static function permissionsFor(string $role): array
    {
        return self::map()[$role] ?? [];
    }

    /**
     * Rôles présentés dans le formulaire de compte. La description n'est pas décorative :
     * c'est elle qui permet de choisir sans connaître la table des permissions.
     *
     * @return list<array{value: string, label: string, description: string}>
     */
    public static function options(): array
    {
        return [
            [
                'value' => self::SUPER_ADMIN,
                'label' => 'Administrateur',
                'description' => 'Accès complet : adhérents, paiements, contenus, comptes utilisateurs et journal d\'audit.',
            ],
            [
                'value' => self::CONTENT_MANAGER,
                'label' => 'Gestionnaire de contenu',
                'description' => 'Publie les actualités et met à jour les textes et photos des pages du site. Ne voit pas les adhérents.',
            ],
            [
                'value' => self::WRITER,
                'label' => "Rédacteur d'articles",
                'description' => 'Rédige et publie uniquement les actualités. Ne voit ni les adhérents ni les pages du site.',
            ],
        ];
    }
}
