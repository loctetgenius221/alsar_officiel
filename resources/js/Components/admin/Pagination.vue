<script setup lang="ts">
/**
 * Pagination générique pour les listes admin (Adhérents/Paiements/Journal d'audit) — basée
 * sur la structure standard du paginator Laravel (`links`), réutilisée à 3 endroits.
 */
import { Link } from '@inertiajs/vue3';

defineProps<{
    links: { url: string | null; label: string; active: boolean }[];
    from: number | null;
    to: number | null;
    total: number;
}>();

/**
 * Laravel fournit des libellés anglais avec entités HTML ("&laquo; Previous") — on les
 * traduit ici plutôt que d'injecter du HTML brut via v-html (évite tout risque XSS, même
 * si ce contenu vient du serveur et n'a jamais été user-controlled).
 */
function labelText(label: string): string {
    if (label.includes('Previous')) return '‹ Précédent';
    if (label.includes('Next')) return 'Suivant ›';
    return label;
}
</script>

<template>
    <div v-if="links.length > 3" class="flex flex-wrap items-center justify-between gap-4 border-t border-sand-200 px-6 py-4">
        <p class="font-sans text-xs text-ink-soft">
            {{ from ?? 0 }}–{{ to ?? 0 }} sur {{ total }}
        </p>
        <nav class="flex flex-wrap gap-1" aria-label="Pagination">
            <template v-for="(link, index) in links" :key="index">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="px-3 py-1.5 font-sans text-xs"
                    :class="link.active ? 'bg-gold-500 text-ink' : 'text-ink-soft hover:bg-sand-100'"
                >
                    {{ labelText(link.label) }}
                </Link>
                <span v-else class="px-3 py-1.5 font-sans text-xs text-sand-300">
                    {{ labelText(link.label) }}
                </span>
            </template>
        </nav>
    </div>
</template>
