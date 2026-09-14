<script setup lang="ts">
/**
 * Frise verticale réutilisable (Vision & Histoire du parti, parcours du Président).
 * Version volontairement simple (pas de pin/scroll horizontal, cf. CLAUDE.md §9) : une
 * amélioration possible plus tard, pas un prérequis.
 * Photo d'accompagnement optionnelle : uniquement desktop (sticky à côté de la frise),
 * masquée sur mobile plutôt que rétrécie — pas la place pour un traitement adapté en
 * dessous de la frise sur petit écran.
 */
import { ref } from 'vue';
import { useScrollReveal } from '@/Composables/useScrollReveal';
import PhotoFrame from '@/Components/ui/PhotoFrame.vue';

withDefaults(
    defineProps<{
        items: { date: string; text: string }[];
        image?: string;
        imageAlt?: string;
    }>(),
    {
        image: undefined,
        imageAlt: '',
    },
);

const el = ref<HTMLElement | null>(null);
useScrollReveal(el, { stagger: 0.1 });
</script>

<template>
    <div class="grid gap-10 lg:grid-cols-12 lg:items-start">
        <ol ref="el" class="border-l border-sand-300" :class="image ? 'lg:col-span-7' : 'lg:col-span-12'">
            <li v-for="item in items" :key="item.date" class="relative pb-10 pl-8 last:pb-0">
                <span
                    class="absolute top-1.5 -left-1.25 size-2.5 rounded-full bg-gold-500"
                    aria-hidden="true"
                />
                <p class="font-display text-lg text-gold-600">{{ item.date }}</p>
                <p class="mt-1 max-w-lg font-sans text-sm text-ink-soft sm:text-base">{{ item.text }}</p>
            </li>
        </ol>

        <div v-if="image" class="hidden lg:sticky lg:top-28 lg:col-span-5 lg:col-start-8 lg:block">
            <PhotoFrame :src="image" :alt="imageAlt" ratio="portrait" parallax />
        </div>
    </div>
</template>
