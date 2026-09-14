<script setup lang="ts">
/**
 * Champ image générique pour le CMS de contenu de page (extrait du bloc "image de
 * couverture" d'ArticleForm.vue pour être réutilisable par SchemaField.vue). `defineModel`
 * plutôt que value+emit : le v-model expose directement l'objet {path, url, alt, file,
 * remove} imbriqué dans `useForm()`, et ses propriétés peuvent être mutées localement (c'est
 * le modèle, pas un prop brut) sans faire remonter d'événement à travers chaque niveau de
 * récursion du formulaire schema-driven.
 */
import { computed, ref, useId } from 'vue';
import Input from '@/Components/ui/Input.vue';
import type { ImageFieldValue } from '@/lib/page-content-schema';

const fieldValue = defineModel<ImageFieldValue>({ required: true });

const props = withDefaults(
    defineProps<{
        label: string;
        mimes?: string;
        isVideo?: boolean;
        error?: string;
    }>(),
    { mimes: 'jpg,jpeg,png,webp', isVideo: false, error: undefined },
);

const inputId = useId();

const localPreview = ref<string | null>(null);

const previewUrl = computed(() => (fieldValue.value.remove ? null : (localPreview.value ?? fieldValue.value.url)));

const accept = computed(() =>
    props.isVideo
        ? 'video/mp4,video/webm'
        : props.mimes
              .split(',')
              .map((mime) => `image/${mime.trim() === 'jpg' ? 'jpeg' : mime.trim()}`)
              .join(','),
);

function onChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    fieldValue.value.file = file;
    fieldValue.value.remove = false;
    localPreview.value = file ? URL.createObjectURL(file) : null;
}

function remove() {
    fieldValue.value.file = null;
    fieldValue.value.remove = true;
    localPreview.value = null;
}
</script>

<template>
    <div>
        <label :for="inputId" class="font-sans text-sm text-ink-soft">{{ label }}</label>
        <!-- Miniature + contrôles côte à côte plutôt qu'un aperçu pleine largeur en 16:9 :
             sur une page qui empile potentiellement des dizaines de champs image (répéteurs
             de photos), un gros aperçu par champ rendait le formulaire interminable. -->
        <div class="mt-2 flex items-start gap-3">
            <div class="size-16 shrink-0 overflow-hidden rounded border border-sand-300 bg-sand-100">
                <video v-if="isVideo && previewUrl" :src="previewUrl" muted class="size-full object-cover" />
                <img v-else-if="previewUrl" :src="previewUrl" alt="" class="size-full object-cover" />
                <div v-else class="flex size-full items-center justify-center font-sans text-[0.65rem] text-ink-soft">
                    Aucun
                </div>
            </div>
            <div class="min-w-0 flex-1 space-y-2">
                <input
                    :id="inputId"
                    type="file"
                    :accept="accept"
                    class="w-full font-sans text-xs text-ink-soft file:mr-2 file:border-0 file:bg-sand-200 file:px-2.5 file:py-1.5 file:font-sans file:text-xs file:text-ink"
                    @change="onChange"
                />
                <div class="flex items-center gap-3">
                    <Input v-if="!isVideo" v-model="fieldValue.alt" placeholder="Texte alternatif" class="flex-1" />
                    <button
                        v-if="previewUrl"
                        type="button"
                        class="shrink-0 font-sans text-xs text-danger underline"
                        @click="remove"
                    >
                        Retirer
                    </button>
                </div>
            </div>
        </div>
        <p v-if="error" class="mt-1 font-sans text-xs text-danger">{{ error }}</p>
    </div>
</template>
