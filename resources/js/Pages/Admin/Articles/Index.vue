<script setup lang="ts">
/**
 * Liste des articles (CLAUDE.md §6 : CRUD articles). Même schéma que Members/Index.vue :
 * filtres poussés en query string, recherche débouncée, pagination serveur.
 */
import { Head, Link, router, setLayoutProps } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Input from '@/Components/ui/Input.vue';
import Select from '@/Components/ui/Select.vue';
import Button from '@/Components/ui/Button.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import StatusBadge from '@/Components/admin/StatusBadge.vue';
import Pagination from '@/Components/admin/Pagination.vue';
import { confirmAction } from '@/lib/confirm';
import { Newspaper, Plus, Search, SearchX, Trash2 } from '@lucide/vue';

defineOptions({ layout: AdminLayout });
setLayoutProps({ title: 'Actualités' });

const props = defineProps<{
    articles: {
        data: {
            /** UUID public : jamais la clé primaire, cf. app/Support/HasPublicId.php. */
            publicId: string;
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

// Distingue « liste vide parce qu'on a filtré » de « liste vide parce qu'il n'y a rien » :
// la première appelle « élargissez votre recherche », la seconde « écrivez votre premier article ».
const hasFilters = computed(() => Boolean(search.value || category.value || status.value));

function resetFilters() {
    search.value = '';
    category.value = '';
    status.value = '';
}

const categoryOptions = props.categories.map((c) => ({ value: String(c.id), label: c.name }));
const statusOptions = [
    { value: 'draft', label: 'Brouillon' },
    { value: 'scheduled', label: 'Programmé' },
    { value: 'published', label: 'Publié' },
];

async function destroy(article: { publicId: string; title: string }) {
    // Suppression depuis une liste : le risque de se tromper de ligne est réel, donc le titre
    // exact de l'article concerné est repris dans la question (cf. lib/confirm.ts).
    const confirmed = await confirmAction({
        title: `Supprimer l'article « ${article.title || 'Sans titre'} » ?`,
        description:
            "Il sera retiré du site public immédiatement. Cette suppression est définitive : le texte et l'image de couverture ne pourront pas être récupérés.",
        confirmLabel: 'Supprimer définitivement',
        cancelLabel: 'Conserver',
        tone: 'danger',
    });
    if (!confirmed) return;

    router.delete(`/admin/actualites/${article.publicId}`, { preserveScroll: true });
}
</script>

<template>
    <Head title="Actualités" />

    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-display text-2xl text-ink">Actualités</h2>
            <Button href="/admin/actualites/nouveau">
                <Plus class="size-4" aria-hidden="true" />
                Nouvel article
            </Button>
        </div>

        <Card>
            <div class="grid gap-4 sm:grid-cols-3">
                <Input id="search" v-model="search" label="Recherche" placeholder="Titre..." :icon="Search" />
                <Select
                    id="category"
                    v-model="category"
                    label="Catégorie"
                    :options="categoryOptions"
                    placeholder="Toutes"
                />
                <Select
                    id="status"
                    v-model="status"
                    label="Statut"
                    :options="statusOptions"
                    placeholder="Tous"
                />
            </div>
        </Card>

        <Card :padded="false">
            <!-- `relative` : voir Users/Index.vue — sans lui, le `sr-only` de la colonne
                 d'actions sort de la zone défilante et fait déborder toute la page sur mobile. -->
            <div v-if="articles.data.length" class="relative overflow-x-auto">
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
                            :key="article.publicId"
                            class="border-b border-sand-100 last:border-0 hover:bg-sand-50"
                        >
                            <td class="px-6 py-3">
                                <Link
                                    :href="`/admin/actualites/${article.publicId}/modifier`"
                                    class="font-medium text-ink hover:underline"
                                >
                                    {{ article.title }}
                                </Link>
                                <p class="font-mono text-xs text-ink-soft">/actualites/{{ article.slug }}</p>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-ink-soft">{{ article.category }}</td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                <StatusBadge kind="article" :status="article.status" />
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-ink-soft">
                                {{ article.publishedAt ?? '—' }}
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-ink-soft">{{ article.updatedAt }}</td>
                            <td class="px-6 py-3 text-right whitespace-nowrap">
                                <!-- Bouton à icône seule : `aria-label` pour les lecteurs
                                     d'écran, `title` pour l'infobulle à la souris — sans quoi
                                     il faut cliquer pour savoir ce que fait la corbeille. -->
                                <button
                                    type="button"
                                    class="p-1.5 text-ink-soft transition-colors hover:text-danger"
                                    aria-label="Supprimer cet article"
                                    title="Supprimer cet article"
                                    @click="destroy(article)"
                                >
                                    <Trash2 class="size-4" aria-hidden="true" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Deux états vides bien distincts : « la recherche ne donne rien » et « il n'y a
                 rien du tout » n'appellent pas la même réaction de l'utilisateur. -->
            <EmptyState
                v-else-if="hasFilters"
                :icon="SearchX"
                title="Aucun article ne correspond à ces filtres"
                description="Essayez un autre mot-clé, ou remettez les filtres à zéro pour voir toutes les actualités."
            >
                <Button variant="secondary" @click="resetFilters">Réinitialiser les filtres</Button>
            </EmptyState>
            <EmptyState
                v-else
                :icon="Newspaper"
                title="Aucun article pour l'instant"
                description="Les actualités publiées ici apparaissent sur le site public, dans la rubrique Actualités."
            >
                <Button href="/admin/actualites/nouveau">
                    <Plus class="size-4" aria-hidden="true" />
                    Écrire le premier article
                </Button>
            </EmptyState>

            <Pagination
                :links="articles.links"
                :from="articles.from"
                :to="articles.to"
                :total="articles.total"
            />
        </Card>
    </div>
</template>
