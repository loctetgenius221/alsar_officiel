<script setup lang="ts">
/**
 * Graphique de tendance mensuelle (aire ou colonnes), une seule série — teinte séquentielle
 * unique (gold-600), pas de dégradé arc-en-ciel (CLAUDE.md §3.1). Construit en SVG "à la
 * main" (pas de librairie de charts) : gabarit de marks fixe (ligne 2px, points ≥8px avec
 * anneau de surface, colonnes ≤24px arrondies 4px), grille en traits fins, survol par
 * tranche de données avec infobulle — cf. skill dataviz (marks-and-anatomy / interaction).
 */
import { computed, ref } from 'vue';

interface Point {
    label: string;
    value: number;
}

const props = withDefaults(
    defineProps<{
        points: Point[];
        variant?: 'area' | 'bar';
        valueFormatter?: (value: number) => string;
    }>(),
    {
        variant: 'area',
        valueFormatter: (value: number) => String(value),
    },
);

const WIDTH = 560;
const HEIGHT = 200;
// Marge latérale suffisante pour que le libellé centré du premier/dernier mois ne déborde
// pas du viewBox (text-anchor="middle" sur le point exact du bord sinon coupé en deux).
const PAD_LEFT = 18;
const PAD_RIGHT = 18;
const PAD_TOP = 20;
const PAD_BOTTOM = 26;
const PLOT_W = WIDTH - PAD_LEFT - PAD_RIGHT;
const PLOT_H = HEIGHT - PAD_TOP - PAD_BOTTOM;
const BAR_WIDTH = 22; // <= 24px, mark spec

/** Arrondit le maximum à un palier "propre" (1/2/5 × 10^n) pour des graduations lisibles. */
function niceMax(value: number): number {
    if (value <= 0) return 1;
    const magnitude = 10 ** Math.floor(Math.log10(value));
    const normalized = value / magnitude;
    const step = normalized <= 1 ? 1 : normalized <= 2 ? 2 : normalized <= 5 ? 5 : 10;
    return step * magnitude;
}

const n = computed(() => props.points.length);
const maxValue = computed(() => niceMax(Math.max(...props.points.map((p) => p.value), 1)));
const gridSteps = computed(() => [0, maxValue.value / 2, maxValue.value]);
const slice = computed(() => (n.value > 0 ? PLOT_W / n.value : PLOT_W));

function xFor(i: number): number {
    // Ligne/aire : points aux bornes de tranche. Colonnes : centrées dans leur tranche.
    return props.variant === 'bar' ? PAD_LEFT + i * slice.value + slice.value / 2 : PAD_LEFT + i * (PLOT_W / Math.max(n.value - 1, 1));
}
function yFor(value: number): number {
    return PAD_TOP + PLOT_H - (value / maxValue.value) * PLOT_H;
}

const linePath = computed(() =>
    props.points.map((p, i) => `${i === 0 ? 'M' : 'L'} ${xFor(i).toFixed(1)} ${yFor(p.value).toFixed(1)}`).join(' '),
);
const areaPath = computed(() => {
    if (!props.points.length) return '';
    const base = (PAD_TOP + PLOT_H).toFixed(1);
    return `${linePath.value} L ${xFor(n.value - 1).toFixed(1)} ${base} L ${xFor(0).toFixed(1)} ${base} Z`;
});

const hovered = ref<number | null>(null);

const tooltipStyle = computed(() => {
    if (hovered.value === null) return {};
    const point = props.points[hovered.value];
    return {
        left: `${(xFor(hovered.value) / WIDTH) * 100}%`,
        top: `${(yFor(point.value) / HEIGHT) * 100}%`,
        // Poussée au-dessus du point (translateY négatif + 10px d'écart) et recentrée
        // (translateX -50%) — en style inline plutôt qu'en classes Tailwind arbitraires,
        // qui se sont révélées peu fiables ici pour un calc() composé avec un pourcentage.
        transform: 'translate(-50%, calc(-100% - 10px))',
    };
});
</script>

