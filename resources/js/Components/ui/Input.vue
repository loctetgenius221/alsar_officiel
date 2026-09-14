<script setup lang="ts">
withDefaults(
    defineProps<{
        modelValue?: string | null;
        label?: string;
        type?: string;
        error?: string;
        placeholder?: string;
        id?: string;
    }>(),
    {
        modelValue: '',
        label: undefined,
        type: 'text',
        error: undefined,
        placeholder: undefined,
        id: undefined,
    },
);
defineEmits<{ 'update:modelValue': [string] }>();
</script>

<template>
    <div>
        <label v-if="label" :for="id" class="font-sans text-sm text-ink-soft">{{ label }}</label>
        <input
            :id="id"
            :type="type"
            :value="modelValue"
            :placeholder="placeholder"
            class="mt-2 w-full border border-sand-300 bg-paper px-4 py-2.5 font-sans text-sm text-ink focus-visible:border-gold-500"
            @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
        />
        <p v-if="error" class="mt-1 font-sans text-xs text-danger">{{ error }}</p>
    </div>
</template>
