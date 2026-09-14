<script setup lang="ts">
/**
 * Vérification publique d'une adhésion (CLAUDE.md §9 : QR code vérifiable publiquement).
 * Accessible sans connexion — utile en meeting/événement pour contrôler les adhésions.
 */
import SiteLayout from '@/Layouts/SiteLayout.vue';
import PageHeader from '@/Components/site/PageHeader.vue';
import { CircleCheck, CircleX } from '@lucide/vue';

defineOptions({ layout: SiteLayout });

defineProps<{
    result: {
        valid: boolean;
        fullName: string;
        status: string;
        section: string;
        joinedAt: string | null;
    } | null;
}>();
</script>

<template>
    <PageHeader
        kicker="Vérification"
        title="Vérifier une adhésion"
        image="/images/slider-img-3.webp"
        image-alt="Rencontre des membres d'ALSAR"
        image-position="top"
        compact
    />

    <div class="mx-auto max-w-xl px-6 pb-24 text-center sm:px-10">
        <template v-if="result?.valid">
            <CircleCheck class="mx-auto size-12 text-success" aria-hidden="true" />
            <p class="mt-4 font-display text-2xl text-ink">{{ result.fullName }}</p>
            <p class="mt-2 font-sans text-sm text-ink-soft">
                Membre actif d'ALSAR
                <span v-if="result.joinedAt">depuis le {{ result.joinedAt }}</span>
            </p>
        </template>

        <template v-else-if="result">
            <CircleX class="mx-auto size-12 text-warning" aria-hidden="true" />
            <p class="mt-4 font-display text-xl text-ink">Adhésion en attente</p>
            <p class="mt-2 font-sans text-sm text-ink-soft">
                {{ result.fullName }} a rejoint ALSAR mais sa cotisation n'est pas encore active.
            </p>
        </template>

        <template v-else>
            <CircleX class="mx-auto size-12 text-danger" aria-hidden="true" />
            <p class="mt-4 font-display text-xl text-ink">Code invalide</p>
            <p class="mt-2 font-sans text-sm text-ink-soft">
                Aucune adhésion ne correspond à ce code de vérification.
            </p>
        </template>
    </div>
</template>
