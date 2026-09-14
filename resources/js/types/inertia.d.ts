import '@inertiajs/core';

export interface AuthUser {
    id: number;
    name: string;
    email: string;
}

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
