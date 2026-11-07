<script setup>
/*
 * Painted-look boho corner arrangement: pampas plumes, dried palm spears, cream roses, bunny-tail grass
 * and dark dried leaves, drawn from code for the top-left corner and turned to fit the others.
 * If the admin uploaded their own corner artwork (Site Settings > Decorations), that image is used instead;
 * with the "Watercolor Garden" theme (Admin > Theme, provided as `decorTheme`) GardenCorner.vue is drawn.
 * Decorative only: it sits behind the section content (the section needs `isolate`).
 */
import { computed, inject } from 'vue';
import { painter } from '@/lib/bohoArt';
import GardenCorner from './GardenCorner.vue';

const props = defineProps({
    corner: { type: String, default: 'top-left' }, // top-left | top-right | bottom-left | bottom-right
    behind: { type: Boolean, default: true }, // under the section content (needs `isolate` on the section)
});

const customImage = inject('decorImage', computed(() => null));
const theme = inject('decorTheme', computed(() => 'boho'));

// Where it sits, and how the top-left arrangement is turned to fit that corner. The turn is done inside
// the SVG (or as a plain CSS transform on an uploaded image), not with Tailwind's rotate/scale classes,
// so the printed card's renderer (html2canvas) turns it too.
const CORNERS = {
    'top-left': { place: 'left-0 top-0', svg: '', css: 'none' },
    'top-right': { place: 'right-0 top-0', svg: 'translate(340 0) scale(-1 1)', css: 'scaleX(-1)' },
    'bottom-left': { place: 'bottom-0 left-0', svg: 'rotate(-90 170 170)', css: 'rotate(-90deg)' },
    'bottom-right': { place: 'bottom-0 right-0', svg: 'rotate(180 170 170)', css: 'rotate(180deg)' },
};
const corner = computed(() => CORNERS[props.corner] ?? CORNERS['top-left']);
const placement = computed(() => corner.value.place);

// ---- the arrangement (corner at 0,0; everything grows toward the page; same seed = same picture) ----

const { pampas, palmFan, rose, leafSprig, bunnyTail } = painter(20271224);
const fans = [palmFan([26, 10], 16, 52, 196, 15), palmFan([10, 30], 74, 46, 168, 13)];
const plumes = [pampas([0, 0], 40, 318, 34), pampas([0, 0], 9, 270, -26), pampas([0, 0], 78, 250, 28)];
const sprigs = [leafSprig([0, 0], 58, 228, -22, '#6b4c33'), leafSprig([0, 0], 26, 206, 20, '#8a6a4b'), leafSprig([0, 0], 88, 150, -12, '#7a5a3f')];
const tails = [bunnyTail([0, 0], 31, 178, 10), bunnyTail([0, 0], 49, 214, -12), bunnyTail([0, 0], 66, 166, 14)];
const roses = [rose([54, 60], 42, 8), rose([124, 30], 29, 40), rose([30, 128], 27, 70), rose([158, 78], 15, 15)];
</script>

