<script setup lang="ts">
/**
 * Liste de barres horizontales — une catégorie par ligne, longueur = magnitude, valeur
 * directement affichée au bout de la barre (jamais de survol obligatoire pour lire une
 * valeur, cf. skill dataviz/interaction : "tooltips enhance, they never gate"). `tone` fixe
 * la teinte de chaque ligne : 'gold' pour une série neutre (région, etc.), ou un ton de
 * statut réservé (success/warning/danger/neutral) quand la ligne encode un état — jamais les
 * deux dans le même graphique (cf. dataviz/color-formula, "the collision rule").
 */
import { type Component } from 'vue';

export type BarTone = 'gold' | 'success' | 'warning' | 'danger' | 'neutral';

export interface BarRow {
    key: string;
    label: string;
    value: number;
    tone?: BarTone;
    icon?: Component;
}

const props = withDefaults(
    defineProps<{
        rows: BarRow[];
        valueFormatter?: (value: number) => string;
        emptyText?: string;
    }>(),
    {
        valueFormatter: (value: number) => String(value),
        emptyText: 'Aucune donnée pour l’instant.',
    },
);

const TONE_CLASSES: Record<BarTone, { bar: string; text: string }> = {
    gold: { bar: 'bg-gold-600', text: 'text-gold-700' },
    success: { bar: 'bg-success', text: 'text-success' },
    warning: { bar: 'bg-warning', text: 'text-warning' },
    danger: { bar: 'bg-danger', text: 'text-danger' },
    neutral: { bar: 'bg-sand-400', text: 'text-sand-600' },
};

function toneClasses(tone: BarTone = 'gold') {
    return TONE_CLASSES[tone];
}

function widthPercent(value: number): number {
    const max = Math.max(...props.rows.map((r) => r.value), 1);
    return max > 0 ? (value / max) * 100 : 0;
}
</script>

<template>
    <ul v-if="rows.length" class="space-y-3.5">
        <li
            v-for="row in rows"
            :key="row.key"
            class="-mx-2 rounded-md px-2 py-1 transition-colors duration-150 hover:bg-sand-50"
        >
            <div class="flex items-center justify-between gap-3 font-sans text-sm">
                <span class="flex min-w-0 items-center gap-2 text-ink">
                    <component
                        :is="row.icon"
                        v-if="row.icon"
                        class="size-4 shrink-0"
                        :class="toneClasses(row.tone).text"
                        aria-hidden="true"
                    />
                    <span class="truncate">{{ row.label }}</span>
                </span>
                <span class="shrink-0 font-sans text-sm tabular-nums text-ink-soft">{{ valueFormatter(row.value) }}</span>
            </div>
            <div class="mt-1.5 h-2 w-full overflow-hidden rounded-full bg-sand-100">
                <div
                    class="h-full rounded-r-[4px] transition-[width] duration-500 ease-out"
                    :class="toneClasses(row.tone).bar"
                    :style="{ width: `${widthPercent(row.value)}%` }"
                />
            </div>
        </li>
    </ul>
    <p v-else class="font-sans text-sm text-ink-soft">{{ emptyText }}</p>
</template>
