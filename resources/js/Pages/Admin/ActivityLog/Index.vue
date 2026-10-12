<script setup lang="ts">
/**
 * Journal d'audit (CLAUDE.md §6/§8) : qui a fait quoi et quand, en lecture seule.
 * Alimenté par spatie/laravel-activitylog, déclenché par les actions admin (statut,
 * confirmation manuelle de paiement, note interne, renvoi de carte).
 */
import { Head, setLayoutProps } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Card from '@/Components/ui/Card.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Pagination from '@/Components/admin/Pagination.vue';
import { History, Newspaper, UserCog, Users } from '@lucide/vue';
import type { Component } from 'vue';

// Un glyphe par type d'objet : dans un journal qui s'allonge, c'est ce qui permet de repérer
// d'un coup d'œil les lignes qui touchent aux adhérents plutôt qu'aux articles.
const SUBJECT_ICONS: Record<string, Component> = {
    Member: Users,
    Article: Newspaper,
    User: UserCog,
};

function subjectIcon(subjectType: string): Component {
    return SUBJECT_ICONS[subjectType] ?? History;
}

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
                <li v-for="activity in activities.data" :key="activity.id" class="flex gap-3 px-6 py-4">
                    <span
                        class="mt-0.5 flex size-7 shrink-0 items-center justify-center bg-sand-100 text-ink-soft"
                        aria-hidden="true"
                    >
                        <component :is="subjectIcon(activity.subjectType)" class="size-3.5" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="font-sans text-sm text-ink">
                                <span class="font-medium">{{ activity.causerName }}</span>
                                — {{ activity.description }}
                                <!-- Parenthèse omise quand l'objet n'est pas renseigné : « ( —) »
                                     ne dit rien et alourdit une ligne déjà dense. -->
                                <span v-if="activity.subjectType" class="text-ink-soft"
                                    >({{ activity.subjectType }} {{ activity.subjectLabel }})</span
                                >
                            </p>
                            <p class="font-sans text-xs text-ink-soft">{{ activity.createdAt }}</p>
                        </div>
                        <details v-if="activity.properties" class="mt-2">
                            <summary class="cursor-pointer font-sans text-xs text-ink-soft hover:text-ink">
                                Détails
                            </summary>
                            <pre
                                class="mt-2 overflow-x-auto bg-sand-50 p-3 font-mono text-xs text-ink-soft"
                                >{{ JSON.stringify(activity.properties, null, 2) }}</pre>
                        </details>
                    </div>
                </li>
            </ul>
            <EmptyState
                :icon="History"
                title="Aucune activité enregistrée pour l'instant"
                description="Chaque action faite depuis le back-office (publication, blocage d'un compte, confirmation d'un paiement) viendra s'inscrire ici, avec son auteur et sa date."
            />

            <Pagination
                :links="activities.links"
                :from="activities.from"
                :to="activities.to"
                :total="activities.total"
            />
        </Card>
    </div>
</template>
