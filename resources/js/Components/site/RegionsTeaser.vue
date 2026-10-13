<script setup lang="ts">
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import MotifDivider from '@/Components/ui/MotifDivider.vue';
import SenegalMap from '@/Components/site/SenegalMap.vue';
import { ArrowRight } from '@lucide/vue';
import { useScrollReveal } from '@/Composables/useScrollReveal';
// import baobabPattern from '../../../../public/images/pattern-baobab.svg';

export interface RegionsTeaserSection {
    kicker: string;
    title: string;
    body: string;
    map_caption: string;
}

defineProps<{ section: RegionsTeaserSection }>();

const mapCol = ref<HTMLElement | null>(null);
useScrollReveal(mapCol, { y: 0, duration: 1 });

const textCol = ref<HTMLElement | null>(null);
useScrollReveal(textCol, { stagger: 0.12, delay: 0.15 });
</script>

<template>
    <section class="relative mx-auto max-w-6xl overflow-hidden px-6 py-20 sm:px-10">
        <div class="relative z-5 grid gap-12 lg:grid-cols-12 lg:items-center lg:gap-10">
            <div ref="mapCol" class="lg:col-span-5">
                <div class="aspect-square sm:aspect-4/5 lg:aspect-square">
                    <SenegalMap />
                </div>
                <p class="mt-3 font-sans text-xs text-sand-600">
                    {{ section.map_caption }}
                </p>
            </div>

            <div ref="textCol" class="lg:col-span-6 lg:col-start-7">
                <MotifDivider align="left" size="base" />
                <p class="mt-2 font-sans text-sm tracking-wide text-gold-700 uppercase">
                    {{ section.kicker }}
                </p>
                <h2 class="mt-4 font-display text-display-3 text-ink">
                    {{ section.title }}
                </h2>
                <p class="mt-5 max-w-lg font-sans text-base leading-(--text-body--line-height) text-ink-soft">
                    {{ section.body }}
                </p>
                <Link
                    href="/regions"
                    class="group relative mt-8 inline-flex items-center gap-2 pb-1 font-sans text-sm font-medium text-ink after:absolute after:inset-x-0 after:bottom-0 after:h-px after:origin-left after:scale-x-0 after:bg-gold-500 after:transition-transform after:duration-300 hover:after:scale-x-100"
                >
                    Découvrir les régions
                    <ArrowRight
                        class="size-4 transition-transform group-hover:translate-x-1"
                        aria-hidden="true"
                    />
                </Link>
            </div>
        </div>
    </section>
</template>
