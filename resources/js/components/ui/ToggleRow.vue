<script setup lang="ts">
import { useId } from 'vue';

const props = defineProps<{
    label: string;
    hint?: string;
    /** Shown instead of the switch when the device cannot do this at all. */
    unsupported?: string;
}>();

const model = defineModel<boolean>({ required: true });

const id = useId();
</script>

<template>
    <div class="flex items-center justify-between gap-4 rounded-2xl bg-night-soft px-4 py-3">
        <span class="flex flex-col gap-0.5">
            <label :for="id" class="text-base font-medium">{{ props.label }}</label>
            <span v-if="props.hint" class="text-xs text-foam/55">{{ props.hint }}</span>
            <span v-if="props.unsupported" class="text-xs text-foam/45">{{ props.unsupported }}</span>
        </span>

        <!--
            A real checkbox under the styling: it keeps keyboard operation, the
            accessibility tree and form semantics for free.
        -->
        <input
            :id="id"
            v-model="model"
            type="checkbox"
            role="switch"
            :disabled="Boolean(props.unsupported)"
            class="peer sr-only"
        />
        <label
            :for="id"
            aria-hidden="true"
            class="relative h-8 w-14 shrink-0 cursor-pointer rounded-full bg-night transition-colors peer-checked:bg-beer peer-disabled:opacity-40 peer-focus-visible:outline-3 peer-focus-visible:outline-beer peer-focus-visible:outline-offset-2"
        >
            <span
                class="absolute top-1 left-1 size-6 rounded-full bg-foam transition-transform"
                :class="model ? 'translate-x-6' : ''"
            />
        </label>
    </div>
</template>
