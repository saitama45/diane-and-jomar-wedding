<script setup>
import { vReveal } from '@/lib/motion';

defineProps({
    kicker: { type: String, default: '' },
    title: { type: String, required: true },
    light: { type: Boolean, default: false },
});
</script>

<template>
    <header class="text-center">
        <p v-if="kicker" v-reveal="'fade'" class="kicker mb-3" :class="light ? 'text-gold-light' : 'text-gold'">
            {{ kicker }}
        </p>
        <h2
            v-reveal="{ type: 'blur', duration: 1.6 }"
            class="font-script text-[clamp(3.2rem,9vw,6rem)] leading-[1.15]"
            :class="light ? 'text-paper' : 'text-ink'"
        >
            {{ title }}
        </h2>
        <div class="mx-auto mt-3 flex items-center justify-center gap-3" :class="light ? 'text-gold-light' : 'text-gold'">
            <span v-reveal="{ type: 'line', delay: 0.3 }" class="h-px w-16 bg-current opacity-60 md:w-24"></span>
            <svg v-reveal="{ type: 'zoom', delay: 0.5 }" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M12 3c.6 4.4 2.6 6.4 7 7-4.4.6-6.4 2.6-7 7-.6-4.4-2.6-6.4-7-7 4.4-.6 6.4-2.6 7-7z" />
            </svg>
            <span v-reveal="{ type: 'line', delay: 0.3 }" class="h-px w-16 bg-current opacity-60 md:w-24"></span>
        </div>
        <slot />
    </header>
</template>
