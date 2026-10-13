<script setup lang="ts">
/**
 * Les 3 actualités publiées les plus récentes (HomeController) — plus de contenu en dur,
 * cf. le nouveau CMS Actualités du back-office (Admin/Articles).
 */
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import MotifDivider from '@/Components/ui/MotifDivider.vue';
import { useScrollReveal } from '@/Composables/useScrollReveal';
import { useBrokenImages } from '@/Composables/useBrokenImages';
import { Newspaper } from '@lucide/vue';
import type { PublicArticle } from '@/lib/articles';

const props = defineProps<{ articles: PublicArticle[] }>();

// Une couverture peut avoir disparu du disque depuis sa mise en ligne : mieux vaut un
// emplacement sobre que l'icône d'image cassée du navigateur sur la page d'accueil.
const { isBroken, markBroken } = useBrokenImages();

const newsGrid = ref<HTMLElement | null>(null);
useScrollReveal(newsGrid, { stagger: 0.15 });

const [featured, ...secondary] = props.articles;
</script>

<template>
    <section v-if="featured" class="mx-auto max-w-6xl px-6 py-20 sm:px-10">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <!-- Filet motif : seule section de l'accueil qui en était dépourvue, elle
                     rompait la cadence des autres en-têtes (CLAUDE.md §3.3). Pas de kicker
                     au-dessus ici : il ferait doublon avec le titre, qui est déjà le nom
                     de la rubrique. -->
                <MotifDivider align="left" size="base" />
                <h2 class="mt-4 font-display text-display-3 text-ink">Actualités</h2>
            </div>
            <Link
                href="/actualites"
                class="relative pb-1 font-sans text-sm font-medium text-ink after:absolute after:inset-x-0 after:bottom-0 after:h-px after:origin-left after:scale-x-0 after:bg-gold-500 after:transition-transform after:duration-300 hover:after:scale-x-100"
            >
                Tout voir →
            </Link>
        </div>

        <div ref="newsGrid" class="mt-10 grid gap-8 lg:grid-cols-12">
            <Link :href="`/actualites/${featured.slug}`" class="group lg:col-span-7">
                <!-- alt="" volontaire : l'image accompagne un lien dont le titre (h3 ci-dessous)
                     décrit déjà la destination — la doubler d'un alt descriptif ferait annoncer
                     le même contenu deux fois aux lecteurs d'écran (WCAG, technique H2). -->
                <div class="aspect-16/10 overflow-hidden rounded-2xl bg-sand-100">
                    <img
                        v-if="featured.image && !isBroken(featured.slug)"
                        :src="featured.image"
                        alt=""
                        class="h-full w-full object-cover transition-transform duration-500 ease-out group-hover:scale-105"
                        loading="lazy"
                        @error="markBroken(featured.slug)"
                    />
                    <div v-else class="flex h-full items-center justify-center" aria-hidden="true">
                        <Newspaper class="size-10 text-sand-400" />
                    </div>
                </div>
                <p class="mt-5 font-sans text-xs tracking-wide text-gold-700 uppercase">
                    {{ featured.category }} · {{ featured.date }}
                </p>
                <h3 class="mt-2 font-display text-display-4 text-ink group-hover:underline">
                    {{ featured.title }}
                </h3>
            </Link>

            <div class="flex flex-col gap-8 lg:col-span-4 lg:col-start-9">
                <Link
                    v-for="item in secondary"
                    :key="item.slug"
                    :href="`/actualites/${item.slug}`"
                    class="group"
                >
                    <p class="font-sans text-xs tracking-wide text-gold-700 uppercase">
                        {{ item.category }} · {{ item.date }}
                    </p>
                    <h3 class="mt-2 font-display text-lg text-ink group-hover:underline">{{ item.title }}</h3>
                </Link>
            </div>
        </div>
    </section>
</template>
