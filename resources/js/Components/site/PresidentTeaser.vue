<script setup lang="ts">
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import PhotoFrame from '@/Components/ui/PhotoFrame.vue';
import MotifDivider from '@/Components/ui/MotifDivider.vue';
import { ArrowRight } from '@lucide/vue';
import { useScrollReveal } from '@/Composables/useScrollReveal';
import type { PageImageField } from '@/types/inertia';

export interface PresidentTeaserSection {
    kicker: string;
    quote: string;
    body: string;
    badges: { text: string }[];
    cta_label: string;
    portrait: PageImageField;
}

defineProps<{ section: PresidentTeaserSection }>();

const textCol = ref<HTMLElement | null>(null);
useScrollReveal(textCol);
</script>

<template>
    <section class="mx-auto max-w-6xl px-6 py-22 sm:px-10">
        <div class="grid gap-10 lg:grid-cols-12 lg:gap-8">
            <div class="lg:col-span-5">
                <PhotoFrame
                    :src="section.portrait.url ?? undefined"
                    :alt="section.portrait.alt"
                    position="right"
                />
            </div>

            <div ref="textCol" class="lg:col-span-6 lg:col-start-7 lg:self-center">
                <MotifDivider align="left" size="base" />
                <p class="mt-2 font-sans text-sm tracking-wide text-gold-700 uppercase">
                    {{ section.kicker }}
                </p>

                <div class="relative mt-4">
                    <span
                        aria-hidden="true"
                        class="pointer-events-none absolute -top-8 -left-1 font-display text-8xl text-gold-300/50 select-none"
                    >
                        «
                    </span>
                    <blockquote class="relative font-display text-display-3 text-ink">
                        {{ section.quote }}
                    </blockquote>
                </div>

                <p
                    class="mt-6 max-w-lg font-sans text-base leading-[var(--text-body--line-height)] text-ink-soft"
                >
                    {{ section.body }}
                </p>

                <div
                    class="mt-8 flex flex-wrap items-center gap-x-3 gap-y-2 border-t border-sand-200 pt-6 font-sans text-sm text-ink-soft"
                >
                    <template v-for="(badge, i) in section.badges" :key="i">
                        <span v-if="i > 0" class="text-sand-400" aria-hidden="true">·</span>
                        <span>{{ badge.text }}</span>
                    </template>
                </div>

                <Link
                    href="/le-president"
                    class="group relative mt-8 inline-flex items-center gap-2 pb-1 font-sans text-sm font-medium text-ink after:absolute after:inset-x-0 after:bottom-0 after:h-px after:origin-left after:scale-x-0 after:bg-gold-500 after:transition-transform after:duration-300 hover:after:scale-x-100"
                >
                    {{ section.cta_label }}
                    <ArrowRight
                        class="size-4 transition-transform group-hover:translate-x-1"
                        aria-hidden="true"
                    />
                </Link>
            </div>
        </div>
    </section>
</template>
