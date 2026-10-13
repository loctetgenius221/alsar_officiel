<script setup lang="ts">
/**
 * Bandeau d'en-tête éditorial pour les pages intérieures. Photo de fond optionnelle
 * (`image`) : le header étant solide dès le chargement sur ces pages (cf. SiteLayout
 * `heroTransparent`), le padding-top compense sa hauteur fixe dans les deux cas.
 * `compact` : bandeau réduit pour les pages où le contenu qui suit doit capter l'attention
 * en priorité (adhésion, confirmation, vérification) — l'en-tête reste présent (contexte,
 * cohérence de marque) mais cède vite la place au formulaire/résultat.
 */
import { ref } from 'vue';
import MotifDivider from '@/Components/ui/MotifDivider.vue';
import { useScrollReveal } from '@/Composables/useScrollReveal';

const props = withDefaults(
    defineProps<{
        kicker: string;
        title: string;
        intro?: string;
        /** Photo de fond optionnelle (chemin public). Sans elle, bandeau sobre fond papier. */
        image?: string;
        imageAlt?: string;
        /** Ancrage vertical du recadrage `object-cover` — utile quand un visage sur la photo
         * n'est pas centré verticalement (ex. portrait cadré en haut de l'image). */
        imagePosition?: 'top' | 'center' | 'bottom';
        compact?: boolean;
    }>(),
    {
        intro: undefined,
        image: undefined,
        imageAlt: '',
        imagePosition: 'center',
        compact: false,
    },
);

const imagePositionClass = {
    top: 'object-top',
    center: 'object-center',
    bottom: 'object-bottom',
}[props.imagePosition];

const el = ref<HTMLElement | null>(null);
useScrollReveal(el, { stagger: 0.1, start: 'top 100%' });
</script>

<template>
    <header class="relative overflow-hidden" :class="image ? 'surface-ink' : ''">
        <template v-if="image">
            <div class="absolute inset-0">
                <img
                    :src="image"
                    :alt="imageAlt"
                    class="h-full w-full object-cover"
                    :class="imagePositionClass"
                    loading="eager"
                />
            </div>
            <div class="absolute inset-0 bg-linear-to-t from-ink/90 via-ink/55 to-ink/25" />
        </template>

        <div
            class="relative mx-auto max-w-6xl px-6 sm:px-10"
            :class="[
                compact ? 'pt-28 pb-8 sm:pt-32 sm:pb-10' : 'pt-32 pb-16 sm:pt-36',
                image && !compact ? 'pt-44 pb-20 sm:pt-52 sm:pb-24' : '',
                image && compact ? 'pt-32 pb-8 sm:pt-36 sm:pb-10' : '',
            ]"
        >
            <div ref="el" class="max-w-3xl">
                <MotifDivider align="left" size="base" />
                <p
                    class="mt-2 font-sans text-sm tracking-wide uppercase"
                    :class="image ? 'text-gold-500' : 'text-gold-700'"
                >
                    {{ kicker }}
                </p>
                <h1
                    class="mt-4 font-display"
                    :class="[
                        compact ? 'text-display-3' : 'text-display-2',
                        image ? 'text-paper' : 'text-ink',
                    ]"
                >
                    {{ title }}
                </h1>
                <p
                    v-if="intro"
                    class="max-w-xl font-sans text-base leading-(--text-body--line-height)"
                    :class="[compact ? 'mt-3' : 'mt-6', image ? 'text-sand-100' : 'text-ink-soft']"
                >
                    {{ intro }}
                </p>
            </div>
        </div>
    </header>
</template>
