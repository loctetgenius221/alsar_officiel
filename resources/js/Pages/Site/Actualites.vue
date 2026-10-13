<script setup lang="ts">
/**
 * Liste des actualités (CLAUDE.md §4, page 14) — filtrable par catégorie. Données réelles via
 * Site\ArticleController (CMS Actualités du back-office), plus de contenu en dur.
 *
 * Cette page était volontairement en texte seul, pour ne pas dupliquer le teaser magazine de
 * l'accueil (CLAUDE.md §14 : une variante de mise en page par page, jamais un copier-coller).
 * L'intention reste la bonne ; le résultat ne l'était pas : sur un parti dont tout le capital
 * visuel est la photo de terrain, seize lignes de titres se lisaient comme un index d'archives.
 *
 * Le compromis retenu garde le RYTHME d'un index de presse — dense, chronologique, scannable —
 * et lui rend son ancrage visuel : une vignette par entrée, pas une grille de cartes. Seul le
 * premier article prend le format large (« à la une »). On reste donc loin de la grille 7/4 de
 * NewsTeaser.
 *
 * Les couvertures et les chapôs étaient déjà transmis par le serveur (Article::toPublicArray) :
 * cette page ne les affichait simplement pas.
 */
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import SiteLayout from '@/Layouts/SiteLayout.vue';
import PageHeader from '@/Components/site/PageHeader.vue';
import { Newspaper } from '@lucide/vue';
import type { PublicArticle } from '@/lib/articles';
import { useScrollReveal } from '@/Composables/useScrollReveal';
import { useBrokenImages } from '@/Composables/useBrokenImages';

defineOptions({ layout: SiteLayout });

const props = defineProps<{ articles: PublicArticle[]; categories: string[] }>();

const categoryOptions = computed(() => ['Toutes', ...props.categories]);
const activeCategory = ref('Toutes');

const filtered = computed(() =>
    activeCategory.value === 'Toutes'
        ? props.articles
        : props.articles.filter((article) => article.category === activeCategory.value),
);

// L'article « à la une » est le plus récent de la sélection courante : changer de catégorie
// remet donc en tête l'article le plus récent de CETTE catégorie, et non un article figé qui
// n'y appartiendrait pas.
const featured = computed(() => filtered.value[0] ?? null);
const rest = computed(() => filtered.value.slice(1));

// Une couverture peut avoir disparu du disque depuis sa mise en ligne : on retombe alors sur
// l'emplacement sobre plutôt que sur l'icône d'image cassée du navigateur.
const { isBroken, markBroken } = useBrokenImages();

const listRef = ref<HTMLElement | null>(null);
useScrollReveal(listRef, { stagger: 0.06 });

function countLabel(total: number): string {
    return total <= 1 ? `${total} article` : `${total} articles`;
}
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

    <div class="mx-auto max-w-6xl px-6 pb-24 sm:px-10">
        <div
            class="flex flex-wrap items-baseline justify-between gap-x-8 gap-y-3 border-b border-sand-200 py-6"
        >
            <div class="flex flex-wrap gap-x-8 gap-y-3">
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
            <!-- `aria-live` : le filtrage se fait sans rechargement, le compte est la seule
                 confirmation qu'un lecteur d'écran reçoit après un clic sur une catégorie. -->
            <p class="font-sans text-xs tracking-wide text-ink-soft uppercase" aria-live="polite">
                {{ countLabel(filtered.length) }}
            </p>
        </div>

        <template v-if="featured">
            <!-- À la une : seul article en format large, pour donner un point d'entrée au fil
                 plutôt qu'une suite d'entrées de poids identique. -->
            <Link :href="`/actualites/${featured.slug}`" class="group mt-10 grid gap-8 lg:grid-cols-12">
                <div class="overflow-hidden rounded-2xl bg-sand-100 lg:col-span-7">
                    <!-- alt="" volontaire : le titre du lien décrit déjà la destination, un alt
                         descriptif la ferait annoncer deux fois (WCAG, technique H2). -->
                    <img
                        v-if="featured.image && !isBroken(featured.slug)"
                        :src="featured.image"
                        alt=""
                        class="aspect-16/9 w-full object-cover transition-transform duration-500 ease-out group-hover:scale-105"
                        loading="eager"
                        @error="markBroken(featured.slug)"
                    />
                    <div v-else class="flex aspect-16/9 items-center justify-center" aria-hidden="true">
                        <Newspaper class="size-10 text-sand-400" />
                    </div>
                </div>

                <div class="flex flex-col justify-center lg:col-span-5">
                    <p class="font-sans text-xs tracking-wide text-gold-700 uppercase">
                        À la une · {{ featured.category }}
                    </p>
                    <h2 class="mt-3 font-display text-display-4 text-ink group-hover:underline">
                        {{ featured.title }}
                    </h2>
                    <p
                        v-if="featured.excerpt"
                        class="mt-4 font-sans text-base leading-(--text-body--line-height) text-ink-soft"
                    >
                        {{ featured.excerpt }}
                    </p>
                    <p class="mt-5 font-sans text-sm text-ink-soft">{{ featured.date }}</p>
                </div>
            </Link>

            <ul
                v-if="rest.length"
                ref="listRef"
                class="mt-6 divide-y divide-sand-200 border-t border-sand-200"
            >
                <li v-for="article in rest" :key="article.slug">
                    <Link
                        :href="`/actualites/${article.slug}`"
                        class="group grid gap-4 py-7 sm:grid-cols-12 sm:gap-6"
                    >
                        <div class="overflow-hidden rounded-xl bg-sand-100 sm:col-span-3">
                            <img
                                v-if="article.image && !isBroken(article.slug)"
                                :src="article.image"
                                alt=""
                                class="aspect-4/3 w-full object-cover transition-transform duration-500 ease-out group-hover:scale-105"
                                loading="lazy"
                                @error="markBroken(article.slug)"
                            />
                            <div
                                v-else
                                class="flex aspect-4/3 items-center justify-center"
                                aria-hidden="true"
                            >
                                <Newspaper class="size-6 text-sand-400" />
                            </div>
                        </div>

                        <div class="sm:col-span-9">
                            <p class="font-sans text-xs tracking-wide text-gold-700 uppercase">
                                {{ article.category }} · {{ article.date }}
                            </p>
                            <h3 class="mt-2 font-display text-xl text-ink group-hover:underline sm:text-2xl">
                                {{ article.title }}
                            </h3>
                            <!-- Chapô tronqué à deux lignes : il sert à départager deux titres
                                 proches, pas à être lu en entier depuis la liste. -->
                            <p
                                v-if="article.excerpt"
                                class="mt-2 line-clamp-2 font-sans text-sm leading-(--text-body--line-height) text-ink-soft"
                            >
                                {{ article.excerpt }}
                            </p>
                        </div>
                    </Link>
                </li>
            </ul>
        </template>

        <p v-else class="py-16 text-center font-sans text-sm text-ink-soft">
            Aucune actualité dans cette catégorie pour l'instant.
        </p>
    </div>
</template>
