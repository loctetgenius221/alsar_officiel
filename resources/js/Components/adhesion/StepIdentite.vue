<script setup lang="ts">
/**
 * Étape 1/2 du wizard d'adhésion : identité (CLAUDE.md §5.1). Validation vee-validate + zod,
 * état persisté dans le store Pinia (localStorage) à chaque passage à l'étape suivante.
 */
import { onMounted, ref, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { toTypedSchema } from '@vee-validate/zod';
import { useForm } from 'vee-validate';
import { identiteSchema } from '@/lib/adhesion-schemas';
import { useAdhesionStore } from '@/stores/adhesion';
import { formatSenegalesePhone } from '@/Composables/usePhoneFormat';
import { xsrfToken } from '@/lib/csrf';

const props = defineProps<{
    regions: string[];
    serverErrors?: Record<string, string>;
}>();
const emit = defineEmits<{ next: [] }>();

// Le formulaire local (vee-validate/zod) ne peut pas savoir qu'un email est déjà pris en
// base au moment où le champ perd le focus sans la vérification live, ni qu'une photo a été
// rejetée par le serveur (mimes réels, taille) — sans ceci, une erreur 422 renvoyait
// silencieusement l'adhérent au début du wizard sans jamais lui dire pourquoi.
const SERVER_FIELD_MAP: Record<string, string> = {
    firstName: 'first_name',
    lastName: 'last_name',
    birthDate: 'birth_date',
    gender: 'gender',
    phone: 'phone',
    email: 'email',
    region: 'region',
    commune: 'commune',
    address: 'address',
    profession: 'profession',
    idDocumentNumber: 'id_document_number',
};

function serverError(field: string): string | undefined {
    return props.serverErrors?.[SERVER_FIELD_MAP[field]];
}

const store = useAdhesionStore();

const { defineField, errors, handleSubmit } = useForm({
    validationSchema: toTypedSchema(identiteSchema),
    initialValues: {
        ...store.state.identite,
        gender: store.state.identite.gender || undefined,
    },
});

const [firstName, firstNameAttrs] = defineField('firstName');
const [lastName, lastNameAttrs] = defineField('lastName');
const [birthDate, birthDateAttrs] = defineField('birthDate');
const [gender, genderAttrs] = defineField('gender');
const [phone, phoneAttrs] = defineField('phone');
const [email, emailAttrs] = defineField('email');
const [region, regionAttrs] = defineField('region');
const [commune, communeAttrs] = defineField('commune');
const [address, addressAttrs] = defineField('address');
const [profession, professionAttrs] = defineField('profession');
const [idDocumentNumber, idDocumentNumberAttrs] = defineField('idDocumentNumber');

function onPhoneInput(event: Event) {
    phone.value = formatSenegalesePhone((event.target as HTMLInputElement).value);
}

// Vérification live de la disponibilité de l'email — la seule règle qu'on ne peut pas
// valider côté client (unicité en base). Sans ça, l'adhérent ne le découvrirait qu'après
// avoir rempli les 3 étapes du wizard, à la soumission finale.
type EmailCheckState = 'idle' | 'checking' | 'available' | 'taken';
const emailCheckState = ref<EmailCheckState>('idle');

async function checkEmailAvailability(value: string) {
    emailCheckState.value = 'checking';

    try {
        const response = await fetch('/rejoignez-nous/verifier-email', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': xsrfToken(),
            },
            body: JSON.stringify({ email: value }),
        });

        if (!response.ok) {
            emailCheckState.value = 'idle';
            return;
        }

        const data = (await response.json()) as { available: boolean };
        emailCheckState.value = data.available ? 'available' : 'taken';
    } catch {
        // Hors ligne / erreur réseau : on ne bloque pas l'adhérent sur un simple confort
        // d'UX, la validation d'unicité serveur à la soumission finale reste la garantie.
        emailCheckState.value = 'idle';
    }
}

