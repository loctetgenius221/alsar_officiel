<script setup lang="ts">
/**
 * État vide d'une liste : icône, phrase, et — quand c'est utile — l'action qui remplit l'écran.
 *
 * Remplace les « Aucun résultat. » en petit gris qui laissaient le doute entier : est-ce que
 * mes filtres sont trop restrictifs, ou est-ce qu'il n'y a réellement rien ? L'icône donne
 * immédiatement le sujet (un journal, des comptes, un historique), le titre dit l'état, et
 * `description` lève l'ambiguïté. Le `slot` accueille au besoin un bouton d'action.
 *
 * L'icône est purement décorative (le titre porte le sens) : `aria-hidden`, jamais annoncée.
 */
import type { Component } from 'vue';

defineProps<{
    icon: Component;
    title: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex flex-col items-center px-6 py-14 text-center">
        <span
            class="flex size-12 items-center justify-center rounded-full bg-sand-100 text-ink-soft/60"
            aria-hidden="true"
        >
            <component :is="icon" class="size-6" />
        </span>

        <p class="mt-4 font-display text-base text-ink">{{ title }}</p>
        <p v-if="description" class="mt-1.5 max-w-sm font-sans text-sm text-ink-soft">
            {{ description }}
        </p>

        <div v-if="$slots.default" class="mt-5">
            <slot />
        </div>
    </div>
</template>
