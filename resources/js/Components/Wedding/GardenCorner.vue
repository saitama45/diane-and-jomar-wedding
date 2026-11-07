<script setup>
/*
 * The "Watercolor Garden" corners (theme `garden`). Like a printed floral invitation, each corner has its
 * own arrangement:
 *   top-left      a bouquet: a big peach bloom, a coral bloom, lilac blossoms, sage and olive leaves
 *   top-right     gold line-art leaves with a few small blossoms
 *   bottom-left   wildflowers: rust and blush flowers on thin gold stems, pink berries, beige leaves
 *   bottom-right  dark foliage around a coral bloom, eucalyptus and lilac
 * Every arrangement is drawn for the top-left corner (corner at 0,0, growing toward the page) and turned
 * into place by `turn` (an SVG transform, so the printed cards' renderer turns it too).
 */
import { computed } from 'vue';
import { gardenPainter } from '@/lib/bohoArt';
import GardenBloom from './GardenBloom.vue';
import GardenFilters from './GardenFilters.vue';

const props = defineProps({
    corner: { type: String, default: 'top-left' },
    turn: { type: String, default: '' },
});

const GREENS = { sage: '#7f9a78', deep: '#3e4f39', olive: '#5d6c43', mist: '#9fb5b1', leaf: '#6f8a68', moss: '#8fa58a', pine: '#4b5e45' };
const leafWith = (shape, color, tone = 0.28) => ({ ...shape, color, tone });

function bouquet() {
    const g = gardenPainter(20261220);
    return {
        gold: [g.branch([0, 84], 12, 330, -44, { leaves: 13, size: 1.4 }), g.branch([84, 0], 72, 322, 40, { leaves: 12, size: 1.35 })],
        eucalyptus: [g.branch([0, 30], 34, 300, 28, { leaves: 13, round: true, size: 1.25 }), g.branch([24, 0], 58, 262, -24, { leaves: 11, round: true, size: 1.1 })],
        leaves: [
            leafWith(g.leaf([36, 36], -8, 228, 76, -16), GREENS.leaf),
            leafWith(g.leaf([36, 36], 18, 248, 82, 18), GREENS.deep, 0.2),
            leafWith(g.leaf([36, 36], 44, 256, 84, -14), GREENS.moss),
            leafWith(g.leaf([36, 36], 70, 244, 80, 16), GREENS.pine, 0.2),
            leafWith(g.leaf([36, 36], 96, 226, 72, -12), GREENS.mist),
            leafWith(g.leaf([92, 24], 4, 186, 52, 10), GREENS.olive),
            leafWith(g.leaf([24, 92], 88, 190, 52, -10), GREENS.sage),
            leafWith(g.leaf([112, 84], 38, 168, 46, -8), GREENS.deep, 0.2),
        ],
        blooms: [
            { bloom: g.bloom([214, 36], 62, 30), palette: 'coral' },
            { bloom: g.bloom([140, 214], 36, 12), palette: 'apricot' },
            { bloom: g.bloom([66, 108], 124, 8), palette: 'peach' },
        ],
        blossoms: [
            { ...g.blossom([228, 154], 31, 12), color: 'lilac' },
            { ...g.blossom([166, 246], 24, 40), color: 'lilac' },
            { ...g.blossom([280, 96], 18, 22), color: 'lilac' },
            { ...g.blossom([256, 214], 14, 8), color: 'cream' },
            { ...g.blossom([42, 280], 13, 0, 4), color: 'rust' },
            { ...g.blossom([304, 42], 12, 30, 4), color: 'rust' },
            { ...g.blossom([70, 306], 9, 18, 4), color: 'rust' },
        ],
        berries: [...g.berries([246, 230], 6, 12, 4), ...g.berries([200, 276], 5, 10, 3.6), ...g.berries([310, 136], 5, 10, 3.6)],
        dots: g.dots(22, [0, 0], 4, 86, 246, 330),
    };
}

