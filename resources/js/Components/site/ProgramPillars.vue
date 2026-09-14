<script setup lang="ts">
/**
 * 3 des 6 vrais axes du programme (CLAUDE.md §7.1). Traité en liste éditoriale numérotée,
 * pas en 3 cartes identiques icône+titre+texte (pattern explicitement proscrit, §3).
 * Icônes Lucide (cohérence §2.1) en touche discrète à côté du numéro — pas de gros pictogramme
 * dans un cercle coloré, on reste sur le principe de la liste, pas de la carte.
 *
 * Survol : un panneau photo suit le curseur (pattern "hover follow" des sites d'agence
 * édito/Awwwards), réservé aux pointeurs fins avec vrai survol (souris/trackpad) via
 * useFinePointer et désactivé sous prefers-reduced-motion — un mouvement calé sur le
 * curseur peut gêner, cf. CLAUDE.md §9. Les photos sont les mêmes vraies photos "terrain"
 * réutilisées ailleurs sur le site (aucune n'illustre littéralement un axe précis : c'est
 * un accompagnement atmosphérique, pas une preuve documentaire — cf. politique photo §3.4).
 *
 * Revenu à ce traitement après retour du client sur la variante "texte/photo alternés en
 * permanence" essayée entre-temps (rendu qui ne convenait pas) — seul `cta_label` est
 * désormais lu depuis le contenu éditable plutôt que codé en dur (correction sans impact
 * visuel : le texte affiché reste identique).
 */
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import gsap from 'gsap';
import MotifDivider from '@/Components/ui/MotifDivider.vue';
import { ArrowRight } from '@lucide/vue';
import { useScrollReveal } from '@/Composables/useScrollReveal';
import { useParallax } from '@/Composables/useParallax';
import { useFinePointer } from '@/Composables/useFinePointer';
import { useReducedMotion } from '@/Composables/useReducedMotion';
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
    cta_label: string;
    pillars: Pillar[];
}

const props = defineProps<{ section: ProgramPillarsSection }>();

const listRef = ref<HTMLElement | null>(null);
useScrollReveal(listRef, { stagger: 0.12 });

// Léger effet de dérive sur le motif décoratif de fond, plus discret que sur une photo
// (CLAUDE.md §3.5 : "parallax subtil ... pas d'excès").
const patternRef = ref<HTMLImageElement | null>(null);
useParallax(patternRef, 6);

// `id` correspond aux ancres des sections sur /programme (Programme.vue) : chaque ligne
// pointe directement vers le détail de son axe plutôt que de se contenter d'illustrer.
const pillars = computed(() =>
    props.section.pillars.map((pillar, i) => ({
        ...pillar,
        index: String(i + 1).padStart(2, '0'),
    })),
);

const { hasFinePointer } = useFinePointer();
const { prefersReducedMotion } = useReducedMotion();

const hoverPanel = ref<HTMLElement | null>(null);
const hoverImage = ref<string | null>(null);
const hoverVisible = ref(false);

let quickX: ReturnType<typeof gsap.quickTo> | null = null;
let quickY: ReturnType<typeof gsap.quickTo> | null = null;

function ensureQuick() {
    if (!hoverPanel.value || quickX) return;
    quickX = gsap.quickTo(hoverPanel.value, 'x', { duration: 0.5, ease: 'power3.out' });
    quickY = gsap.quickTo(hoverPanel.value, 'y', { duration: 0.5, ease: 'power3.out' });
}

function canFollow() {
    return hasFinePointer.value && !prefersReducedMotion.value;
}

function onListMouseMove(event: MouseEvent) {
    if (!canFollow()) return;
    ensureQuick();
    quickX?.(event.clientX + 28);
    quickY?.(event.clientY - 150);
}

function onRowEnter(image: PageImageField) {
    if (!canFollow() || !image.url) return;
    hoverImage.value = image.url;
    hoverVisible.value = true;
}

function onListLeave() {
    hoverVisible.value = false;
}
</script>

<template>
    <section class="relative mx-auto max-w-6xl px-6 py-20 sm:px-10">
        <img
            ref="patternRef"
            :src="baobabPattern"
            alt="Pattern Baobab"
            class="absolute bottom-0 -left-125 z-1 scale-x-[-1] opacity-30"
        />

        <div
            v-if="hasFinePointer && !prefersReducedMotion"
            ref="hoverPanel"
            class="pointer-events-none fixed top-0 left-0 z-40 aspect-4/5 w-52 overflow-hidden rounded-2xl opacity-0 shadow-xl shadow-ink/25 transition-opacity duration-300 sm:w-64"
            :class="hoverVisible ? 'opacity-100' : 'opacity-0'"
            aria-hidden="true"
        >
            <img v-if="hoverImage" :src="hoverImage" alt="" class="h-full w-full object-cover" />
        </div>

        <MotifDivider align="left" size="base" />
        <p class="mt-2 font-sans text-sm tracking-wide text-gold-700 uppercase">{{ section.kicker }}</p>
        <h2 class="mt-4 max-w-2xl font-display text-display-3 text-ink">
            {{ section.title }}
        </h2>

        <ul
            ref="listRef"
            class="mt-14 divide-y divide-sand-200 border-t border-sand-200"
            @mousemove="onListMouseMove"
            @mouseleave="onListLeave"
        >
            <li v-for="(pillar, i) in pillars" :key="pillar.id">
                <Link
                    :href="`/programme#${pillar.id}`"
                    class="group grid gap-4 py-10 lg:grid-cols-12 lg:items-center"
                    :class="i % 2 === 1 ? 'lg:text-right' : ''"
                    @mouseenter="onRowEnter(pillar.image)"
                >
                    <div
                        class="flex items-center gap-3 lg:col-span-2"
                        :class="i % 2 === 1 ? 'lg:flex-row-reverse' : ''"
                    >
                        <component
                            :is="resolveIcon(pillar.icon)"
                            class="size-5 shrink-0 text-gold-600 transition-transform duration-300 group-hover:scale-110"
                            aria-hidden="true"
                        />
                        <p>
                            <span class="font-display text-4xl text-gold-500">{{ pillar.index }}</span>
                            <span
                                class="font-display text-lg text-gold-300/70 transition-colors duration-300 group-hover:text-gold-500"
                                >/06</span
                            >
                        </p>
                    </div>
                    <h3
                        class="font-display text-2xl text-ink group-hover:underline lg:col-span-4"
                        :class="i % 2 === 1 ? 'lg:col-start-9' : 'lg:col-start-3'"
                    >
                        {{ pillar.title }}
                    </h3>
                    <p
                        class="font-sans text-base leading-(--text-body--line-height) text-ink-soft lg:col-span-4"
                        :class="i % 2 === 1 ? 'lg:col-start-5' : 'lg:col-start-7'"
                    >
                        {{ pillar.text }}
                    </p>
                </Link>
            </li>
        </ul>

        <div class="mt-10 flex justify-center">
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
