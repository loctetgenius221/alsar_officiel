<script setup lang="ts">
/**
 * Pendant vidéo de PhotoFrame : lit uniquement quand le panneau est visible à l'écran
 * (économie de ressources/données) et ne joue jamais si l'utilisateur préfère un motion
 * réduit — dans ce cas la première frame (poster) reste statique.
 *
 * Pas de parallax, pour la même raison que PhotoFrame : il imposait un agrandissement de la
 * source (`scale-125`), donc une perte de netteté et un recadrage subi.
 */
import { ref } from 'vue';
import { useIntersectionObserver } from '@vueuse/core';
import { useReducedMotion } from '@/Composables/useReducedMotion';

withDefaults(
    defineProps<{
        src?: string;
        poster?: string;
        alt: string;
        caption?: string;
        ratio?: 'portrait' | 'square' | 'video';
        rounded?: boolean;
    }>(),
    {
        src: undefined,
        poster: undefined,
        caption: undefined,
        ratio: 'portrait',
        rounded: false,
    },
);

const videoEl = ref<HTMLVideoElement | null>(null);
const { prefersReducedMotion } = useReducedMotion();

useIntersectionObserver(videoEl, ([entry]) => {
    if (!videoEl.value || prefersReducedMotion.value) return;
    if (entry?.isIntersecting) {
        videoEl.value.play().catch(() => {});
    } else {
        videoEl.value.pause();
    }
});
</script>

<template>
    <figure class="relative">
        <div
            class="overflow-hidden bg-ink"
            :class="{
                'aspect-4/5': ratio === 'portrait',
                'aspect-square': ratio === 'square',
                'aspect-video': ratio === 'video',
                'rounded-2xl shadow-xl shadow-ink/25': rounded,
            }"
        >
            <video
                v-if="src"
                ref="videoEl"
                :src="src"
                :poster="poster"
                muted
                loop
                playsinline
                preload="metadata"
                class="h-full w-full object-cover"
                :aria-label="alt"
            />
            <div v-else class="flex h-full items-end p-6" role="img" :aria-label="alt">
                <p class="font-sans text-xs text-sand-300/80">{{ alt }} — vidéo à intégrer</p>
            </div>
        </div>
        <figcaption v-if="caption" class="mt-3 font-sans text-xs text-sand-600">{{ caption }}</figcaption>
    </figure>
</template>
