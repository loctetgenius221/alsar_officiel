<script setup lang="ts">
/**
 * Liste des paiements (CLAUDE.md §6) : filtres statut/palier, confirmation manuelle d'un
 * paiement "pending" réglé hors-ligne (cash/virement) — cas réel pour le rôle Trésorier.
 */
import { Head, router, setLayoutProps } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Select from '@/Components/ui/Select.vue';
import Button from '@/Components/ui/Button.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import StatusBadge from '@/Components/admin/StatusBadge.vue';
import Pagination from '@/Components/admin/Pagination.vue';
import { confirmAction } from '@/lib/confirm';
import { Check, Wallet } from '@lucide/vue';

defineOptions({ layout: AdminLayout });
setLayoutProps({ title: 'Paiements' });

const props = defineProps<{
    payments: {
        data: {
            /** UUID public : jamais la clé primaire, cf. app/Support/HasPublicId.php. */
            publicId: string;
            memberName: string;
            membershipCode: string | null;
            tier: string;
            amount: number;
            currency: string;
            gateway: string;
            gatewayReference: string | null;
            status: string;
            paidAt: string | null;
            createdAt: string;
        }[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: { status?: string; tier?: string };
    tiers: Record<string, { label: string; amount: number }>;
}>();

const status = ref(props.filters.status ?? '');
const tier = ref(props.filters.tier ?? '');

watch([status, tier], () => {
    router.get(
        '/admin/paiements',
        { status: status.value || undefined, tier: tier.value || undefined },
        { preserveState: true, replace: true },
    );
});

function formatAmount(amount: number, currency: string): string {
    return `${new Intl.NumberFormat('fr-FR').format(amount)} ${currency}`;
}

async function confirmManually(payment: {
    publicId: string;
    memberName: string;
    amount: number;
    currency: string;
}) {
    // Action comptable irréversible depuis l'interface : la question reprend le nom de
    // l'adhérent et le montant exact, les deux points sur lesquels on peut se tromper de ligne.
    const confirmed = await confirmAction({
        title: `Enregistrer le paiement de ${payment.memberName} ?`,
        description: `Vous déclarez avoir reçu ${formatAmount(payment.amount, payment.currency)} en espèces ou par virement. L'adhérent sera immédiatement activé et recevra sa carte de membre par email. Cette confirmation ne peut pas être annulée depuis le back-office.`,
        confirmLabel: 'Confirmer le règlement',
        cancelLabel: 'Annuler',
    });
    if (!confirmed) return;

    router.post(`/admin/paiements/${payment.publicId}/confirmer`, {}, { preserveScroll: true });
}

const statusOptions = [
    { value: 'pending', label: 'En attente' },
    { value: 'success', label: 'Réussi' },
    { value: 'failed', label: 'Échoué' },
    { value: 'refunded', label: 'Remboursé' },
];
const tierOptions = Object.entries(props.tiers).map(([value, tierInfo]) => ({
    value,
    label: tierInfo.label,
}));
</script>

<template>
    <Head title="Paiements" />

    <div class="space-y-6">
        <h2 class="font-display text-2xl text-ink">Paiements</h2>

        <Card>
            <div class="grid gap-4 sm:grid-cols-2">
                <Select
                    id="status"
                    v-model="status"
                    label="Statut"
                    :options="statusOptions"
                    placeholder="Tous"
                />
                <Select id="tier" v-model="tier" label="Palier" :options="tierOptions" placeholder="Tous" />
            </div>
        </Card>

        <Card :padded="false">
            <div v-if="payments.data.length" class="overflow-x-auto">
                <table class="w-full text-left font-sans text-sm">
                    <thead>
                        <tr class="border-b border-sand-200 text-xs text-ink-soft uppercase">
                            <th class="px-6 py-3 font-medium">Adhérent</th>
                            <th class="px-6 py-3 font-medium">Palier</th>
                            <th class="px-6 py-3 font-medium">Montant</th>
                            <th class="px-6 py-3 font-medium">Référence</th>
                            <th class="px-6 py-3 font-medium">Statut</th>
                            <th class="px-6 py-3 font-medium">Date</th>
                            <th class="px-6 py-3 font-medium">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="payment in payments.data"
                            :key="payment.publicId"
                            class="border-b border-sand-100 last:border-0"
                        >
                            <td class="px-6 py-3 whitespace-nowrap text-ink">{{ payment.memberName }}</td>
                            <td class="px-6 py-3 whitespace-nowrap text-ink-soft capitalize">
                                {{ payment.tier }}
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-ink">
                                {{ formatAmount(payment.amount, payment.currency) }}
                            </td>
                            <td class="px-6 py-3 font-mono text-xs whitespace-nowrap text-ink-soft">
                                {{ payment.gatewayReference ?? '—' }}
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                <StatusBadge kind="payment" :status="payment.status" />
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-ink-soft">{{ payment.createdAt }}</td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                <Button
                                    v-if="payment.status === 'pending'"
                                    size="sm"
                                    variant="secondary"
                                    @click="confirmManually(payment)"
                                >
                                    <Check class="size-4" aria-hidden="true" />
                                    Confirmer
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <EmptyState
                :icon="Wallet"
                title="Aucun paiement à afficher"
                description="Les cotisations réglées depuis le site apparaissent ici. Un règlement reçu en espèces ou par virement se confirme à la main depuis cette liste."
            />

            <Pagination
                :links="payments.links"
                :from="payments.from"
                :to="payments.to"
                :total="payments.total"
            />
        </Card>
    </div>
</template>
