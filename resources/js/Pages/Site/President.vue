<script setup lang="ts">
/**
 * Page consolidée "Le Président" (CLAUDE.md §4) : Biographie + Discours & Revue de presse
 * en sections ancrées (décision prise avec l'utilisateur pour ce lot, comme /le-parti).
 * Contenu biographique et chronologie alignés sur la biographie officielle transmise par
 * le parti (2026-08) : distingue clairement ALPAR (2014, mouvement local à Podor) → ALSAR
 * mouvement national (2019) → ALSAR parti politique officiellement reconnu (2026).
 * Aucune vidéo/discours n'étant documenté, la section "Discours" reste honnête (revue de
 * presse réelle + état à venir), pas de faux contenu.
 */
import { computed, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import SiteLayout from '@/Layouts/SiteLayout.vue';
import PageHeader from '@/Components/site/PageHeader.vue';
import SectionNav from '@/Components/site/SectionNav.vue';
import MotifDivider from '@/Components/ui/MotifDivider.vue';
// import BaobabMotif from '@/Components/ui/BaobabMotif.vue';
import PhotoFrame from '@/Components/ui/PhotoFrame.vue';
import Timeline from '@/Components/site/Timeline.vue';
import ComingSoon from '@/Components/ui/ComingSoon.vue';
import { Video } from '@lucide/vue';
import { useScrollReveal } from '@/Composables/useScrollReveal';
import type { PageImageField } from '@/types/inertia';

defineOptions({ layout: SiteLayout });

interface Fact {
    mode: 'count_up_year' | 'static';
    value: string;
    label: string;
}

interface PresidentSections {
    header: { kicker: string; title: string; intro: string; image: PageImageField };
    portrait: { kicker: string; title: string; body: string; facts: Fact[]; image: PageImageField };
    realisations: { title: string; body: string };
    parcours: { title: string; career: { date: string; text: string }[]; image: PageImageField };
    discours: { kicker: string; title: string; press_outlets: { text: string }[]; coming_soon_text: string };
}

const props = defineProps<{ sections: PresidentSections; seoTitle: string | null; seoDescription: string | null }>();

const navSections = [
    { id: 'biographie', label: 'Biographie' },
    { id: 'discours', label: 'Discours & Revue de presse' },
];

const displayedFacts = computed(() =>
    props.sections.portrait.facts.map((fact) => ({
        ...fact,
        display: fact.mode === 'count_up_year' ? String(new Date().getFullYear() - parseInt(fact.value, 10)) : fact.value,
    })),
);

const textRef = ref<HTMLElement | null>(null);
useScrollReveal(textRef, { stagger: 0.1 });

const factsRef = ref<HTMLElement | null>(null);
useScrollReveal(factsRef, { stagger: 0.1 });

const pressRef = ref<HTMLElement | null>(null);
useScrollReveal(pressRef, { stagger: 0.1 });
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
    <SectionNav :items="navSections" />

    <section id="biographie" class="relative mx-auto max-w-6xl scroll-mt-36 overflow-hidden px-6 py-20 sm:px-10">
        <!-- <BaobabMotif side="left" vertical="bottom" mirror /> -->

        <div class="relative z-5 grid gap-12 lg:grid-cols-12 lg:gap-10">
            <div class="lg:col-span-5">
                <PhotoFrame :src="sections.portrait.image.url ?? undefined" :alt="sections.portrait.image.alt" parallax />
            </div>

            <div ref="textRef" class="lg:col-span-6 lg:col-start-7">
                <MotifDivider align="left" size="base" />
                <p class="mt-2 font-sans text-sm tracking-wide text-gold-700 uppercase">
                    {{ sections.portrait.kicker }}
                </p>
                <h2 class="mt-4 font-display text-display-3 text-ink">
                    {{ sections.portrait.title }}
                </h2>
                <p class="mt-5 max-w-lg font-sans text-base leading-(--text-body--line-height) text-ink-soft">
                    {{ sections.portrait.body }}
                </p>

                <dl ref="factsRef" class="mt-10 grid grid-cols-3 gap-6 border-t border-sand-200 pt-8">
                    <div v-for="fact in displayedFacts" :key="fact.label">
                        <dt class="font-display text-numeral text-gold-600">{{ fact.display }}</dt>
                        <dd class="mt-1 font-sans text-xs text-ink-soft sm:text-sm">{{ fact.label }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="relative z-5 mt-20">
            <h3 class="font-display text-display-4 text-ink">{{ sections.realisations.title }}</h3>
            <p class="mt-5 max-w-2xl font-sans text-base leading-(--text-body--line-height) text-ink-soft">
                {{ sections.realisations.body }}
            </p>

            <h3 class="mt-16 font-display text-display-4 text-ink">{{ sections.parcours.title }}</h3>
            <div class="mt-10">
                <Timeline
                    :items="sections.parcours.career"
                    :image="sections.parcours.image.url ?? undefined"
                    :image-alt="sections.parcours.image.alt"
                />
            </div>
        </div>
    </section>

    <section id="discours" class="border-y border-sand-200 bg-paper-dim">
        <div class="mx-auto max-w-6xl scroll-mt-36 px-6 py-20 sm:px-10">
            <MotifDivider align="left" size="base" />
            <p class="mt-2 font-sans text-sm tracking-wide text-gold-700 uppercase">
                {{ sections.discours.kicker }}
            </p>
            <h2 class="mt-4 max-w-2xl font-display text-display-3 text-ink">
                {{ sections.discours.title }}
            </h2>

            <ul ref="pressRef" class="mt-8 flex flex-wrap gap-x-10 gap-y-3">
                <li
                    v-for="outlet in sections.discours.press_outlets"
                    :key="outlet.text"
                    class="font-display text-lg text-ink-soft"
                >
                    {{ outlet.text }}
                </li>
            </ul>

            <div class="mt-10">
                <ComingSoon :icon="Video" title="Vidéos et discours à venir" :text="sections.discours.coming_soon_text" />
            </div>
        </div>
    </section>
</template>
