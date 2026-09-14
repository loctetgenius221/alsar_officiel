<script setup lang="ts">
/**
 * Liste des articles (CLAUDE.md §6 : CRUD articles). Même schéma que Members/Index.vue :
 * filtres poussés en query string, recherche débouncée, pagination serveur.
 */
import { Head, Link, router, setLayoutProps } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Badge from '@/Components/ui/Badge.vue';
import Input from '@/Components/ui/Input.vue';
import Select from '@/Components/ui/Select.vue';
import Button from '@/Components/ui/Button.vue';
import Pagination from '@/Components/admin/Pagination.vue';
import { articleStatus } from '@/lib/status';
import { Trash2 } from '@lucide/vue';

defineOptions({ layout: AdminLayout });
setLayoutProps({ title: 'Actualités' });

const props = defineProps<{
    articles: {
        data: {
            id: number;
            title: string;
            slug: string;
            category: string;
            status: string;
            publishedAt: string | null;
            updatedAt: string;
        }[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: { search?: string; category?: string; status?: string };
    categories: { id: number; name: string }[];
}>();

const search = ref(props.filters.search ?? '');
const category = ref(props.filters.category ?? '');
const status = ref(props.filters.status ?? '');

function applyFilters() {
    router.get(
        '/admin/actualites',
        {
            search: search.value || undefined,
            category: category.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

const debouncedApply = useDebounceFn(applyFilters, 350);
watch(search, debouncedApply);
watch([category, status], applyFilters);

const categoryOptions = props.categories.map((c) => ({ value: String(c.id), label: c.name }));
const statusOptions = [
    { value: 'draft', label: 'Brouillon' },
    { value: 'scheduled', label: 'Programmé' },
    { value: 'published', label: 'Publié' },
];

function destroy(article: { id: number; title: string }) {
    if (!confirm(`Supprimer l'article « ${article.title} » ?`)) return;
    router.delete(`/admin/actualites/${article.id}`, { preserveScroll: true });
}
</script>

<template>
    <Head title="Actualités" />

    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-display text-2xl text-ink">Actualités</h2>
            <Button href="/admin/actualites/nouveau">Nouvel article</Button>
        </div>

        <Card>
            <div class="grid gap-4 sm:grid-cols-3">
                <Input id="search" v-model="search" label="Recherche" placeholder="Titre..." />
                <Select id="category" v-model="category" label="Catégorie" :options="categoryOptions" placeholder="Toutes" />
                <Select id="status" v-model="status" label="Statut" :options="statusOptions" placeholder="Tous" />
            </div>
        </Card>

        <Card :padded="false">
            <div v-if="articles.data.length" class="overflow-x-auto">
                <table class="w-full text-left font-sans text-sm">
                    <thead>
                        <tr class="border-b border-sand-200 text-xs text-ink-soft uppercase">
                            <th class="px-6 py-3 font-medium">Titre</th>
                            <th class="px-6 py-3 font-medium">Catégorie</th>
                            <th class="px-6 py-3 font-medium">Statut</th>
                            <th class="px-6 py-3 font-medium">Publication</th>
                            <th class="px-6 py-3 font-medium">Modifié le</th>
                            <th class="px-6 py-3 font-medium"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="article in articles.data"
                            :key="article.id"
                            class="border-b border-sand-100 last:border-0 hover:bg-sand-50"
                        >
                            <td class="px-6 py-3">
                                <Link
                                    :href="`/admin/actualites/${article.id}/modifier`"
                                    class="font-medium text-ink hover:underline"
                                >
                                    {{ article.title }}
                                </Link>
                                <p class="font-mono text-xs text-ink-soft">/actualites/{{ article.slug }}</p>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-ink-soft">{{ article.category }}</td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                <Badge :tone="articleStatus(article.status).tone">
                                    {{ articleStatus(article.status).label }}
                                </Badge>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-ink-soft">{{ article.publishedAt ?? '—' }}</td>
                            <td class="px-6 py-3 whitespace-nowrap text-ink-soft">{{ article.updatedAt }}</td>
                            <td class="px-6 py-3 text-right whitespace-nowrap">
                                <button
                                    type="button"
                                    class="p-1.5 text-ink-soft transition-colors hover:text-danger"
                                    aria-label="Supprimer"
                                    @click="destroy(article)"
                                >
                                    <Trash2 class="size-4" aria-hidden="true" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-else class="px-6 py-8 text-center font-sans text-sm text-ink-soft">
                Aucun article ne correspond à ces filtres.
            </p>

            <Pagination :links="articles.links" :from="articles.from" :to="articles.to" :total="articles.total" />
        </Card>
    </div>
</template>
