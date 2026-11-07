<script setup>
/*
 * One watercolour bloom (from gardenPainter().bloom), drawn inside an SVG: a soft colour bleed, three rings
 * of petals and a teal-ringed ochre eye. `palette` picks the colourway; `uid` keeps its gradient ids apart
 * from other blooms on the page. The `garden-*` filters come from GardenFilters.vue.
 */
import { computed } from 'vue';

const props = defineProps({
    bloom: { type: Object, required: true }, // center, radius, outer, middle, inner, dots
    uid: { type: String, required: true },
    palette: { type: String, default: 'peach' }, // peach | coral | apricot
});

// [centre, middle, edge] per petal ring, the bleed colour and the petal outline.
const PALETTES = {
    peach: { outer: ['#e0703f', '#f19c72', '#fcdcc4'], middle: ['#e8805a', '#f5b48f', '#fde3cf'], inner: ['#ee9670', '#fdebdd'], bleed: '#f6c3a3', line: '#e0906a' },
    coral: { outer: ['#d45f6a', '#ee9a9c', '#fde0dc'], middle: ['#e07a80', '#f4b3b1', '#fde7e4'], inner: ['#ea9196', '#fdefed'], bleed: '#f4b9b6', line: '#d9848a' },
    apricot: { outer: ['#cf6a24', '#ec9b52', '#fbdcb3'], middle: ['#dc7c38', '#f2b878', '#fde6c8'], inner: ['#e99a58', '#feefdc'], bleed: '#f5c48e', line: '#d58a4a' },
};
const p = computed(() => PALETTES[props.palette] ?? PALETTES.peach);
</script>

<template>
    <g>
        <defs>
            <radialGradient :id="`${uid}-outer`" gradientUnits="userSpaceOnUse" :cx="bloom.center[0]" :cy="bloom.center[1]" :r="bloom.radius">
                <stop offset="0" :stop-color="p.outer[0]" />
                <stop offset="0.5" :stop-color="p.outer[1]" />
                <stop offset="1" :stop-color="p.outer[2]" />
            </radialGradient>
            <radialGradient :id="`${uid}-middle`" gradientUnits="userSpaceOnUse" :cx="bloom.center[0]" :cy="bloom.center[1]" :r="bloom.radius * 0.76">
                <stop offset="0" :stop-color="p.middle[0]" />
                <stop offset="0.6" :stop-color="p.middle[1]" />
                <stop offset="1" :stop-color="p.middle[2]" />
            </radialGradient>
            <radialGradient :id="`${uid}-inner`" gradientUnits="userSpaceOnUse" :cx="bloom.center[0]" :cy="bloom.center[1]" :r="bloom.radius * 0.5">
                <stop offset="0" :stop-color="p.inner[0]" />
                <stop offset="1" :stop-color="p.inner[1]" />
            </radialGradient>
        </defs>
        <circle :cx="bloom.center[0]" :cy="bloom.center[1]" :r="bloom.radius * 1.08" :fill="p.bleed" opacity="0.45" filter="url(#garden-bleed)" />
        <g filter="url(#garden-wash)">
            <path v-for="(d, i) in bloom.outer" :key="`po${i}`" :d="d" :fill="`url(#${uid}-outer)`" :stroke="p.line" stroke-width="0.6" stroke-opacity="0.6" />
            <path v-for="(d, i) in bloom.middle" :key="`pm${i}`" :d="d" :fill="`url(#${uid}-middle)`" :stroke="p.line" stroke-width="0.55" stroke-opacity="0.5" />
            <path v-for="(d, i) in bloom.inner" :key="`pi${i}`" :d="d" :fill="`url(#${uid}-inner)`" :stroke="p.line" stroke-width="0.5" stroke-opacity="0.45" />
        </g>
        <circle :cx="bloom.center[0]" :cy="bloom.center[1]" :r="bloom.radius * 0.24" fill="#355c60" opacity="0.9" filter="url(#garden-soft)" />
        <circle :cx="bloom.center[0]" :cy="bloom.center[1]" :r="bloom.radius * 0.17" fill="#df8a32" />
        <circle :cx="bloom.center[0]" :cy="bloom.center[1]" :r="bloom.radius * 0.09" fill="#9c5320" />
        <circle v-for="(d, i) in bloom.dots" :key="`ed${i}`" :cx="d.x" :cy="d.y" :r="d.r" fill="#5e3818" opacity="0.75" />
    </g>
</template>
