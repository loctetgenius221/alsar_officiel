<script setup lang="ts">
/**
 * Emplacement photo réutilisable. Tant qu'aucun fichier réel n'est fourni (CLAUDE.md §3.4 :
 * uniquement des photos réelles du terrain, jamais de stock générique), affiche un fallback
 * éditorial sobre plutôt qu'une image générique.
 *
 * Plus de parallax ici, et c'est délibéré : pour se déplacer dans son cadre, l'image devait
 * être agrandie (`scale-125`). Toute photo en parallax était donc affichée 25 % plus grande
 * que sa définition réelle — plus floue — et amputée de 12,5 % en haut et en bas. Sur un
 * portrait, cela détruisait précisément le cadrage tête/épaules choisi à la prise de vue.
 * Le parallax reste utilisé là où il ne coûte rien : les motifs SVG décoratifs
 * (ui/BaobabMotif.vue, site/ProgramPillars.vue).
 */
const props = withDefaults(
    defineProps<{
        src?: string;
        alt: string;
        caption?: string;
        ratio?: 'portrait' | 'square';
        /** Point d'ancrage du recadrage `object-cover` — utile quand le sujet n'est pas centré
         * dans la photo source (ex. portrait cadré à droite de l'image). */
        position?: 'center' | 'left' | 'right';
    }>(),
    {
        src: undefined,
        caption: undefined,
        ratio: 'portrait',
        position: 'center',
    },
);

const positionClass = {
    center: 'object-center',
    left: 'object-left',
    right: 'object-right',
}[props.position];
</script>

<template>
    <figure class="relative">
        <div
            class="overflow-hidden rounded-2xl bg-ink"
            :class="ratio === 'portrait' ? 'aspect-4/5' : 'aspect-square'"
        >
            <img
                v-if="src"
                :src="src"
                :alt="alt"
                class="h-full w-full object-cover"
                :class="positionClass"
                loading="lazy"
            />
            <div v-else class="flex h-full items-end p-6" role="img" :aria-label="alt">
                <p class="font-sans text-xs text-sand-300/80">{{ alt }} — photo à intégrer</p>
            </div>
        </div>
        <figcaption v-if="caption" class="mt-3 font-sans text-xs text-sand-600">{{ caption }}</figcaption>
    </figure>
</template>
