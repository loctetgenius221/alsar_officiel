<script setup lang="ts">
/**
 * Comptes du back-office (CLAUDE.md §6 « Utilisateurs & rôles admin »). Même schéma que
 * Members/Index.vue et Articles/Index.vue : filtres poussés en query string, recherche
 * débouncée, pagination serveur.
 *
 * Pas de suppression : on bloque. Un compte supprimé emporterait la paternité des articles
 * qu'il a écrits, et un blocage se rend en un clic — cf. UserController.
 */
import { Head, Link, router, setLayoutProps } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Badge from '@/Components/ui/Badge.vue';
import Input from '@/Components/ui/Input.vue';
import Select from '@/Components/ui/Select.vue';
import Button from '@/Components/ui/Button.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Pagination from '@/Components/admin/Pagination.vue';
import { confirmAction } from '@/lib/confirm';
import type { RoleOption } from '@/Components/admin/RoleField.vue';
import { Ban, CheckCircle2, RotateCcw, Search, SearchX, UserCog, UserPlus } from '@lucide/vue';

defineOptions({ layout: AdminLayout });
setLayoutProps({ title: 'Utilisateurs' });

interface UserRow {
    /** UUID public : jamais la clé primaire, cf. app/Support/HasPublicId.php. */
    publicId: string;
    name: string;
    email: string;
    role: string | null;
    blocked: boolean;
    blockedAt: string | null;
    createdAt: string;
    isCurrent: boolean;
}

