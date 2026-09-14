<script setup lang="ts">
/**
 * Bilan chiffré, repositionné après la présentation du Président (la trajectoire suit
 * l'homme) plutôt que juste après le hero. Uniquement des faits vérifiés dans l'audit
 * (§4, §7.1) — pas de chiffre d'adhérents inventé. Le vrai compteur d'impact
 * (adhérents/régions) est une fonctionnalité Phase 2, branchée sur la base Member.
 */
import { computed, ref } from 'vue';
import VideoFrame from '@/Components/ui/VideoFrame.vue';
import MotifDivider from '@/Components/ui/MotifDivider.vue';
import { useCountUp } from '@/Composables/useCountUp';
import { useScrollReveal } from '@/Composables/useScrollReveal';
import type { PageImageField } from '@/types/inertia';

interface Stat {
    mode: 'count_up_year' | 'count_up_number' | 'static';
    value: string;
    label: string;
}

export interface ImpactStatsSection {
    kicker: string;
    title: string;
    intro: string;
    video: PageImageField;
    stats: Stat[];
}

const props = defineProps<{ section: ImpactStatsSection }>();

// useCountUp anime via ScrollTrigger + un `Ref<HTMLElement|null>` cible : un seul ref
// "collecteur" (comme `barFills` dans ImageCarousel.vue) alimenté par `ref="statTargets"`
// dans le v-for, chaque item y accède via un computed en lecture seule sur son index.
const statTargets = ref<HTMLElement[]>([]);
const statValues = props.section.stats.map((stat, i) => {
    if (stat.mode === 'static') return ref(stat.value);
    const target = computed(() => statTargets.value[i] ?? null);
    const end =
        stat.mode === 'count_up_year'
            ? new Date().getFullYear() - parseInt(stat.value, 10)
            : parseInt(stat.value, 10);
    return useCountUp(target, end).value;
});

const introRef = ref<HTMLElement | null>(null);
useScrollReveal(introRef, { stagger: 0.1 });
</script>

<template>
    <section class="border-y border-sand-200 bg-paper-dim">
        <div
            class="relative mx-auto grid max-w-6xl gap-12 px-6 py-22 sm:px-10 lg:grid-cols-12 lg:items-center lg:gap-10"
        >
            <div
                class="pointer-events-none absolute inset-0 hidden overflow-hidden lg:block"
                aria-hidden="true"
            >
                <div class="absolute -inset-y-24 right-55 w-35 rotate-10 bg-gold-300/65 opacity-50"></div>
                <div
                    class="absolute -inset-y-24 right-15 w-35 rotate-10 bg-terracotta-500/65 opacity-50"
                ></div>
            </div>

            <div class="lg:col-span-6">
                <MotifDivider align="left" size="base" />
                <div ref="introRef">
                    <p class="mt-2 font-sans text-sm tracking-wide text-gold-700 uppercase">
                        {{ section.kicker }}
                    </p>

                    <h2 class="mt-4 font-display text-display-3 text-ink">
                        {{ section.title }}
                    </h2>
                    <p
                        class="mt-5 max-w-lg font-sans text-base leading-(--text-body--line-height) text-ink-soft"
                    >
                        {{ section.intro }}
                    </p>
                </div>

                <dl
                    class="mt-10 grid grid-cols-[auto_1fr] items-center gap-x-6 gap-y-8 border-t border-sand-200 pt-8"
                >
                    <template v-for="(stat, i) in section.stats" :key="i">
                        <dt ref="statTargets" class="text-right font-display text-numeral text-gold-600">
                            {{ statValues[i] }}
                        </dt>
                        <dd class="font-sans text-sm text-ink-soft sm:text-base">{{ stat.label }}</dd>
                    </template>
                </dl>
            </div>

            <div class="lg:col-span-6 lg:col-start-7 lg:self-center">
                <div class="">
                    <VideoFrame
                        :src="section.video.url ?? undefined"
                        :alt="section.video.alt"
                        ratio="video"
                        rounded
                    />
                </div>
                <div class="relative z-10 mt-3 flex items-center justify-between gap-4">
                    <MotifDivider size="sm" />
                    <MotifDivider size="sm" />
                </div>
            </div>
        </div>
    </section>
</template>
