<script setup>
/*
 * A boho corner bouquet in line art: two dried pampas plumes and two olive-leaf stems that sway.
 * Drawn from code (no image files): plumes are short fronds fanned along a curved stem.
 * Decorative only; place it absolutely inside a section with `isolate` and give it `-z-10`.
 */
defineProps({
    dark: { type: Boolean, default: false }, // on a dark/coloured background
    flip: { type: Boolean, default: false }, // mirror for the right-hand corner
});

const quad = (p0, c, p1, t) => [
    (1 - t) ** 2 * p0[0] + 2 * (1 - t) * t * c[0] + t ** 2 * p1[0],
    (1 - t) ** 2 * p0[1] + 2 * (1 - t) * t * c[1] + t ** 2 * p1[1],
];

function direction(p0, c, p1, t) {
    const dx = 2 * (1 - t) * (c[0] - p0[0]) + 2 * t * (p1[0] - c[0]);
    const dy = 2 * (1 - t) * (c[1] - p0[1]) + 2 * t * (p1[1] - c[1]);
    const length = Math.hypot(dx, dy);
    return [dx / length, dy / length];
}

const rotate = ([x, y], angle) => [x * Math.cos(angle) - y * Math.sin(angle), x * Math.sin(angle) + y * Math.cos(angle)];
const point = ([x, y]) => `${x.toFixed(1)},${y.toFixed(1)}`;

// Feathery plume: fronds on both sides of the upper stem, fullest in the middle, tapering to the tip.
function pampas(p0, c, p1, reach = 24) {
    const paths = [`M${point(p0)} Q${point(c)} ${point(p1)}`];
    for (let t = 0.4; t <= 0.985; t += 0.026) {
        const base = quad(p0, c, p1, t);
        const along = direction(p0, c, p1, t);
        const length = reach * Math.sin((Math.PI * (t - 0.38)) / 0.64) + 4;
        for (const side of [-1, 1]) {
            const out = rotate(along, side * 0.62);
            const end = [base[0] + out[0] * length, base[1] + out[1] * length];
            const bend = [base[0] + (out[0] * 0.55 + along[0] * 0.35) * length, base[1] + (out[1] * 0.55 + along[1] * 0.35) * length];
            paths.push(`M${point(base)} Q${point(bend)} ${point(end)}`);
        }
    }
    return paths.join(' ');
}

// Olive stem: almond leaves alternating along the stem, smaller towards the tip.
function oliveStem(p0, c, p1) {
    const paths = [`M${point(p0)} Q${point(c)} ${point(p1)}`];
    let side = 1;
    for (let t = 0.22; t <= 0.96; t += 0.105) {
        const base = quad(p0, c, p1, t);
        const out = rotate(direction(p0, c, p1, t), side * 0.85);
        const length = 26 * (1 - t * 0.45);
        const width = length * 0.36;
        const tip = [base[0] + out[0] * length, base[1] + out[1] * length];
        const normal = [-out[1], out[0]];
        const mid = [base[0] + out[0] * length * 0.5, base[1] + out[1] * length * 0.5];
        const a = [mid[0] + normal[0] * width, mid[1] + normal[1] * width];
        const b = [mid[0] - normal[0] * width, mid[1] - normal[1] * width];
        paths.push(`M${point(base)} Q${point(a)} ${point(tip)} Q${point(b)} ${point(base)}`);
        side = -side;
    }
    return paths.join(' ');
}

const stems = [
    { kind: 'leaf', d: oliveStem([64, 300], [30, 240], [8, 150]), delay: '-3s' },
    { kind: 'plume', d: pampas([70, 300], [62, 180], [38, 34]), delay: '0s' },
    { kind: 'plume', d: pampas([78, 300], [112, 196], [150, 74], 20), delay: '-5s' },
    { kind: 'leaf', d: oliveStem([74, 300], [96, 226], [196, 176]), delay: '-1.5s' },
];
</script>

<template>
    <svg
        viewBox="0 0 220 300"
        fill="none"
        stroke="currentColor"
        stroke-width="1.1"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
        :class="flip ? '-scale-x-100' : ''"
    >
        <path
            v-for="(stem, i) in stems"
            :key="i"
            :d="stem.d"
            class="boho-sway"
            :class="stem.kind === 'plume' ? (dark ? 'text-gold-light' : 'text-gold') : dark ? 'text-clay' : 'text-sage'"
            :style="{ animationDelay: stem.delay }"
        />
    </svg>
</template>
