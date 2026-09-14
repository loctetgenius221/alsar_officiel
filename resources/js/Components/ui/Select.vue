<script setup lang="ts">
defineProps<{
    modelValue?: string | null;
    label?: string;
    options: { value: string; label: string }[];
    placeholder?: string;
    id?: string;
}>();
defineEmits<{ 'update:modelValue': [string] }>();
</script>

<template>
    <div>
        <label v-if="label" :for="id" class="font-sans text-sm text-ink-soft">{{ label }}</label>
        <select
            :id="id"
            :value="modelValue ?? ''"
            class="mt-2 w-full border border-sand-300 bg-paper px-4 py-2.5 font-sans text-sm text-ink focus-visible:border-gold-500"
            @change="$emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
        >
            <option value="">{{ placeholder ?? 'Tous' }}</option>
            <option v-for="option in options" :key="option.value" :value="option.value">{{ option.label }}</option>
        </select>
    </div>
</template>
