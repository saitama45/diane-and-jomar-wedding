<script setup>
import { computed, inject } from 'vue';
import { vReveal } from '@/lib/motion';

const theme = inject('decorTheme', computed(() => 'boho'));

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
            <!-- Watercolor Garden: a gold leaf sprig around a little flower -->
            <svg
                v-if="theme === 'garden'"
                v-reveal="{ type: 'zoom', delay: 0.5 }"
                width="50"
                height="22"
                viewBox="0 0 50 22"
                fill="none"
                stroke="currentColor"
                stroke-width="1.1"
                stroke-linecap="round"
                aria-hidden="true"
            >
                <path d="M19 11C15.5 6.5 9 5.5 4 8.5c4.5 3.4 10 4 15 2.5Z" />
                <path d="M17.5 13c-3 3.4-7.6 4.8-11.5 3.8 2.6-3.2 7-4.6 11.5-3.8Z" stroke-opacity=".7" />
                <path d="M31 11c3.5-4.5 10-5.5 15-2.5-4.5 3.4-10 4-15 2.5Z" />
                <path d="M32.5 13c3 3.4 7.6 4.8 11.5 3.8-2.6-3.2-7-4.6-11.5-3.8Z" stroke-opacity=".7" />
                <circle cx="25" cy="11" r="3.4" :class="light ? 'fill-clay' : 'fill-terracotta'" stroke="none" fill-opacity=".85" />
                <circle cx="25" cy="11" r="1.4" fill="currentColor" stroke="none" />
            </svg>
            <!-- Boho rainbow arch -->
            <svg v-else v-reveal="{ type: 'zoom', delay: 0.5 }" width="38" height="22" viewBox="0 0 38 22" fill="none" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                <path d="M3 21a16 16 0 0 1 32 0" :class="light ? 'stroke-clay' : 'stroke-terracotta'" />
                <path d="M8.5 21a10.5 10.5 0 0 1 21 0" :class="light ? 'stroke-gold-light' : 'stroke-gold'" />
                <path d="M14 21a5 5 0 0 1 10 0" :class="light ? 'stroke-sand' : 'stroke-latte'" />
            </svg>
            <span v-reveal="{ type: 'line', delay: 0.3 }" class="h-px w-16 bg-current opacity-60 md:w-24"></span>
        </div>
        <slot />
    </header>
</template>
