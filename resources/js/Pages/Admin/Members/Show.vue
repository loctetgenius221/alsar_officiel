<script setup lang="ts">
/**
 * Fiche adhérent (CLAUDE.md §6) : infos, historique paiements, actions (bascule statut,
 * renvoyer la carte, note interne). Le PDF de carte réutilise la route publique existante
 * (/adhesion/carte/{code}) plutôt que de dupliquer la génération (Phase 2).
 */
import { Head, router, setLayoutProps, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import { memberStatus, paymentStatus } from '@/lib/status';

defineOptions({ layout: AdminLayout });

const props = defineProps<{
    member: {
        id: number;
        membershipCode: string;
        fullName: string;
        email: string;
        phone: string;
        birthDate: string | null;
        gender: string;
        region: string;
        commune: string;
        address: string | null;
        profession: string | null;
        idDocumentNumber: string | null;
        photoUrl: string | null;
        section: string;
        referralSource: string | null;
        status: string;
        note: string | null;
        joinedAt: string | null;
        consentAt: string;
    };
    payments: {
        id: number;
        tier: string;
        amount: number;
        currency: string;
        gateway: string;
        status: string;
        paidAt: string | null;
    }[];
}>();

setLayoutProps({ title: props.member.fullName });

const noteForm = useForm({ note: props.member.note ?? '' });

function saveNote() {
    noteForm.patch(`/admin/adherents/${props.member.id}/note`, { preserveScroll: true });
}

function toggleStatus() {
    router.patch(`/admin/adherents/${props.member.id}/statut`, {}, { preserveScroll: true });
}

function resendCard() {
    router.post(`/admin/adherents/${props.member.id}/renvoyer-carte`, {}, { preserveScroll: true });
}

function formatAmount(amount: number, currency: string): string {
    return `${new Intl.NumberFormat('fr-FR').format(amount)} ${currency}`;
}
</script>

<template>
    <Head :title="member.fullName" />

    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="flex size-14 shrink-0 items-center justify-center overflow-hidden border border-sand-200 bg-sand-100">
                    <img v-if="member.photoUrl" :src="member.photoUrl" alt="" class="size-full object-cover" />
                    <span v-else class="font-sans text-[10px] text-ink-soft/70">Photo</span>
                </div>
                <div>
                    <h2 class="font-display text-2xl text-ink">{{ member.fullName }}</h2>
                    <p class="font-mono text-xs text-ink-soft">{{ member.membershipCode }}</p>
                </div>
            </div>
            <Badge :tone="memberStatus(member.status).tone">{{ memberStatus(member.status).label }}</Badge>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <Card class="lg:col-span-2">
                <h3 class="font-display text-lg text-ink">Informations</h3>
                <dl class="mt-4 grid gap-4 sm:grid-cols-2 font-sans text-sm">
                    <div>
                        <dt class="text-ink-soft">Email</dt>
                        <dd class="text-ink">{{ member.email }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-soft">Téléphone</dt>
                        <dd class="text-ink">{{ member.phone }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-soft">Région / Commune</dt>
                        <dd class="text-ink">{{ member.region }} — {{ member.commune }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-soft">Adresse</dt>
                        <dd class="text-ink">{{ member.address ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-soft">Section</dt>
                        <dd class="text-ink capitalize">{{ member.section }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-soft">Profession</dt>
                        <dd class="text-ink">{{ member.profession ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-soft">Numéro de pièce d'identité</dt>
                        <dd class="text-ink">{{ member.idDocumentNumber ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-soft">Connu ALSAR via</dt>
                        <dd class="text-ink">{{ member.referralSource ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-soft">Adhésion le</dt>
                        <dd class="text-ink">{{ member.joinedAt ?? 'Pas encore actif' }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-soft">Consentement donné le</dt>
                        <dd class="text-ink">{{ member.consentAt }}</dd>
                    </div>
                </dl>
            </Card>

            <Card>
                <h3 class="font-display text-lg text-ink">Actions</h3>
                <div class="mt-4 flex flex-col gap-3">
                    <a
                        v-if="member.status === 'active'"
                        :href="`/adhesion/carte/${member.membershipCode}`"
                        class="inline-flex items-center justify-center border border-sand-300 px-5 py-2.5 font-sans text-sm text-ink transition-colors hover:border-ink"
                    >
                        Télécharger la carte (PDF)
                    </a>
                    <Button v-if="member.status === 'active'" variant="secondary" @click="resendCard">
                        Renvoyer la carte par email
                    </Button>
                    <Button v-if="member.status !== 'pending'" variant="secondary" @click="toggleStatus">
                        {{ member.status === 'active' ? 'Désactiver' : 'Réactiver' }}
                    </Button>
                    <p v-if="member.status === 'pending'" class="font-sans text-xs text-ink-soft">
                        Cet adhérent n'a pas encore de cotisation confirmée — voir l'onglet Paiements pour
                        confirmer un règlement manuel.
                    </p>
                </div>
            </Card>
        </div>

        <Card>
            <h3 class="font-display text-lg text-ink">Historique des paiements</h3>
            <div v-if="payments.length" class="mt-4 overflow-x-auto">
                <table class="w-full text-left font-sans text-sm">
                    <thead>
                        <tr class="border-b border-sand-200 text-xs text-ink-soft uppercase">
                            <th class="py-2 font-medium">Palier</th>
                            <th class="py-2 font-medium">Montant</th>
                            <th class="py-2 font-medium">Passerelle</th>
                            <th class="py-2 font-medium">Statut</th>
                            <th class="py-2 font-medium">Payé le</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="payment in payments" :key="payment.id" class="border-b border-sand-100 last:border-0">
                            <td class="py-2 whitespace-nowrap text-ink capitalize">{{ payment.tier }}</td>
                            <td class="py-2 whitespace-nowrap text-ink">{{ formatAmount(payment.amount, payment.currency) }}</td>
                            <td class="py-2 whitespace-nowrap text-ink-soft">{{ payment.gateway }}</td>
                            <td class="py-2 whitespace-nowrap">
                                <Badge :tone="paymentStatus(payment.status).tone">{{ paymentStatus(payment.status).label }}</Badge>
                            </td>
                            <td class="py-2 whitespace-nowrap text-ink-soft">{{ payment.paidAt ?? '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-else class="mt-4 font-sans text-sm text-ink-soft">Aucun paiement enregistré.</p>
        </Card>

        <Card>
            <h3 class="font-display text-lg text-ink">Note interne</h3>
            <p class="mt-1 font-sans text-xs text-ink-soft">Visible uniquement par l'équipe ALSAR.</p>
            <form class="mt-4 space-y-3" @submit.prevent="saveNote">
                <textarea
                    id="note"
                    v-model="noteForm.note"
                    aria-label="Note interne"
                    rows="4"
                    class="w-full border border-sand-300 bg-paper px-4 py-2.5 font-sans text-sm text-ink focus-visible:border-gold-500"
                />
                <Button type="submit" size="sm" :disabled="noteForm.processing">Enregistrer la note</Button>
            </form>
        </Card>
    </div>
</template>
