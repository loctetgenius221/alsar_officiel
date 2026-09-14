<script setup lang="ts">
/**
 * Page consolidée "Le Parti" (CLAUDE.md §4) : Vision & Histoire + Organisation +
 * Statuts & Transparence en sections ancrées, plutôt que 3 gabarits séparés (décision
 * prise avec l'utilisateur pour ce lot). Chronologie alignée sur la biographie officielle
 * transmise par le parti (2026-08) : ALPAR (2014, mouvement local à Podor) → ALSAR
 * mouvement national (2019) → ALSAR parti politique officiellement reconnu (2026).
 */
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import SiteLayout from '@/Layouts/SiteLayout.vue';
import PageHeader from '@/Components/site/PageHeader.vue';
import SectionNav from '@/Components/site/SectionNav.vue';
import MotifDivider from '@/Components/ui/MotifDivider.vue';
// import BaobabMotif from '@/Components/ui/BaobabMotif.vue';
import Timeline from '@/Components/site/Timeline.vue';
import ComingSoon from '@/Components/ui/ComingSoon.vue';
import { ScrollText } from '@lucide/vue';
import { useScrollReveal } from '@/Composables/useScrollReveal';
import type { PageImageField } from '@/types/inertia';

defineOptions({ layout: SiteLayout });

interface PartiSections {
    header: { kicker: string; title: string; intro: string; image: PageImageField };
    vision: {
        kicker: string;
        title: string;
        body_intro: string;
        body_detail: string;
        values: { label: string; text: string }[];
    };
    histoire: { title: string; chronology: { date: string; text: string }[]; image: PageImageField };
    organisation: { kicker: string; title: string; facts: { label: string; value: string }[]; note: string };
    statuts: { kicker: string; title: string; coming_soon_text: string };
}

defineProps<{ sections: PartiSections; seoTitle: string | null; seoDescription: string | null }>();

const navSections = [
    { id: 'vision', label: 'Vision & Histoire' },
    { id: 'organisation', label: 'Organisation' },
    { id: 'statuts', label: 'Statuts & Transparence' },
];

const valuesRef = ref<HTMLElement | null>(null);
useScrollReveal(valuesRef, { stagger: 0.08 });

const orgRef = ref<HTMLElement | null>(null);
useScrollReveal(orgRef, { stagger: 0.1 });
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
        image-position="center"
    />
    <SectionNav :items="navSections" />

    <section id="vision" class="relative mx-auto max-w-6xl scroll-mt-36 overflow-hidden px-6 py-20 sm:px-10">
        <!-- <BaobabMotif side="right" vertical="bottom" /> -->

        <div class="relative z-5 grid gap-12 lg:grid-cols-12 lg:gap-10">
            <div class="lg:col-span-6">
                <MotifDivider align="left" size="base" />
                <p class="mt-2 font-sans text-sm tracking-wide text-gold-700 uppercase">
                    {{ sections.vision.kicker }}
                </p>
                <h2 class="mt-4 font-display text-display-3 text-ink">
                    {{ sections.vision.title }}
                </h2>
                <p class="mt-5 max-w-lg font-sans text-base leading-(--text-body--line-height) text-ink-soft">
                    {{ sections.vision.body_intro }}
                </p>
                <p
                    class="mt-5 max-w-lg font-sans text-base leading-(--text-body--line-height) text-ink-soft"
                    v-html="sections.vision.body_detail"
                />
            </div>

            <ul ref="valuesRef" class="grid gap-x-10 gap-y-8 sm:grid-cols-2 lg:col-span-6 lg:col-start-7">
                <li v-for="value in sections.vision.values" :key="value.label" class="border-t border-sand-200 pt-4">
                    <p class="font-display text-lg text-ink">{{ value.label }}</p>
                    <p class="mt-1 font-sans text-sm text-ink-soft">{{ value.text }}</p>
                </li>
            </ul>
        </div>

        <div class="relative z-5 mt-20">
            <h3 class="font-display text-display-4 text-ink">{{ sections.histoire.title }}</h3>
            <div class="mt-10">
                <Timeline
                    :items="sections.histoire.chronology"
                    :image="sections.histoire.image.url ?? undefined"
                    :image-alt="sections.histoire.image.alt"
                />
            </div>
        </div>
    </section>

    <section id="organisation" class="border-y border-sand-200 bg-paper-dim">
        <div class="mx-auto max-w-6xl scroll-mt-36 px-6 py-20 sm:px-10">
            <div ref="orgRef" class="max-w-2xl">
                <MotifDivider align="left" size="base" />
                <p class="mt-2 font-sans text-sm tracking-wide text-gold-700 uppercase">
                    {{ sections.organisation.kicker }}
                </p>
                <h2 class="mt-4 font-display text-display-3 text-ink">{{ sections.organisation.title }}</h2>

                <dl v-if="sections.organisation.facts.length" class="mt-8 grid grid-cols-[auto_1fr] gap-x-6 gap-y-4 border-t border-sand-200 pt-6">
                    <template v-for="fact in sections.organisation.facts" :key="fact.label">
                        <dt class="font-sans text-sm text-sand-600">{{ fact.label }}</dt>
                        <dd class="font-sans text-sm text-ink">{{ fact.value }}</dd>
                    </template>
                </dl>

                <p class="mt-6 font-sans text-sm text-ink-soft">
                    {{ sections.organisation.note }}
                </p>
            </div>
        </div>
    </section>

    <section id="statuts" class="mx-auto max-w-6xl scroll-mt-36 px-6 py-20 sm:px-10">
        <MotifDivider align="left" size="base" />
        <p class="mt-2 font-sans text-sm tracking-wide text-gold-700 uppercase">{{ sections.statuts.kicker }}</p>
        <h2 class="mt-4 max-w-2xl font-display text-display-3 text-ink">
            {{ sections.statuts.title }}
        </h2>

        <div class="mt-10">
            <ComingSoon :icon="ScrollText" title="À venir" :text="sections.statuts.coming_soon_text" />
        </div>
    </section>
</template>
