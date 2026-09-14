<script setup lang="ts">
withDefaults(defineProps<{ padded?: boolean }>(), { padded: true });
</script>

<template>
    <div class="border border-sand-200 bg-paper" :class="padded ? 'p-6' : ''">
        <slot />
    </div>
</template>
