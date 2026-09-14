<script setup lang="ts">
/**
 * Pastille de statut — réutilise les tokens sémantiques success/warning/danger de
 * resources/css/app.css plutôt que d'inventer des couleurs (CLAUDE.md §3.1).
 */
import { computed } from 'vue';
import { cn } from '@/lib/utils';

const props = withDefaults(defineProps<{ tone?: 'success' | 'warning' | 'danger' | 'neutral' }>(), {
    tone: 'neutral',
});

const classes = computed(() =>
    cn(
        'inline-flex items-center gap-1.5 px-2.5 py-1 font-sans text-xs font-medium',
        {
            success: 'bg-success/10 text-success',
            warning: 'bg-warning/10 text-warning',
            danger: 'bg-danger/10 text-danger',
            neutral: 'bg-sand-100 text-ink-soft',
        }[props.tone],
    ),
);
</script>

<template>
    <span :class="classes"><slot /></span>
</template>
