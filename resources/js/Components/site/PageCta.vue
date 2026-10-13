<script setup lang="ts">
/**
 * Appel à l'action de fin de page, pour les pages intérieures.
 *
 * Pourquoi : le programme, les régions et la médiathèque se terminaient directement sur le
 * pied de page. Or c'est précisément au bas de ces pages que le visiteur est le plus engagé —
 * il vient de lire les six axes, ou de parcourir les implantations — et on ne lui proposait
 * rien. Une page de fond sans sortie est une impasse de conversion.
 *
 * Même surface sombre que le bloc d'adhésion de l'accueil (site/JoinCta.vue), et c'est voulu :
 * le fond `bg-ink` devient le signal reconnaissable du moment « rejoindre », quelle que soit la
 * page. La DIFFÉRENCE est dans le poids — bandeau compact, titre en text-display-3, pas de pile
 * de photos — là où l'accueil déploie le format complet (CLAUDE.md §14 : une variante de mise en
 * page par page, jamais un copier-coller).
 *
 * Un fond clair avait été essayé d'abord : sur la page Programme, dont les axes alternent déjà
 * `bg-paper` / `bg-paper-dim`, le bandeau se confondait avec le dernier axe — `sand-100` et
 * `paper-dim` sont la même valeur (#f2ede2). Le fond sombre règle le problème définitivement,
 * quel que soit le nombre d'axes.
 */
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import MotifDivider from '@/Components/ui/MotifDivider.vue';
import MagneticButton from '@/Components/ui/MagneticButton.vue';
import { useScrollReveal } from '@/Composables/useScrollReveal';

withDefaults(
    defineProps<{
        title: string;
        body: string;
        ctaLabel?: string;
        ctaHref?: string;
        /** Lien secondaire facultatif — jamais plus d'un, pour ne pas diluer l'action. */
        secondaryLabel?: string;
        secondaryHref?: string;
    }>(),
    {
        ctaLabel: 'Rejoindre ALSAR',
        ctaHref: '/rejoignez-nous',
        secondaryLabel: undefined,
        secondaryHref: undefined,
    },
);

const el = ref<HTMLElement | null>(null);
useScrollReveal(el, { stagger: 0.1 });
</script>

<template>
    <section class="surface-ink">
        <div ref="el" class="mx-auto max-w-6xl px-6 py-16 sm:px-10 sm:py-20">
            <div class="grid gap-8 lg:grid-cols-12 lg:items-center">
                <div class="lg:col-span-7">
                    <MotifDivider align="left" size="base" />
                    <h2 class="mt-4 font-display text-display-3 text-paper">{{ title }}</h2>
                    <p
                        class="mt-4 max-w-xl font-sans text-base leading-(--text-body--line-height) text-sand-100"
                    >
                        {{ body }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-4 lg:col-span-5 lg:justify-end">
                    <MagneticButton :href="ctaHref">{{ ctaLabel }}</MagneticButton>
                    <Link
                        v-if="secondaryLabel && secondaryHref"
                        :href="secondaryHref"
                        class="group relative inline-flex items-center gap-2 pb-1 font-sans text-sm font-medium text-paper after:absolute after:inset-x-0 after:bottom-0 after:h-px after:origin-left after:scale-x-0 after:bg-gold-500 after:transition-transform after:duration-300 hover:after:scale-x-100"
                    >
                        {{ secondaryLabel }}
                    </Link>
                </div>
            </div>
        </div>
    </section>
</template>
