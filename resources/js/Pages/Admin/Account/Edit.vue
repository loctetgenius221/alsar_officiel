<script setup lang="ts">
/**
 * « Mon compte » — accessible à tous les rôles, sans permission particulière.
 *
 * C'est le seul endroit où un gestionnaire de contenu ou un rédacteur peut remplacer le mot de
 * passe provisoire que l'administrateur lui a communiqué : les écrans /admin/utilisateurs leur
 * sont fermés. Le rôle est affiché mais non modifiable — personne ne s'accorde ses propres droits.
 */
import { Head, setLayoutProps, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Input from '@/Components/ui/Input.vue';
import Button from '@/Components/ui/Button.vue';
import FormErrorSummary from '@/Components/ui/FormErrorSummary.vue';
import { USER_FIELD_LABELS } from '@/lib/field-labels';
import { focusFirstError } from '@/lib/form-errors';
import { KeyRound, Save, UserRound } from '@lucide/vue';

defineOptions({ layout: AdminLayout });
setLayoutProps({ title: 'Mon compte' });

const props = defineProps<{
    account: {
        name: string;
        email: string;
        role: string | null;
        createdAt: string;
    };
}>();

const profileForm = useForm({
    name: props.account.name,
    email: props.account.email,
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

function saveProfile() {
    profileForm.patch('/admin/mon-compte', {
        preserveScroll: true,
        onError: (errors) => focusFirstError(errors),
    });
}

function savePassword() {
    passwordForm.patch('/admin/mon-compte/mot-de-passe', {
        preserveScroll: true,
        onError: (errors) => focusFirstError(errors),
        onSuccess: () => passwordForm.reset(),
    });
}
</script>

<template>
    <Head title="Mon compte" />

    <div class="space-y-6">
        <div>
            <h2 class="font-display text-2xl text-ink">Mon compte</h2>
            <p class="mt-1 font-sans text-sm text-ink-soft">
                Rôle : <span class="text-ink">{{ account.role ?? '—' }}</span> — compte créé le
                {{ account.createdAt }}. Seul un administrateur peut changer un rôle.
            </p>
        </div>

        <!-- Deux formulaires indépendants et de taille comparable : côte à côte sur grand
             écran plutôt qu'empilés dans une colonne étroite, qui laissait la moitié de
             l'écran vide. `items-start` évite que la carte la plus courte s'étire. -->
        <div class="grid items-start gap-6 lg:grid-cols-2">
            <form @submit.prevent="saveProfile">
                <FormErrorSummary
                    :errors="profileForm.errors"
                    :labels="USER_FIELD_LABELS"
                    title="Vos informations n'ont pas pu être enregistrées"
                />

                <Card class="mt-6">
                    <h3 class="flex items-center gap-2 font-display text-lg text-ink">
                        <UserRound class="size-4 shrink-0 text-gold-600" aria-hidden="true" />
                        Mes informations
                    </h3>

                    <div class="mt-4 space-y-4">
                        <Input
                            id="name"
                            v-model="profileForm.name"
                            label="Nom complet"
                            :error="profileForm.errors.name"
                        />
                        <Input
                            id="email"
                            v-model="profileForm.email"
                            type="email"
                            label="Adresse email"
                            autocomplete="username"
                            hint="C'est votre identifiant de connexion."
                            :error="profileForm.errors.email"
                        />
                    </div>

                    <div class="mt-6 flex justify-end">
                        <Button type="submit" :disabled="profileForm.processing">
                            <Save class="size-4" aria-hidden="true" />
                            {{ profileForm.processing ? 'Enregistrement…' : 'Enregistrer' }}
                        </Button>
                    </div>
                </Card>
            </form>

            <form @submit.prevent="savePassword">
                <Card>
                    <h3 class="flex items-center gap-2 font-display text-lg text-ink">
                        <KeyRound class="size-4 shrink-0 text-gold-600" aria-hidden="true" />
                        Changer mon mot de passe
                    </h3>
                    <p class="mt-1 font-sans text-sm text-ink-soft">
                        Si vous utilisez encore le mot de passe qu'un administrateur vous a communiqué, c'est
                        le moment de le remplacer : lui seul le connaît aussi.
                    </p>

                    <div class="mt-4 space-y-4">
                        <Input
                            id="current_password"
                            v-model="passwordForm.current_password"
                            type="password"
                            label="Mot de passe actuel"
                            autocomplete="current-password"
                            :error="passwordForm.errors.current_password"
                        />
                        <Input
                            id="password"
                            v-model="passwordForm.password"
                            type="password"
                            label="Nouveau mot de passe"
                            autocomplete="new-password"
                            hint="10 caractères minimum, avec au moins une lettre et un chiffre."
                            :error="passwordForm.errors.password"
                        />
                        <Input
                            id="password_confirmation"
                            v-model="passwordForm.password_confirmation"
                            type="password"
                            label="Confirmer le nouveau mot de passe"
                            autocomplete="new-password"
                        />
                    </div>

                    <div class="mt-6 flex justify-end">
                        <Button type="submit" variant="secondary" :disabled="passwordForm.processing">
                            <KeyRound class="size-4" aria-hidden="true" />
                            {{ passwordForm.processing ? 'Enregistrement…' : 'Changer mon mot de passe' }}
                        </Button>
                    </div>
                </Card>
            </form>
        </div>
    </div>
</template>