<template>
    <div class="relative">
        <svg :viewBox="`0 0 ${WIDTH} ${HEIGHT}`" class="w-full" role="img" aria-hidden="true">
            <g aria-hidden="true">
                <line
                    v-for="step in gridSteps"
                    :key="step"
                    :x1="PAD_LEFT"
                    :x2="WIDTH - PAD_RIGHT"
                    :y1="yFor(step)"
                    :y2="yFor(step)"
                    class="stroke-sand-200"
                    stroke-width="1"
                />
                <text
                    v-for="step in gridSteps"
                    :key="`t-${step}`"
                    :x="PAD_LEFT"
                    :y="yFor(step) - 5"
                    class="fill-sand-500 font-sans"
                    font-size="10"
                >
                    {{ valueFormatter(Math.round(step)) }}
                </text>
            </g>

            <template v-if="variant === 'area'">
                <path :d="areaPath" class="fill-gold-600/10" />
                <path :d="linePath" class="fill-none stroke-gold-600" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" />
                <line
                    v-if="hovered !== null"
                    :x1="xFor(hovered)"
                    :x2="xFor(hovered)"
                    :y1="PAD_TOP"
                    :y2="PAD_TOP + PLOT_H"
                    class="stroke-sand-300"
                    stroke-width="1"
                />
                <circle
                    v-for="(p, i) in points"
                    :key="`dot-${i}`"
                    :cx="xFor(i)"
                    :cy="yFor(p.value)"
                    :r="hovered === i ? 5 : 4"
                    class="fill-gold-600 stroke-paper transition-[r] duration-150"
                    stroke-width="2"
                />
            </template>

            <template v-else>
                <rect
                    v-for="(p, i) in points"
                    :key="`bar-${i}`"
                    :x="xFor(i) - BAR_WIDTH / 2"
                    :y="yFor(p.value)"
                    :width="BAR_WIDTH"
                    :height="Math.max(PAD_TOP + PLOT_H - yFor(p.value), 1)"
                    rx="4"
                    ry="4"
                    :class="hovered === i ? 'fill-gold-500' : 'fill-gold-600'"
                    class="transition-colors duration-150"
                />
            </template>

            <text
                v-for="(p, i) in points"
                :key="`x-${i}`"
                :x="xFor(i)"
                :y="HEIGHT - 6"
                text-anchor="middle"
                class="fill-ink-soft font-sans"
                font-size="11"
            >
                {{ p.label }}
            </text>

            <!-- Zones de survol : toute la tranche mensuelle, plus grande que le trait/la colonne -->
            <rect
                v-for="(_, i) in points"
                :key="`hit-${i}`"
                :x="PAD_LEFT + i * slice"
                :y="PAD_TOP"
                :width="slice"
                :height="PLOT_H"
                fill="transparent"
                class="cursor-crosshair"
                @pointerenter="hovered = i"
                @pointerleave="hovered = null"
            />
        </svg>

        <div
            v-if="hovered !== null"
            class="pointer-events-none absolute z-10 rounded bg-ink px-2.5 py-1.5 font-sans text-xs whitespace-nowrap text-paper shadow-lg"
            :style="tooltipStyle"
        >
            <p class="font-medium">{{ points[hovered].label }}</p>
            <p class="tabular-nums text-paper/80">{{ valueFormatter(points[hovered].value) }}</p>
        </div>

        <!-- Vue tableau accessible : les mêmes valeurs restent atteignables sans survol -->
        <table class="sr-only">
            <caption>Valeurs mensuelles</caption>
            <thead>
                <tr>
                    <th scope="col">Mois</th>
                    <th scope="col">Valeur</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="p in points" :key="p.label">
                    <td>{{ p.label }}</td>
                    <td>{{ valueFormatter(p.value) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
