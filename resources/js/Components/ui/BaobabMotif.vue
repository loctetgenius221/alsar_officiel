<script setup lang="ts">
/**
 * Motif décoratif baobab (CLAUDE.md §3.3), en filigrane dans un coin de section — usage
 * sobre et ponctuel (une touche par page tout au plus, jamais en bandeau répété). Léger
 * parallax vertical au scroll (CLAUDE.md §3.5 : "subtil, pas d'excès"), coupé sous
 * prefers-reduced-motion via useParallax. Purement décoratif (alt vide + aria-hidden) :
 * ne doit jamais porter d'information. Le conteneur parent doit être `relative
 * overflow-hidden` pour contenir le débord.
 */
import { ref } from 'vue';
import { useParallax } from '@/Composables/useParallax';
import baobabPattern from '../../../../public/images/pattern-baobab.svg';

withDefaults(
    defineProps<{
        side?: 'left' | 'right';
        vertical?: 'top' | 'bottom';
        /** Retourne le motif horizontalement, utile pour varier l'orientation d'une page à l'autre. */
        mirror?: boolean;
    }>(),
    {
        side: 'right',
        vertical: 'bottom',
        mirror: false,
    },
);

const patternRef = ref<HTMLImageElement | null>(null);
useParallax(patternRef, 6);
</script>

<template>
    <img
        ref="patternRef"
        :src="baobabPattern"
        alt=""
        aria-hidden="true"
        class="pointer-events-none absolute z-1 w-64 opacity-20 sm:w-80 sm:opacity-25 lg:w-[28rem] lg:opacity-30"
        :class="[
            vertical === 'top' ? 'top-0' : 'bottom-0',
            side === 'left' ? '-left-16 sm:-left-24 lg:-left-32' : '-right-16 sm:-right-24 lg:-right-32',
            mirror ? 'scale-x-[-1]' : '',
        ]"
    />
</template>
