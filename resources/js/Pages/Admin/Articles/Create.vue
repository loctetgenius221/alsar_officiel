<script setup lang="ts">
import { Head, setLayoutProps, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ArticleForm, { type ArticleFormData } from '@/Components/admin/ArticleForm.vue';
import Button from '@/Components/ui/Button.vue';

defineOptions({ layout: AdminLayout });
setLayoutProps({ title: 'Nouvel article' });

defineProps<{ categories: { id: number; name: string }[] }>();

const form = useForm<ArticleFormData>({
    category_id: null,
    title: '',
    slug: '',
    excerpt: '',
    body: '',
    cover_image: null,
    remove_cover_image: false,
    seo_title: '',
    seo_description: '',
    published_at: '',
});

function submit() {
    form.post('/admin/actualites');
}
</script>

<template>
    <Head title="Nouvel article" />

    <form class="space-y-6" @submit.prevent="submit">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-display text-2xl text-ink">Nouvel article</h2>
            <div class="flex gap-3">
                <Button href="/admin/actualites" variant="secondary">Annuler</Button>
                <Button type="submit" :disabled="form.processing">Enregistrer</Button>
            </div>
        </div>

        <ArticleForm :form="form" :categories="categories" />
    </form>
</template>
