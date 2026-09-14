<script setup lang="ts">
/**
 * Bouton de base du back-office (CLAUDE.md §10 : ui/ = composants de base réutilisés).
 * Rectangulaire, jamais pilule/glow (CLAUDE.md §3) — même registre que les CTA du site public.
 * Rend un <Link> Inertia si `href` est fourni (navigation), sinon un <button> (action locale).
 */
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        variant?: 'primary' | 'secondary' | 'danger' | 'ghost';
        size?: 'sm' | 'base';
        href?: string;
        type?: 'button' | 'submit';
        disabled?: boolean;
    }>(),
    {
        variant: 'primary',
        size: 'base',
        href: undefined,
        type: 'button',
        disabled: false,
    },
);

const classes = computed(() =>
    cn(
        'inline-flex items-center justify-center gap-2 whitespace-nowrap font-sans font-medium transition-colors duration-200 disabled:cursor-not-allowed disabled:opacity-50',
        props.size === 'sm' ? 'px-3 py-1.5 text-xs' : 'px-5 py-2.5 text-sm',
        {
            primary: 'bg-gold-500 text-ink hover:bg-gold-600',
            secondary: 'border border-sand-300 text-ink hover:border-ink',
            danger: 'bg-danger text-paper hover:opacity-90',
            ghost: 'text-ink-soft hover:text-ink',
        }[props.variant],
    ),
);
</script>

<template>
    <Link v-if="href" :href="href" :class="classes">
        <slot />
    </Link>
    <button v-else :type="type" :disabled="disabled" :class="classes">
        <slot />
    </button>
</template>