const debouncedEmailCheck = useDebounceFn(checkEmailAvailability, 500);

watch(email, (value) => {
    emailCheckState.value = 'idle';
    if (value && !errors.value.email) {
        debouncedEmailCheck(value);
    }
});

// Une session reprise (localStorage) peut arriver ici avec un email déjà rempli qui n'a
// jamais déclenché la vérification live (le watch ci-dessus ne réagit qu'à un changement) —
// sans ceci, un email déjà pris passait silencieusement les 3 étapes avant d'échouer à la
// toute fin, sans jamais prévenir l'adhérent.
onMounted(() => {
    if (email.value && !errors.value.email) {
        checkEmailAvailability(email.value);
    }
});

// La photo ne passe pas par le schéma zod (fichier, pas texte) — validée ici et stockée à
// part dans le store (jamais dans le localStorage persisté, cf. stores/adhesion.ts).
const photoError = ref('');
const photoPreview = ref<string | null>(null);
const MAX_PHOTO_SIZE = 4 * 1024 * 1024;

function onPhotoChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    photoError.value = '';

    if (!file) {
        store.photoFile = null;
        photoPreview.value = null;
        return;
    }

    if (!file.type.startsWith('image/')) {
        photoError.value = 'Le fichier doit être une image (JPG, PNG ou WebP).';
        return;
    }

    if (file.size > MAX_PHOTO_SIZE) {
        photoError.value = 'Image trop lourde (4 Mo maximum).';
        return;
    }

    store.photoFile = file;
    photoPreview.value = URL.createObjectURL(file);
}

const onSubmit = handleSubmit((values) => {
    if (emailCheckState.value === 'taken') {
        return;
    }
    store.state.identite = { ...store.state.identite, ...values };
    emit('next');
});
</script>

