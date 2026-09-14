<script setup lang="ts">
/**
 * Séparateur graphique discret, inspiration textile sénégalaise (trait fin, abstrait,
 * non figuratif) — CLAUDE.md §3.3. À utiliser en petites touches localisées (pas en
 * bandeau pleine largeur) : à valider avec le client avant tout usage plus étendu.
 * Se "dessine" au scroll (stroke-dashoffset) la première fois qu'il entre à l'écran —
 * signature de marque discrète, cf. CLAUDE.md §3.5. Sans JS ou sous prefers-reduced-motion,
 * le trait reste simplement visible d'emblée (pas de contenu qui dépend de l'animation).
 */
import { onMounted, ref } from 'vue';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { useReducedMotion } from '@/Composables/useReducedMotion';

gsap.registerPlugin(ScrollTrigger);

withDefaults(
    defineProps<{
        align?: 'center' | 'left';
        size?: 'sm' | 'base' | 'md';
    }>(),
    {
        align: 'center',
        size: 'md',
    },
);

const pathEl = ref<SVGPathElement | null>(null);
const { prefersReducedMotion } = useReducedMotion();

onMounted(() => {
    if (!pathEl.value || prefersReducedMotion.value) return;

    const length = pathEl.value.getTotalLength();
    gsap.set(pathEl.value, { strokeDasharray: length, strokeDashoffset: length });
    gsap.to(pathEl.value, {
        strokeDashoffset: 0,
        duration: 1.1,
        ease: 'power2.out',
        scrollTrigger: {
            trigger: pathEl.value,
            start: 'top 92%',
            once: true,
        },
    });
});
</script>

<template>
    <div class="flex py-1" :class="align === 'left' ? 'justify-start' : 'justify-center'" aria-hidden="true">
        <svg
            viewBox="0 0 240 16"
            class="h-auto text-gold-500/40"
            :class="{ 'w-16': size === 'sm', 'w-32': size === 'base', 'w-60': size === 'md' }"
        >
            <path
                ref="pathEl"
                d="M0 8 L20 2 L40 14 L60 2 L80 14 L100 2 L120 14 L140 2 L160 14 L180 2 L200 14 L220 2 L240 8"
                fill="none"
                stroke="currentColor"
                stroke-width="1"
            />
        </svg>
    </div>
</template>
