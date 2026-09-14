<script setup lang="ts">
/**
 * Barre unique empilée horizontale — répartition part-à-tout d'une petite série ordonnée
 * (ex. paliers de cotisation, du moins au plus engagé). Teinte ordinale à 3 crans
 * (gold-500 → gold-700 → gold-800), espacement validé avec le script du skill dataviz
 * plutôt qu'une palette catégorielle arbitraire — l'ordre des paliers porte du sens
 * (montant croissant), une rampe à une seule teinte le montre directement dans la couleur.
 * Légende toujours affichée (règle : jamais de lecture par couleur seule) et valeurs
 * accessibles au clavier/lecteur d'écran sans survol (title natif) — le survol ne fait
 * qu'enrichir, jamais gate.
 */
export interface RatioSegment {
    key: string;
    label: string;
    value: number;
    hint?: string;
}

const props = withDefaults(
    defineProps<{
        segments: RatioSegment[];
        valueFormatter?: (value: number) => string;
    }>(),
    {
        valueFormatter: (value: number) => String(value),
    },
);

const RAMP = ['bg-gold-500', 'bg-gold-700', 'bg-gold-800'];

const total = () => props.segments.reduce((sum, s) => sum + s.value, 0);
</script>

<template>
    <div>
        <div class="flex h-3 w-full overflow-hidden rounded-full bg-sand-100" role="img" aria-label="Répartition par palier">
            <template v-if="total() > 0">
                <div
                    v-for="(segment, i) in segments"
                    :key="segment.key"
                    class="h-full first:rounded-l-full last:rounded-r-full"
                    :class="[RAMP[i % RAMP.length], i > 0 ? 'ml-0.5' : '']"
                    :style="{ width: `${(segment.value / total()) * 100}%` }"
                    :title="`${segment.label} — ${valueFormatter(segment.value)}${segment.hint ? ` (${segment.hint})` : ''}`"
                />
            </template>
            <div v-else class="h-full w-full rounded-full bg-sand-200" />
        </div>

        <ul class="mt-4 grid gap-x-6 gap-y-2 sm:grid-cols-3">
            <li v-for="(segment, i) in segments" :key="segment.key" class="flex items-center gap-2 font-sans text-sm">
                <span class="size-2.5 shrink-0 rounded-full" :class="RAMP[i % RAMP.length]" aria-hidden="true" />
                <span class="min-w-0 truncate text-ink">{{ segment.label }}</span>
                <span class="ml-auto shrink-0 tabular-nums text-ink-soft">{{ valueFormatter(segment.value) }}</span>
            </li>
        </ul>
    </div>
</template>
