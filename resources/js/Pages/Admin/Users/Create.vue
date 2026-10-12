<script setup lang="ts">
/**
 * Création d'un compte back-office. Volontairement court : nom, email, rôle, mot de passe.
 *
 * Le mot de passe est choisi ici par l'administrateur puis communiqué de vive voix — il n'y a
 * pas (encore) d'email d'invitation sur ce projet, et un compte sans mot de passe utilisable
 * serait un compte inutilisable. L'intéressé le change ensuite depuis « Mon compte ».
 */
import { computed } from 'vue';
import { Head, router, setLayoutProps, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Input from '@/Components/ui/Input.vue';
import Button from '@/Components/ui/Button.vue';
import FormErrorSummary from '@/Components/ui/FormErrorSummary.vue';
import RoleField, { type RoleOption } from '@/Components/admin/RoleField.vue';
import { USER_FIELD_LABELS } from '@/lib/field-labels';
import { focusFirstError } from '@/lib/form-errors';
import { useUnsavedChanges } from '@/Composables/useUnsavedChanges';
import { confirmAction } from '@/lib/confirm';
import { KeyRound, UserPlus } from '@lucide/vue';

defineOptions({ layout: AdminLayout });
setLayoutProps({ title: 'Nouveau compte' });

const props = defineProps<{ roles: RoleOption[] }>();

const form = useForm({
    name: '',
    email: '',
    // Le rôle le moins puissant est présélectionné : si l'on valide sans y penser, on accorde
    // le minimum, jamais l'accès complet.
    role: props.roles[props.roles.length - 1]?.value ?? '',
    password: '',
    password_confirmation: '',
});

const isDirty = computed(() => form.isDirty);
const { allowLeave } = useUnsavedChanges(isDirty);

function submit() {
    form.post('/admin/utilisateurs', {
        // Messages de succès/erreur pris en charge globalement (lib/inertia-feedback.ts) ;
        // il ne reste qu'à ramener la vue sur le champ fautif.
        onError: (errors) => focusFirstError(errors),
        onSuccess: () => allowLeave(),
    });
}

async function cancel() {
    if (form.isDirty) {
        const confirmed = await confirmAction({
            title: 'Abandonner la création de ce compte ?',
            description: "Rien n'a encore été enregistré : les informations saisies seront perdues.",
            confirmLabel: 'Abandonner',
            cancelLabel: 'Continuer la saisie',
            tone: 'danger',
        });
        if (!confirmed) return;
    }

    allowLeave();
    router.visit('/admin/utilisateurs');
}
</script>

<template>
    <Head title="Nouveau compte" />

    <form class="max-w-2xl space-y-6" @submit.prevent="submit">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-display text-2xl text-ink">Nouveau compte</h2>
            <div class="flex items-center gap-3">
                <Button variant="secondary" @click="cancel">Annuler</Button>
                <Button type="submit" :disabled="form.processing">
                    <UserPlus class="size-4" aria-hidden="true" />
                    {{ form.processing ? 'Création…' : 'Créer le compte' }}
                </Button>
            </div>
        </div>

        <FormErrorSummary
            :errors="form.errors"
            :labels="USER_FIELD_LABELS"
            title="Le compte n'a pas pu être créé"
        />

        <Card>
            <div class="space-y-4">
                <Input id="name" v-model="form.name" label="Nom complet" :error="form.errors.name" />
                <Input
                    id="email"
                    v-model="form.email"
                    type="email"
                    label="Adresse email"
                    autocomplete="off"
                    hint="C'est l'identifiant de connexion au back-office."
                    :error="form.errors.email"
                />
            </div>
        </Card>

        <Card>
            <RoleField v-model="form.role" :options="roles" :error="form.errors.role" />
        </Card>

        <Card>
            <h3 class="flex items-center gap-2 font-display text-lg text-ink">
                <KeyRound class="size-4 shrink-0 text-gold-600" aria-hidden="true" />
                Mot de passe provisoire
            </h3>
            <p class="mt-1 font-sans text-sm text-ink-soft">
                Communiquez-le de vive voix plutôt que par email ou SMS. La personne pourra le changer
                elle-même depuis « Mon compte ».
            </p>

            <div class="mt-4 space-y-4">
                <Input
                    id="password"
                    v-model="form.password"
                    type="password"
                    label="Mot de passe"
                    autocomplete="new-password"
                    hint="10 caractères minimum, avec au moins une lettre et un chiffre."
                    :error="form.errors.password"
                />
                <Input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    label="Confirmer le mot de passe"
                    autocomplete="new-password"
                />
            </div>
        </Card>
    </form>
</template>
