<script setup lang="ts">
/**
 * Journal d'audit (CLAUDE.md §6/§8) : qui a fait quoi et quand, en lecture seule.
 * Alimenté par spatie/laravel-activitylog, déclenché par les actions admin (statut,
 * confirmation manuelle de paiement, note interne, renvoi de carte).
 */
import { Head, setLayoutProps } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Pagination from '@/Components/admin/Pagination.vue';

defineOptions({ layout: AdminLayout });
setLayoutProps({ title: "Journal d'audit" });

defineProps<{
    activities: {
        data: {
            id: number;
            description: string;
            causerName: string;
            subjectType: string;
            subjectLabel: string;
            properties: Record<string, unknown> | null;
            createdAt: string;
        }[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
}>();
</script>

<template>
    <Head title="Journal d'audit" />

    <div class="space-y-6">
        <h2 class="font-display text-2xl text-ink">Journal d'audit</h2>

        <Card :padded="false">
            <ul v-if="activities.data.length" class="divide-y divide-sand-100">
                <li v-for="activity in activities.data" :key="activity.id" class="px-6 py-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="font-sans text-sm text-ink">
                            <span class="font-medium">{{ activity.causerName }}</span>
                            — {{ activity.description }}
                            <span class="text-ink-soft">({{ activity.subjectType }} {{ activity.subjectLabel }})</span>
                        </p>
                        <p class="font-sans text-xs text-ink-soft">{{ activity.createdAt }}</p>
                    </div>
                    <details v-if="activity.properties" class="mt-2">
                        <summary class="cursor-pointer font-sans text-xs text-ink-soft hover:text-ink">Détails</summary>
                        <pre class="mt-2 overflow-x-auto bg-sand-50 p-3 font-mono text-xs text-ink-soft">{{ JSON.stringify(activity.properties, null, 2) }}</pre>
                    </details>
                </li>
            </ul>
            <p v-else class="px-6 py-8 text-center font-sans text-sm text-ink-soft">Aucune activité enregistrée pour l'instant.</p>

            <Pagination :links="activities.links" :from="activities.from" :to="activities.to" :total="activities.total" />
        </Card>
    </div>
</template>