<template>
    <form class="space-y-12" novalidate @submit="onSubmit">
        <p class="font-sans text-xs text-ink-soft">
            Les champs marqués d'un <span class="text-terracotta-500">*</span> sont obligatoires.
        </p>

        <fieldset class="space-y-6">
            <legend class="mb-6 font-sans text-xs tracking-wide text-gold-700 uppercase">
                Informations personnelles
            </legend>
            <div class="grid gap-6 sm:grid-cols-2">
                <div>
                    <label for="firstName" class="font-sans text-sm text-ink-soft">
                        Prénom <span class="text-terracotta-500">*</span>
                    </label>
                    <input
                        id="firstName"
                        v-model="firstName"
                        v-bind="firstNameAttrs"
                        type="text"
                        autocomplete="given-name"
                        :aria-invalid="!!(errors.firstName || serverError('firstName'))"
                        :aria-describedby="errors.firstName || serverError('firstName') ? 'firstName-error' : undefined"
                        class="mt-2 w-full border bg-paper px-4 py-3 font-sans text-sm text-ink transition-colors"
                        :class="
                            errors.firstName || serverError('firstName')
                                ? 'border-danger focus-visible:border-danger'
                                : 'border-sand-300 focus-visible:border-gold-500'
                        "
                    />
                    <p
                        v-if="errors.firstName || serverError('firstName')"
                        id="firstName-error"
                        class="mt-1 font-sans text-xs text-danger"
                    >
                        {{ errors.firstName || serverError('firstName') }}
                    </p>
                </div>
                <div>
                    <label for="lastName" class="font-sans text-sm text-ink-soft">
                        Nom <span class="text-terracotta-500">*</span>
                    </label>
                    <input
                        id="lastName"
                        v-model="lastName"
                        v-bind="lastNameAttrs"
                        type="text"
                        autocomplete="family-name"
                        :aria-invalid="!!(errors.lastName || serverError('lastName'))"
                        :aria-describedby="errors.lastName || serverError('lastName') ? 'lastName-error' : undefined"
                        class="mt-2 w-full border bg-paper px-4 py-3 font-sans text-sm text-ink transition-colors"
                        :class="
                            errors.lastName || serverError('lastName')
                                ? 'border-danger focus-visible:border-danger'
                                : 'border-sand-300 focus-visible:border-gold-500'
                        "
                    />
                    <p
                        v-if="errors.lastName || serverError('lastName')"
                        id="lastName-error"
                        class="mt-1 font-sans text-xs text-danger"
                    >
                        {{ errors.lastName || serverError('lastName') }}
                    </p>
                </div>
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <div>
                    <label for="birthDate" class="font-sans text-sm text-ink-soft">Date de naissance</label>
                    <input
                        id="birthDate"
                        v-model="birthDate"
                        v-bind="birthDateAttrs"
                        type="date"
                        class="mt-2 w-full border border-sand-300 bg-paper px-4 py-3 font-sans text-sm text-ink transition-colors focus-visible:border-gold-500"
                    />
                </div>
                <div>
                    <span class="font-sans text-sm text-ink-soft">Sexe <span class="text-terracotta-500">*</span></span>
                    <div class="mt-2 grid grid-cols-2 gap-3">
                        <label
                            class="flex cursor-pointer items-center justify-center gap-2 border px-4 py-3 font-sans text-sm transition-colors"
                            :class="
                                gender === 'homme'
                                    ? 'border-gold-500 bg-gold-100/40 text-ink'
                                    : 'border-sand-300 text-ink-soft hover:border-sand-400'
                            "
                        >
                            <input
                                v-model="gender"
                                v-bind="genderAttrs"
                                type="radio"
                                value="homme"
                                name="gender"
                                class="sr-only"
                            />
                            Homme
                        </label>
                        <label
                            class="flex cursor-pointer items-center justify-center gap-2 border px-4 py-3 font-sans text-sm transition-colors"
                            :class="
                                gender === 'femme'
                                    ? 'border-gold-500 bg-gold-100/40 text-ink'
                                    : 'border-sand-300 text-ink-soft hover:border-sand-400'
                            "
                        >
                            <input
                                v-model="gender"
                                v-bind="genderAttrs"
                                type="radio"
                                value="femme"
                                name="gender"
                                class="sr-only"
                            />
                            Femme
                        </label>
                    </div>
                    <p v-if="errors.gender || serverError('gender')" class="mt-1 font-sans text-xs text-danger">
                        {{ errors.gender || serverError('gender') }}
                    </p>
                </div>
            </div>
        </fieldset>

        <fieldset class="space-y-6 border-t border-sand-200 pt-10">
            <legend class="mb-6 font-sans text-xs tracking-wide text-gold-700 uppercase">Coordonnées</legend>
            <div class="grid gap-6 sm:grid-cols-2">
                <div>
                    <label for="phone" class="font-sans text-sm text-ink-soft">
                        Téléphone (+221) <span class="text-terracotta-500">*</span>
                    </label>
                    <input
                        id="phone"
                        v-model="phone"
                        v-bind="phoneAttrs"
                        type="tel"
                        inputmode="numeric"
                        placeholder="77 543 60 81"
                        autocomplete="tel"
                        :aria-invalid="!!(errors.phone || serverError('phone'))"
                        :aria-describedby="errors.phone || serverError('phone') ? 'phone-error' : undefined"
                        class="mt-2 w-full border bg-paper px-4 py-3 font-sans text-sm text-ink transition-colors"
                        :class="
                            errors.phone || serverError('phone')
                                ? 'border-danger focus-visible:border-danger'
                                : 'border-sand-300 focus-visible:border-gold-500'
                        "
                        @input="onPhoneInput"
                    />
                    <p
                        v-if="errors.phone || serverError('phone')"
                        id="phone-error"
                        class="mt-1 font-sans text-xs text-danger"
                    >
                        {{ errors.phone || serverError('phone') }}
                    </p>
                </div>
                <div>
                    <label for="email" class="font-sans text-sm text-ink-soft">
                        Email <span class="text-terracotta-500">*</span>
                    </label>
                    <input
                        id="email"
                        v-model="email"
                        v-bind="emailAttrs"
                        type="email"
                        autocomplete="email"
                        :aria-invalid="!!(errors.email || serverError('email') || emailCheckState === 'taken')"
                        :aria-describedby="errors.email || serverError('email') ? 'email-error' : undefined"
                        class="mt-2 w-full border bg-paper px-4 py-3 font-sans text-sm text-ink transition-colors"
                        :class="
                            errors.email || serverError('email') || emailCheckState === 'taken'
                                ? 'border-danger focus-visible:border-danger'
                                : 'border-sand-300 focus-visible:border-gold-500'
                        "
                    />
                    <p
                        v-if="errors.email || serverError('email')"
                        id="email-error"
                        class="mt-1 font-sans text-xs text-danger"
                    >
                        {{ errors.email || serverError('email') }}
                    </p>
                    <p v-else-if="emailCheckState === 'checking'" class="mt-1 font-sans text-xs text-ink-soft">
                        Vérification de la disponibilité...
                    </p>
                    <p v-else-if="emailCheckState === 'taken'" class="mt-1 font-sans text-xs text-danger">
                        Cette adresse est déjà utilisée par un autre adhérent.
                    </p>
                    <p v-else-if="emailCheckState === 'available'" class="mt-1 font-sans text-xs text-success">
                        Adresse disponible.
                    </p>
                </div>
            </div>
        </fieldset>

        <fieldset class="space-y-6 border-t border-sand-200 pt-10">
            <legend class="mb-6 font-sans text-xs tracking-wide text-gold-700 uppercase">Localisation</legend>
            <div class="grid gap-6 sm:grid-cols-2">
                <div>
                    <label for="region" class="font-sans text-sm text-ink-soft">
                        Région <span class="text-terracotta-500">*</span>
                    </label>
                    <select
                        id="region"
                        v-model="region"
                        v-bind="regionAttrs"
                        :aria-invalid="!!(errors.region || serverError('region'))"
                        :aria-describedby="errors.region || serverError('region') ? 'region-error' : undefined"
                        class="mt-2 w-full border bg-paper px-4 py-3 font-sans text-sm text-ink transition-colors"
                        :class="
                            errors.region || serverError('region')
                                ? 'border-danger focus-visible:border-danger'
                                : 'border-sand-300 focus-visible:border-gold-500'
                        "
                    >
                        <option value="" disabled>Sélectionnez une région</option>
                        <option v-for="item in regions" :key="item" :value="item">{{ item }}</option>
                    </select>
                    <p
                        v-if="errors.region || serverError('region')"
                        id="region-error"
                        class="mt-1 font-sans text-xs text-danger"
                    >
                        {{ errors.region || serverError('region') }}
                    </p>
                </div>
                <div>
                    <label for="commune" class="font-sans text-sm text-ink-soft">
                        Commune <span class="text-terracotta-500">*</span>
                    </label>
                    <input
                        id="commune"
                        v-model="commune"
                        v-bind="communeAttrs"
                        type="text"
                        :aria-invalid="!!(errors.commune || serverError('commune'))"
                        :aria-describedby="errors.commune || serverError('commune') ? 'commune-error' : undefined"
                        class="mt-2 w-full border bg-paper px-4 py-3 font-sans text-sm text-ink transition-colors"
                        :class="
                            errors.commune || serverError('commune')
                                ? 'border-danger focus-visible:border-danger'
                                : 'border-sand-300 focus-visible:border-gold-500'
                        "
                    />
                    <p
                        v-if="errors.commune || serverError('commune')"
                        id="commune-error"
                        class="mt-1 font-sans text-xs text-danger"
                    >
                        {{ errors.commune || serverError('commune') }}
                    </p>
                </div>
            </div>

            <div>
                <label for="address" class="font-sans text-sm text-ink-soft">Adresse</label>
                <input
                    id="address"
                    v-model="address"
                    v-bind="addressAttrs"
                    type="text"
                    class="mt-2 w-full border border-sand-300 bg-paper px-4 py-3 font-sans text-sm text-ink transition-colors focus-visible:border-gold-500"
                />
                <p v-if="serverError('address')" class="mt-1 font-sans text-xs text-danger">
                    {{ serverError('address') }}
                </p>
            </div>
        </fieldset>

        <fieldset class="space-y-6 border-t border-sand-200 pt-10">
            <legend class="mb-6 font-sans text-xs tracking-wide text-gold-700 uppercase">
                Informations complémentaires
            </legend>
            <div class="grid gap-6 sm:grid-cols-2">
                <div>
                    <label for="profession" class="font-sans text-sm text-ink-soft">Profession</label>
                    <input
                        id="profession"
                        v-model="profession"
                        v-bind="professionAttrs"
                        type="text"
                        class="mt-2 w-full border border-sand-300 bg-paper px-4 py-3 font-sans text-sm text-ink transition-colors focus-visible:border-gold-500"
                    />
                </div>
                <div>
                    <label for="idDocumentNumber" class="font-sans text-sm text-ink-soft">
                        Numéro de pièce d'identité
                    </label>
                    <input
                        id="idDocumentNumber"
                        v-model="idDocumentNumber"
                        v-bind="idDocumentNumberAttrs"
                        type="text"
                        placeholder="CNI, passeport... (optionnel)"
                        :aria-invalid="!!(errors.idDocumentNumber || serverError('idDocumentNumber'))"
                        :aria-describedby="
                            errors.idDocumentNumber || serverError('idDocumentNumber')
                                ? 'idDocumentNumber-error'
                                : undefined
                        "
                        class="mt-2 w-full border bg-paper px-4 py-3 font-sans text-sm text-ink transition-colors"
                        :class="
                            errors.idDocumentNumber || serverError('idDocumentNumber')
                                ? 'border-danger focus-visible:border-danger'
                                : 'border-sand-300 focus-visible:border-gold-500'
                        "
                    />
                    <p
                        v-if="errors.idDocumentNumber || serverError('idDocumentNumber')"
                        id="idDocumentNumber-error"
                        class="mt-1 font-sans text-xs text-danger"
                    >
                        {{ errors.idDocumentNumber || serverError('idDocumentNumber') }}
                    </p>
                </div>
            </div>

            <div>
                <label for="photo" class="font-sans text-sm text-ink-soft">Photo (optionnelle)</label>
                <p class="mt-1 font-sans text-xs text-ink-soft">Affichée sur votre carte de membre numérique.</p>
                <div class="mt-3 flex items-center gap-4">
                    <div
                        class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-sand-300 bg-sand-100"
                    >
                        <img
                            v-if="photoPreview"
                            :src="photoPreview"
                            alt="Aperçu de la photo"
                            class="size-full object-cover"
                        />
                        <span v-else class="font-sans text-[10px] text-ink-soft/70">Aucune</span>
                    </div>
                    <input
                        id="photo"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        class="font-sans text-sm text-ink-soft file:mr-4 file:border-0 file:bg-sand-200 file:px-4 file:py-2 file:font-sans file:text-sm file:text-ink"
                        @change="onPhotoChange"
                    />
                </div>
                <p v-if="photoError || serverErrors?.photo" class="mt-1 font-sans text-xs text-danger">
                    {{ photoError || serverErrors?.photo }}
                </p>
            </div>
        </fieldset>

        <div class="flex justify-end border-t border-sand-200 pt-8">
            <button
                type="submit"
                class="bg-ink px-6 py-3 font-sans text-sm font-medium text-paper transition-colors hover:bg-ink-soft"
            >
                Continuer
            </button>
        </div>
    </form>
</template>
