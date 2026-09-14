<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        type?: 'button' | 'submit';
        disabled?: boolean;
        loading?: boolean;
        variant?: 'primary' | 'ghost';
    }>(),
    { type: 'button', disabled: false, loading: false, variant: 'primary' },
);
</script>

<template>
    <button
        :type="props.type"
        :disabled="props.disabled || props.loading"
        :aria-busy="props.loading ? 'true' : undefined"
        class="min-h-14 rounded-2xl px-5 text-base font-bold transition-opacity disabled:opacity-40"
        :class="
            props.variant === 'primary'
                ? 'bg-beer text-night'
                : 'border border-foam/20 bg-transparent text-foam'
        "
    >
        <slot />
    </button>
</template>
