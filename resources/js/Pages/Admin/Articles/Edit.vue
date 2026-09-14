<script setup lang="ts">
import { computed } from 'vue';
import { Head, router, setLayoutProps, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ArticleForm, { type ArticleFormData } from '@/Components/admin/ArticleForm.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import FormErrorSummary from '@/Components/ui/FormErrorSummary.vue';
import { articleStatus } from '@/lib/status';
import { ARTICLE_FIELD_LABELS } from '@/lib/field-labels';
import { focusFirstError } from '@/lib/form-errors';
import { confirmAction } from '@/lib/confirm';
import { useUnsavedChanges } from '@/Composables/useUnsavedChanges';
import { SquareArrowOutUpRight, Trash2 } from '@lucide/vue';

defineOptions({ layout: AdminLayout });

const props = defineProps<{
    article: {
        id: number;
        categoryId: number;
        title: string;
        slug: string;
        excerpt: string | null;
        body: string | null;
        coverImageUrl: string | null;
        seoTitle: string | null;
        seoDescription: string | null;
        publishedAt: string | null;
        status: string;
    };
    categories: { id: number; name: string }[];
}>();

setLayoutProps({ title: props.article.title });

const form = useForm<ArticleFormData>({
    category_id: props.article.categoryId,
    title: props.article.title,
    slug: props.article.slug,
    excerpt: props.article.excerpt ?? '',
    body: props.article.body ?? '',
    cover_image: null,
    remove_cover_image: false,
    seo_title: props.article.seoTitle ?? '',
    seo_description: props.article.seoDescription ?? '',
    published_at: props.article.publishedAt ?? '',
});

const isDirty = computed(() => form.isDirty);
const { allowLeave } = useUnsavedChanges(isDirty);

function submit() {
    form.patch(`/admin/actualites/${props.article.id}`, {
        preserveScroll: true,
        // Le succès est annoncé par le message flashé du serveur (qui précise si l'article est
        // en ligne, en brouillon ou programmé) ; cf. lib/inertia-feedback.ts.
        onSuccess: () => form.defaults(),
        onError: (errors) => focusFirstError(errors),
    });
}

async function destroy() {
    const confirmed = await confirmAction({
        title: `Supprimer l'article « ${props.article.title || 'Sans titre'} » ?`,
        description:
            "L'article sera retiré du site public et de la liste des actualités. Cette suppression est définitive : le texte, l'image de couverture et l'adresse de la page ne pourront pas être récupérés.",
        confirmLabel: 'Supprimer définitivement',
        cancelLabel: 'Conserver',
        tone: 'danger',
    });
    if (!confirmed) return;

    // Le formulaire peut être « sale » (modifications en cours) : on ne veut pas que le
    // garde-fou demande en plus de confirmer qu'on quitte la page qu'on vient de supprimer.
    allowLeave();
    router.delete(`/admin/actualites/${props.article.id}`);
}
</script>

<template>
    <Head :title="article.title" />

    <form class="space-y-6" @submit.prevent="submit">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="font-display text-2xl text-ink">{{ article.title || 'Sans titre' }}</h2>
                <Badge :tone="articleStatus(article.status).tone">{{
                    articleStatus(article.status).label
                }}</Badge>
                <!-- Signal permanent et discret : l'utilisateur sait à tout moment s'il lui
                     reste quelque chose à enregistrer, sans avoir à tenter de quitter la page
                     pour le découvrir. -->
                <span v-if="form.isDirty" class="font-sans text-xs text-warning">
                    Modifications non enregistrées
                </span>
            </div>
            <div class="flex flex-wrap gap-3">
                <a
                    v-if="article.status === 'published'"
                    :href="`/actualites/${article.slug}`"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-2 border border-sand-300 px-5 py-2.5 font-sans text-sm text-ink transition-colors hover:border-ink"
                >
                    <SquareArrowOutUpRight class="size-4" aria-hidden="true" />
                    Voir
                </a>
                <button
                    type="button"
                    class="inline-flex items-center gap-2 border border-sand-300 px-5 py-2.5 font-sans text-sm text-danger transition-colors hover:border-danger"
                    @click="destroy"
                >
                    <Trash2 class="size-4" aria-hidden="true" />
                    Supprimer
                </button>
                <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Enregistrement…' : 'Enregistrer' }}
                </Button>
            </div>
        </div>

        <FormErrorSummary
            :errors="form.errors"
            :labels="ARTICLE_FIELD_LABELS"
            title="Les modifications n'ont pas pu être enregistrées"
        />

        <p
            v-if="form.progress"
            class="border border-sand-200 bg-sand-50 px-4 py-3 font-sans text-sm text-ink-soft"
            role="status"
        >
            Envoi de l'image en cours… {{ form.progress.percentage ?? 0 }} %
            <span class="mt-2 block h-px w-full bg-sand-200">
                <span
                    class="block h-px bg-gold-500 transition-[width] duration-200"
                    :style="{ width: `${form.progress.percentage ?? 0}%` }"
                />
            </span>
        </p>

        <ArticleForm :form="form" :categories="categories" :existing-cover-url="article.coverImageUrl" />
    </form>
</template>
