<script setup lang="ts">
/**
 * Liste des actualités (CLAUDE.md §4, page 14) — filtrable par catégorie. Traitement
 * volontairement texte seul (liste dense façon index de presse), différent du teaser
 * magazine avec photo de NewsTeaser sur l'accueil. Données réelles via
 * Site\ArticleController (CMS Actualités du back-office), plus de contenu en dur.
 */
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import SiteLayout from '@/Layouts/SiteLayout.vue';
import PageHeader from '@/Components/site/PageHeader.vue';
import type { PublicArticle } from '@/lib/articles';
import { useScrollReveal } from '@/Composables/useScrollReveal';

defineOptions({ layout: SiteLayout });

const props = defineProps<{ articles: PublicArticle[]; categories: string[] }>();

const categoryOptions = computed(() => ['Toutes', ...props.categories]);
const activeCategory = ref('Toutes');

const filtered = computed(() =>
    activeCategory.value === 'Toutes'
        ? props.articles
        : props.articles.filter((article) => article.category === activeCategory.value),
);

const listRef = ref<HTMLElement | null>(null);
useScrollReveal(listRef, { stagger: 0.06 });
</script>

<template>
    <Head title="Actualités">
        <meta
            name="description"
            content="Vie du parti, actions à Podor, vie nationale et solidarité — le fil des communiqués et des faits marquants d'ALSAR."
        />
    </Head>

    <PageHeader
        kicker="Actualités"
        title="La vie du parti, au jour le jour"
        intro="Vie du parti, actions à Podor, vie nationale et solidarité — le fil des communiqués et des faits marquants d'ALSAR."
        image="/images/slider-img-2.webp"
        image-alt="ALSAR sur le terrain"
        image-position="center"
    />

    <div class="mx-auto max-w-6xl px-6 sm:px-10">
        <div class="flex flex-wrap gap-x-8 gap-y-3 border-b border-sand-200 py-6">
            <button
                v-for="category in categoryOptions"
                :key="category"
                type="button"
                class="font-sans text-sm transition-colors"
                :class="
                    activeCategory === category
                        ? 'border-b-2 border-gold-500 pb-1 font-medium text-ink'
                        : 'text-ink-soft hover:text-ink'
                "
                @click="activeCategory = category"
            >
                {{ category }}
            </button>
        </div>

        <ul v-if="filtered.length" ref="listRef" class="divide-y divide-sand-200">
            <li v-for="article in filtered" :key="article.slug">
                <Link
                    :href="`/actualites/${article.slug}`"
                    class="group grid gap-2 py-8 sm:grid-cols-12 sm:items-baseline sm:gap-6"
                >
                    <p class="font-sans text-xs tracking-wide text-gold-700 uppercase sm:col-span-3">
                        {{ article.category }} · {{ article.date }}
                    </p>
                    <h2 class="font-display text-xl text-ink group-hover:underline sm:col-span-9 sm:text-2xl">
                        {{ article.title }}
                    </h2>
                </Link>
            </li>
        </ul>
        <p v-else class="py-16 text-center font-sans text-sm text-ink-soft">
            Aucune actualité dans cette catégorie pour l'instant.
        </p>
    </div>
</template>
