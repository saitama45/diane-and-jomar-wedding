<script setup>
const on = defineModel({ type: Boolean, default: false });

defineProps({
    label: { type: String, required: true }, // read by screen readers
    disabled: { type: Boolean, default: false },
});
</script>

<template>
    <button
        type="button"
        role="switch"
        :aria-checked="on"
        :aria-label="label"
        :title="label"
        :disabled="disabled"
        class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors duration-300 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold disabled:cursor-wait disabled:opacity-60"
        :class="on ? 'bg-cocoa' : 'bg-ink/20'"
        @click="on = !on"
    >
        <span class="inline-block size-5 rounded-full bg-white shadow transition-transform duration-300" :class="on ? 'translate-x-5.5' : 'translate-x-0.5'"></span>
    </button>
</template>