function goldArt() {
    const g = gardenPainter(20261221);
    return {
        gold: [
            g.branch([0, 44], 18, 334, -30, { leaves: 9, size: 2.1 }),
            g.branch([44, 0], 66, 312, 30, { leaves: 8, size: 2 }),
            g.branch([0, 160], 4, 210, -14, { leaves: 6, size: 1.6 }),
            g.branch([160, 0], 86, 200, 12, { leaves: 6, size: 1.6 }),
        ],
        eucalyptus: [],
        leaves: [leafWith(g.leaf([16, 16], 30, 190, 60, 10), '#b9c8b2', 0.3), leafWith(g.leaf([12, 30], 62, 170, 52, -8), '#cfd8c4', 0.3)],
        blooms: [],
        blossoms: [
            { ...g.blossom([128, 72], 17, 10), color: 'lilac' },
            { ...g.blossom([74, 136], 14, 34), color: 'blush' },
            { ...g.blossom([182, 40], 10, 18, 4), color: 'rust' },
            { ...g.blossom([40, 196], 9, 40, 4), color: 'rust' },
        ],
        berries: [...g.berries([104, 104], 5, 10, 3.4), ...g.berries([150, 150], 4, 9, 3)],
        dots: g.dots(18, [0, 0], 4, 86, 150, 330),
    };
}

function wildflowers() {
    const g = gardenPainter(20261222);
    const stems = [8, 22, 36, 50, 64, 80].map((deg, i) => g.stem([0, 0], deg, 170 + ((i * 47) % 110), i % 2 ? 22 : -22));
    const COLORS = ['rust', 'blush', 'rust', 'apricot', 'rust', 'blush'];
    return {
        gold: [],
        stems,
        eucalyptus: [],
        sprigs: [g.branch([0, 0], 16, 240, -20, { leaves: 9, size: 0.85 }), g.branch([0, 0], 58, 250, 22, { leaves: 9, size: 0.85 }), g.branch([0, 0], 84, 190, -12, { leaves: 7, size: 0.75 })],
        leaves: [leafWith(g.leaf([10, 10], 30, 150, 44, 8), '#d8c49c', 0.35), leafWith(g.leaf([10, 10], 66, 140, 40, -8), '#cdb88f', 0.35)],
        blooms: [],
        blossoms: [
            ...stems.map((s, i) => ({ ...g.blossom(s.tip, 15 + (i % 3) * 3, i * 17, i % 2 ? 5 : 4), color: COLORS[i] })),
            { ...g.blossom([120, 64], 11, 20, 4), color: 'rust' },
            { ...g.blossom([70, 140], 10, 40, 5), color: 'blush' },
        ],
        berries: [...g.berries([150, 108], 7, 14, 3.6), ...g.berries([96, 196], 6, 12, 3.4), ...g.berries([216, 70], 6, 12, 3.4), ...g.berries([60, 250], 5, 10, 3.2)],
        dots: g.dots(14, [0, 0], 4, 86, 180, 320),
    };
}

function foliage() {
    const g = gardenPainter(20261223);
    return {
        gold: [g.branch([0, 64], 10, 300, -32, { leaves: 11, size: 1.25 })],
        eucalyptus: [g.branch([0, 18], 44, 280, 24, { leaves: 12, round: true, size: 1.2 })],
        leaves: [
            leafWith(g.leaf([26, 26], -4, 236, 74, -14), GREENS.deep, 0.2),
            leafWith(g.leaf([26, 26], 20, 252, 80, 16), GREENS.olive),
            leafWith(g.leaf([26, 26], 46, 260, 82, -14), GREENS.pine, 0.2),
            leafWith(g.leaf([26, 26], 72, 248, 78, 16), GREENS.sage),
            leafWith(g.leaf([26, 26], 98, 230, 70, -12), GREENS.deep, 0.2),
            leafWith(g.leaf([70, 40], 30, 190, 54, 8), GREENS.mist),
        ],
        blooms: [{ bloom: g.bloom([40, 64], 96, 20), palette: 'coral' }],
        blossoms: [
            { ...g.blossom([196, 128], 23, 12), color: 'lilac' },
            { ...g.blossom([132, 196], 17, 30), color: 'blush' },
            { ...g.blossom([246, 72], 12, 8), color: 'cream' },
            { ...g.blossom([84, 246], 11, 20, 4), color: 'rust' },
        ],
        berries: [...g.berries([220, 186], 5, 11, 3.6)],
        dots: g.dots(18, [0, 0], 4, 86, 230, 326),
    };
}

// The arrangements are the same on every visit (seeded), so they are drawn once.
const ARRANGEMENTS = { 'top-left': bouquet(), 'top-right': goldArt(), 'bottom-left': wildflowers(), 'bottom-right': foliage() };
const art = computed(() => ARRANGEMENTS[props.corner] ?? ARRANGEMENTS['top-left']);

