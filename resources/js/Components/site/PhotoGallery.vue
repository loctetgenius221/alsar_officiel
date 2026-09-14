<script setup lang="ts">
/**
 * Galerie photo avec lightbox accessible (Reka UI Dialog : focus trap, ESC, fond bloqué).
 * Navigation clavier (flèches) en plus des boutons — inspiré des lightbox des grandes
 * bibliothèques de composants (Radix/shadcn), reconstruit ici avec nos propres tokens.
 */
import { ref } from 'vue';
import { DialogClose, DialogContent, DialogOverlay, DialogPortal, DialogRoot, DialogTitle } from 'reka-ui';
import { ChevronLeft, ChevronRight, X } from '@lucide/vue';

const props = defineProps<{
    images: { src: string; alt: string; caption?: string }[];
}>();

const open = ref(false);
const activeIndex = ref(0);

function openAt(i: number) {
    activeIndex.value = i;
    open.value = true;
}

function next() {
    activeIndex.value = (activeIndex.value + 1) % props.images.length;
}

function prev() {
    activeIndex.value = (activeIndex.value - 1 + props.images.length) % props.images.length;
}

function onKeydown(event: KeyboardEvent) {
    if (event.key === 'ArrowRight') next();
    if (event.key === 'ArrowLeft') prev();
}
</script>

<template>
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
        <button
            v-for="(image, i) in images"
            :key="image.src"
            type="button"
            class="group relative aspect-square overflow-hidden rounded-2xl bg-ink-soft"
            @click="openAt(i)"
        >
            <img
                :src="image.src"
                :alt="image.alt"
                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
                loading="lazy"
            />
        </button>
    </div>

    <DialogRoot v-model:open="open">
        <DialogPortal>
            <DialogOverlay class="fixed inset-0 z-40 bg-ink/95" />
            <DialogContent
                class="fixed inset-0 z-50 flex flex-col items-center justify-center px-6 py-10 outline-none"
                @keydown="onKeydown"
            >
                <DialogTitle class="sr-only">{{ images[activeIndex]?.alt }}</DialogTitle>

                <DialogClose
                    class="absolute top-6 right-6 text-paper/80 transition-colors hover:text-paper"
                    aria-label="Fermer"
                >
                    <X class="size-7" aria-hidden="true" />
                </DialogClose>

                <button
                    type="button"
                    class="absolute top-1/2 left-4 -translate-y-1/2 text-paper/80 transition-colors hover:text-paper sm:left-8"
                    aria-label="Photo précédente"
                    @click="prev"
                >
                    <ChevronLeft class="size-8" aria-hidden="true" />
                </button>
                <button
                    type="button"
                    class="absolute top-1/2 right-4 -translate-y-1/2 text-paper/80 transition-colors hover:text-paper sm:right-8"
                    aria-label="Photo suivante"
                    @click="next"
                >
                    <ChevronRight class="size-8" aria-hidden="true" />
                </button>

                <img
                    :src="images[activeIndex]?.src"
                    :alt="images[activeIndex]?.alt"
                    class="max-h-[80vh] max-w-full object-contain"
                />
                <p v-if="images[activeIndex]?.caption" class="mt-4 font-sans text-sm text-sand-200">
                    {{ images[activeIndex]?.caption }}
                </p>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
