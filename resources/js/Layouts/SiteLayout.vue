<script setup lang="ts">
import { onMounted } from 'vue';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import AppHeader from '@/Components/site/AppHeader.vue';
import AppFooter from '@/Components/site/AppFooter.vue';
import { useLenis } from '@/Composables/useLenis';

/** Passer `heroTransparent` uniquement pour une page avec un hero plein cadre sombre
 * (ex. Accueil) : le header y démarre transparent puis se solidifie au scroll. Par défaut
 * (`false`), le header reste solide dès le chargement — nécessaire pour toute page sans
 * hero sombre en haut. */
withDefaults(defineProps<{ heroTransparent?: boolean }>(), { heroTransparent: false });

onMounted(() => {
    useLenis();
    ScrollTrigger.refresh();
});
</script>

<template>
    <a
        href="#contenu"
        class="sr-only focus-visible:not-sr-only focus-visible:absolute focus-visible:top-4 focus-visible:left-4 focus-visible:z-50 focus-visible:bg-ink focus-visible:px-4 focus-visible:py-2 focus-visible:font-sans focus-visible:text-sm focus-visible:text-paper"
    >
        Aller au contenu
    </a>
    <AppHeader :always-solid="!heroTransparent" />
    <main id="contenu">
        <slot />
    </main>
    <AppFooter />
</template>
