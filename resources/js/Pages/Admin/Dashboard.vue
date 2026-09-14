<script setup lang="ts">
/**
 * Dashboard admin (CLAUDE.md §6) : KPIs réels calculés en base (DashboardController), pas de
 * chiffres statiques.
 *
 * Cotisation désactivée pour le moment (décision produit) : tous les widgets liés aux
 * paiements (montant collecté, taux d'adhésion payante, tendance de revenus, dernières
 * transactions, répartition par palier, statut des paiements) sont masqués ici — le
 * contrôleur continue de les calculer et de les transmettre (code intact, réactivable sans
 * y retoucher), ce composant choisit simplement de ne pas les afficher tant qu'aucun
 * paiement n'est créé.
 *
 * Graphiques (skill dataviz) : une seule teinte séquentielle (gold) pour la tendance des
 * adhérents et la répartition régionale — une série, la magnitude est portée par la
 * longueur/hauteur, pas par la couleur. Cf. TrendChart/HorizontalBarChart.
 */
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Card from '@/Components/ui/Card.vue';
import TrendChart from '@/Components/admin/charts/TrendChart.vue';
import HorizontalBarChart, { type BarRow } from '@/Components/admin/charts/HorizontalBarChart.vue';
import { useCountUp } from '@/Composables/useCountUp';

defineOptions({ layout: AdminLayout });
setLayoutProps({ title: 'Dashboard' });

const props = defineProps<{
    kpis: {
        newMembers30d: number;
        totalMembers: number;
    };
    regionBreakdown: { region: string; total: number }[];
    memberTrend: { label: string; value: number }[];
}>();

function formatCount(value: number): string {
    return new Intl.NumberFormat('fr-FR').format(value);
}

const newMembersRef = ref<HTMLElement | null>(null);
const { value: newMembersValue } = useCountUp(newMembersRef, props.kpis.newMembers30d);

const totalMembersRef = ref<HTMLElement | null>(null);
const { value: totalMembersValue } = useCountUp(totalMembersRef, props.kpis.totalMembers);

const regionRows = computed<BarRow[]>(() =>
    props.regionBreakdown.map((row) => ({ key: row.region, label: row.region, value: row.total, tone: 'gold' })),
);
</script>

<template>
    <Head title="Dashboard" />

    <div class="space-y-8">
        <div class="grid gap-4 sm:grid-cols-2">
            <Card>
                <p class="font-sans text-xs tracking-wide text-ink-soft uppercase">Nouveaux adhérents (30j)</p>
                <p ref="newMembersRef" class="mt-2 font-display text-numeral text-ink">{{ newMembersValue }}</p>
            </Card>
            <Card>
                <p class="font-sans text-xs tracking-wide text-ink-soft uppercase">Total adhérents</p>
                <p ref="totalMembersRef" class="mt-2 font-display text-numeral text-ink">{{ totalMembersValue }}</p>
            </Card>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <Card>
                <h2 class="font-display text-lg text-ink">Nouveaux adhérents</h2>
                <p class="font-sans text-xs text-ink-soft">6 derniers mois</p>
                <div class="mt-4">
                    <TrendChart :points="memberTrend" variant="area" :value-formatter="formatCount" />
                </div>
            </Card>
            <Card>
                <h2 class="font-display text-lg text-ink">Répartition par région</h2>
                <div class="mt-4">
                    <HorizontalBarChart :rows="regionRows" :value-formatter="formatCount" empty-text="Aucun adhérent pour l'instant." />
                </div>
            </Card>
        </div>
    </div>
</template>
