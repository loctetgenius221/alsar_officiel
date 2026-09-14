<script setup lang="ts">
/**
 * CTA principal "aimanté" (CLAUDE.md §3.5 : "magnetic buttons légers sur les CTA
 * principaux") — utilisé pour Adhérer/Rejoindre ALSAR. Au survol (souris fine
 * uniquement), le bouton suit légèrement le curseur, une fine lueur balaie le fond et
 * la flèche glisse — sobre, pas de glow néon ni de dégradé arc-en-ciel (CLAUDE.md §3).
 * Entièrement désactivé sous prefers-reduced-motion : reste un CTA doré statique avec
 * juste le changement de couleur au survol.
 */
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import gsap from 'gsap';
import { ArrowRight } from '@lucide/vue';
import { useReducedMotion } from '@/Composables/useReducedMotion';

defineProps<{ href: string }>();

const { prefersReducedMotion } = useReducedMotion();

const wrapEl = ref<HTMLElement | null>(null);
let quickX: ReturnType<typeof gsap.quickTo> | null = null;
let quickY: ReturnType<typeof gsap.quickTo> | null = null;

function ensureQuick() {
    if (!wrapEl.value || quickX) return;
    quickX = gsap.quickTo(wrapEl.value, 'x', { duration: 0.5, ease: 'power3.out' });
    quickY = gsap.quickTo(wrapEl.value, 'y', { duration: 0.5, ease: 'power3.out' });
}

function onMouseMove(event: MouseEvent) {
    if (prefersReducedMotion.value || !wrapEl.value) return;
    ensureQuick();
    const rect = wrapEl.value.getBoundingClientRect();
    const relX = event.clientX - (rect.left + rect.width / 2);
    const relY = event.clientY - (rect.top + rect.height / 2);
    quickX?.(gsap.utils.clamp(-8, 8, relX * 0.35));
    quickY?.(gsap.utils.clamp(-8, 8, relY * 0.35));
}

function onMouseLeave() {
    if (!wrapEl.value) return;
    ensureQuick();
    quickX?.(0);
    quickY?.(0);
}
</script>

<template>
    <span ref="wrapEl" class="inline-block" @mousemove="onMouseMove" @mouseleave="onMouseLeave">
        <Link
            :href="href"
            class="group relative inline-flex items-center gap-2 overflow-hidden bg-gold-500 px-6 py-3 font-sans text-sm font-medium text-ink shadow-sm transition-[background-color,box-shadow] duration-300 hover:bg-gold-600 hover:shadow-lg hover:shadow-gold-500/20"
        >
            <span
                aria-hidden="true"
                class="pointer-events-none absolute inset-0 -translate-x-full bg-linear-to-r from-transparent via-white/30 to-transparent transition-transform duration-700 ease-out group-hover:translate-x-full"
            />
            <span class="relative"><slot /></span>
            <ArrowRight
                class="relative size-4 transition-transform duration-300 ease-out group-hover:translate-x-1"
                aria-hidden="true"
            />
        </Link>
    </span>
</template>
