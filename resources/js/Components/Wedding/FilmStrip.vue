<script setup>
import { computed } from 'vue';

const props = defineProps({
    images: { type: Array, default: () => [] },
    reverse: { type: Boolean, default: false },
    duration: { type: Number, default: 70 },
    tilt: { type: Number, default: 0 },
    height: { type: String, default: 'h-28 md:h-36' },
});

// Doubled so the marquee can loop seamlessly at -50%.
const frames = computed(() => [...props.images, ...props.images]);
</script>

<template>
    <div class="relative overflow-hidden py-2" :style="{ transform: `rotate(${tilt}deg) scale(1.04)` }" aria-hidden="true">
        <div class="film-strip">
            <div
                class="marquee flex w-max gap-3 px-1.5"
                :class="{ 'marquee-reverse': reverse }"
                :style="{ '--marquee-duration': `${duration}s` }"
            >
                <img
                    v-for="(src, i) in frames"
                    :key="i"
                    :src="src"
                    alt=""
                    loading="lazy"
                    class="aspect-[3/2] w-auto shrink-0 object-cover sepia-[.35] saturate-[.85] transition duration-700 hover:sepia-0 hover:saturate-100"
                    :class="height"
                />
            </div>
        </div>
    </div>
</template>
