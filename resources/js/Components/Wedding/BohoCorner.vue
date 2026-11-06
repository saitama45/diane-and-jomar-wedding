<script setup>
/*
 * Painted-look boho corner arrangement: pampas plumes, dried palm spears, cream roses, bunny-tail grass
 * and dark dried leaves, drawn from code for the top-left corner and turned to fit the others.
 * If the admin uploaded their own corner artwork (Site Settings > Decorations), that image is used instead.
 * Decorative only: it sits behind the section content (the section needs `isolate`).
 */
import { computed, inject } from 'vue';

const props = defineProps({
    corner: { type: String, default: 'top-left' }, // top-left | top-right | bottom-left | bottom-right
    behind: { type: Boolean, default: true }, // under the section content (needs `isolate` on the section)
});

const customImage = inject('decorImage', computed(() => null));

const placement = computed(
    () =>
        ({
            'top-left': 'left-0 top-0',
            'top-right': 'right-0 top-0 -scale-x-100',
            'bottom-left': 'bottom-0 left-0 -rotate-90',
            'bottom-right': 'bottom-0 right-0 rotate-180',
        })[props.corner],
);

// ---- drawing helpers (deterministic: a seeded random keeps the arrangement identical on every visit) ----

function seeded(seed) {
    return () => {
        seed = (seed + 0x6d2b79f5) | 0;
        let t = Math.imul(seed ^ (seed >>> 15), 1 | seed);
        t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
        return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
    };
}
const random = seeded(20271224);
const pick = (list) => list[Math.floor(random() * list.length)];

const rad = (deg) => (deg * Math.PI) / 180;
const toward = (deg) => [Math.cos(rad(deg)), Math.sin(rad(deg))];
const add = (a, b, k = 1) => [a[0] + b[0] * k, a[1] + b[1] * k];
const fmt = ([x, y]) => `${x.toFixed(1)},${y.toFixed(1)}`;
const rotate = ([x, y], deg) => [x * Math.cos(rad(deg)) - y * Math.sin(rad(deg)), x * Math.sin(rad(deg)) + y * Math.cos(rad(deg))];
const normalOf = ([x, y]) => [-y, x];

function curve(base, deg, length, bend) {
    const dir = toward(deg);
    const tip = add(base, dir, length);
    const control = add(add(base, dir, length * 0.5), normalOf(dir), bend);
    const at = (t) => [
        (1 - t) ** 2 * base[0] + 2 * (1 - t) * t * control[0] + t ** 2 * tip[0],
        (1 - t) ** 2 * base[1] + 2 * (1 - t) * t * control[1] + t ** 2 * tip[1],
    ];
    const along = (t) => {
        const dx = 2 * (1 - t) * (control[0] - base[0]) + 2 * t * (tip[0] - control[0]);
        const dy = 2 * (1 - t) * (control[1] - base[1]) + 2 * t * (tip[1] - control[1]);
        const length = Math.hypot(dx, dy);
        return [dx / length, dy / length];
    };
    return { d: `M${fmt(base)} Q${fmt(control)} ${fmt(tip)}`, at, along };
}

const PAMPAS = ['#e9dcc4', '#dcc6a3', '#cfb48a', '#c3a477', '#efe5d3', '#b8966a'];

function pampas(base, deg, length, bend) {
    const stem = curve(base, deg, length, bend);
    const fronds = [];
    const outline = [[], []];
    for (let t = 0.3; t <= 0.99; t += 0.011) {
        const p = stem.at(t);
        const along = stem.along(t);
        const reach = (Math.sin((Math.PI * (t - 0.27)) / 0.75) ** 0.8) * 30 + 3;
        for (const side of [-1, 1]) {
            const length = reach * (0.65 + random() * 0.55);
            const out = rotate(along, side * (26 + random() * 20));
            const end = add(p, out, length);
            const bendTo = add(add(p, out, length * 0.55), along, length * 0.3);
            fronds.push({
                d: `M${fmt(p)} Q${fmt(bendTo)} ${fmt(end)}`,
                color: pick(PAMPAS),
                width: (0.8 + random() * 0.9).toFixed(2),
                opacity: (0.55 + random() * 0.4).toFixed(2),
            });
        }
        outline[0].push(add(p, normalOf(along), reach * 0.7));
        outline[1].unshift(add(p, normalOf(along), -reach * 0.7));
    }
    const body = `M${[...outline[0], ...outline[1]].map(fmt).join(' L')} Z`;
    return { stem: stem.d, body, fronds };
}

