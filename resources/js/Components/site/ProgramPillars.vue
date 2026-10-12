<script setup lang="ts">
/**
 * 3 des 6 vrais axes du programme (CLAUDE.md §7.1), en damier texte / photo.
 *
 * Mise en page : une colonne par axe, et l'ordre carte/photo s'inverse sur les colonnes
 * impaires — d'où l'alternance en quinconce de la maquette de référence. Les photos gardent un
 * rapport fixe et la carte de texte occupe la hauteur restante (`flex-1`), ce qui aligne
 * automatiquement les trois colonnes quelle que soit la longueur des textes.
 *
 * Sur mobile les photos passent en 16:9 : empilées sous chaque carte, trois photos en 7:6
 * transformaient la section en défilement interminable.
 *
 * L'espace vide en haut des cartes n'est pas un oubli : le numéro d'axe reste en tête et le
 * couple titre + texte est poussé en pied (`justify-between`), exactement comme sur la
 * maquette. C'est ce décalage qui donne son rythme à la grille.
 *
 * Remplace le traitement précédent en liste numérotée avec panneau photo suivant le curseur :
 * les photos étant désormais visibles en permanence, ce survol n'avait plus d'objet (et avec
 * lui disparaissent useFinePointer, gsap.quickTo et le panneau flottant).
 *
 * Note pour la suite : une variante « texte/photo alternés » avait été écartée par le client
 * par le passé. Celle-ci est revenue sur demande explicite, maquette à l'appui — la différence
 * tient au format : grille de cartes compactes, et non bandes pleine largeur.
 *
 * Écart assumé vis-à-vis de CLAUDE.md §3 (qui proscrit les « cartes rounded-2xl avec ombre
 * portée identique partout ») : la maquette demandée repose sur des cartes arrondies. On en
 * garde le principe mais dans le registre ALSAR — rayon modéré, filet sable d'un demi-pixel,
 * AUCUNE ombre portée, titres en serif d'affichage et accent or au survol.
 */
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import MotifDivider from '@/Components/ui/MotifDivider.vue';
import { ArrowRight } from '@lucide/vue';
import { useScrollReveal } from '@/Composables/useScrollReveal';
import { useParallax } from '@/Composables/useParallax';
import { resolveIcon } from '@/lib/icon-registry';
import baobabPattern from '../../../../public/images/pattern-baobab.svg';
import type { PageImageField } from '@/types/inertia';

interface Pillar {
    id: string;
    icon: string;
    title: string;
    text: string;
    image: PageImageField;
}

export interface ProgramPillarsSection {
    kicker: string;
    title: string;
    /** Facultatif : ajouté au schéma après coup, donc absent du contenu déjà en base. */
    intro?: string;
    cta_label: string;
    pillars: Pillar[];
}

const props = defineProps<{ section: ProgramPillarsSection }>();

const gridRef = ref<HTMLElement | null>(null);
useScrollReveal(gridRef, { stagger: 0.12 });

// Léger effet de dérive sur le motif décoratif de fond, plus discret que sur une photo
// (CLAUDE.md §3.5 : "parallax subtil ... pas d'excès").
const patternRef = ref<HTMLImageElement | null>(null);
useParallax(patternRef, 6);

// `id` correspond aux ancres des sections sur /programme (Programme.vue) : chaque carte
// pointe directement vers le détail de son axe plutôt que de se contenter d'illustrer.
const pillars = computed(() =>
    props.section.pillars.map((pillar, i) => ({
        ...pillar,
        index: String(i + 1).padStart(2, '0'),
    })),
);
</script>

<template>
    <section class="relative mx-auto max-w-6xl px-6 py-20 sm:px-10">
        <img
            ref="patternRef"
            :src="baobabPattern"
            alt=""
            aria-hidden="true"
            class="pointer-events-none absolute bottom-0 -left-125 -z-1 scale-x-[-1] opacity-30"
        />

        <!-- En-tête strictement identique aux autres sections de l'accueil (RegionsTeaser,
             ImpactStats, PresidentTeaser) : filet à gauche, kicker or, titre en text-display-3,
             chapô en max-w-lg. La maquette de référence centrait ce bloc ; on garde la
             convention du site, qui prime sur ce point. -->
        <MotifDivider align="left" size="base" />
        <p class="mt-2 font-sans text-sm tracking-wide text-gold-700 uppercase">{{ section.kicker }}</p>
        <h2 class="mt-4 max-w-2xl font-display text-display-3 text-ink">
            {{ section.title }}
        </h2>
        <p
            v-if="section.intro"
            class="mt-5 max-w-lg font-sans text-base leading-(--text-body--line-height) text-ink-soft"
        >
            {{ section.intro }}
        </p>

        <div ref="gridRef" class="mt-14 grid gap-4 md:grid-cols-3 md:gap-5">
            <Link
                v-for="(pillar, i) in pillars"
                :key="pillar.id"
                :href="`/programme#${pillar.id}`"
                class="group flex flex-col gap-4 md:gap-5"
                :class="i % 2 === 1 ? 'md:flex-col-reverse' : ''"
            >
                <!-- `flex-1` : la carte absorbe la hauteur que la photo ne prend pas, donc les
                     trois colonnes se terminent au même niveau sans hauteur figée. -->
                <article
                    class="flex flex-1 flex-col justify-between rounded-xl border border-sand-200 bg-paper-dim/40 p-6 transition-colors duration-300 group-hover:border-gold-500/60"
                >
                    <p class="flex items-center gap-2 font-sans text-sm text-ink-soft/70">
                        <component
                            :is="resolveIcon(pillar.icon)"
                            class="size-4 shrink-0 text-gold-600"
                            aria-hidden="true"
                        />
                        <span>(Axe {{ pillar.index }})</span>
                    </p>

                    <div class="mt-12">
                        <h3
                            class="font-display text-display-4 text-ink decoration-gold-500/60 underline-offset-4 group-hover:underline"
                        >
                            {{ pillar.title }}
                        </h3>
                        <p class="mt-2 font-sans text-sm leading-(--text-body--line-height) text-ink-soft">
                            {{ pillar.text }}
                        </p>
                    </div>
                </article>

                <figure v-if="pillar.image.url" class="overflow-hidden rounded-xl">
                    <img
                        :src="pillar.image.url"
                        :alt="pillar.image.alt"
                        loading="lazy"
                        class="aspect-16/9 w-full object-cover transition-transform duration-500 group-hover:scale-[1.04] md:aspect-7/6"
                    />
                </figure>
            </Link>
        </div>

        <div class="mt-12 flex justify-center">
            <Link
                href="/programme"
                class="group relative inline-flex items-center gap-2 pb-1 font-sans text-sm font-medium text-ink after:absolute after:inset-x-0 after:bottom-0 after:h-px after:origin-left after:scale-x-0 after:bg-gold-500 after:transition-transform after:duration-300 hover:after:scale-x-100"
            >
                {{ section.cta_label }}
                <ArrowRight
                    class="size-4 transition-transform group-hover:translate-x-1"
                    aria-hidden="true"
                />
            </Link>
        </div>
    </section>
</template>
