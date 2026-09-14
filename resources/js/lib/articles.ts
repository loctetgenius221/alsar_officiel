/**
 * Type partagé par les pages/composants qui affichent des actualités — les données réelles
 * viennent désormais du CMS Actualités du back-office (Admin/Articles) via les props Inertia
 * (HomeController, Site\ArticleController), plus de contenu en dur ici.
 */
export interface PublicArticle {
    slug: string;
    category: string;
    date: string | null;
    title: string;
    excerpt: string | null;
    image: string | null;
}
