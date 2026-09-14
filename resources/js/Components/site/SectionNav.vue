<script setup lang="ts">
/**
 * Nav d'ancres sticky entre les sections d'une page consolidée (ex. /le-parti). Épinglée
 * juste sous le header (fixe, cf. AppHeader) — pas de flou/glassmorphism, fond papier plein
 * pour rester cohérent avec le header solide de ces pages.
 */
defineProps<{
    items: { id: string; label: string }[];
}>();
</script>

<template>
    <nav
        class="sticky top-16 z-20 border-y border-sand-200 bg-paper lg:top-20"
        aria-label="Navigation de la page"
    >
        <div class="mx-auto flex max-w-6xl gap-6 overflow-x-auto px-6 py-3 sm:px-10">
            <a
                v-for="item in items"
                :key="item.id"
                :href="`#${item.id}`"
                class="shrink-0 font-sans text-sm text-ink-soft hover:text-ink"
            >
                {{ item.label }}
            </a>
        </div>
    </nav>
</template>
