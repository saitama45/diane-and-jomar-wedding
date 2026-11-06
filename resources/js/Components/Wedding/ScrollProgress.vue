<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import LineIcon from '@/Components/LineIcon.vue';
import { scrollToTarget } from '@/lib/motion';

const progress = ref(0);
const RADIUS = 22;
const CIRCUMFERENCE = 2 * Math.PI * RADIUS;

function onScroll() {
    const max = document.documentElement.scrollHeight - window.innerHeight;
    progress.value = max > 0 ? Math.min(1, window.scrollY / max) : 0;
}

onMounted(() => {
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
});

onBeforeUnmount(() => window.removeEventListener('scroll', onScroll));
</script>

<template>
    <div class="pointer-events-none fixed inset-x-0 top-0 z-[55] h-0.5">
        <div class="h-full origin-left bg-gradient-to-r from-gold via-gold-light to-gold" :style="{ transform: `scaleX(${progress})` }"></div>
    </div>

    <Transition name="fade">
        <button
            v-if="progress > 0.08"
            type="button"
            class="fixed bottom-5 right-5 z-50 grid size-14 place-items-center rounded-full bg-paper/90 text-ink shadow-[0_10px_30px_-12px_rgb(0_0_0/0.45)] backdrop-blur transition hover:-translate-y-1 md:bottom-8 md:right-8"
            aria-label="Back to top"
            @click="scrollToTarget(0)"
        >
            <svg class="absolute inset-0 -rotate-90" viewBox="0 0 56 56" aria-hidden="true">
                <circle cx="28" cy="28" :r="RADIUS" fill="none" stroke="currentColor" stroke-opacity=".12" stroke-width="1.5" />
                <circle
                    cx="28"
                    cy="28"
                    :r="RADIUS"
                    fill="none"
                    stroke="#b9873f"
                    stroke-width="1.5"
                    stroke-linecap="round"
                    :stroke-dasharray="CIRCUMFERENCE"
                    :stroke-dashoffset="CIRCUMFERENCE * (1 - progress)"
                />
            </svg>
            <LineIcon name="arrow-up" :size="20" />
        </button>
    </Transition>
</template>
