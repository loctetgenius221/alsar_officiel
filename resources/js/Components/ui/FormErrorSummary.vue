<script setup lang="ts">
/**
 * Récapitulatif des erreurs en tête de formulaire (cf. lib/form-errors.ts pour le pourquoi).
 *
 * Trois rôles, dans cet ordre d'importance :
 *   1. rendre l'échec visible — avant, un formulaire refusé ne changeait visiblement rien ;
 *   2. dire COMBIEN de champs sont en cause, pour qu'on sache quand on a fini ;
 *   3. permettre d'aller directement à chaque champ, y compris s'il est loin dans la page
 *      ou dans une colonne latérale.
 *
 * `role="alert"` : le contenu est annoncé aux lecteurs d'écran dès son apparition, sans quoi
 * la soumission resterait silencieuse pour un utilisateur non voyant.
 */
import { computed } from 'vue';
import { AlertCircle } from '@lucide/vue';
import { errorCountLabel, errorEntries, findErrorElement, type ErrorBag } from '@/lib/form-errors';

const props = withDefaults(
    defineProps<{
        errors: ErrorBag;
        /** Noms lisibles par clé d'erreur (ex. schéma de l'éditeur de contenu). */
        labels?: Record<string, string>;
        /** Phrase d'introduction, à adapter au contexte (« Votre adhésion n'a pas été envoyée »). */
        title?: string;
    }>(),
    {
        labels: () => ({}),
        title: "L'enregistrement n'a pas pu être effectué",
    },
);

const entries = computed(() => errorEntries(props.errors, props.labels));

function goToField(key: string) {
    const element = findErrorElement(key);
    if (!element) return;

    const behavior: ScrollBehavior = window.matchMedia('(prefers-reduced-motion: reduce)').matches
        ? 'auto'
        : 'smooth';
    element.scrollIntoView({ behavior, block: 'center' });

    const focusable = element.matches('input, select, textarea, button')
        ? element
        : element.querySelector<HTMLElement>('input, select, textarea, [contenteditable="true"], button');
    focusable?.focus({ preventScroll: true });
}
</script>

<template>
    <div v-if="entries.length" role="alert" class="flex gap-4 border border-danger/30 bg-danger/5 p-4 sm:p-5">
        <AlertCircle class="mt-0.5 size-5 shrink-0 text-danger" aria-hidden="true" />

        <div class="min-w-0 flex-1">
            <p class="font-display text-base text-ink">{{ title }}</p>
            <p class="mt-1 font-sans text-sm text-ink-soft">
                {{ errorCountLabel(entries.length) }}. Rien n'a été perdu : corrigez les points ci-dessous
                puis enregistrez à nouveau.
            </p>

            <ul class="mt-3 space-y-1.5">
                <li v-for="entry in entries" :key="entry.key" class="font-sans text-sm">
                    <button
                        type="button"
                        class="text-left underline decoration-danger/40 underline-offset-2 transition-colors hover:decoration-danger"
                        @click="goToField(entry.key)"
                    >
                        <span class="font-medium text-ink">{{ entry.label }}</span>
                        <span class="text-ink-soft"> — {{ entry.message }}</span>
                    </button>
                </li>
            </ul>
        </div>
    </div>
</template>
