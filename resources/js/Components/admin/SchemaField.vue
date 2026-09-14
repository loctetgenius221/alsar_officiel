<script setup lang="ts">
/**
 * Dispatcher générique du CMS de contenu de page : un seul composant sait rendre n'importe
 * quel champ du schéma (App\Domain\Content\PageSectionSchemas), y compris récursivement à
 * l'intérieur d'un répéteur (import circulaire avec Repeater.vue assumé — résolu à
 * l'exécution par Vue/Vite, cf. composants récursifs). `defineModel` porte la valeur du champ
 * (deux-sens) ; les computed get/set typés ci-dessous ne font que la faire correspondre au
 * type concret attendu par chaque contrôle (Input veut une string, ImageField un
 * ImageFieldValue, Repeater un tableau) sans jamais recourir à `any`.
 */
import { computed } from 'vue';
import Input from '@/Components/ui/Input.vue';
import Select from '@/Components/ui/Select.vue';
import RichTextEditor from '@/Components/admin/RichTextEditor.vue';
import ImageField from '@/Components/admin/ImageField.vue';
import Repeater from '@/Components/admin/Repeater.vue';
import { resolveIcon } from '@/lib/icon-registry';
import type { FieldRecord, FieldSchema, FieldValue, ImageFieldValue } from '@/lib/page-content-schema';

const model = defineModel<FieldValue>({ required: true });

const props = withDefaults(
    defineProps<{
        field: FieldSchema;
        path: string;
        errors: Record<string, string>;
        iconOptions?: string[];
    }>(),
    { iconOptions: () => [] },
);

const error = computed(() => props.errors[props.path]);
const iconSelectOptions = computed(() => props.iconOptions.map((name) => ({ value: name, label: name })));

const textModel = computed<string>({
    get: () => (typeof model.value === 'string' ? model.value : ''),
    set: (value) => {
        model.value = value;
    },
});

function isImageValue(value: FieldValue): value is ImageFieldValue {
    return typeof value === 'object' && value !== null && !Array.isArray(value) && 'path' in value;
}

const imageModel = computed<ImageFieldValue>({
    get: () =>
        isImageValue(model.value)
            ? model.value
            : { path: null, url: null, alt: '', file: null, remove: false },
    set: (value) => {
        model.value = value;
    },
});

const repeaterModel = computed<FieldRecord[]>({
    get: () => (Array.isArray(model.value) ? model.value : []),
    set: (value) => {
        model.value = value;
    },
});
</script>

<template>
    <!-- `data-error-anchor` : cible du récapitulatif d'erreurs en tête de formulaire
         (Components/ui/FormErrorSummary.vue), qui doit pouvoir amener la vue sur n'importe
         quel champ, y compris au fond d'un répéteur. -->
    <div :data-error-anchor="path">
        <Input v-if="field.type === 'text'" v-model="textModel" :label="field.label" :error="error" />

        <div v-else-if="field.type === 'textarea'">
            <label class="font-sans text-sm text-ink-soft">{{ field.label }}</label>
            <textarea
                v-model="textModel"
                rows="3"
                class="mt-2 w-full border border-sand-300 bg-paper px-4 py-2.5 font-sans text-sm text-ink focus-visible:border-gold-500"
            />
            <p v-if="error" class="mt-1 font-sans text-xs text-danger">{{ error }}</p>
        </div>

        <div v-else-if="field.type === 'richtext'">
            <label class="font-sans text-sm text-ink-soft">{{ field.label }}</label>
            <div class="mt-2">
                <RichTextEditor v-model="textModel" />
            </div>
            <p v-if="error" class="mt-1 font-sans text-xs text-danger">{{ error }}</p>
        </div>

        <ImageField
            v-else-if="field.type === 'image'"
            v-model="imageModel"
            :label="field.label"
            :mimes="field.mimes"
            :is-video="field.mimes?.includes('mp4') ?? false"
            :error="errors[`${path}.file`]"
        />

        <div v-else-if="field.type === 'select_icon'">
            <label class="font-sans text-sm text-ink-soft">{{ field.label }}</label>
            <div class="mt-2 flex items-center gap-3">
                <div class="flex-1">
                    <Select v-model="textModel" :options="iconSelectOptions" placeholder="Aucune" />
                </div>
                <component
                    :is="resolveIcon(textModel)"
                    class="size-5 shrink-0 text-gold-600"
                    aria-hidden="true"
                />
            </div>
            <p v-if="error" class="mt-1 font-sans text-xs text-danger">{{ error }}</p>
        </div>

        <Repeater
            v-else-if="field.type === 'repeater'"
            v-model="repeaterModel"
            :fields="field.fields ?? {}"
            :label="field.label"
            :max="field.max ?? null"
            :min="field.min ?? 0"
            :allow-reorder="field.allowReorder ?? true"
            :allow-remove="field.allowRemove ?? true"
            :item-label-field="field.itemLabelField ?? ''"
            :path="path"
            :errors="errors"
            :icon-options="iconOptions"
        />
    </div>
</template>
