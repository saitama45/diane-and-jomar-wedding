<script setup>
import { computed, reactive } from 'vue';
import { photoAt } from '@/lib/photo';

const props = defineProps({
    images: { type: Array, default: () => [] },
    reverse: { type: Boolean, default: false },
    duration: { type: Number, default: 70 },
    tilt: { type: Number, default: 0 },
    height: { type: String, default: 'h-28 md:h-36' },
});

// Doubled so the marquee can loop seamlessly at -50%.
const frames = computed(() => [...props.images, ...props.images]);

// Each frame takes its photo's own shape once it has loaded, so a portrait stands tall and whole instead of losing
// the heads to a wide crop; until then a 3:2 frame holds its place. Only extreme shapes (taller than 3:5, wider
// than 16:9) are cropped, toward the top where the faces usually are. Kept per photo, so both copies match.
// The photos load straight away at low priority (not lazily): a frame changing shape while the strip runs would
// make it jump, and the opening animation gives them time to arrive behind it.
const shapes = reactive({});
function measure(src, event) {
    const { naturalWidth: width, naturalHeight: height } = event.target;
    if (width && height) shapes[src] = Math.min(16 / 9, Math.max(3 / 5, width / height));
}
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
                    :src="photoAt(src, 480)"
                    alt=""
                    fetchpriority="low"
                    decoding="async"
                    class="w-auto shrink-0 object-cover object-[50%_25%] sepia-[.35] saturate-[.85] transition duration-700 hover:sepia-0 hover:saturate-100"
                    :class="height"
                    :style="{ aspectRatio: shapes[src] ?? 1.5 }"
                    @load="measure(src, $event)"
                />
            </div>
        </div>
    </div>
</template>
