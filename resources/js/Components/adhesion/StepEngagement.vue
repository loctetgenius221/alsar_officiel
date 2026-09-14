<script setup lang="ts">
/**
 * Étape 2/2 (dernière étape — cotisation désactivée pour le moment, cf. stores/adhesion.ts) :
 * engagement militant + consentement horodaté (CLAUDE.md §5.2). Le consentement est revalidé
 * côté serveur (StoreMemberRequest) et horodaté à la création du membre — le timestamp
 * affiché ici n'a qu'une valeur d'UX, pas de valeur légale. `emit('next')` déclenche
 * directement la soumission finale côté parent (Pages/Site/Adhesion.vue), il n'y a plus
 * d'étape suivante.
 */
import { toTypedSchema } from '@vee-validate/zod';
import { useForm } from 'vee-validate';
import { Link } from '@inertiajs/vue3';
import { engagementSchema } from '@/lib/adhesion-schemas';
import { useAdhesionStore } from '@/stores/adhesion';

const props = defineProps<{ serverErrors?: Record<string, string>; processing?: boolean }>();
const emit = defineEmits<{ next: []; back: [] }>();
const store = useAdhesionStore();

const SERVER_FIELD_MAP: Record<string, string> = {
    section: 'section',
    referralSource: 'referral_source',
    consent: 'consent',
};

function serverError(field: string): string | undefined {
    return props.serverErrors?.[SERVER_FIELD_MAP[field]];
}

const { defineField, errors, handleSubmit } = useForm({
    validationSchema: toTypedSchema(engagementSchema),
    initialValues: { ...store.state.engagement },
});

const [section, sectionAttrs] = defineField('section');
const [referralSource, referralSourceAttrs] = defineField('referralSource');
const [consent, consentAttrs] = defineField('consent');

const onSubmit = handleSubmit((values) => {
    store.state.engagement = { ...store.state.engagement, ...values };
    emit('next');
});
</script>

<template>
    <form class="space-y-12" novalidate @submit="onSubmit">
        <fieldset>
            <legend class="mb-6 font-sans text-xs tracking-wide text-gold-700 uppercase">Section souhaitée</legend>
            <div class="grid gap-3 sm:grid-cols-3">
                <label
                    class="flex cursor-pointer items-center justify-center border px-4 py-3 text-center font-sans text-sm transition-colors"
                    :class="
                        section === 'standard'
                            ? 'border-gold-500 bg-gold-100/40 text-ink'
                            : 'border-sand-300 text-ink-soft hover:border-sand-400'
                    "
                >
                    <input
                        v-model="section"
                        v-bind="sectionAttrs"
                        type="radio"
                        value="standard"
                        name="section"
                        class="sr-only"
                    />
                    Aucune (membre standard)
                </label>
                <label
                    class="flex cursor-pointer items-center justify-center border px-4 py-3 text-center font-sans text-sm transition-colors"
                    :class="
                        section === 'jeunes'
                            ? 'border-gold-500 bg-gold-100/40 text-ink'
                            : 'border-sand-300 text-ink-soft hover:border-sand-400'
                    "
                >
                    <input
                        v-model="section"
                        v-bind="sectionAttrs"
                        type="radio"
                        value="jeunes"
                        name="section"
                        class="sr-only"
                    />
                    Mouvement des jeunes
                </label>
                <label
                    class="flex cursor-pointer items-center justify-center border px-4 py-3 text-center font-sans text-sm transition-colors"
                    :class="
                        section === 'femmes'
                            ? 'border-gold-500 bg-gold-100/40 text-ink'
                            : 'border-sand-300 text-ink-soft hover:border-sand-400'
                    "
                >
                    <input
                        v-model="section"
                        v-bind="sectionAttrs"
                        type="radio"
                        value="femmes"
                        name="section"
                        class="sr-only"
                    />
                    Mouvement des femmes
                </label>
            </div>
            <p v-if="errors.section || serverError('section')" class="mt-2 font-sans text-xs text-danger">
                {{ errors.section || serverError('section') }}
            </p>
        </fieldset>

        <div class="border-t border-sand-200 pt-10">
            <label for="referralSource" class="font-sans text-sm text-ink-soft">
                Comment avez-vous connu ALSAR ?
            </label>
            <select
                id="referralSource"
                v-model="referralSource"
                v-bind="referralSourceAttrs"
                class="mt-2 w-full max-w-sm border border-sand-300 bg-paper px-4 py-3 font-sans text-sm text-ink transition-colors focus-visible:border-gold-500"
            >
                <option value="">Préférez ne pas répondre</option>
                <option value="reseaux-sociaux">Réseaux sociaux</option>
                <option value="bouche-a-oreille">Bouche-à-oreille</option>
                <option value="meeting">Meeting ou événement</option>
                <option value="presse">Presse</option>
                <option value="autre">Autre</option>
            </select>
        </div>

        <div class="border-t border-sand-200 pt-10">
            <label
                class="flex items-start gap-3 border px-5 py-4 font-sans text-sm text-ink-soft transition-colors"
                :class="
                    errors.consent || serverError('consent') ? 'border-danger' : 'border-sand-300 has-checked:border-gold-500 has-checked:bg-gold-100/40'
                "
            >
                <input
                    v-model="consent"
                    v-bind="consentAttrs"
                    type="checkbox"
                    class="mt-1"
                    :aria-invalid="!!(errors.consent || serverError('consent'))"
                    :aria-describedby="errors.consent || serverError('consent') ? 'consent-error' : undefined"
                />
                <span>
                    <span class="text-terracotta-500">*</span>
                    J'accepte les
                    <Link href="/le-parti#statuts" target="_blank" class="text-ink underline">
                        statuts du parti
                    </Link>
                    et la
                    <Link href="/politique-de-confidentialite" target="_blank" class="text-ink underline">
                        politique de confidentialité
                    </Link>
                    .
                </span>
            </label>
            <p
                v-if="errors.consent || serverError('consent')"
                id="consent-error"
                class="mt-2 font-sans text-xs text-danger"
            >
                {{ errors.consent || serverError('consent') }}
            </p>
        </div>

        <div class="flex justify-between border-t border-sand-200 pt-8">
            <button
                type="button"
                class="border-b border-sand-300 pb-1 font-sans text-sm text-ink-soft transition-colors hover:border-ink-soft disabled:opacity-50"
                :disabled="processing"
                @click="emit('back')"
            >
                ← Retour
            </button>
            <button
                type="submit"
                class="bg-ink px-6 py-3 font-sans text-sm font-medium text-paper transition-colors hover:bg-ink-soft disabled:opacity-50"
                :disabled="processing"
            >
                {{ processing ? 'Envoi…' : 'Rejoindre ALSAR' }}
            </button>
        </div>
    </form>
</template>
