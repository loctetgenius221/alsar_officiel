<script setup lang="ts">
/**
 * Hero plein cadre, carrousel photo en arrière-plan (CLAUDE.md §4 : "Hero photo/vidéo +
 * accroche + CTA adhésion"). Le double scrim (bas + gauche) garantit un contraste texte
 * suffisant quelle que soit la photo affichée, sans recourir au glassmorphism.
 *
 * `min(92vh, 46rem)` plutôt qu'un `92vh` pur : sur un écran très haut (grand moniteur,
 * tablette en portrait) `92vh` dépasse vite 1100-1300px. Le plafond de 46rem (736px) garde un
 * hero plein cadre sur les résolutions courantes (la plupart des écrans d'ordinateur ont une
 * hauteur utile bien inférieure) sans dérailler sur les proportions inhabituelles.
 *
 * Sous `lg` (tablette portrait comprise, cf. retour client), le contenu est en plus centré
 * verticalement (`content-center`) plutôt que calé en bas (`content-end`) : sur ces largeurs,
 * le plafond laisse presque toujours de l'espace résiduel dans la boîte, et le caler en bas
 * revient à empiler tout cet espace en un grand vide au-dessus du texte plutôt que de le
 * répartir. À `lg` et au-delà (écrans larges, proportion plus proche du 16:9), le calage en
 * bas d'origine reste le rendu voulu.
 */
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import ImageCarousel from '@/Components/ui/ImageCarousel.vue';
import MagneticButton from '@/Components/ui/MagneticButton.vue';
import { useGsapReveal } from '@/Composables/useGsapReveal';
import { useScrollReveal } from '@/Composables/useScrollReveal';
import type { PageImageField } from '@/types/inertia';

export interface HeroSection {
    kicker: string;
    headline: string;
    body: string;
    cta_primary_label: string;
    cta_secondary_label: string;
    slides: { image: PageImageField }[];
}

const props = defineProps<{ section: HeroSection }>();

const headline = ref<HTMLElement | null>(null);
useGsapReveal(headline);

const kicker = ref<HTMLElement | null>(null);
useScrollReveal(kicker, { y: 12, duration: 0.6, start: 'top 100%' });

const body = ref<HTMLElement | null>(null);
useScrollReveal(body, { stagger: 0.15, delay: 0.5, start: 'top 100%' });

const slides = computed(() =>
    props.section.slides
        .filter((slide) => slide.image.url)
        .map((slide) => ({ src: slide.image.url as string, alt: slide.image.alt })),
);
</script>

<template>
    <section class="relative min-h-[min(92vh,46rem)] overflow-hidden bg-ink">
        <div class="absolute inset-0">
            <ImageCarousel :slides="slides" />
        </div>

        <!-- Scrim double (bas + gauche) : le texte reste lisible quelle que soit la photo active -->
        <div class="absolute inset-0 bg-linear-to-t from-ink/90 via-ink/45 to-transparent" />
        <div class="absolute inset-0 bg-linear-to-r from-ink/75 via-ink/10 to-transparent" />

        <div
            class="relative z-10 mx-auto grid min-h-[min(92vh,46rem)] max-w-6xl content-center gap-10 px-6 pt-28 pb-20 sm:px-10 sm:pb-24 lg:grid-cols-12 lg:content-end lg:pb-28"
        >
            <div class="lg:col-span-8">
                <p
                    ref="kicker"
                    class="mb-4 font-sans text-xs font-bold tracking-wide text-gold-500 uppercase sm:mb-6 sm:text-sm"
                >
                    {{ section.kicker }}
                </p>
                <h1 ref="headline" class="font-display text-display-1 text-paper">
                    {{ section.headline }}
                </h1>
                <div ref="body">
                    <p
                        class="mt-6 max-w-md font-sans text-sm leading-(--text-body--line-height) text-sand-100 sm:mt-8 sm:text-base"
                    >
                        {{ section.body }}
                    </p>
                    <div
                        class="mt-8 flex flex-col gap-3 sm:mt-10 sm:flex-row sm:flex-wrap sm:items-center sm:gap-4"
                    >
                        <MagneticButton href="/rejoignez-nous">{{ section.cta_primary_label }}</MagneticButton>
                        <Link
                            href="/programme"
                            class="inline-flex items-center gap-2 border border-paper/50 px-6 py-3 font-sans text-sm font-medium text-paper transition-colors duration-300 hover:border-paper hover:bg-paper/10"
                        >
                            {{ section.cta_secondary_label }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