function palmFan(base, deg, span, length, blades) {
    const shapes = [];
    for (let i = 0; i < blades; i++) {
        const angle = deg - span / 2 + (span * i) / (blades - 1);
        const dir = toward(angle);
        const normal = normalOf(dir);
        const len = length * (0.72 + 0.28 * Math.sin((Math.PI * i) / (blades - 1))) * (0.92 + random() * 0.14);
        const width = 6.2 + random() * 2.4;
        const tip = add(base, dir, len);
        const c1 = add(add(base, dir, len * 0.32), normal, width);
        const c2 = add(add(base, dir, len * 0.82), normal, width * 0.55);
        const c3 = add(add(base, dir, len * 0.82), normal, -width * 0.55);
        const c4 = add(add(base, dir, len * 0.32), normal, -width);
        shapes.push({
            blade: `M${fmt(base)} C${fmt(c1)} ${fmt(c2)} ${fmt(tip)} C${fmt(c3)} ${fmt(c4)} ${fmt(base)} Z`,
            rib: `M${fmt(add(base, dir, len * 0.12))} L${fmt(add(base, dir, len * 0.94))}`,
        });
    }
    return { base, length, shapes };
}

function rose(center, radius, turn) {
    const layers = [
        { count: 7, size: 1, offset: 0 },
        { count: 6, size: 0.78, offset: 26 },
        { count: 5, size: 0.56, offset: 11 },
        { count: 4, size: 0.34, offset: 40 },
    ];
    const petals = [];
    for (const layer of layers) {
        for (let i = 0; i < layer.count; i++) {
            const angle = turn + layer.offset + (360 * i) / layer.count + (random() - 0.5) * 10;
            const dir = toward(angle);
            const normal = normalOf(dir);
            const r = radius * layer.size * (0.92 + random() * 0.14);
            const w = r * 0.72;
            const start = add(center, dir, r * 0.08);
            const tip = add(center, dir, r);
            petals.push(
                `M${fmt(start)} C${fmt(add(add(center, dir, r * 0.25), normal, w))} ${fmt(add(add(center, dir, r * 1.02), normal, w * 0.7))} ${fmt(tip)} ` +
                    `C${fmt(add(add(center, dir, r * 1.02), normal, -w * 0.7))} ${fmt(add(add(center, dir, r * 0.25), normal, -w))} ${fmt(start)} Z`,
            );
        }
    }
    const swirl = `M${fmt(add(center, [radius * 0.12, 0]))} a${(radius * 0.12).toFixed(1)},${(radius * 0.1).toFixed(1)} 0 1 1 -${(radius * 0.2).toFixed(1)},${(radius * 0.04).toFixed(1)}`;
    return { center, radius, petals, swirl };
}

function leafSprig(base, deg, length, bend, color) {
    const stem = curve(base, deg, length, bend);
    const leaves = [];
    let side = 1;
    for (let t = 0.2; t <= 0.97; t += 0.085) {
        const p = stem.at(t);
        const dir = rotate(stem.along(t), side * (38 + random() * 14));
        const normal = normalOf(dir);
        const len = (24 + random() * 8) * (1 - t * 0.35);
        const w = len * 0.3;
        const tip = add(p, dir, len);
        const mid = add(p, dir, len * 0.5);
        leaves.push(`M${fmt(p)} Q${fmt(add(mid, normal, w))} ${fmt(tip)} Q${fmt(add(mid, normal, -w))} ${fmt(p)} Z`);
        side = -side;
    }
    return { stem: stem.d, leaves, color };
}

function bunnyTail(base, deg, length, bend) {
    const stem = curve(base, deg, length, bend);
    const tip = stem.at(1);
    const angle = (Math.atan2(stem.along(1)[1], stem.along(1)[0]) * 180) / Math.PI;
    return { stem: stem.d, tip, angle };
}

// ---- the arrangement (corner at 0,0; everything grows toward the page) ----

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
    </svg>
</template>
