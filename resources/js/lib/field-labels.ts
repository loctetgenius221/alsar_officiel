/**
 * Noms lisibles des champs, pour le récapitulatif d'erreurs (Components/ui/FormErrorSummary.vue).
 *
 * Les clés sont celles renvoyées par le serveur (noms de colonnes), les valeurs sont les
 * libellés réellement affichés à côté des champs à l'écran — un utilisateur doit retrouver
 * dans la liste des erreurs exactement le mot qu'il voit au-dessus du champ, sinon il cherche.
 *
 * L'éditeur de contenu générique (PageContent/Edit.vue) ne figure pas ici : ses libellés sont
 * dérivés du schéma reçu en prop, cf. `sectionFieldLabels()` plus bas.
 */
import type { FieldSchema, PageSchema } from '@/lib/page-content-schema';

export const ARTICLE_FIELD_LABELS: Record<string, string> = {
    title: 'Titre',
    slug: 'Slug (URL)',
    excerpt: 'Extrait',
    body: "Contenu de l'article",
    category_id: 'Catégorie',
    cover_image: 'Image de couverture',
    published_at: 'Date de publication',
    seo_title: 'Titre SEO',
    seo_description: 'Description SEO',
};

export const ADHESION_FIELD_LABELS: Record<string, string> = {
    first_name: 'Prénom',
    last_name: 'Nom',
    birth_date: 'Date de naissance',
    gender: 'Sexe',
    phone: 'Téléphone',
    email: 'Adresse email',
    region: 'Région',
    commune: 'Commune',
    address: 'Adresse',
    profession: 'Profession',
    photo: 'Photo',
    id_document_number: "Numéro de pièce d'identité",
    section: 'Section',
    referral_source: 'Comment avez-vous connu ALSAR ?',
    consent: 'Acceptation des statuts',
    website: 'Vérification anti-robot',
};

/**
 * Équivalent côté client de PageSectionSchemas::attributeLabels() (PHP) : reconstruit
 * « Section → Champ » depuis le schéma reçu en prop, pour que le récapitulatif d'erreurs de
 * l'éditeur de contenu affiche « Héros → Titre » et non `sections.hero.headline`.
 */
export function sectionFieldLabels(schema: PageSchema): Record<string, string> {
    const labels: Record<string, string> = {};

    for (const [sectionKey, section] of Object.entries(schema)) {
        for (const [fieldKey, field] of Object.entries(section.fields)) {
            collect(labels, `sections.${sectionKey}.${fieldKey}`, section.label, field);
        }
    }

    return { ...labels, seo_title: 'Titre SEO', seo_description: 'Description SEO' };
}

function collect(labels: Record<string, string>, path: string, prefix: string, field: FieldSchema): void {
    const label = `${prefix} → ${field.label}`;

    if (field.type === 'repeater') {
        labels[path] = label;
        for (const [subKey, subField] of Object.entries(field.fields ?? {})) {
            collect(labels, `${path}.*.${subKey}`, label, subField);
        }
        return;
    }

    if (field.type === 'image') {
        labels[`${path}.file`] = label;
        labels[`${path}.alt`] = `${label} (description de l'image)`;
        labels[`${path}.path`] = label;
        labels[`${path}.remove`] = label;
        return;
    }

    labels[path] = label;
}
