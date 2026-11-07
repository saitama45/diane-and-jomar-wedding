<script setup>
/*
 * A thin painted ring hugged by pampas plumes, dried leaves and a little palm fan (the monogram wreath
 * on the save-the-date card). Whatever goes in the slot sits in the middle of the ring. With the
 * "Watercolor Garden" theme it is a gold ring with a peach bloom, lilac blossoms, sage leaves and gold dots.
 */
import { computed, inject } from 'vue';
import { gardenPainter, onCircle, painter } from '@/lib/bohoArt';
import GardenBloom from './GardenBloom.vue';
import GardenFilters from './GardenFilters.vue';

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

const theme = inject('decorTheme', computed(() => 'boho'));

// The garden wreath: a bloom low on the left of the ring, leaves along it, gold line art at the top right.
const gp = gardenPainter(20270101);
const gBloom = gp.bloom(at(138), 32, 20);
const gLilacs = [gp.blossom(at(168), 12, 10), gp.blossom(at(110), 10, 30)];
const gRust = [gp.blossom(at(186), 6, 0, 4), gp.blossom(at(96), 6, 20, 4)];
const G_LEAF = ['#7d8f74', '#6c7f66', '#8fa088', '#5d6e57'];
const gLeaves = [
    gp.leaf(at(150), 238, 58, 11, -5),
    gp.leaf(at(160), 262, 46, 9, -4),
    gp.leaf(at(125), 45, 56, 11, 5),
    gp.leaf(at(115), 15, 44, 9, 4),
].map((shape, i) => ({ ...shape, color: G_LEAF[i] }));
const gGold = [gp.branch(at(-112), -14, 104, -16, { leaves: 6, size: 0.62 }), gp.branch(at(-30), 66, 70, -10, { leaves: 4, size: 0.55 })];
const gDots = gp.dots(8, CENTER, -95, 25, 104, 124);
</script>

<template>
    <div class="relative">
        <svg v-if="theme === 'garden'" viewBox="0 0 300 300" aria-hidden="true" class="absolute inset-0 h-full w-full overflow-visible">
            <GardenFilters />
            <circle :cx="CENTER[0]" :cy="CENTER[1]" :r="RING" fill="none" stroke="#c39a46" stroke-width="1.4" />
            <g fill="none" stroke="#c39a46" stroke-width="1" stroke-linecap="round">
                <template v-for="(b, i) in gGold" :key="`gg${i}`">
                    <path :d="b.stem" />
                    <path v-for="(shape, s) in b.shapes" :key="s" :d="shape.d" />
                </template>
            </g>
            <g filter="url(#garden-wash)">
                <g v-for="(l, i) in gLeaves" :key="`gl${i}`">
                    <path :d="l.d" :fill="l.color" fill-opacity="0.9" />
                    <path :d="l.rib" stroke="#e7ecdf" stroke-opacity="0.45" stroke-width="0.7" fill="none" />
                </g>
            </g>
            <GardenBloom :bloom="gBloom" uid="wreath-bloom" />
            <g v-for="(b, i) in gLilacs" :key="`gli${i}`" filter="url(#garden-wash)">
                <ellipse v-for="(p, j) in b.shapes" :key="j" :cx="p.cx" :cy="p.cy" :rx="p.rx" :ry="p.ry" :transform="`rotate(${p.angle.toFixed(1)} ${p.cx.toFixed(1)} ${p.cy.toFixed(1)})`" fill="#cdb6de" stroke="#b59ccb" stroke-width="0.5" />
                <circle :cx="b.center[0]" :cy="b.center[1]" :r="b.radius * 0.22" fill="#8e6aa8" />
            </g>
            <g v-for="(b, i) in gRust" :key="`gr${i}`" filter="url(#garden-wash)">
                <ellipse v-for="(p, j) in b.shapes" :key="j" :cx="p.cx" :cy="p.cy" :rx="p.rx" :ry="p.ry" :transform="`rotate(${p.angle.toFixed(1)} ${p.cx.toFixed(1)} ${p.cy.toFixed(1)})`" fill="#c45f3a" />
            </g>
            <circle v-for="(d, i) in gDots" :key="`gd${i}`" :cx="d.x" :cy="d.y" :r="d.r * 0.8" fill="url(#garden-gold)" />
        </svg>
        <svg v-else viewBox="0 0 300 300" aria-hidden="true" class="absolute inset-0 h-full w-full overflow-visible">
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