const props = defineProps<{
    users: {
        data: UserRow[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: { search?: string; role?: string; status?: string };
    roles: RoleOption[];
}>();

const search = ref(props.filters.search ?? '');
const role = ref(props.filters.role ?? '');
const status = ref(props.filters.status ?? '');

function applyFilters() {
    router.get(
        '/admin/utilisateurs',
        {
            search: search.value || undefined,
            role: role.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

const debouncedApply = useDebounceFn(applyFilters, 350);
watch(search, debouncedApply);
watch([role, status], applyFilters);

// Distingue « liste vide parce qu'on a filtré » de « liste vide parce qu'il n'y a rien ».
const hasFilters = computed(() => Boolean(search.value || role.value || status.value));

function resetFilters() {
    search.value = '';
    role.value = '';
    status.value = '';
}

// Le libellé affiché dans le filtre est celui du formulaire ; la valeur envoyée reste le nom
// du rôle tel qu'il est stocké (AdminRoles), pour que le serveur n'ait aucune traduction à faire.
const roleOptions = props.roles.map((option) => ({ value: option.value, label: option.label }));
const statusOptions = [
    { value: 'active', label: 'Actif' },
    { value: 'blocked', label: 'Bloqué' },
];

async function toggleBlock(user: UserRow) {
    const confirmed = await confirmAction(
        user.blocked
            ? {
                  title: `Rétablir l'accès de ${user.name} ?`,
                  description: 'Il pourra se reconnecter immédiatement avec son mot de passe habituel.',
                  confirmLabel: "Rétablir l'accès",
                  cancelLabel: 'Laisser bloqué',
              }
            : {
                  title: `Suspendre l'accès de ${user.name} ?`,
                  description:
                      'Sa session en cours sera fermée et il ne pourra plus se connecter. Son compte, ' +
                      'ses articles et son historique sont conservés : vous pourrez rétablir son accès à tout moment.',
                  confirmLabel: "Suspendre l'accès",
                  cancelLabel: 'Annuler',
                  tone: 'danger',
              },
    );
    if (!confirmed) return;

    router.patch(`/admin/utilisateurs/${user.publicId}/blocage`, {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Utilisateurs" />

    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="font-display text-2xl text-ink">Utilisateurs</h2>
                <p class="mt-1 font-sans text-sm text-ink-soft">
                    Qui accède au back-office, et jusqu'où. Chaque rôle ne voit que les écrans qui le
                    concernent.
                </p>
            </div>
            <Button href="/admin/utilisateurs/nouveau">
                <UserPlus class="size-4" aria-hidden="true" />
                Nouveau compte
            </Button>
        </div>

        <Card>
            <div class="grid gap-4 sm:grid-cols-3">
                <Input
                    id="search"
                    v-model="search"
                    label="Recherche"
                    placeholder="Nom ou email..."
                    :icon="Search"
                />
                <Select id="role" v-model="role" label="Rôle" :options="roleOptions" placeholder="Tous" />
                <Select
                    id="status"
                    v-model="status"
                    label="Accès"
                    :options="statusOptions"
                    placeholder="Tous"
                />
            </div>
        </Card>

        <Card :padded="false">
            <!-- `relative` n'est pas décoratif : sans bloc conteneur positionné ici, le
                 `<span class="sr-only">` de la dernière colonne (position: absolute) se place
                 par rapport au document et non par rapport à cette zone défilante. Il échappe
                 alors au `overflow-x-auto`, et la PAGE entière gagne un défilement horizontal
                 sur mobile alors que seul le tableau devait défiler. -->
            <div v-if="users.data.length" class="relative overflow-x-auto">
                <table class="w-full text-left font-sans text-sm">
                    <thead>
                        <tr class="border-b border-sand-200 text-xs text-ink-soft uppercase">
                            <th class="px-6 py-3 font-medium">Utilisateur</th>
                            <th class="px-6 py-3 font-medium">Rôle</th>
                            <th class="px-6 py-3 font-medium">Accès</th>
                            <th class="px-6 py-3 font-medium">Créé le</th>
                            <th class="px-6 py-3 font-medium"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="user in users.data"
                            :key="user.publicId"
                            class="border-b border-sand-100 last:border-0 hover:bg-sand-50"
                        >
                            <td class="px-6 py-3">
                                <Link
                                    :href="`/admin/utilisateurs/${user.publicId}/modifier`"
                                    class="font-medium text-ink hover:underline"
                                >
                                    {{ user.name }}
                                </Link>
                                <p class="text-xs text-ink-soft">{{ user.email }}</p>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-ink-soft">{{ user.role ?? '—' }}</td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                <!-- Même grammaire que les pastilles de statut
                                     (Components/admin/StatusBadge.vue) : icône + libellé, pour
                                     que l'état se lise sans dépendre de la seule couleur. -->
                                <Badge :tone="user.blocked ? 'danger' : 'success'">
                                    <component
                                        :is="user.blocked ? Ban : CheckCircle2"
                                        class="size-3.5 shrink-0"
                                        aria-hidden="true"
                                    />
                                    {{ user.blocked ? 'Bloqué' : 'Actif' }}
                                </Badge>
                                <p v-if="user.blockedAt" class="mt-1 text-xs text-ink-soft">
                                    depuis le {{ user.blockedAt }}
                                </p>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-ink-soft">{{ user.createdAt }}</td>
                            <td class="px-6 py-3 text-right whitespace-nowrap">
                                <!-- Son propre compte : l'action est absente, pas grisée — on
                                     n'a pas à expliquer un bouton inactif dans un tableau.
                                     L'explication complète est sur l'écran de modification. -->
                                <span v-if="user.isCurrent" class="text-xs text-ink-soft">Votre compte</span>
                                <button
                                    v-else
                                    type="button"
                                    class="inline-flex items-center gap-1.5 p-1.5 text-xs text-ink-soft transition-colors"
                                    :class="user.blocked ? 'hover:text-success' : 'hover:text-danger'"
                                    :title="
                                        user.blocked
                                            ? `Rétablir l'accès de ${user.name}`
                                            : `Suspendre l'accès de ${user.name}`
                                    "
                                    @click="toggleBlock(user)"
                                >
                                    <component
                                        :is="user.blocked ? RotateCcw : Ban"
                                        class="size-4"
                                        aria-hidden="true"
                                    />
                                    {{ user.blocked ? 'Rétablir' : 'Bloquer' }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Deux états vides distincts : une recherche infructueuse n'appelle pas la même
                 réaction qu'un back-office où personne n'a encore été invité. -->
            <EmptyState
                v-else-if="hasFilters"
                :icon="SearchX"
                title="Aucun compte ne correspond à ces filtres"
                description="Essayez un autre nom, ou remettez les filtres à zéro pour voir tous les comptes."
            >
                <Button variant="secondary" @click="resetFilters">Réinitialiser les filtres</Button>
            </EmptyState>
            <EmptyState
                v-else
                :icon="UserCog"
                title="Vous êtes seul sur le back-office"
                description="Créez un compte pour chaque personne qui doit publier ou gérer du contenu : chacune aura son propre mot de passe et ne verra que ce qui la concerne."
            >
                <Button href="/admin/utilisateurs/nouveau">
                    <UserPlus class="size-4" aria-hidden="true" />
                    Créer un compte
                </Button>
            </EmptyState>

            <Pagination :links="users.links" :from="users.from" :to="users.to" :total="users.total" />
        </Card>
    </div>
</template>
