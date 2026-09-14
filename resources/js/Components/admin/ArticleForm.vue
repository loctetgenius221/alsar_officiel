<script setup lang="ts">
/**
 * Formulaire partagé Create/Edit (CLAUDE.md §6 : CRUD articles, éditeur riche, programmation
 * de publication, SEO par article). Le parent possède le `useForm` Inertia (méthode
 * post/patch différente selon création/édition) ; ce composant se contente d'afficher les
 * champs et de piloter deux comportements purement présentationnels : la suggestion de slug
 * depuis le titre, et le mode de publication (brouillon / maintenant / programmé) qui se
 * traduit au final en une seule valeur `published_at`.
 */
import { computed, ref, watch } from 'vue';
import type { InertiaForm } from '@inertiajs/vue3';
import RichTextEditor from '@/Components/admin/RichTextEditor.vue';
import Input from '@/Components/ui/Input.vue';
import Select from '@/Components/ui/Select.vue';
import Card from '@/Components/ui/Card.vue';

export interface ArticleFormData {
    category_id: number | null;
    title: string;
    slug: string;
    excerpt: string;
    body: string;
    cover_image: File | null;
    remove_cover_image: boolean;
    seo_title: string;
    seo_description: string;
    published_at: string;
}

const props = defineProps<{
    form: InertiaForm<ArticleFormData>;
    categories: { id: number; name: string }[];
    existingCoverUrl?: string | null;
}>();

const categoryOptions = computed(() => props.categories.map((c) => ({ value: String(c.id), label: c.name })));
const categoryIdString = computed({
    get: () => (props.form.category_id ? String(props.form.category_id) : ''),
    set: (value: string) => {
        props.form.category_id = value ? Number(value) : null;
    },
});

/** Équivalent JS approximatif de Str::slug() côté serveur — juste une suggestion, le
 * serveur reste la seule source de vérité sur l'unicité (StoreArticleRequest). */
