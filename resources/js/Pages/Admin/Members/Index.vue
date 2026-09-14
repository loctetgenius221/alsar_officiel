<script setup lang="ts">
/**
 * Liste des adhérents (CLAUDE.md §6) : recherche + filtres région/statut/section, export CSV,
 * pagination. Les filtres sont poussés en query string (router.get) pour rester partageables
 * par URL et pour que l'export CSV applique exactement les mêmes filtres que la liste.
 */
import { Head, Link, router, setLayoutProps } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Badge from '@/Components/ui/Badge.vue';
import Input from '@/Components/ui/Input.vue';
import Select from '@/Components/ui/Select.vue';
import Pagination from '@/Components/admin/Pagination.vue';
import { memberStatus } from '@/lib/status';

defineOptions({ layout: AdminLayout });
setLayoutProps({ title: 'Adhérents' });

const props = defineProps<{
    members: {
        data: {
            id: number;
            membershipCode: string;
            fullName: string;
            email: string;
            phone: string;
            region: string;
            section: string;
            status: string;
            joinedAt: string | null;
        }[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: { search?: string; region?: string; status?: string; section?: string };
    regions: string[];
}>();

const search = ref(props.filters.search ?? '');
const region = ref(props.filters.region ?? '');
const status = ref(props.filters.status ?? '');
const section = ref(props.filters.section ?? '');

function applyFilters() {
    router.get(
        '/admin/adherents',
        {
            search: search.value || undefined,
            region: region.value || undefined,
            status: status.value || undefined,
            section: section.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

const debouncedApply = useDebounceFn(applyFilters, 350);
watch(search, debouncedApply);
watch([region, status, section], applyFilters);

function exportUrl(): string {
    const params = new URLSearchParams();
    if (search.value) params.set('search', search.value);
    if (region.value) params.set('region', region.value);
    if (status.value) params.set('status', status.value);
    if (section.value) params.set('section', section.value);
    const query = params.toString();
    return query ? `/admin/adherents-export?${query}` : '/admin/adherents-export';
}

const statusOptions = [
    { value: 'pending', label: 'En attente' },
    { value: 'active', label: 'Actif' },
    { value: 'inactive', label: 'Inactif' },
];
const sectionOptions = [
    { value: 'standard', label: 'Standard' },
    { value: 'jeunes', label: 'Jeunes' },
    { value: 'femmes', label: 'Femmes' },
];
</script>

<template>
    <Head title="Adhérents" />

    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-display text-2xl text-ink">Adhérents</h2>
            <a
                :href="exportUrl()"
                class="border border-sand-300 px-4 py-2 font-sans text-sm text-ink transition-colors hover:border-ink"
            >
                Exporter en CSV
            </a>
        </div>

        <Card>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Input id="search" v-model="search" label="Recherche" placeholder="Nom, email, téléphone..." />
                <Select
                    id="region"
                    v-model="region"
                    label="Région"
                    :options="regions.map((r) => ({ value: r, label: r }))"
                    placeholder="Toutes"
                />
                <Select id="status" v-model="status" label="Statut" :options="statusOptions" placeholder="Tous" />
                <Select id="section" v-model="section" label="Section" :options="sectionOptions" placeholder="Toutes" />
            </div>
        </Card>

        <Card :padded="false">
            <div v-if="members.data.length" class="overflow-x-auto">
                <table class="w-full text-left font-sans text-sm">
                    <thead>
                        <tr class="border-b border-sand-200 text-xs text-ink-soft uppercase">
                            <th class="px-6 py-3 font-medium">Adhérent</th>
                            <th class="px-6 py-3 font-medium">Contact</th>
                            <th class="px-6 py-3 font-medium">Région</th>
                            <th class="px-6 py-3 font-medium">Section</th>
                            <th class="px-6 py-3 font-medium">Statut</th>
                            <th class="px-6 py-3 font-medium">Adhésion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="member in members.data"
                            :key="member.id"
                            class="border-b border-sand-100 last:border-0 hover:bg-sand-50"
                        >
                            <td class="px-6 py-3 whitespace-nowrap">
                                <Link :href="`/admin/adherents/${member.id}`" class="font-medium text-ink hover:underline">
                                    {{ member.fullName }}
                                </Link>
                                <p class="font-mono text-xs text-ink-soft">{{ member.membershipCode }}</p>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-ink-soft">
                                <p>{{ member.email }}</p>
                                <p>{{ member.phone }}</p>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-ink-soft">{{ member.region }}</td>
                            <td class="px-6 py-3 whitespace-nowrap text-ink-soft capitalize">{{ member.section }}</td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                <Badge :tone="memberStatus(member.status).tone">{{ memberStatus(member.status).label }}</Badge>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-ink-soft">{{ member.joinedAt ?? '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-else class="px-6 py-8 text-center font-sans text-sm text-ink-soft">Aucun adhérent ne correspond à ces filtres.</p>

            <Pagination :links="members.links" :from="members.from" :to="members.to" :total="members.total" />
        </Card>
    </div>
</template>
