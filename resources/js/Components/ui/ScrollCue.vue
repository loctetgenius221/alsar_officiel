<script setup lang="ts">
/**
 * Repère de défilement en bas du hero.
 *
 * Pourquoi : le hero occupe presque toute la hauteur de l'écran et se termine sur un fond
 * continu. Rien n'indiquait qu'il y avait une page en dessous — sur un grand écran, le pli
 * tombe juste après les boutons, et le visiteur peut croire avoir tout vu.
 *
 * Trois partis pris :
 *   - il DISPARAÎT au premier défilement : un repère qui reste affiché alors qu'on a déjà
 *     compris devient un élément de décor qui encombre le hero ;
 *   - un filet parcouru par un segment lumineux plutôt qu'une flèche qui rebondit — le rebond
 *     est le tic des gabarits, le filet appartient au registre éditorial du site ;
 *   - le chevron reste, lui, immobile : c'est lui qui porte le sens, pas le mouvement.
 *
 * Décoratif : `aria-hidden`. Le contenu suivant est atteignable au clavier et par le lien
 * d'évitement du layout — un lecteur d'écran n'a pas besoin qu'on lui dise de faire défiler.
 */
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { ChevronDown } from '@lucide/vue';

/** Seuil volontairement bas : le moindre geste de défilement vaut « j'ai compris ». */
const HIDE_AFTER_PX = 40;

const visible = ref(true);

function onScroll() {
    if (window.scrollY > HIDE_AFTER_PX) visible.value = false;
}

onMounted(() => {
    // `passive` : ce gestionnaire ne bloque jamais le défilement, il ne fait que lire.
    window.addEventListener('scroll', onScroll, { passive: true });
    // Au cas où la page est rouverte déjà défilée (retour arrière du navigateur).
    onScroll();
});

onBeforeUnmount(() => window.removeEventListener('scroll', onScroll));
</script>

<template>
    <Transition
        enter-active-class="transition-opacity duration-500"
        leave-active-class="transition-opacity duration-300"
        enter-from-class="opacity-0"
        leave-to-class="opacity-0"
    >
        <div
            v-if="visible"
            class="pointer-events-none absolute right-6 bottom-16 z-10 hidden flex-col items-center gap-3 sm:right-10 sm:flex"
            aria-hidden="true"
        >
            <span class="font-sans text-[0.625rem] tracking-[0.2em] text-paper/50 uppercase">Défiler</span>

            <span class="relative h-12 w-px overflow-hidden bg-paper/20">
                <span class="absolute inset-x-0 h-1/2 animate-scroll-cue bg-gold-500" />
            </span>

            <ChevronDown class="size-4 text-paper/50" />
        </div>
    </Transition>
</template>
