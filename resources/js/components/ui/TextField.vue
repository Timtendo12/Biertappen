<script setup lang="ts">
import { computed, useId } from 'vue';

const props = defineProps<{
    label: string;
    type?: 'text' | 'email' | 'password';
    error?: string;
    autocomplete?: string;
    required?: boolean;
    placeholder?: string;
}>();

const model = defineModel<string>({ required: true });

const id = useId();
const errorId = computed(() => `${id}-error`);
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <label :for="id" class="text-sm font-medium text-foam/80">{{ props.label }}</label>
        <input
            :id="id"
            v-model="model"
            :type="props.type ?? 'text'"
            :autocomplete="props.autocomplete"
            :required="props.required"
            :placeholder="props.placeholder"
            :aria-invalid="props.error ? 'true' : undefined"
            :aria-describedby="props.error ? errorId : undefined"
            class="min-h-12 rounded-xl bg-night-soft px-4 text-base text-foam placeholder:text-foam/35"
            :class="props.error ? 'ring-2 ring-danger' : ''"
        />
        <!-- The message is tied to the input so screen readers announce it on focus,
             and it is text rather than colour alone. -->
        <p v-if="props.error" :id="errorId" class="text-sm text-danger">{{ props.error }}</p>
    </div>
</template>
