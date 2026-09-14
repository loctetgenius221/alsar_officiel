<script setup lang="ts">
import { Head, router, setLayoutProps, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ArticleForm, { type ArticleFormData } from '@/Components/admin/ArticleForm.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import { articleStatus } from '@/lib/status';
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

function submit() {
    form.patch(`/admin/actualites/${props.article.id}`, { preserveScroll: true });
}

function destroy() {
    if (!confirm(`Supprimer l'article « ${props.article.title} » ?`)) return;
    router.delete(`/admin/actualites/${props.article.id}`);
}
</script>

<template>
    <Head :title="article.title" />

    <form class="space-y-6" @submit.prevent="submit">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <h2 class="font-display text-2xl text-ink">{{ article.title || 'Sans titre' }}</h2>
                <Badge :tone="articleStatus(article.status).tone">{{ articleStatus(article.status).label }}</Badge>
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
                <Button type="submit" :disabled="form.processing">Enregistrer</Button>
            </div>
        </div>

        <ArticleForm :form="form" :categories="categories" :existing-cover-url="article.coverImageUrl" />
    </form>
</template>
