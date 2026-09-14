<script setup lang="ts">
/**
 * Pile de photos en éventail (façon pile de tirages posée sur un bureau) avec flèches de
 * navigation — alternative à ImageCarousel pour un rendu plus "album souvenir", moins
 * institutionnel que le hero. Défile automatiquement (pause au survol/focus), coupé si
 * prefers-reduced-motion — dans ce cas seule la navigation manuelle reste active.
 * Flèches en `text-paper` : pensé pour un fond sombre (ex. JoinCta) — prévoir une variante
 * claire si réutilisé sur un fond papier.
 */
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { useReducedMotion } from '@/Composables/useReducedMotion';

const props = defineProps<{
    photos: { src: string; alt: string }[];
}>();

const { prefersReducedMotion } = useReducedMotion();
const activeIndex = ref(0);

const AUTOPLAY_MS = 4000;
let timer: ReturnType<typeof setInterval> | null = null;

function stopAutoplay() {
    if (timer) {
        clearInterval(timer);
        timer = null;
    }
}

function startAutoplay() {
    stopAutoplay();
    if (prefersReducedMotion.value) return;
    timer = setInterval(next, AUTOPLAY_MS);
}

const STACK_STYLES = [
    { rotate: -2, x: 0, y: 0, scale: 1 },
    { rotate: 6, x: 16, y: 10, scale: 0.96 },
    { rotate: -8, x: -14, y: 20, scale: 0.92 },
];

function stackPosition(i: number) {
    return (i - activeIndex.value + props.photos.length) % props.photos.length;
}

function styleFor(i: number) {
    const position = stackPosition(i);
    const visible = position < STACK_STYLES.length;
    const s = STACK_STYLES[Math.min(position, STACK_STYLES.length - 1)];
    return {
        transform: `translate(${s.x}px, ${s.y}px) rotate(${s.rotate}deg) scale(${s.scale})`,
        zIndex: String(props.photos.length - position),
        opacity: visible ? '1' : '0',
        transitionDuration: prefersReducedMotion.value ? '0ms' : '500ms',
    };
}

function next() {
    activeIndex.value = (activeIndex.value + 1) % props.photos.length;
    startAutoplay();
}

function prev() {
    activeIndex.value = (activeIndex.value - 1 + props.photos.length) % props.photos.length;
    startAutoplay();
}

onMounted(startAutoplay);
onBeforeUnmount(stopAutoplay);
</script>

<template>
    <div
        @mouseenter="stopAutoplay"
        @mouseleave="startAutoplay"
        @focusin="stopAutoplay"
        @focusout="startAutoplay"
    >
        <div class="relative mx-auto aspect-4/5 w-full max-w-55 sm:max-w-70 lg:max-w-sm">
            <div
                v-for="(photo, i) in photos"
                :key="photo.src"
                class="absolute inset-0 overflow-hidden rounded-2xl bg-ink-soft transition-all ease-out"
                :style="styleFor(i)"
            >
                <img :src="photo.src" :alt="photo.alt" class="h-full w-full object-cover" loading="lazy" />
            </div>

            <div
                class="absolute -top-4 -right-4 z-20 flex size-14 items-center justify-center rounded-full bg-paper p-2 ring-4 ring-ink"
                aria-hidden="true"
            >
                <img src="/images/logo-alsar.png" alt="" class="h-full w-full object-contain" />
            </div>
        </div>

        <div class="mt-6 flex justify-center gap-3">
            <button
                type="button"
                class="flex size-10 items-center justify-center border border-paper/30 text-paper transition-colors hover:border-paper/60"
                aria-label="Photo précédente"
                @click="prev"
            >
                <ChevronLeft class="size-5" aria-hidden="true" />
            </button>
            <button
                type="button"
                class="flex size-10 items-center justify-center border border-paper/30 text-paper transition-colors hover:border-paper/60"
                aria-label="Photo suivante"
                @click="next"
            >
                <ChevronRight class="size-5" aria-hidden="true" />
            </button>
        </div>
    </div>
</template>
