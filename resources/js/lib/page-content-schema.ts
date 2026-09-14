/**
 * Types miroirs de App\Domain\Content\PageSectionSchemas (PHP) — le schéma lui-même est
 * envoyé tel quel en prop Inertia par PageContentController@edit, ces types servent
 * uniquement à typer son usage côté Vue (Pages/Admin/PageContent/Edit.vue et les composants
 * admin/SchemaField, admin/Repeater, admin/ImageField qui le consomment récursivement).
 */

export interface FieldSchema {
    type: 'text' | 'textarea' | 'richtext' | 'image' | 'select_icon' | 'repeater';
    label: string;
    mimes?: string;
    fields?: Record<string, FieldSchema>;
    min?: number;
    max?: number | null;
    allowReorder?: boolean;
    allowRemove?: boolean;
    itemLabelField?: string;
}

export interface SectionSchema {
    label: string;
    fields: Record<string, FieldSchema>;
}

export type PageSchema = Record<string, SectionSchema>;

export interface ImageFieldValue {
    path: string | null;
    url: string | null;
    alt: string;
    file: File | null;
    remove: boolean;
}

/** Valeur d'un champ quelconque du formulaire, à toute profondeur de répéteur imbriqué. */
export type FieldValue = string | ImageFieldValue | FieldRecord[];
export type FieldRecord = Record<string, FieldValue>;
export type SectionsFormData = Record<string, FieldRecord>;

/**
 * Complète les valeurs reçues du serveur (`page.sections`, façonnées par
 * PageSectionSchemas::resolveImagesForDisplay) avec les propriétés purement côté-client
 * (`file`, `remove` sur chaque champ image) dont useForm() a besoin, et garantit qu'un champ
 * absent des données existantes (page jamais éditée) a quand même une valeur par défaut.
 */
export function prepareFormSections(schema: PageSchema, sections: Record<string, unknown>): SectionsFormData {
    const result: SectionsFormData = {};
    for (const [sectionKey, section] of Object.entries(schema)) {
        result[sectionKey] = prepareFields(section.fields, asRecord(sections[sectionKey]));
    }
    return result;
}

function prepareFields(fields: Record<string, FieldSchema>, values: Record<string, unknown>): FieldRecord {
    const result: FieldRecord = {};
    for (const [key, field] of Object.entries(fields)) {
        const value = values[key];
        if (field.type === 'image') {
            const image = asRecord(value);
            result[key] = {
                path: (image.path as string | null) ?? null,
                url: (image.url as string | null) ?? null,
                alt: (image.alt as string) ?? '',
                file: null,
                remove: false,
            };
        } else if (field.type === 'repeater') {
            const items = Array.isArray(value) ? value : [];
            result[key] = items.map((item) => prepareFields(field.fields ?? {}, asRecord(item)));
        } else {
            result[key] = (value as string) ?? '';
        }
    }
    return result;
}

function asRecord(value: unknown): Record<string, unknown> {
    return value && typeof value === 'object' ? (value as Record<string, unknown>) : {};
}
