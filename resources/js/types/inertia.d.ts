import '@inertiajs/core';

export interface AuthUser {
    id: number;
    name: string;
    email: string;
}

/**
 * Doit rester aligné sur les constantes de app/Support/AdminRoles.php : une faute de frappe
 * dans un `can('articles.manage')` devient ainsi une erreur de compilation (vue-tsc) plutôt
 * qu'un menu silencieusement vide.
 */
export type AdminPermission =
    | 'articles.manage'
    | 'page-content.manage'
    | 'members.manage'
    | 'payments.manage'
    | 'users.manage'
    | 'activity-log.view';

export interface PageImageField {
    url: string | null;
    alt: string;
}

export interface SiteSettingsSections {
    header?: {
        brand_text: string;
        logo: PageImageField;
        nav_items: { label: string }[];
        cta_label: string;
    };
    footer?: {
        brand_text: string;
        logo: PageImageField;
        tagline: string;
        contact_email: string;
        contact_address: string;
        facebook_url: string;
        copyright_text: string;
    };
}

/**
 * Props partagées globalement par HandleInertiaRequests::share() (app/Http/Middleware).
 * Augmentation officielle recommandée par @inertiajs/core (voir son types.d.ts) plutôt
 * qu'un cast `as any` à chaque usage de `usePage()`.
 */
declare module '@inertiajs/core' {
    interface InertiaConfig {
        sharedPageProps: {
            auth: {
                user: AuthUser | null;
                roles: string[];
                /** Permissions effectives — cf. lib/permissions.ts et app/Support/AdminRoles.php. */
                permissions: AdminPermission[];
            };
            flash: {
                success: string | null;
                error: string | null;
            };
            siteSettings: {
                sections: SiteSettingsSections;
            };
        };
    }
}
