<script setup lang="ts">
/**
 * Dashboard admin (CLAUDE.md §6) : KPIs réels calculés en base (DashboardController), pas de
 * chiffres statiques.
 *
 * Écran commun à tous les rôles, et donc à géométrie variable : le serveur n'envoie que les
 * blocs que la permission de l'utilisateur couvre (`kpis`/`regionBreakdown`/`memberTrend` pour
 * members.manage, `contentKpis` pour articles.manage). Les `v-if` ci-dessous ne masquent donc
 * pas des données reçues — elles rendent ce qui est arrivé. Un rédacteur voit l'état de ses
 * publications, pas le nombre d'adhérents.
 *
 * Iconographie : chaque carte de chiffre porte le glyphe de ce qu'elle compte, dans la même
 * teinte que la pastille de statut correspondante (un article publié est vert ici comme dans
 * la liste). L'icône sert de point d'accroche pour retrouver une carte d'un coup d'œil ; elle
 * est toujours `aria-hidden`, le libellé juste à côté disant déjà la même chose.
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
import { Head, Link, setLayoutProps } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Button from '@/Components/ui/Button.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import StatusBadge from '@/Components/admin/StatusBadge.vue';
import TrendChart from '@/Components/admin/charts/TrendChart.vue';
import HorizontalBarChart, { type BarRow } from '@/Components/admin/charts/HorizontalBarChart.vue';
import { useCountUp } from '@/Composables/useCountUp';
import {
    CalendarClock,
    CheckCircle2,
    MapPin,
    Newspaper,
    PenLine,
    Plus,
    TrendingUp,
    UserPlus,
    Users,
} from '@lucide/vue';

defineOptions({ layout: AdminLayout });
setLayoutProps({ title: 'Dashboard' });

const props = defineProps<{
    kpis?: {
        newMembers30d: number;
        totalMembers: number;
    };
    regionBreakdown?: { region: string; total: number }[];
    memberTrend?: { label: string; value: number }[];
    contentKpis?: {
        published: number;
        scheduled: number;
        drafts: number;
        latest: { publicId: string; title: string; status: string; updatedAt: string }[];
    };
}>();

function formatCount(value: number): string {
    return new Intl.NumberFormat('fr-FR').format(value);
}

// Appels conditionnels assumés : la présence de `kpis` dépend du rôle et ne change jamais pour
// une instance donnée de ce composant. Sans cette garde, useCountUp installerait un
// ScrollTrigger sur un élément qui n'est pas rendu.
const newMembersRef = ref<HTMLElement | null>(null);
const newMembersValue = props.kpis ? useCountUp(newMembersRef, props.kpis.newMembers30d).value : ref(0);

const totalMembersRef = ref<HTMLElement | null>(null);
const totalMembersValue = props.kpis ? useCountUp(totalMembersRef, props.kpis.totalMembers).value : ref(0);

const regionRows = computed<BarRow[]>(() =>
    (props.regionBreakdown ?? []).map((row) => ({
        key: row.region,
        label: row.region,
        value: row.total,
        tone: 'gold',
    })),
);

// Teintes reprises des tonalités de statut (lib/status.ts) : le vert du « publié » de la liste
// d'articles est le même ici, le orange du « programmé » aussi.
const contentCards = computed(() =>
    props.contentKpis
        ? [
              {
                  label: 'Articles publiés',
                  value: props.contentKpis.published,
                  icon: CheckCircle2,
                  tint: 'bg-success/10 text-success',
              },
              {
                  label: 'Programmés',
                  value: props.contentKpis.scheduled,
                  icon: CalendarClock,
                  tint: 'bg-warning/10 text-warning',
              },
              {
                  label: 'Brouillons',
                  value: props.contentKpis.drafts,
                  icon: PenLine,
                  tint: 'bg-sand-100 text-ink-soft',
              },
          ]
        : [],
);
</script>

<template>
    <Head title="Dashboard" />

    <div class="space-y-8">
        <template v-if="kpis">
            <div class="grid gap-4 sm:grid-cols-2">
                <Card>
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="font-sans text-xs tracking-wide text-ink-soft uppercase">
                                Nouveaux adhérents (30j)
                            </p>
                            <p ref="newMembersRef" class="mt-2 font-display text-numeral text-ink">
                                {{ newMembersValue }}
                            </p>
                        </div>
                        <span
                            class="flex size-10 shrink-0 items-center justify-center bg-gold-500/10 text-gold-700"
                            aria-hidden="true"
                        >
                            <UserPlus class="size-5" />
                        </span>
                    </div>
                </Card>
                <Card>
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="font-sans text-xs tracking-wide text-ink-soft uppercase">
                                Total adhérents
                            </p>
                            <p ref="totalMembersRef" class="mt-2 font-display text-numeral text-ink">
                                {{ totalMembersValue }}
                            </p>
                        </div>
                        <span
                            class="flex size-10 shrink-0 items-center justify-center bg-gold-500/10 text-gold-700"
                            aria-hidden="true"
                        >
                            <Users class="size-5" />
                        </span>
                    </div>
                </Card>
            </div>

            <!-- `items-start` : par défaut les cellules d'une grille s'étirent à la hauteur de
                 la plus haute. La courbe de tendance a un rapport hauteur/largeur fixe (SVG en
                 viewBox, cf. TrendChart) et ne peut donc pas occuper la place gagnée : sa carte
                 se retrouvait avec un grand vide sous le graphique, proportionnel au nombre de
                 régions affichées à côté. Chaque carte prend désormais sa hauteur naturelle —
                 étirer un conteneur dont le contenu ne suit pas ne produit jamais que du vide. -->
            <div class="grid items-start gap-6 lg:grid-cols-2">
                <Card>
                    <h2 class="flex items-center gap-2 font-display text-lg text-ink">
                        <TrendingUp class="size-4 shrink-0 text-gold-600" aria-hidden="true" />
                        Nouveaux adhérents
                    </h2>
                    <p class="font-sans text-xs text-ink-soft">6 derniers mois</p>
                    <div class="mt-4">
                        <TrendChart
                            :points="memberTrend ?? []"
                            variant="area"
                            :value-formatter="formatCount"
                        />
                    </div>
                </Card>
                <Card>
                    <h2 class="flex items-center gap-2 font-display text-lg text-ink">
                        <MapPin class="size-4 shrink-0 text-gold-600" aria-hidden="true" />
                        Répartition par région
                    </h2>
                    <div class="mt-4">
                        <HorizontalBarChart
                            :rows="regionRows"
                            :value-formatter="formatCount"
                            empty-text="Aucun adhérent pour l'instant."
                        />
                    </div>
                </Card>
            </div>
        </template>

        <!-- Bloc contenu : pour un rédacteur, c'est tout son dashboard — d'où les trois états de
             publication en évidence, et un accès direct aux derniers articles touchés. -->
        <template v-if="contentKpis">
            <div class="grid gap-4 sm:grid-cols-3">
                <Card v-for="card in contentCards" :key="card.label">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="font-sans text-xs tracking-wide text-ink-soft uppercase">
                                {{ card.label }}
                            </p>
                            <p class="mt-2 font-display text-numeral text-ink">
                                {{ formatCount(card.value) }}
                            </p>
                        </div>
                        <span
                            class="flex size-10 shrink-0 items-center justify-center"
                            :class="card.tint"
                            aria-hidden="true"
                        >
                            <component :is="card.icon" class="size-5" />
                        </span>
                    </div>
                </Card>
            </div>

            <Card :padded="contentKpis.latest.length > 0">
                <div v-if="contentKpis.latest.length">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <h2 class="flex items-center gap-2 font-display text-lg text-ink">
                            <Newspaper class="size-4 shrink-0 text-gold-600" aria-hidden="true" />
                            Derniers articles modifiés
                        </h2>
                        <Button href="/admin/actualites/nouveau" size="sm">
                            <Plus class="size-4" aria-hidden="true" />
                            Nouvel article
                        </Button>
                    </div>

                    <ul class="mt-4 divide-y divide-sand-100">
                        <li
                            v-for="article in contentKpis.latest"
                            :key="article.publicId"
                            class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0"
                        >
                            <Link
                                :href="`/admin/actualites/${article.publicId}/modifier`"
                                class="min-w-0 font-sans text-sm font-medium text-ink hover:underline"
                            >
                                {{ article.title }}
                            </Link>
                            <span class="flex shrink-0 items-center gap-3">
                                <StatusBadge kind="article" :status="article.status" />
                                <span class="font-sans text-xs text-ink-soft">{{ article.updatedAt }}</span>
                            </span>
                        </li>
                    </ul>
                </div>

                <EmptyState
                    v-else
                    :icon="Newspaper"
                    title="Aucun article pour l'instant"
                    description="Le premier que vous publierez apparaîtra ici, avec son état et sa date de dernière modification."
                >
                    <Button href="/admin/actualites/nouveau">
                        <Plus class="size-4" aria-hidden="true" />
                        Écrire le premier article
                    </Button>
                </EmptyState>
            </Card>
        </template>
    </div>
</template>
