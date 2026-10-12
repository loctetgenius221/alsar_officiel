<script setup lang="ts">
/**
 * Modification d'un compte back-office, en trois blocs indépendants plutôt qu'un seul
 * formulaire géant : on vient ici pour UNE raison à la fois (corriger un nom, redonner un mot
 * de passe oublié, suspendre un accès), et chaque bloc s'enregistre seul sans embarquer les
 * deux autres.
 *
 * Les garde-fous (son propre rôle, dernier administrateur actif) sont appliqués par le serveur
 * — UserController. Ici on les ANTICIPE à l'écran, en expliquant pourquoi l'action est
 * indisponible plutôt qu'en laissant l'utilisateur buter dessus.
 */
import { Head, router, setLayoutProps, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Badge from '@/Components/ui/Badge.vue';
import Input from '@/Components/ui/Input.vue';
import Button from '@/Components/ui/Button.vue';
import FormErrorSummary from '@/Components/ui/FormErrorSummary.vue';
import RoleField, { type RoleOption } from '@/Components/admin/RoleField.vue';
import { USER_FIELD_LABELS } from '@/lib/field-labels';
import { focusFirstError } from '@/lib/form-errors';
import { confirmAction } from '@/lib/confirm';
import { ArrowLeft, Ban, KeyRound, RotateCcw, Save, ShieldCheck } from '@lucide/vue';

defineOptions({ layout: AdminLayout });
setLayoutProps({ title: 'Modifier un compte' });

const props = defineProps<{
    user: {
        /** UUID public : jamais la clé primaire, cf. app/Support/HasPublicId.php. */
        publicId: string;
        name: string;
        email: string;
        role: string | null;
        blocked: boolean;
        blockedAt: string | null;
        createdAt: string;
        isCurrent: boolean;
        isLastSuperAdmin: boolean;
    };
    roles: RoleOption[];
}>();

const profileForm = useForm({
    name: props.user.name,
    email: props.user.email,
    role: props.user.role ?? '',
});

const passwordForm = useForm({
    password: '',
    password_confirmation: '',
});

const roleLocked = props.user.isCurrent || props.user.isLastSuperAdmin;
const roleLockReason = props.user.isCurrent
    ? 'Vous ne pouvez pas changer votre propre rôle : vous risqueriez de vous retirer vous-même ' +
      "l'accès. Demandez-le à un autre administrateur."
    : "Ce compte est le dernier administrateur actif. Nommez d'abord un autre administrateur, " +
      'sinon plus personne ne pourra gérer les comptes.';

function saveProfile() {
    profileForm.patch(`/admin/utilisateurs/${props.user.publicId}`, {
        preserveScroll: true,
        onError: (errors) => focusFirstError(errors),
    });
}

function savePassword() {
    passwordForm.patch(`/admin/utilisateurs/${props.user.publicId}/mot-de-passe`, {
        preserveScroll: true,
        onError: (errors) => focusFirstError(errors),
        onSuccess: () => passwordForm.reset(),
    });
}

async function toggleBlock() {
    const confirmed = await confirmAction(
        props.user.blocked
            ? {
                  title: `Rétablir l'accès de ${props.user.name} ?`,
                  description: 'Il pourra se reconnecter immédiatement avec son mot de passe habituel.',
                  confirmLabel: "Rétablir l'accès",
                  cancelLabel: 'Laisser bloqué',
              }
            : {
                  title: `Suspendre l'accès de ${props.user.name} ?`,
                  description:
                      'Sa session en cours sera fermée et il ne pourra plus se connecter. Son compte, ' +
                      'ses articles et son historique sont conservés.',
                  confirmLabel: "Suspendre l'accès",
                  cancelLabel: 'Annuler',
                  tone: 'danger',
              },
    );
    if (!confirmed) return;

    router.patch(`/admin/utilisateurs/${props.user.publicId}/blocage`, {}, { preserveScroll: true });
}
</script>

<template>
    <Head :title="user.name" />

    <div class="space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="font-display text-2xl text-ink">{{ user.name }}</h2>
                <p class="mt-1 font-sans text-sm text-ink-soft">
                    Compte créé le {{ user.createdAt }}
                    <span v-if="user.isCurrent"> — c'est le vôtre.</span>
                </p>
            </div>
            <Badge :tone="user.blocked ? 'danger' : 'success'">{{ user.blocked ? 'Bloqué' : 'Actif' }}</Badge>
        </div>

        <!-- Deux colonnes sur grand écran, comme la fiche adhérent (Members/Show.vue) :
             l'identité et le rôle d'un côté, les deux actions ponctuelles de l'autre. En une
             seule colonne étroite, la moitié droite de l'écran restait vide sur un poste de
             bureau et il fallait défiler pour atteindre le blocage.

             `items-start` : sans lui, les deux colonnes de la grille s'étirent à la hauteur de
             la plus haute, et la carte « Accès » afficherait un grand vide sous son bouton. -->
        <div class="grid items-start gap-6 lg:grid-cols-3">
            <form class="space-y-6 lg:col-span-2" @submit.prevent="saveProfile">
                <FormErrorSummary
                    :errors="profileForm.errors"
                    :labels="USER_FIELD_LABELS"
                    title="Les modifications n'ont pas pu être enregistrées"
                />

                <Card>
                    <div class="space-y-4">
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
                            hint="C'est l'identifiant de connexion au back-office."
                            :error="profileForm.errors.email"
                        />
                    </div>

                    <div class="mt-6 border-t border-sand-200 pt-6">
                        <RoleField
                            v-model="profileForm.role"
                            :options="roles"
                            :error="profileForm.errors.role"
                            :disabled="roleLocked"
                            :disabled-reason="roleLockReason"
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

            <div class="space-y-6">
                <Card>
                    <h3 class="flex items-center gap-2 font-display text-lg text-ink">
                        <ShieldCheck class="size-4 shrink-0 text-gold-600" aria-hidden="true" />
                        Accès au back-office
                    </h3>

                    <p v-if="user.isCurrent" class="mt-1 font-sans text-sm text-ink-soft">
                        Vous ne pouvez pas bloquer votre propre compte : vous perdriez immédiatement l'accès.
                        Un autre administrateur peut le faire à votre place.
                    </p>
                    <template v-else>
                        <p class="mt-1 font-sans text-sm text-ink-soft">
                            <template v-if="user.blocked">
                                Accès suspendu depuis le {{ user.blockedAt }}. Le compte, ses articles et son
                                historique sont intacts.
                            </template>
                            <template v-else>
                                Bloquer ferme la session en cours et empêche toute nouvelle connexion. Rien
                                n'est supprimé : l'accès se rétablit en un clic.
                            </template>
                        </p>

                        <div class="mt-6 flex justify-end">
                            <Button :variant="user.blocked ? 'secondary' : 'danger'" @click="toggleBlock">
                                <component
                                    :is="user.blocked ? RotateCcw : Ban"
                                    class="size-4"
                                    aria-hidden="true"
                                />
                                {{ user.blocked ? "Rétablir l'accès" : "Suspendre l'accès" }}
                            </Button>
                        </div>
                    </template>
                </Card>

                <form @submit.prevent="savePassword">
                    <Card>
                        <h3 class="flex items-center gap-2 font-display text-lg text-ink">
                            <KeyRound class="size-4 shrink-0 text-gold-600" aria-hidden="true" />
                            Réinitialiser le mot de passe
                        </h3>
                        <p class="mt-1 font-sans text-sm text-ink-soft">
                            À utiliser quand la personne a oublié le sien. L'ancien mot de passe cesse
                            aussitôt de fonctionner ; communiquez le nouveau de vive voix.
                        </p>

                        <div class="mt-4 space-y-4">
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
                                label="Confirmer le mot de passe"
                                autocomplete="new-password"
                            />
                        </div>

                        <div class="mt-6 flex justify-end">
                            <Button type="submit" variant="secondary" :disabled="passwordForm.processing">
                                <KeyRound class="size-4" aria-hidden="true" />
                                {{
                                    passwordForm.processing ? 'Enregistrement…' : 'Remplacer le mot de passe'
                                }}
                            </Button>
                        </div>
                    </Card>
                </form>
            </div>
        </div>

        <div>
            <Button variant="ghost" href="/admin/utilisateurs">
                <ArrowLeft class="size-4" aria-hidden="true" />
                Retour à la liste
            </Button>
        </div>
    </div>
</template>
