<script setup lang="ts">
/**
 * État "contenu à venir" honnête — utilisé quand une section du sitemap existe mais que le
 * contenu réel n'est pas encore disponible (ex. statuts, discours filmés). Jamais de faux
 * contenu pour combler l'espace.
 */
import type { Component } from 'vue';

defineProps<{
    icon: Component;
    title: string;
    text: string;
}>();
</script>

<template>
    <div class="flex flex-col items-start gap-4 border border-dashed border-sand-300 bg-paper-dim px-8 py-12">
        <component :is="icon" class="size-8 text-sand-500" aria-hidden="true" />
        <div>
            <p class="font-display text-lg text-ink">{{ title }}</p>
            <p class="mt-2 max-w-md font-sans text-sm text-ink-soft">{{ text }}</p>
        </div>
    </div>
</template>
