<script setup lang="ts">
import type { Component } from 'vue';

withDefaults(
    defineProps<{
        modelValue?: string | null;
        label?: string;
        type?: string;
        error?: string;
        placeholder?: string;
        id?: string;
        /**
         * Déclaré explicitement plutôt que laissé aux attributs hérités : la racine de ce
         * composant est un <div>, un `autocomplete` passé de l'extérieur atterrirait donc sur
         * le conteneur au lieu du champ. Indispensable sur les mots de passe, où un
         * `new-password` évite que le navigateur ré-injecte l'ancien.
         */
        autocomplete?: string;
        /** Aide sous le champ — ex. l'exigence de longueur d'un mot de passe. */
        hint?: string;
        /**
         * Icône posée dans le champ, à gauche (loupe sur une recherche, par exemple).
         * Décorative : elle double une information déjà portée par le label et le placeholder,
         * d'où `aria-hidden` et `pointer-events-none` — cliquer dessus doit placer le curseur
         * dans le champ, pas tomber sur un SVG inerte.
         */
        icon?: Component;
    }>(),
    {
        modelValue: '',
        label: undefined,
        type: 'text',
        error: undefined,
        placeholder: undefined,
        id: undefined,
        autocomplete: undefined,
        hint: undefined,
        icon: undefined,
    },
);
defineEmits<{ 'update:modelValue': [string] }>();
</script>

<template>
    <div>
        <label v-if="label" :for="id" class="font-sans text-sm text-ink-soft">{{ label }}</label>

        <div class="relative mt-2">
            <component
                :is="icon"
                v-if="icon"
                class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-ink-soft/60"
                aria-hidden="true"
            />
            <input
                :id="id"
                :type="type"
                :value="modelValue"
                :placeholder="placeholder"
                :autocomplete="autocomplete"
                class="w-full border border-sand-300 bg-paper py-2.5 font-sans text-sm text-ink focus-visible:border-gold-500"
                :class="icon ? 'pr-4 pl-10' : 'px-4'"
                @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
            />
        </div>

        <p v-if="error" class="mt-1 font-sans text-xs text-danger">{{ error }}</p>
        <p v-else-if="hint" class="mt-1 font-sans text-xs text-ink-soft">{{ hint }}</p>
    </div>
</template>
