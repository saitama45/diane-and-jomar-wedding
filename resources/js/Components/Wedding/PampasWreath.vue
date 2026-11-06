<script setup>
/*
 * A thin painted ring hugged by pampas plumes, dried leaves and a little palm fan (the monogram wreath
 * on the save-the-date card). Whatever goes in the slot sits in the middle of the ring.
 */
import { onCircle, painter } from '@/lib/bohoArt';

const CENTER = [150, 150];
const RING = 92;
const at = (deg) => onCircle(CENTER, RING, deg);

const { pampas, palmFan, leafSprig, bunnyTail } = painter(20270424);

// Plumes follow the ring: up the left side from the bottom, and up the right side.
const plumes = [
    pampas(at(112), 202, 120, 44, 0.9),
    pampas(at(166), 256, 95, 26, 0.72),
    pampas(at(62), -28, 115, -40, 0.85),
    pampas(at(8), -82, 80, -18, 0.66),
];
const sprigs = [leafSprig(at(96), 6, 62, -12, '#6b4c33', 0.62), leafSprig(at(102), 196, 50, 10, '#8a6a4b', 0.55)];
const fan = palmFan(at(76), 4, 64, 54, 9);
const tails = [bunnyTail(at(118), 150, 46, 8), bunnyTail(at(122), 172, 40, -6)];
</script>

<template>
    <div class="relative">
        <svg viewBox="0 0 300 300" aria-hidden="true" class="absolute inset-0 h-full w-full overflow-visible">
            <defs>
                <filter id="wreath-soft" x="-10%" y="-10%" width="120%" height="120%"><feGaussianBlur stdDeviation="0.4" /></filter>
                <filter id="wreath-fluff" x="-30%" y="-30%" width="160%" height="160%"><feGaussianBlur stdDeviation="4" /></filter>
                <filter id="wreath-fuzz" x="-50%" y="-50%" width="200%" height="200%"><feGaussianBlur stdDeviation="1.1" /></filter>
                <radialGradient id="wreath-palm" gradientUnits="userSpaceOnUse" :cx="fan.base[0]" :cy="fan.base[1]" :r="fan.length">
                    <stop offset="0" stop-color="#5e3d27" />
                    <stop offset="0.6" stop-color="#8f6544" />
                    <stop offset="1" stop-color="#c49a6c" />
                </radialGradient>
            </defs>

            <circle :cx="CENTER[0]" :cy="CENTER[1]" :r="RING" fill="none" stroke="#a9714b" stroke-width="1.5" stroke-opacity="0.85" />

            <g opacity="0.9">
                <path v-for="(shape, s) in fan.shapes" :key="s" :d="shape.blade" fill="url(#wreath-palm)" stroke="#5e3d27" stroke-width="0.35" stroke-opacity="0.5" />
            </g>

            <g
                v-for="(plume, i) in plumes"
                :key="`pl${i}`"
                class="boho-breeze"
                :style="{ transformOrigin: `${plume.base[0]}px ${plume.base[1]}px`, animationDelay: `${-i * 2.1}s` }"
            >
                <path :d="plume.body" fill="#d4b994" opacity="0.45" filter="url(#wreath-fluff)" />
                <path :d="plume.stem" stroke="#a88660" stroke-width="1.1" fill="none" />
                <g filter="url(#wreath-soft)" fill="none" stroke-linecap="round">
                    <path v-for="(frond, f) in plume.fronds" :key="f" :d="frond.d" :stroke="frond.color" :stroke-width="frond.width" :stroke-opacity="frond.opacity" />
                </g>
            </g>

            <g v-for="(sprig, i) in sprigs" :key="`sp${i}`" opacity="0.9">
                <path :d="sprig.stem" :stroke="sprig.color" stroke-width="1" fill="none" />
                <path v-for="(leaf, l) in sprig.leaves" :key="l" :d="leaf" :fill="sprig.color" />
            </g>

            <g v-for="(tail, i) in tails" :key="`bt${i}`">
                <path :d="tail.stem" stroke="#b39a7a" stroke-width="0.9" fill="none" />
                <ellipse :cx="tail.tip[0]" :cy="tail.tip[1]" rx="8" ry="3.8" fill="#e3d3b8" filter="url(#wreath-fuzz)" :transform="`rotate(${tail.angle.toFixed(1)} ${tail.tip[0].toFixed(1)} ${tail.tip[1].toFixed(1)})`" />
                <ellipse :cx="tail.tip[0]" :cy="tail.tip[1]" rx="5.5" ry="2.4" fill="#eee3d1" :transform="`rotate(${tail.angle.toFixed(1)} ${tail.tip[0].toFixed(1)} ${tail.tip[1].toFixed(1)})`" />
            </g>
        </svg>
        <div class="relative grid h-full w-full place-items-center">
            <slot />
        </div>
    </div>
</template>