const BLOSSOMS = {
    lilac: { fill: '#c6a6e0', line: '#a786c6', heart: '#7f58a0' },
    blush: { fill: '#f1b3b7', line: '#de8f95', heart: '#c0606a' },
    rust: { fill: '#c64f2b', line: '#a83f22', heart: '#f0b443' },
    apricot: { fill: '#ef9d5e', line: '#d9803f', heart: '#8a4a1c' },
    cream: { fill: '#f7e9d3', line: '#dcc49f', heart: '#d9a441' },
};
</script>

<template>
    <svg viewBox="0 0 340 340" aria-hidden="true">
        <GardenFilters />

        <g :transform="turn || undefined">
            <!-- Gold line-art leaves -->
            <g fill="none" stroke="#c09440" stroke-width="1.25" stroke-linecap="round">
                <template v-for="(b, i) in art.gold" :key="`gb${i}`">
                    <path :d="b.stem" />
                    <template v-for="(shape, s) in b.shapes" :key="s">
                        <path :d="shape.d" />
                        <path :d="shape.rib" stroke-width="0.75" />
                    </template>
                </template>
            </g>

            <!-- Wildflower stems and their little beige leaves -->
            <g v-if="art.stems" fill="none" stroke="#b78f45" stroke-width="1.2" stroke-linecap="round">
                <path v-for="(s, i) in art.stems" :key="`st${i}`" :d="s.d" />
            </g>
            <template v-if="art.sprigs">
                <g v-for="(b, i) in art.sprigs" :key="`sp${i}`" filter="url(#garden-wash)">
                    <path :d="b.stem" stroke="#b78f45" stroke-width="1" fill="none" />
                    <path v-for="(shape, s) in b.shapes" :key="s" :d="shape.d" fill="#e2cfa6" stroke="#c09440" stroke-width="0.8" />
                </g>
            </template>

            <!-- Eucalyptus -->
            <g v-for="(e, n) in art.eucalyptus" :key="`eu${n}`" filter="url(#garden-wash)">
                <path :d="e.stem" stroke="#6f8679" stroke-width="1.4" fill="none" />
                <circle v-for="(s, i) in e.shapes" :key="i" :cx="s.cx" :cy="s.cy" :r="s.r" fill="#a3b8ae" fill-opacity="0.9" stroke="#7f978a" stroke-width="0.6" />
            </g>

            <!-- Leaves: a colour, then one half lighter, then the vein -->
            <g filter="url(#garden-wash)">
                <g v-for="(l, i) in art.leaves" :key="`lf${i}`">
                    <path :d="l.d" :fill="l.color" />
                    <path :d="l.half" fill="#ffffff" :fill-opacity="l.tone" />
                    <path :d="l.rib" stroke="#f1f4ea" stroke-opacity="0.5" stroke-width="0.9" fill="none" />
                </g>
            </g>

            <!-- Blooms, back to front -->
            <GardenBloom v-for="(b, i) in art.blooms" :key="`bl${i}`" :bloom="b.bloom" :palette="b.palette" :uid="`garden-${corner}-${i}`" />

            <!-- Blossoms -->
            <g v-for="(b, i) in art.blossoms" :key="`bs${i}`" filter="url(#garden-wash)">
                <ellipse
                    v-for="(p, j) in b.shapes"
                    :key="j"
                    :cx="p.cx"
                    :cy="p.cy"
                    :rx="p.rx"
                    :ry="p.ry"
                    :transform="`rotate(${p.angle.toFixed(1)} ${p.cx.toFixed(1)} ${p.cy.toFixed(1)})`"
                    :fill="BLOSSOMS[b.color].fill"
                    :stroke="BLOSSOMS[b.color].line"
                    stroke-width="0.6"
                />
                <circle :cx="b.center[0]" :cy="b.center[1]" :r="b.radius * 0.24" :fill="BLOSSOMS[b.color].heart" />
            </g>

            <!-- Pink berries -->
            <circle v-for="(d, i) in art.berries" :key="`be${i}`" :cx="d.x" :cy="d.y" :r="d.r" fill="#e69a9a" stroke="#c97878" stroke-width="0.5" />

            <!-- Gold dots -->
            <circle v-for="(d, i) in art.dots" :key="`gd${i}`" :cx="d.x" :cy="d.y" :r="d.r * 1.15" fill="url(#garden-gold)" />
        </g>
    </svg>
</template>