<template>
    <img
        v-if="customImage"
        :src="customImage"
        alt=""
        aria-hidden="true"
        class="pointer-events-none absolute size-48 select-none object-contain object-left-top opacity-75 md:size-[22rem] md:opacity-100"
        :class="[placement, behind && '-z-10']"
        :style="{ transform: corner.css }"
    />
    <!-- Watercolor Garden: each corner has its own arrangement, a little larger than the pampas -->
    <GardenCorner
        v-else-if="theme === 'garden'"
        :corner="props.corner"
        :turn="corner.svg"
        class="pointer-events-none absolute size-56 select-none opacity-90 md:size-[27rem] md:opacity-100"
        :class="[placement, behind && '-z-10']"
    />
    <svg
        v-else
        viewBox="0 0 340 340"
        aria-hidden="true"
        class="pointer-events-none absolute size-48 select-none opacity-75 md:size-[22rem] md:opacity-100"
        :class="[placement, behind && '-z-10']"
    >
        <defs>
            <filter id="boho-soft" x="-10%" y="-10%" width="120%" height="120%"><feGaussianBlur stdDeviation="0.45" /></filter>
            <filter id="boho-fluff" x="-30%" y="-30%" width="160%" height="160%"><feGaussianBlur stdDeviation="5" /></filter>
            <filter id="boho-fuzz" x="-50%" y="-50%" width="200%" height="200%"><feGaussianBlur stdDeviation="1.3" /></filter>
            <filter id="boho-shadow" x="-30%" y="-30%" width="160%" height="160%">
                <feDropShadow dx="1.5" dy="3" stdDeviation="3" flood-color="#5a3a26" flood-opacity="0.22" />
            </filter>
            <radialGradient v-for="(fan, i) in fans" :id="`boho-palm-${i}`" :key="`p${i}`" gradientUnits="userSpaceOnUse" :cx="fan.base[0]" :cy="fan.base[1]" :r="fan.length">
                <stop offset="0" stop-color="#5e3d27" />
                <stop offset="0.55" stop-color="#8f6544" />
                <stop offset="1" stop-color="#c49a6c" />
            </radialGradient>
            <radialGradient v-for="(r, i) in roses" :id="`boho-rose-${i}`" :key="`r${i}`" gradientUnits="userSpaceOnUse" :cx="r.center[0]" :cy="r.center[1]" :r="r.radius">
                <stop offset="0" stop-color="#cdb08a" />
                <stop offset="0.45" stop-color="#ead9c0" />
                <stop offset="1" stop-color="#fcf7ef" />
            </radialGradient>
        </defs>

        <g :transform="corner.svg || undefined">
        <!-- Dried palm spears (back) -->
        <g v-for="(fan, i) in fans" :key="`fan${i}`" opacity="0.92">
            <template v-for="(shape, s) in fan.shapes" :key="s">
                <path :d="shape.blade" :fill="`url(#boho-palm-${i})`" stroke="#5e3d27" stroke-width="0.4" stroke-opacity="0.5" />
                <path :d="shape.rib" stroke="#e7cfa9" stroke-width="0.5" stroke-opacity="0.45" fill="none" />
            </template>
        </g>

        <!-- Pampas plumes: a blurred fluffy core with fine fronds on top; they sway a little -->
        <g v-for="(plume, i) in plumes" :key="`pl${i}`" class="boho-breeze" :style="{ animationDelay: `${-i * 2.3}s` }">
            <path :d="plume.body" fill="#d8c09b" opacity="0.4" filter="url(#boho-fluff)" />
            <path :d="plume.stem" stroke="#a88660" stroke-width="1.2" fill="none" />
            <g filter="url(#boho-soft)" fill="none" stroke-linecap="round">
                <path v-for="(frond, f) in plume.fronds" :key="f" :d="frond.d" :stroke="frond.color" :stroke-width="frond.width" :stroke-opacity="frond.opacity" />
            </g>
        </g>

        <!-- Dried leaves -->
        <g v-for="(sprig, i) in sprigs" :key="`sp${i}`" opacity="0.9">
            <path :d="sprig.stem" :stroke="sprig.color" stroke-width="1.1" fill="none" />
            <path v-for="(leaf, l) in sprig.leaves" :key="l" :d="leaf" :fill="sprig.color" />
        </g>

        <!-- Bunny-tail grass -->
        <g v-for="(tail, i) in tails" :key="`bt${i}`" class="boho-breeze" :style="{ animationDelay: `${-1 - i * 1.7}s` }">
            <path :d="tail.stem" stroke="#b39a7a" stroke-width="0.9" fill="none" />
            <ellipse :cx="tail.tip[0]" :cy="tail.tip[1]" rx="9.5" ry="4.4" fill="#e3d3b8" opacity="0.9" filter="url(#boho-fuzz)" :transform="`rotate(${tail.angle.toFixed(1)} ${tail.tip[0].toFixed(1)} ${tail.tip[1].toFixed(1)})`" />
            <ellipse :cx="tail.tip[0]" :cy="tail.tip[1]" rx="6.5" ry="2.8" fill="#eee3d1" :transform="`rotate(${tail.angle.toFixed(1)} ${tail.tip[0].toFixed(1)} ${tail.tip[1].toFixed(1)})`" />
        </g>

        <!-- Cream roses (front) -->
        <g v-for="(r, i) in roses" :key="`ro${i}`" filter="url(#boho-shadow)">
            <path v-for="(petal, p) in r.petals" :key="p" :d="petal" :fill="`url(#boho-rose-${i})`" stroke="#c7aa82" stroke-width="0.55" stroke-opacity="0.7" />
            <path :d="r.swirl" fill="none" stroke="#a8875f" stroke-width="0.9" stroke-linecap="round" />
        </g>
        </g>
    </svg>
</template>
