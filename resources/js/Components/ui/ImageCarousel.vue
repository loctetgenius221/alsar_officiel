<script setup lang="ts">
/**
 * Carrousel plein cadre (crossfade + zoom lent façon "Ken Burns", barres de progression
 * animées façon story). Composant générique réutilisable (hero, médiathèque...).
 * Respecte prefers-reduced-motion : pas de défilement auto ni de zoom continu, navigation
 * manuelle uniquement (arrows + barres cliquables), toujours accessible au clavier.
 */
import { onBeforeUnmount, onMounted, ref } from 'vue';
import gsap from 'gsap';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { useReducedMotion } from '@/Composables/useReducedMotion';

const props = withDefaults(
    defineProps<{
        slides: { src: string; alt: string }[];
        intervalMs?: number;
    }>(),
    {
        intervalMs: 6000,
    },
);

const { prefersReducedMotion } = useReducedMotion();
const activeIndex = ref(0);
const barFills = ref<HTMLElement[]>([]);
let tween: gsap.core.Tween | null = null;

function playFrom(index: number) {
    tween?.kill();

    props.slides.forEach((_, i) => {
        const bar = barFills.value[i];
        if (!bar) return;
        gsap.set(bar, { scaleX: i < index ? 1 : 0 });
    });

    if (prefersReducedMotion.value) return;

    const bar = barFills.value[index];
    if (!bar) return;

    tween = gsap.to(bar, {
        scaleX: 1,
        duration: props.intervalMs / 1000,
        ease: 'none',
        onComplete: () => goTo((index + 1) % props.slides.length),
    });
}

function goTo(index: number) {
    activeIndex.value = index;
    playFrom(index);
}

function next() {
    goTo((activeIndex.value + 1) % props.slides.length);
}

function prev() {
    goTo((activeIndex.value - 1 + props.slides.length) % props.slides.length);
}

function pause() {
    tween?.pause();
}

function resume() {
    tween?.resume();
}

onMounted(() => playFrom(0));
onBeforeUnmount(() => tween?.kill());
</script>

<template>
    <div
        class="relative h-full w-full"
        role="region"
        aria-roledescription="carrousel"
        aria-label="Photos ALSAR"
        @mouseenter="pause"
        @mouseleave="resume"
        @focusin="pause"
        @focusout="resume"
    >
        <div
            v-for="(slide, i) in slides"
            :key="slide.src"
            class="absolute inset-0 overflow-hidden transition-opacity duration-1000 ease-in-out"
            :class="i === activeIndex ? 'opacity-100' : 'pointer-events-none opacity-0'"
            :aria-hidden="i !== activeIndex"
        >
            <img
                :src="slide.src"
                :alt="slide.alt"
                class="h-full w-full object-cover transition-transform ease-linear"
                :class="i === activeIndex && !prefersReducedMotion ? 'scale-110' : 'scale-100'"
                :style="{ transitionDuration: `${intervalMs + 500}ms` }"
                :loading="i === 0 ? 'eager' : 'lazy'"
                decoding="async"
            />
        </div>

        <button
            type="button"
            class="absolute top-1/2 left-4 z-20 -translate-y-1/2 p-2 text-paper/80 transition-colors hover:text-paper"
            aria-label="Photo précédente"
            @click="prev"
        >
            <ChevronLeft class="size-8" aria-hidden="true" />
        </button>
        <button
            type="button"
            class="absolute top-1/2 right-4 z-20 -translate-y-1/2 p-2 text-paper/80 transition-colors hover:text-paper"
            aria-label="Photo suivante"
            @click="next"
        >
            <ChevronRight class="size-8" aria-hidden="true" />
        </button>

        <div class="absolute inset-x-0 bottom-6 z-20 flex gap-2 px-6 sm:px-10 lg:bottom-10">
            <button
                v-for="(slide, i) in slides"
                :key="`bar-${slide.src}`"
                type="button"
                class="h-[3px] flex-1 overflow-hidden rounded-full bg-paper/25"
                :aria-label="`Aller à la photo ${i + 1}`"
                :aria-current="i === activeIndex"
                @click="goTo(i)"
            >
                <span ref="barFills" class="block h-full origin-left scale-x-0 bg-gold-500" />
            </button>
        </div>
    </div>
</template>
