<script setup lang="ts">
/**
 * Page "Programme" (CLAUDE.md §4, pages 7-13), consolidée en une seule page à sections
 * ancrées — même principe que /le-parti et /le-president pour ce lot. Les 6 axes et leurs
 * engagements sont sourcés de l'audit §7 (Profession de foi). Le programme reste énoncé
 * sous forme de grands principes (pas de chiffrage/calendrier documenté) — l'audit le
 * signale lui-même, donc on ne l'invente pas ici.
 *
 * Traitement volontairement différent d'ProgramPillars.vue (icône/numéro toujours à
 * gauche, fonds alternés entre sections) plutôt qu'une répétition de l'alignement
 * alterné utilisé sur le teaser d'accueil.
 */
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import SiteLayout from '@/Layouts/SiteLayout.vue';
import PageHeader from '@/Components/site/PageHeader.vue';
import { useScrollReveal } from '@/Composables/useScrollReveal';
import { resolveIcon } from '@/lib/icon-registry';
import type { PageImageField } from '@/types/inertia';

defineOptions({ layout: SiteLayout });

interface Axis {
    id: string;
    icon: string;
    title: string;
    text: string;
}

interface ProgrammeSections {
    header: { kicker: string; title: string; intro: string; image: PageImageField };
    intro: { text: string };
    axes: { axes: Axis[] };
}

const props = defineProps<{ sections: ProgrammeSections; seoTitle: string | null; seoDescription: string | null }>();
const axes = props.sections.axes.axes;

const introRef = ref<HTMLElement | null>(null);
useScrollReveal(introRef, { stagger: 0.1, start: 'top 100%' });
</script>

<template>
    <Head :title="seoTitle ?? undefined">
        <meta v-if="seoDescription" name="description" :content="seoDescription" />
    </Head>

    <PageHeader
        :kicker="sections.header.kicker"
        :title="sections.header.title"
        :intro="sections.header.intro"
        :image="sections.header.image.url ?? undefined"
        :image-alt="sections.header.image.alt"
        image-position="top"
    />
    <div class="mx-auto max-w-6xl px-6 pt-10 sm:px-10">
        <p ref="introRef" class="max-w-2xl font-sans text-sm text-ink-soft italic">
            {{ sections.intro.text }}
        </p>
    </div>

    <div class="mt-6">
        <section
            v-for="(axis, i) in axes"
            :id="axis.id"
            :key="axis.id"
            class="scroll-mt-36 border-t border-sand-200"
            :class="i % 2 === 1 ? 'bg-paper-dim' : ''"
        >
            <div class="mx-auto max-w-6xl px-6 py-16 sm:px-10">
                <div class="grid gap-6 lg:grid-cols-12 lg:gap-10">
                    <div class="lg:col-span-3">
                        <component :is="resolveIcon(axis.icon)" class="size-7 text-gold-600" aria-hidden="true" />
                        <p class="mt-4 font-display text-5xl text-gold-500/40">
                            {{ String(i + 1).padStart(2, '0') }}
                        </p>
                        <p class="mt-2 font-sans text-xs tracking-wide text-sand-600 uppercase">
                            Axe {{ i + 1 }}/6
                        </p>
                    </div>
                    <div class="lg:col-span-8 lg:col-start-5">
                        <h2 class="font-display text-display-3 text-ink">{{ axis.title }}</h2>
                        <p
                            class="mt-4 max-w-xl font-sans text-base leading-(--text-body--line-height) text-ink-soft"
                        >
                            {{ axis.text }}
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