function slugify(value: string): string {
    return value
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

const slugTouched = ref(props.form.slug !== '');
watch(
    () => props.form.title,
    (title) => {
        if (!slugTouched.value) props.form.slug = slugify(title);
    },
);
function onSlugInput() {
    slugTouched.value = true;
}

// --- Publication : trois modes présentationnels ramenés à un seul champ published_at ---
type PublishMode = 'draft' | 'now' | 'scheduled';
function initialMode(): PublishMode {
    if (!props.form.published_at) return 'draft';
    return new Date(props.form.published_at) > new Date() ? 'scheduled' : 'now';
}
const publishMode = ref<PublishMode>(initialMode());

function toDatetimeLocal(date: Date): string {
    const pad = (n: number) => String(n).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

watch(publishMode, (mode) => {
    if (mode === 'draft') {
        props.form.published_at = '';
    } else if (mode === 'now') {
        props.form.published_at = toDatetimeLocal(new Date());
    } else if (mode === 'scheduled' && !props.form.published_at) {
        props.form.published_at = toDatetimeLocal(new Date(Date.now() + 24 * 60 * 60 * 1000));
    }
});

// --- Image de couverture : aperçu local si nouveau fichier, sinon l'image existante ---
const coverPreview = ref<string | null>(props.existingCoverUrl ?? null);
const coverRemoved = ref(false);

function onCoverChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    props.form.cover_image = file;
    coverRemoved.value = false;
    props.form.remove_cover_image = false;

    if (file) {
        coverPreview.value = URL.createObjectURL(file);
    } else {
        coverPreview.value = props.existingCoverUrl ?? null;
    }
}

function removeCover() {
    props.form.cover_image = null;
    props.form.remove_cover_image = true;
    coverPreview.value = null;
    coverRemoved.value = true;
}
</script>

<template>
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <Card class="space-y-5">
                <Input
                    id="title"
                    v-model="form.title"
                    data-error-anchor="title"
                    label="Titre"
                    :error="form.errors.title"
                />

                <div data-error-anchor="slug">
                    <label for="slug" class="font-sans text-sm text-ink-soft">Slug (URL)</label>
                    <input
                        id="slug"
                        v-model="form.slug"
                        type="text"
                        class="mt-2 w-full border border-sand-300 bg-paper px-4 py-2.5 font-mono text-sm text-ink focus-visible:border-gold-500"
                        @input="onSlugInput"
                    />
                    <p class="mt-1 font-sans text-xs text-ink-soft">
                        /actualites/<span class="text-ink">{{ form.slug || '…' }}</span>
                    </p>
                    <p v-if="form.errors.slug" class="mt-1 font-sans text-xs text-danger">
                        {{ form.errors.slug }}
                    </p>
                </div>

                <div data-error-anchor="excerpt">
                    <label for="excerpt" class="font-sans text-sm text-ink-soft">
                        Extrait <span class="text-ink-soft/70">(affiché dans les listes)</span>
                    </label>
                    <textarea
                        id="excerpt"
                        v-model="form.excerpt"
                        rows="3"
                        maxlength="500"
                        class="mt-2 w-full border border-sand-300 bg-paper px-4 py-2.5 font-sans text-sm text-ink focus-visible:border-gold-500"
                    />
                    <p v-if="form.errors.excerpt" class="mt-1 font-sans text-xs text-danger">
                        {{ form.errors.excerpt }}
                    </p>
                </div>
            </Card>

            <Card data-error-anchor="body">
                <label class="font-sans text-sm text-ink-soft">Contenu de l'article</label>
                <div class="mt-2">
                    <RichTextEditor v-model="form.body" />
                </div>
                <p v-if="form.errors.body" class="mt-1 font-sans text-xs text-danger">
                    {{ form.errors.body }}
                </p>
            </Card>

            <Card class="space-y-5">
                <h3 class="font-display text-lg text-ink">Référencement (SEO)</h3>
                <Input
                    id="seo_title"
                    v-model="form.seo_title"
                    data-error-anchor="seo_title"
                    label="Titre SEO"
                    placeholder="Laisser vide pour réutiliser le titre de l'article"
                    :error="form.errors.seo_title"
                />
                <div data-error-anchor="seo_description">
                    <label for="seo_description" class="font-sans text-sm text-ink-soft"
                        >Description SEO</label
                    >
                    <textarea
                        id="seo_description"
                        v-model="form.seo_description"
                        rows="2"
                        maxlength="170"
                        placeholder="Laisser vide pour réutiliser l'extrait"
                        class="mt-2 w-full border border-sand-300 bg-paper px-4 py-2.5 font-sans text-sm text-ink focus-visible:border-gold-500"
                    />
                    <p class="mt-1 font-sans text-xs text-ink-soft">
                        {{ (form.seo_description ?? '').length }}/170
                    </p>
                </div>
            </Card>
        </div>

        <div class="space-y-6">
            <Card data-error-anchor="published_at">
                <h3 class="font-display text-lg text-ink">Publication</h3>
                <div class="mt-4 space-y-2">
                    <label class="flex items-center gap-2 font-sans text-sm text-ink">
                        <input v-model="publishMode" type="radio" value="draft" name="publish_mode" />
                        Brouillon
                    </label>
                    <label class="flex items-center gap-2 font-sans text-sm text-ink">
                        <input v-model="publishMode" type="radio" value="now" name="publish_mode" />
                        Publier maintenant
                    </label>
                    <label class="flex items-center gap-2 font-sans text-sm text-ink">
                        <input v-model="publishMode" type="radio" value="scheduled" name="publish_mode" />
                        Programmer
                    </label>
                </div>
                <input
                    v-if="publishMode === 'scheduled'"
                    v-model="form.published_at"
                    type="datetime-local"
                    class="mt-3 w-full border border-sand-300 bg-paper px-4 py-2.5 font-sans text-sm text-ink focus-visible:border-gold-500"
                />
                <p v-if="form.errors.published_at" class="mt-1 font-sans text-xs text-danger">
                    {{ form.errors.published_at }}
                </p>
            </Card>

            <Card data-error-anchor="category_id">
                <label for="category" class="font-sans text-sm text-ink-soft">Catégorie</label>
                <Select
                    id="category"
                    v-model="categoryIdString"
                    :options="categoryOptions"
                    placeholder="Sélectionnez"
                />
                <p v-if="form.errors.category_id" class="mt-1 font-sans text-xs text-danger">
                    {{ form.errors.category_id }}
                </p>
            </Card>

            <Card data-error-anchor="cover_image">
                <label class="font-sans text-sm text-ink-soft">Image de couverture</label>
                <div class="mt-3 aspect-video overflow-hidden border border-sand-300 bg-sand-100">
                    <img v-if="coverPreview" :src="coverPreview" alt="" class="size-full object-cover" />
                    <div
                        v-else
                        class="flex size-full items-center justify-center font-sans text-xs text-ink-soft"
                    >
                        Aucune image
                    </div>
                </div>
                <input
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    class="mt-3 w-full font-sans text-xs text-ink-soft file:mr-3 file:border-0 file:bg-sand-200 file:px-3 file:py-2 file:font-sans file:text-xs file:text-ink"
                    @change="onCoverChange"
                />
                <button
                    v-if="coverPreview"
                    type="button"
                    class="mt-2 font-sans text-xs text-danger underline"
                    @click="removeCover"
                >
                    Retirer l'image
                </button>
                <p v-if="form.errors.cover_image" class="mt-1 font-sans text-xs text-danger">
                    {{ form.errors.cover_image }}
                </p>
            </Card>
        </div>
    </div>
</template>
