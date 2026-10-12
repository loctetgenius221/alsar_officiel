<script setup lang="ts">
/**
 * Choix du rôle, en cartes radio plutôt qu'en `<select>`.
 *
 * C'est la décision la plus lourde de ce formulaire : elle détermine ce que la personne verra
 * et pourra modifier dans tout le back-office. La conséquence de chaque option doit donc être
 * lisible AU MOMENT du choix — un menu déroulant n'affiche qu'une ligne à la fois et cache
 * précisément l'information qui permet de décider.
 *
 * `data-error-anchor` : permet au récapitulatif d'erreurs (FormErrorSummary) de ramener la vue
 * ici, le groupe de boutons radio n'ayant pas d'`id` propre (cf. lib/form-errors.ts).
 */
export interface RoleOption {
    value: string;
    label: string;
    description: string;
}

withDefaults(
    defineProps<{
        modelValue: string;
        options: RoleOption[];
        error?: string;
        /** Verrouille le choix (ex. son propre compte, dernier administrateur actif). */
        disabled?: boolean;
        /** Pourquoi c'est verrouillé — toujours dire la raison, jamais griser en silence. */
        disabledReason?: string;
    }>(),
    { error: undefined, disabled: false, disabledReason: undefined },
);

defineEmits<{ 'update:modelValue': [string] }>();
</script>

<template>
    <fieldset data-error-anchor="role">
        <legend class="font-sans text-sm text-ink-soft">Rôle</legend>

        <p v-if="disabled && disabledReason" class="mt-2 font-sans text-xs text-ink-soft">
            {{ disabledReason }}
        </p>

        <div class="mt-3 space-y-3">
            <label
                v-for="option in options"
                :key="option.value"
                class="flex cursor-pointer gap-3 border p-4 transition-colors"
                :class="[
                    modelValue === option.value
                        ? 'border-gold-500 bg-gold-500/5'
                        : 'border-sand-200 hover:border-sand-300',
                    disabled ? 'cursor-not-allowed opacity-60' : '',
                ]"
            >
                <input
                    type="radio"
                    name="role"
                    class="mt-1 size-4 shrink-0 accent-gold-600"
                    :value="option.value"
                    :checked="modelValue === option.value"
                    :disabled="disabled"
                    @change="$emit('update:modelValue', option.value)"
                />
                <span class="min-w-0">
                    <span class="block font-sans text-sm font-medium text-ink">{{ option.label }}</span>
                    <span class="mt-1 block font-sans text-xs leading-relaxed text-ink-soft">
                        {{ option.description }}
                    </span>
                </span>
            </label>
        </div>

        <p v-if="error" class="mt-2 font-sans text-xs text-danger">{{ error }}</p>
    </fieldset>
</template>
