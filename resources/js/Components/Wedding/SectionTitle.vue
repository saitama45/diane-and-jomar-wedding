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
            <!-- Boho rainbow arch -->
            <svg v-reveal="{ type: 'zoom', delay: 0.5 }" width="38" height="22" viewBox="0 0 38 22" fill="none" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                <path d="M3 21a16 16 0 0 1 32 0" :class="light ? 'stroke-clay' : 'stroke-terracotta'" />
                <path d="M8.5 21a10.5 10.5 0 0 1 21 0" :class="light ? 'stroke-gold-light' : 'stroke-gold'" />
                <path d="M14 21a5 5 0 0 1 10 0" :class="light ? 'stroke-sand' : 'stroke-latte'" />
            </svg>
            <span v-reveal="{ type: 'line', delay: 0.3 }" class="h-px w-16 bg-current opacity-60 md:w-24"></span>
        </div>
        <slot />
    </header>
</template>
