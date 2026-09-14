<script setup lang="ts">
import { Head, Link, setLayoutProps } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Card from '@/Components/ui/Card.vue';

defineOptions({ layout: AdminLayout });
setLayoutProps({ title: 'Contenu des pages' });

defineProps<{ pages: { slug: string; label: string; updatedAt: string }[] }>();
</script>

<template>
    <Head title="Contenu des pages" />

    <div class="space-y-6">
        <div>
            <h2 class="font-display text-2xl text-ink">Contenu des pages</h2>
            <p class="mt-1 font-sans text-sm text-ink-soft">
                Textes et photos du site public, modifiables sans intervention développeur.
            </p>
        </div>

        <Card :padded="false">
            <table class="w-full text-left font-sans text-sm">
                <thead>
                    <tr class="border-b border-sand-200 text-xs text-ink-soft uppercase">
                        <th class="px-6 py-3 font-medium">Page</th>
                        <th class="px-6 py-3 font-medium">Modifié le</th>
                        <th class="px-6 py-3 font-medium"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="page in pages"
                        :key="page.slug"
                        class="border-b border-sand-100 last:border-0 hover:bg-sand-50"
                    >
                        <td class="px-6 py-3 font-medium text-ink">{{ page.label }}</td>
                        <td class="px-6 py-3 whitespace-nowrap text-ink-soft">{{ page.updatedAt }}</td>
                        <td class="px-6 py-3 text-right whitespace-nowrap">
                            <Link
                                :href="`/admin/contenu-des-pages/${page.slug}/modifier`"
                                class="font-sans text-sm font-medium text-ink hover:underline"
                            >
                                Modifier
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </Card>
    </div>
</template>
