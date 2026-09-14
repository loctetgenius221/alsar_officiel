<script setup lang="ts">
/**
 * Répéteur générique du CMS de contenu de page — un gabarit de champs fixe par ligne
 * (add/remove/réordonner), jamais une mise en page libre : CLAUDE.md §11 impose "un éditeur
 * de blocs simple... pas un page-builder complexe". `defineModel` porte le tableau
 * directement (même référence reactive jusqu'à `useForm()`) : push/splice le mutent en
 * place, ce qui suffit à propager la réactivité sans jamais réassigner `model.value` en bloc.
 *
 * `allowReorder`/`allowRemove` à false verrouille l'ordre et le nombre de lignes (ex. les
 * libellés de nav du header, dont les `href` fixes sont zippés par index côté public — un
 * réordonnancement casserait ce mapping, cf. plan).
 *
 * Lignes repliées par défaut (seul un résumé reste visible) : certains répéteurs (photos du
 * carrousel, galerie...) comptent 5 à 8 éléments à plusieurs champs chacun — tout déplié
 * d'un coup rendait la page d'édition interminable. Une ligne en cours d'édition (contient
 * déjà une erreur de validation, ou vient d'être ajoutée) reste ouverte.
 */
import { computed, reactive } from 'vue';
import { ChevronDown } from '@lucide/vue';
import type { FieldRecord, FieldSchema, ImageFieldValue } from '@/lib/page-content-schema';
import SchemaField from '@/Components/admin/SchemaField.vue';

const model = defineModel<FieldRecord[]>({ required: true });

const props = withDefaults(
    defineProps<{
        fields: Record<string, FieldSchema>;
        label: string;
        max?: number | null;
        min?: number;
        allowReorder?: boolean;
        allowRemove?: boolean;
        itemLabelField?: string;
        path: string;
        errors: Record<string, string>;
        iconOptions?: string[];
    }>(),
    { max: null, min: 0, allowReorder: true, allowRemove: true, itemLabelField: '', iconOptions: () => [] },
);

const WIDE_TYPES = ['textarea', 'richtext', 'image', 'repeater'];

function emptyItem(): FieldRecord {
    const item: FieldRecord = {};
    for (const [key, field] of Object.entries(props.fields)) {
        if (field.type === 'image') {
            item[key] = { path: null, url: null, alt: '', file: null, remove: false } satisfies ImageFieldValue;
        } else if (field.type === 'repeater') {
            item[key] = [];
        } else {
            item[key] = '';
        }
    }
    return item;
}

// Un item repéré par sa position dans le tableau (pas d'id stable côté client) — cohérent
// avec le reste du répéteur, qui manipule déjà les lignes par index (push/splice/drag).
const expanded = reactive<Set<number>>(new Set());
function isExpanded(index: number): boolean {
    return expanded.has(index);
}
function toggle(index: number) {
    if (expanded.has(index)) {
        expanded.delete(index);
    } else {
        expanded.add(index);
    }
}

function addItem() {
    model.value.push(emptyItem());
    expanded.add(model.value.length - 1);
}

function removeItem(index: number) {
    model.value.splice(index, 1);
    expanded.delete(index);
}

/** Première valeur textuelle non vide trouvée dans l'item — sert de résumé quand aucun
 * `itemLabelField` explicite n'est configuré pour ce répéteur. */
function fallbackSummary(item: FieldRecord): string | null {
    for (const [key, field] of Object.entries(props.fields)) {
        const value = item[key];
        if ((field.type === 'text' || field.type === 'textarea') && typeof value === 'string' && value.trim()) {
            return value.trim();
        }
        if (field.type === 'image' && value && typeof value === 'object' && !Array.isArray(value) && 'alt' in value) {
            const alt = (value as ImageFieldValue).alt;
            if (alt) return alt;
        }
    }
    return null;
}

function itemLabel(item: FieldRecord, index: number): string {
    const labelValue = props.itemLabelField ? item[props.itemLabelField] : undefined;
    if (typeof labelValue === 'string' && labelValue) return labelValue;
    return `Élément ${index + 1}`;
}

function itemSummary(item: FieldRecord, index: number): string {
    const summary = fallbackSummary(item);
    if (!summary) return '';
    const truncated = summary.length > 70 ? `${summary.slice(0, 70)}…` : summary;
    // Évite de répéter le même texte comme titre ET résumé quand itemLabelField pointe déjà
    // vers ce même champ.
    return truncated === itemLabel(item, index) ? '' : truncated;
}

/** Une ligne contenant une erreur de validation doit rester visible plutôt que repliée. */
function hasError(index: number): boolean {
    const prefix = `${props.path}.${index}.`;
    return Object.keys(props.errors).some((key) => key.startsWith(prefix));
}

const openState = computed(() => (index: number) => isExpanded(index) || hasError(index));

let dragIndex: number | null = null;
function onDragStart(index: number) {
    dragIndex = index;
}
function onDrop(index: number) {
    if (dragIndex === null || dragIndex === index) return;
    const [moved] = model.value.splice(dragIndex, 1);
    model.value.splice(index, 0, moved);
    dragIndex = null;
}
</script>

<template>
    <div>
        <div class="flex items-center justify-between gap-4">
            <p class="font-sans text-sm text-ink-soft">{{ label }}</p>
            <button
                v-if="allowRemove && (max === null || model.length < max)"
                type="button"
                class="font-sans text-xs font-medium text-gold-700 hover:underline"
                @click="addItem"
            >
                + Ajouter
            </button>
        </div>

        <p v-if="!allowReorder && !allowRemove" class="mt-1 font-sans text-xs text-ink-soft">
            L'ordre et le nombre d'éléments sont fixes ici — seul le contenu est modifiable.
        </p>

        <div class="mt-3 space-y-2">
            <div
                v-for="(item, index) in model"
                :key="index"
                class="border border-sand-200 bg-sand-50/60"
                :draggable="allowReorder"
                @dragstart="onDragStart(index)"
                @dragover.prevent
                @drop="onDrop(index)"
            >
                <div class="flex items-center gap-2 px-4 py-2.5">
                    <span v-if="allowReorder" class="cursor-grab shrink-0 text-sand-500 select-none" aria-hidden="true">⠿</span>
                    <button
                        type="button"
                        class="flex min-w-0 flex-1 items-center gap-2 text-left"
                        @click="toggle(index)"
                    >
                        <ChevronDown
                            class="size-4 shrink-0 text-ink-soft transition-transform duration-200"
                            :class="openState(index) ? 'rotate-0' : '-rotate-90'"
                            aria-hidden="true"
                        />
                        <span class="min-w-0 truncate font-sans text-sm font-medium text-ink">
                            {{ itemLabel(item, index) }}
                        </span>
                        <span v-if="itemSummary(item, index)" class="min-w-0 truncate font-sans text-xs text-ink-soft">
                            — {{ itemSummary(item, index) }}
                        </span>
                    </button>
                    <button
                        v-if="allowRemove"
                        type="button"
                        class="shrink-0 font-sans text-xs text-danger hover:underline"
                        @click="removeItem(index)"
                    >
                        Supprimer
                    </button>
                </div>

                <div v-if="openState(index)" class="grid gap-4 border-t border-sand-200 p-4 sm:grid-cols-2">
                    <SchemaField
                        v-for="(subField, subKey) in fields"
                        :key="subKey"
                        v-model="item[subKey]"
                        :class="WIDE_TYPES.includes(subField.type) ? 'sm:col-span-2' : ''"
                        :field="subField"
                        :path="`${path}.${index}.${subKey}`"
                        :errors="errors"
                        :icon-options="iconOptions"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
