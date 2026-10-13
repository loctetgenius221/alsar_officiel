<script setup lang="ts">
/**
 * Carte réelle des 14 régions du Sénégal (tracés exacts, cf. lib/senegal-regions.ts).
 * Mode décoratif par défaut (teaser d'accueil, survol seulement). Passer `interactive`
 * pour la page /regions : régions cliquables et activables au clavier (Entrée/Espace),
 * émet `select` avec le nom de la région — aucune donnée par région n'est stockée ici,
 * c'est au composant parent de savoir quoi afficher (honnêteté des données, cf. CLAUDE.md).
 * Lisibilité : les régions sont en sable (`sand-200`), l'or est réservé à la région mise en
 * avant — le siège, ou celle que l'on vient de sélectionner sur /regions. Auparavant TOUTES
 * les régions étaient en or à 45 % d'opacité : la carte se confondait avec le fond papier
 * (#e6ddcc contre #faf7f1, une fois l'opacité appliquée il ne restait presque rien), et l'or
 * ne distinguait plus rien puisqu'il était partout. Une couleur d'accent qui sert à tout ne
 * signale plus rien.
 * Les régions apparaissent en fondu échelonné à l'entrée dans l'écran (chaque région
 * "s'allume" l'une après l'autre) — illustre la présence nationale sans distraire de la
 * lecture. Uniquement l'opacité anime (jamais la géométrie), pour ne jamais créer
 * d'interstices entre régions limitrophes. Coupé sous prefers-reduced-motion.
 * Halo pulsé en continu sur Dakar (siège du parti) — animation ambiante indépendante du
 * scroll, en CSS pur (comme BrandTicker) : tourne en permanence dès l'affichage, coupée
 * automatiquement par prefers-reduced-motion (règle globale dans app.css).
 */
import { onMounted, ref } from 'vue';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { SENEGAL_REGIONS } from '@/lib/senegal-regions';
import { useReducedMotion } from '@/Composables/useReducedMotion';

gsap.registerPlugin(ScrollTrigger);

const props = withDefaults(
    defineProps<{
        interactive?: boolean;
        selected?: string | null;
    }>(),
    {
        interactive: false,
        selected: null,
    },
);

const emit = defineEmits<{ select: [name: string] }>();

const HIGHLIGHTED_REGION = 'Dakar';

const { prefersReducedMotion } = useReducedMotion();

const svgGroup = ref<SVGGElement | null>(null);
const viewBox = ref('0 0 500 800');
const dakarCenter = ref<{ x: number; y: number } | null>(null);

onMounted(() => {
    const bbox = svgGroup.value?.getBBox();
    if (bbox) viewBox.value = `${bbox.x} ${bbox.y} ${bbox.width} ${bbox.height}`;

    const dakarIndex = SENEGAL_REGIONS.findIndex((region) => region.name === HIGHLIGHTED_REGION);
    const dakarPath = svgGroup.value?.children[dakarIndex] as SVGGraphicsElement | undefined;
    if (dakarPath) {
        const dakarBbox = dakarPath.getBBox();
        dakarCenter.value = { x: dakarBbox.x + dakarBbox.width / 2, y: dakarBbox.y + dakarBbox.height / 2 };
    }

    if (!svgGroup.value || prefersReducedMotion.value) return;

    gsap.from(svgGroup.value.children, {
        opacity: 0,
        duration: 0.5,
        stagger: 0.025,
        ease: 'power1.out',
        scrollTrigger: {
            trigger: svgGroup.value,
            start: 'top 85%',
            once: true,
        },
    });
});

function isActive(name: string) {
    return props.selected ? name === props.selected : name === HIGHLIGHTED_REGION;
}

function select(name: string) {
    if (props.interactive) emit('select', name);
}
</script>

<template>
    <svg :viewBox="viewBox" class="h-full w-full" role="img" aria-label="Carte des 14 régions du Sénégal">
        <g ref="svgGroup">
            <path
                v-for="region in SENEGAL_REGIONS"
                :key="region.name"
                :d="region.path"
                class="stroke-paper stroke-[1.5] transition-colors duration-200 outline-none"
                :class="[
                    isActive(region.name) ? 'fill-gold-500' : 'fill-sand-200 hover:fill-sand-300',
                    interactive ? 'cursor-pointer focus-visible:fill-terracotta-500' : '',
                ]"
                :tabindex="interactive ? 0 : -1"
                :role="interactive ? 'button' : undefined"
                :aria-label="interactive ? region.name : undefined"
                @click="select(region.name)"
                @keydown.enter="select(region.name)"
                @keydown.space.prevent="select(region.name)"
            >
                <title>{{ region.name }}</title>
            </path>
        </g>

        <g v-if="dakarCenter" class="pointer-events-none" aria-hidden="true">
            <!-- Pastille du siège : encre sur or, lisible. Un point or-600 sur une région
                 or-500 se confondait avec elle. -->
            <circle :cx="dakarCenter.x" :cy="dakarCenter.y" r="4" class="animate-ping-halo fill-gold-700" />
            <circle :cx="dakarCenter.x" :cy="dakarCenter.y" r="2.5" class="fill-ink" />
        </g>
    </svg>
</template>
