<script setup lang="ts">
/**
 * Remplace l'ancien PressStrip (mentions presse) : on n'a pas de confirmation que les
 * médias cités dans l'audit se considèrent "partenaires" ou suivent réellement le parti
 * de façon continue — présenter ça comme acquis serait trompeur. À la place, bande de
 * marque auto-référentielle : slogan officiel + valeurs réellement affichées par ALSAR
 * (CLAUDE.md/audit §6.3), aucune affirmation sur un tiers.
 * Bande défilante en CSS pur (translateX -50% sur une liste dupliquée) plutôt qu'en JS :
 * plus performant pour une animation continue, et coupée automatiquement par
 * prefers-reduced-motion (règle globale dans app.css).
 */
import { computed, ref } from 'vue';
import { Star } from '@lucide/vue';
import { useScrollReveal } from '@/Composables/useScrollReveal';

export interface BrandTickerSection {
    phrases: { text: string }[];
}

const props = defineProps<{ section: BrandTickerSection }>();

const sectionRef = ref<HTMLElement | null>(null);
useScrollReveal(sectionRef, { y: 0, duration: 1 });

const phrases = computed(() => props.section.phrases.map((phrase) => phrase.text));
</script>

<template>
    <section ref="sectionRef" class="surface-ink py-6">
        <div class="group overflow-hidden">
            <div class="flex w-max animate-marquee items-center group-hover:[animation-play-state:paused]">
                <div v-for="n in 2" :key="n" class="flex shrink-0 items-center" :aria-hidden="n === 2">
                    <template v-for="phrase in phrases" :key="phrase">
                        <span
                            class="px-8 font-sans text-lg font-semibold tracking-wide text-gold-500 uppercase"
                        >
                            {{ phrase }}
                        </span>
                        <Star class="size-3 shrink-0 text-gold-500/50" aria-hidden="true" />
                    </template>
                </div>
            </div>
        </div>
    </section>
</template>
