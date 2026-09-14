<script setup lang="ts">
/**
 * Emplacement photo réutilisable. Tant qu'aucun fichier réel n'est fourni (CLAUDE.md §3.4 :
 * uniquement des photos réelles du terrain, jamais de stock générique), affiche un fallback
 * éditorial sobre plutôt qu'une image générique.
 */
import { ref } from 'vue';
import { useParallax } from '@/Composables/useParallax';

const props = withDefaults(
    defineProps<{
        src?: string;
        alt: string;
        caption?: string;
        ratio?: 'portrait' | 'square';
        /** Léger parallax vertical au scroll (CLAUDE.md §3.5) — désactivé par défaut. */
        parallax?: boolean;
        /** Point d'ancrage du recadrage `object-cover` — utile quand le sujet n'est pas centré
         * dans la photo source (ex. portrait cadré à droite de l'image). */
        position?: 'center' | 'left' | 'right';
    }>(),
    {
        src: undefined,
        caption: undefined,
        ratio: 'portrait',
        parallax: false,
        position: 'center',
    },
);

const positionClass = {
    center: 'object-center',
    left: 'object-left',
    right: 'object-right',
}[props.position];

const imgEl = ref<HTMLImageElement | null>(null);
if (props.parallax) useParallax(imgEl);
</script>

<template>
    <figure class="relative">
        <div
            class="overflow-hidden rounded-2xl bg-ink"
            :class="ratio === 'portrait' ? 'aspect-4/5' : 'aspect-square'"
        >
            <img
                v-if="src"
                ref="imgEl"
                :src="src"
                :alt="alt"
                class="h-full w-full object-cover"
                :class="[positionClass, parallax ? 'scale-125' : '']"
                loading="lazy"
            />
            <div v-else class="flex h-full items-end p-6" role="img" :aria-label="alt">
                <p class="font-sans text-xs text-sand-300/80">{{ alt }} — photo à intégrer</p>
            </div>
        </div>
        <figcaption v-if="caption" class="mt-3 font-sans text-xs text-sand-600">{{ caption }}</figcaption>
    </figure>
</template>
