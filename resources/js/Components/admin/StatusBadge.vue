<script setup lang="ts">
/**
 * Pastille de statut du back-office : icône + libellé + tonalité, tirés de lib/status.ts.
 *
 * Exister en tant que composant plutôt que de composer Badge + icône dans chaque tableau n'est
 * pas qu'une économie de lignes : c'est ce qui garantit qu'« en attente » reste la même horloge
 * de la même taille sur les adhérents, les paiements et partout où il apparaîtra demain.
 *
 * L'icône est `aria-hidden` : le libellé juste à côté dit déjà la même chose, et un lecteur
 * d'écran n'a pas à l'entendre deux fois.
 */
import { computed } from 'vue';
import Badge from '@/Components/ui/Badge.vue';
import { STATUS_TABLES, type StatusKind } from '@/lib/status';

const props = defineProps<{
    kind: StatusKind;
    status: string;
}>();

const descriptor = computed(() => STATUS_TABLES[props.kind](props.status));
</script>

<template>
    <Badge :tone="descriptor.tone">
        <component :is="descriptor.icon" class="size-3.5 shrink-0" aria-hidden="true" />
        {{ descriptor.label }}
    </Badge>
</template>
