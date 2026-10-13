<script setup lang="ts">
import { computed, ref } from 'vue';
import PhotoStack from '@/Components/ui/PhotoStack.vue';
import MotifDivider from '@/Components/ui/MotifDivider.vue';
import MagneticButton from '@/Components/ui/MagneticButton.vue';
import { useScrollReveal } from '@/Composables/useScrollReveal';
import type { PageImageField } from '@/types/inertia';

export interface JoinCtaSection {
    kicker: string;
    title: string;
    body: string;
    cta_label: string;
    photos: { image: PageImageField }[];
}

const props = defineProps<{ section: JoinCtaSection }>();

const photos = computed(() =>
    props.section.photos
        .filter((photo) => photo.image.url)
        .map((photo) => ({ src: photo.image.url as string, alt: photo.image.alt })),
);

const textCol = ref<HTMLElement | null>(null);
useScrollReveal(textCol, { stagger: 0.12 });
</script>

<template>
    <section class="surface-ink">
        <div class="mx-auto max-w-6xl px-6 py-24 sm:px-10">
            <div class="grid gap-16 lg:grid-cols-12 lg:items-center lg:gap-10">
                <div ref="textCol" class="lg:col-span-6">
                    <MotifDivider align="left" size="base" />

                    <p class="mt-6 font-sans text-sm tracking-wide text-gold-500 uppercase">
                        {{ section.kicker }}
                    </p>
                    <h2 class="mt-4 font-display text-display-2 text-paper">
                        {{ section.title }}
                    </h2>
                    <p
                        class="mt-6 max-w-lg font-sans text-base leading-(--text-body--line-height) text-sand-300"
                    >
                        {{ section.body }}
                    </p>
                    <MagneticButton href="/rejoignez-nous" class="mt-8">{{
                        section.cta_label
                    }}</MagneticButton>
                </div>

                <div class="lg:col-span-5 lg:col-start-8">
                    <PhotoStack :photos="photos" />
                </div>
            </div>
        </div>
    </section>
</template>
