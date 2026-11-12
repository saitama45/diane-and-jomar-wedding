<script setup>
/*
 * Pick where the couple's faces are on a photo: click or drag on the photo, or use the arrow keys.
 * The value is "x% y%" of the photo (lib/focus.js). The two previews crop the photo the way a computer
 * screen and a phone do (the same coverFocus() as HeroSection.vue), with the names where the hero puts them.
 */
import { computed, ref } from 'vue';
import { coverFocus, namesPlace, parseFocus } from '@/lib/focus';

const model = defineModel({ type: String, default: '50% 50%' });

defineProps({
    src: { type: String, required: true },
    id: { type: String, default: undefined },
    label: { type: String, default: 'Where your faces are' },
    help: { type: String, default: '' },
    names: { type: String, default: '' },
});

const clamp = (n) => Math.round(Math.min(100, Math.max(0, n)));

const point = computed(() => {
    const focus = parseFocus(model.value);
    return { x: clamp(focus.x * 100), y: clamp(focus.y * 100) };
});
const set = (x, y) => (model.value = `${clamp(x)}% ${clamp(y)}%`);

// The previews: a 16:9 computer screen and a phone, cropped as the website does once the photo's size is known.
const size = ref(null);
const onLoad = (event) => (size.value = { w: event.target.naturalWidth, h: event.target.naturalHeight });
const preview = (box) => {
    const fallback = `${point.value.x}% ${point.value.y}%`;
    if (!size.value) return { style: { objectPosition: fallback, transformOrigin: fallback }, faceY: point.value.y / 100 };
    const crop = coverFocus(size.value, box, { x: point.value.x / 100, y: point.value.y / 100 });
    return { style: { objectPosition: crop.position, transformOrigin: crop.origin }, faceY: crop.at.y };
};
const computer = computed(() => preview({ w: 16, h: 9 }));
const phone = computed(() => preview({ w: 9, h: 19.5 }));

// Same rule as the hero: names below faces in the upper part, above faces in the lower part.
const PLACES = { below: 'justify-end pb-[13%]', above: 'justify-start pt-[15%]', centre: 'justify-center' };
const computerNames = computed(() => PLACES[namesPlace(computer.value.faceY)]);

const frame = ref();
let dragging = false;

function pick(event) {
    const box = frame.value.getBoundingClientRect();
    set(((event.clientX - box.left) / box.width) * 100, ((event.clientY - box.top) / box.height) * 100);
}

function down(event) {
    dragging = true;
    frame.value.setPointerCapture?.(event.pointerId);
    pick(event);
}

const move = (event) => dragging && pick(event);
const up = () => (dragging = false);

function nudge(event) {
    const step = event.shiftKey ? 5 : 1;
    const moves = { ArrowLeft: [-step, 0], ArrowRight: [step, 0], ArrowUp: [0, -step], ArrowDown: [0, step] };
    const by = moves[event.key];
    if (!by) return;
    event.preventDefault();
    set(point.value.x + by[0], point.value.y + by[1]);
}
</script>

<template>
    <div class="mt-4 rounded-xl bg-linen/50 p-4 ring-1 ring-ink/10">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <p class="admin-label mb-0!">{{ label }}</p>
            <button type="button" class="text-sm text-ink-soft underline-offset-4 hover:text-rust hover:underline" @click="set(50, 50)">Centre</button>
        </div>
        <p v-if="help" class="mt-1 text-xs text-ink-soft">{{ help }}</p>

        <div class="mt-4 flex flex-wrap items-end gap-6">
            <!-- The photo: click or drag on the faces -->
            <div
                :id="id"
                ref="frame"
                tabindex="0"
                role="group"
                :aria-label="`${label}: ${point.x}% across, ${point.y}% down. Click the faces, or move the point with the arrow keys.`"
                class="relative cursor-crosshair touch-none select-none overflow-hidden rounded-lg ring-1 ring-ink/15 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold"
                @pointerdown="down"
                @pointermove="move"
                @pointerup="up"
                @pointercancel="up"
                @keydown="nudge"
            >
                <img :src="src" alt="" draggable="false" class="block max-h-72 w-auto max-w-full" @load="onLoad" />
                <span
                    class="pointer-events-none absolute size-9 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white shadow-[0_0_0_1px_rgb(0_0_0/0.35),0_2px_10px_rgb(0_0_0/0.4)]"
                    :style="{ left: `${point.x}%`, top: `${point.y}%` }"
                    aria-hidden="true"
                >
                    <span class="absolute left-1/2 top-1/2 size-1.5 -translate-x-1/2 -translate-y-1/2 rounded-full bg-white"></span>
                </span>
            </div>

            <!-- What a computer screen and a phone show -->
            <figure>
                <div class="relative aspect-video w-64 overflow-hidden rounded-md bg-film ring-1 ring-ink/15">
                    <img :src="src" alt="" class="absolute inset-0 h-full w-full scale-110 object-cover" :style="computer.style" />
                    <div class="absolute inset-0 bg-gradient-to-b from-film/55 via-film/20 to-film/85"></div>
                    <div class="absolute inset-0 flex flex-col items-center px-2 text-center text-paper" :class="computerNames">
                        <p class="font-script text-2xl leading-none drop-shadow-[0_2px_8px_rgb(0_0_0/0.4)]">{{ names }}</p>
                        <span class="mt-1.5 h-px w-16 bg-gold-light/70"></span>
                    </div>
                </div>
                <figcaption class="mt-1.5 text-xs text-ink-soft">Computer</figcaption>
            </figure>
            <figure>
                <div class="relative aspect-[9/19.5] w-24 overflow-hidden rounded-xl bg-film ring-1 ring-ink/15">
                    <img :src="src" alt="" class="absolute inset-0 h-full w-full scale-110 object-cover" :style="phone.style" />
                    <div class="absolute inset-0 bg-gradient-to-b from-film/55 via-film/20 to-film/85"></div>
                    <div class="absolute inset-0 flex flex-col items-center justify-center px-1 text-center text-paper">
                        <p class="font-script text-[0.95rem] leading-tight drop-shadow-[0_2px_6px_rgb(0_0_0/0.4)]">{{ names }}</p>
                        <span class="mt-1 h-px w-8 bg-gold-light/70"></span>
                    </div>
                </div>
                <figcaption class="mt-1.5 text-xs text-ink-soft">Phone</figcaption>
            </figure>
        </div>
    </div>
</template>
