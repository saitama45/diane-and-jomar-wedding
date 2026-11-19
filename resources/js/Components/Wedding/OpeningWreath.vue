<script setup>
/*
 * The motion graphic played when a guest opens the invitation (IntroGate.vue), drawn in code in the flower
 * theme. A gold spark draws a ring; a wreath grows round it from the lower left (boho: dried palm fans that
 * open, pampas, dried leaves and bunny tails; garden: gold line art that draws itself, eucalyptus and
 * watercolour leaves); two wedding rings slide together and link between the couple's initials, which
 * write themselves in gold; flowers bloom; the gold catches the light; the names and the date appear.
 * `timeline()` builds it as a GSAP timeline (about 7 s); IntroGate plays it and adds the way out.
 */
import { computed, inject, ref } from 'vue';
import { GARDEN_BLOSSOMS, gardenPainter, onCircle, painter } from '@/lib/bohoArt';
import { gsap } from '@/lib/motion';
import GardenBloom from './GardenBloom.vue';
import GardenFilters from './GardenFilters.vue';

const props = defineProps({
    names: { type: Array, required: true }, // [first, second], in the opening screen's order
    date: { type: String, default: '' },
});

const theme = inject('decorTheme', computed(() => 'boho'));
const initials = computed(() => props.names.map((name) => (name ?? '').trim().charAt(0).toUpperCase()));

const C = [200, 200];
const R = 118;
const at = (deg, radius = R) => onCircle(C, radius, deg);
const xy = ([x, y]) => `${x.toFixed(1)} ${y.toFixed(1)}`;

// The ring is drawn like a pen stroke from the upper left, down the left side and round, so it is a path.
const START = 225;
const RING = `M${xy(at(START))} ${[1, 2, 3].map((k) => `A${R} ${R} 0 0 0 ${xy(at(START - 120 * k))}`).join(' ')}`;

// The wreath grows from the lower left round both sides: a piece's delay grows with its distance from there.
const GROW_FROM = 120;
const SPREAD = 1.1; // seconds from the first piece to the last
const norm = (deg) => ((deg % 360) + 360) % 360;
const delayOf = (deg, spread = SPREAD) => (Math.abs(norm(deg - GROW_FROM + 180) - 180) / 180) * spread;

/*
 * Everything hugs the ring. A wreath has two arms climbing from the bottom: the left arm turns clockwise on
 * screen (`dir` 1, the angle grows), the right arm the other way (`dir` -1). `hug` aims a stem of `length`
 * starting on the ring at `deg` along the circle; `lean` tilts it outward (+) or inward (-) in degrees.
 */
function hug(deg, length, dir, lean = 0, radius = R) {
    const sweep = ((length / radius) * 180) / Math.PI;
    const sag = radius * (1 - Math.cos((sweep * Math.PI) / 360));
    return { base: at(deg, radius), heading: deg + dir * (90 + sweep / 2) - dir * lean, bend: dir * 2 * sag, delay: delayOf(deg), turn: -dir * 16 };
}
// Angles along an arm from `from` to `to` every `step` degrees: each leans out or in by turns; `k` runs 0 → 1.
function arm(from, to, step) {
    const count = Math.floor(Math.abs(to - from) / step + 1e-6);
    return Array.from({ length: count + 1 }, (_, i) => ({ deg: from + Math.sign(to - from) * step * i, side: i % 2 ? -1 : 1, k: i / Math.max(1, count) }));
}

/* ---------- Boho: dried palm fans behind, pampas along both arms, dried leaves, bunny tails, roses ---------- */

const bp = painter(20261119);
function fan(deg, heading, span, length, blades) {
    const shape = bp.palmFan(at(deg), heading, span, length, blades);
    // Each blade's angle from the middle of the fan: the fan opens from a closed bunch.
    const spread = shape.shapes.map((_, i) => -span / 2 + (span * i) / (blades - 1));
    return { ...shape, spread, delay: delayOf(deg) };
}
const boho = {
    // Fans point outward and up along their arm.
    fans: [fan(110, 152, 62, 62, 9), fan(198, 240, 46, 46, 7), fan(64, 22, 60, 58, 9), fan(-30, -72, 40, 40, 7)],
    plumes: [
        [116, 96, 1, 0.9],
        [160, 88, 1, 0.8],
        [202, 74, 1, 0.68],
        [236, 54, 1, 0.5],
        [64, 96, -1, 0.9],
        [22, 84, -1, 0.78],
        [-16, 68, -1, 0.62],
        [-46, 48, -1, 0.48],
    ].map(([deg, length, dir, fluff]) => {
        const h = hug(deg, length, dir);
        return { ...h, ...bp.pampas(h.base, h.heading, length, h.bend, fluff) };
    }),
    sprigs: [
        [130, 56, 1, 24, '#6b4c33', 0.6],
        [52, 54, -1, 24, '#7a5a3e', 0.58],
        [250, 40, 1, 18, '#a8865f', 0.45],
        [-30, 44, -1, -14, '#8a6a4b', 0.5],
    ].map(([deg, length, dir, lean, color, size]) => {
        const h = hug(deg, length, dir, lean);
        return { ...h, ...bp.leafSprig(h.base, h.heading, length, h.bend, color, size) };
    }),
    tails: [
        [140, 50, 1, 40],
        [104, 46, 1, 55],
        [38, 46, -1, 45],
        [-30, 40, -1, 40],
    ].map(([deg, length, dir, lean]) => {
        const h = hug(deg, length, dir, lean);
        return { ...h, ...bp.bunnyTail(h.base, h.heading, length, h.bend * 0.5) };
    }),
    roses: [
        [124, R + 2, 19, 10],
        [102, R + 8, 11, 40],
        [148, R + 6, 10, 70],
        [-40, R + 2, 11, 30],
        [-24, R + 8, 7, 0],
    ].map(([deg, radius, size, turn]) => ({ ...bp.rose(at(deg, radius), size, turn), delay: delayOf(deg, 0.8) })),
};

/* ---------- Garden: gold line-art vines, watercolour leaves along both arms, eucalyptus, flowers ---------- */

const gp = gardenPainter(20261120);
const GREENS = ['#7d8f74', '#5d6c43', '#8fa58a', '#3e4f39', '#7f9a78', '#9fb5b1', '#6c7f66'];
// A gold vine just outside the ring, from `from` to `to`, with line-art leaves along it.
function goldVine(from, to, radius, leaves, size) {
    const dir = Math.sign(to - from);
    const stem = `M${xy(at(from, radius))} A${radius} ${radius} 0 0 ${dir > 0 ? 1 : 0} ${xy(at(to, radius))}`;
    const shapes = arm(from, to, Math.abs(to - from) / (leaves - 1)).map(({ deg, side, k }) => {
        const length = (30 - k * 12) * size;
        return gp.leaf(at(deg, radius), deg + dir * 90 - dir * side * 40, length, length * 0.32, side * length * 0.06);
    });
    return { stem, shapes, delay: delayOf(from) };
}
// Watercolour leaves along an arm, longest at the bottom.
function leafArm(from, to, step, dir, offset) {
    return arm(from, to, step).map(({ deg, side, k }, i) => {
        const length = 38 - k * 16;
        const h = hug(deg, length, dir, side * 30, R + side * 1.5);
        return { ...h, ...gp.leaf(h.base, h.heading, length, length * 0.3, side * 2), color: GREENS[(i + offset) % GREENS.length], tone: 0.2 + (i % 3) * 0.05 };
    });
}
const garden = {
    gold: [goldVine(150, 262, R + 12, 8, 0.8), goldVine(40, -78, R + 12, 8, 0.8)],
    leaves: [...leafArm(118, 236, 9, 1, 0), ...leafArm(64, -44, 9, -1, 3)],
    eucalyptus: [
        [90, 64, -1, R + 10],
        [196, 54, 1, R - 9],
    ].map(([deg, length, dir, radius]) => {
        const h = hug(deg, length, dir, 0, radius);
        return { ...h, ...gp.branch(h.base, h.heading, length, h.bend, { leaves: 6, round: true, size: 0.62 }) };
    }),
    blooms: [
        [124, R + 2, 30, 18, 'peach'],
        [96, R + 6, 19, 6, 'coral'],
        [150, R + 4, 13, 30, 'apricot'],
        [-40, R + 2, 14, 12, 'coral'],
    ].map(([deg, radius, size, turn, palette]) => ({ bloom: gp.bloom(at(deg, radius), size, turn), palette, delay: delayOf(deg, 0.8) })),
    blossoms: [
        [164, R + 2, 12, 10, 5, 'lilac'],
        [110, R + 16, 10, 30, 5, 'lilac'],
        [72, R + 4, 10, 20, 5, 'blush'],
        [84, R + 18, 6, 20, 4, 'rust'],
        [138, R + 22, 8, 8, 5, 'cream'],
        [-54, R + 4, 9, 40, 5, 'blush'],
        [-26, R + 6, 9, 15, 5, 'lilac'],
        [-64, R + 10, 5, 0, 4, 'rust'],
        [20, R + 2, 7, 8, 5, 'cream'],
    ].map(([deg, radius, size, turn, petals, color]) => ({ ...gp.blossom(at(deg, radius), size, turn, petals), color, delay: delayOf(deg, 0.8) })),
    berries: [...gp.berries(at(106, R + 24), 5, 9, 3.2), ...gp.berries(at(-46, R + 20), 4, 8, 3)],
    dots: [...gp.dots(9, C, 80, 170, R + 18, R + 40), ...gp.dots(7, C, -80, -10, R + 16, R + 36)],
};

/* ---------- Gold: the spark's trail, twinkles, the wedding rings ---------- */

const sparkle = gardenPainter(52).random;
const TRAIL = Array.from({ length: 28 }, (_, i) => {
    const p = (i + 0.5) / 28;
    const [x, y] = at(START - 360 * p, R + (sparkle() - 0.5) * 10);
    return { x, y, r: 0.7 + sparkle() * 1.5, p, dx: (sparkle() - 0.5) * 14, dy: 4 + sparkle() * 14 };
});
const TWINKLES = Array.from({ length: 12 }, () => {
    const [x, y] = at(sparkle() * 360, R + (sparkle() - 0.5) * 70);
    return { x, y, size: 3 + sparkle() * 4 };
});
const star = (s) => `M0 ${-s} L${s * 0.16} ${-s * 0.16} L${s} 0 L${s * 0.16} ${s * 0.16} L0 ${s} L${-s * 0.16} ${s * 0.16} L${-s} 0 L${-s * 0.16} ${-s * 0.16}Z`;

// Two tilted rings: the second is drawn over the first, then the first again over the second just round
// their upper crossing (the #ow-link mask), so they look linked. `from` is the side each slides in from.
const BAND = { rx: 18, ry: 14, tilt: -12 };
const BANDS = [
    { cx: 197, cy: 213, from: -1 },
    { cx: 218, cy: 220, from: 1 },
];
const tilted = (b, [x, y], turn) => {
    const a = (turn * Math.PI) / 180;
    return [b.cx + x * Math.cos(a) - y * Math.sin(a), b.cy + x * Math.sin(a) + y * Math.cos(a)];
};
// Below zero inside a ring's ellipse, above zero outside it.
const outside = (b, [x, y]) => {
    const [u, v] = tilted({ cx: 0, cy: 0 }, [x - b.cx, y - b.cy], -BAND.tilt);
    return (u / BAND.rx) ** 2 + (v / BAND.ry) ** 2 - 1;
};
const LINK = (() => {
    const edge = Array.from({ length: 360 }, (_, i) => tilted(BANDS[0], [BAND.rx * Math.cos((i * Math.PI) / 180), BAND.ry * Math.sin((i * Math.PI) / 180)], BAND.tilt));
    const crossings = edge.filter((p, i) => Math.sign(outside(BANDS[1], p)) !== Math.sign(outside(BANDS[1], edge[(i + 1) % edge.length])));
    return crossings.reduce((top, p) => (p[1] < top[1] ? p : top));
})();
const BAND_LAYERS = [...BANDS, { ...BANDS[0], link: true }];

const root = ref();
const ring = ref();
const spark = ref();
const shine = ref();

function timeline() {
    const q = gsap.utils.selector(root.value);
    const tl = gsap.timeline({ defaults: { ease: 'power3.out' } });

    // 1. A spark lights up and draws the ring. The trail of gold dust is timed to the pen's sine.inOut.
    const DRAW_AT = 0.3;
    const DRAW = 1.7;
    const length = ring.value.getTotalLength();
    const pen = { p: 0 };
    const movePen = () => {
        const [x, y] = at(START - 360 * pen.p);
        gsap.set(spark.value, { x, y });
        ring.value.style.strokeDashoffset = `${length * (1 - pen.p)}`;
    };
    ring.value.style.strokeDasharray = `${length}`;
    movePen();

    tl.fromTo(spark.value, { autoAlpha: 0, scale: 0 }, { autoAlpha: 1, scale: 1, duration: 0.45, ease: 'back.out(3)', transformOrigin: '50% 50%' }, 0).to(
        pen,
        { p: 1, duration: DRAW, ease: 'sine.inOut', onUpdate: movePen },
        DRAW_AT,
    );
    q('.ow-trail').forEach((el, i) => {
        const dust = TRAIL[i];
        const when = DRAW_AT + (Math.acos(1 - 2 * dust.p) / Math.PI) * DRAW;
        tl.fromTo(el, { opacity: 0 }, { opacity: 1, duration: 0.12, ease: 'none' }, when).to(el, { opacity: 0, x: dust.dx, y: dust.dy, duration: 1, ease: 'power1.out' }, when + 0.12);
    });

    // The ring closes: the spark flares out, a ripple runs outward and a warm light comes up behind.
    const CLOSED = DRAW_AT + DRAW;
    tl.to(spark.value, { scale: 1.9, duration: 0.25, ease: 'power2.out' }, CLOSED)
        .to(spark.value, { autoAlpha: 0, scale: 0.3, duration: 0.5, ease: 'power2.in' }, CLOSED + 0.25)
        .fromTo(q('.ow-ripple'), { opacity: 0.8, scale: 1 }, { opacity: 0, scale: 1.32, duration: 1.3, ease: 'power2.out', svgOrigin: `${C[0]} ${C[1]}`, immediateRender: false }, CLOSED)
        .fromTo(q('.ow-glow'), { opacity: 0, scale: 0.5 }, { opacity: 1, scale: 1, duration: 1.8, ease: 'power2.out' }, CLOSED - 0.2);

    // 2. The wreath grows from the lower left round both sides; palm fans open, gold line art draws itself.
    const GROW = CLOSED - 0.15;
    q('.ow-grow').forEach((el) => {
        const { ox, oy, delay, turn = 0 } = el.dataset;
        tl.from(el, { scale: 0, rotation: Number(turn), opacity: 0, svgOrigin: `${ox} ${oy}`, duration: 1.1, ease: 'back.out(1.3)' }, GROW + Number(delay));
    });
    q('.ow-blade').forEach((el) => {
        const { ox, oy, delay, spread } = el.dataset;
        tl.from(el, { rotation: -Number(spread), svgOrigin: `${ox} ${oy}`, duration: 1.3, ease: 'power3.out' }, GROW + Number(delay) + 0.1);
    });
    q('.ow-draw').forEach((el) => {
        const size = el.getTotalLength();
        gsap.set(el, { strokeDasharray: size, strokeDashoffset: size });
        tl.to(el, { strokeDashoffset: 0, duration: 1.2, ease: 'power2.inOut' }, GROW + Number(el.dataset.delay));
    });

    // 3. Two wedding rings slide together and link; a glint where they cross.
    const RINGS = GROW + 1.45;
    tl.from(q('.ow-band'), { x: (i, el) => Number(el.dataset.from) * 34, y: -6, opacity: 0, duration: 1.1 }, RINGS)
        .fromTo(q('.ow-glint'), { scale: 0, opacity: 0 }, { scale: 1, opacity: 1, rotation: 45, duration: 0.25, ease: 'power2.out', transformOrigin: '50% 50%', immediateRender: false }, RINGS + 0.95)
        .to(q('.ow-glint'), { scale: 0, opacity: 0, rotation: 90, duration: 0.5, ease: 'power2.in' }, RINGS + 1.2);

    // 4. The initials write themselves in gold, either side of the rings, then fill.
    const LETTERS = RINGS + 0.5;
    q('.ow-letter').forEach((el, i) => {
        tl.fromTo(el, { strokeDasharray: 320, strokeDashoffset: 320, fillOpacity: 0 }, { strokeDashoffset: 0, duration: 1.3, ease: 'power2.inOut' }, LETTERS + i * 0.25).to(
            el,
            { fillOpacity: 1, duration: 0.7, ease: 'power1.inOut' },
            LETTERS + i * 0.25 + 0.8,
        );
    });
    tl.from(q('.ow-initial'), { x: (i, el) => Number(el.dataset.from) * 12, duration: 1.6, ease: 'power2.out' }, LETTERS);

    // 5. Flowers bloom over the wreath, then berries and gold dots.
    const BLOOM = LETTERS + 0.8;
    q('.ow-bloom').forEach((el) => {
        const { ox, oy, delay } = el.dataset;
        tl.from(el, { scale: 0, rotation: -80, opacity: 0, svgOrigin: `${ox} ${oy}`, duration: 0.9, ease: 'back.out(1.7)' }, BLOOM + Number(delay));
    });
    tl.from(q('.ow-dot'), { scale: 0, opacity: 0, transformOrigin: '50% 50%', duration: 0.5, ease: 'back.out(2)', stagger: { each: 0.03, from: 'random' } }, BLOOM + 0.4);

    // 6. The gold catches the light: a gleam runs over the gold, a light sweeps the wreath, stars twinkle.
    const SHINE = BLOOM + 0.9;
    gsap.set(q('.ow-twinkle'), { scale: 0, opacity: 0, transformOrigin: '50% 50%' });
    tl.fromTo(shine.value, { attr: { gradientTransform: 'translate(-320 -320)' } }, { attr: { gradientTransform: 'translate(320 320)' }, duration: 1.4, ease: 'power1.inOut' }, SHINE)
        .to(
            q('.ow-twinkle'),
            {
                keyframes: [
                    { scale: 1, opacity: 1, rotation: 45, duration: 0.3, ease: 'power2.out' },
                    { scale: 0, opacity: 0, rotation: 90, duration: 0.6, ease: 'power2.in' },
                ],
                stagger: 0.09,
            },
            SHINE + 0.1,
        );

    // 7. The couple's names and the date.
    tl.from(q('.ow-title'), { y: 16, opacity: 0, filter: 'blur(6px)', duration: 1.1, stagger: 0.18, clearProps: 'filter' }, SHINE + 0.2);

    return tl;
}

defineExpose({ timeline });
</script>

<template>
    <div ref="root" class="flex flex-col items-center px-6 text-center">
        <div class="relative aspect-square w-[min(84vw,52vh)] sm:w-[min(66vw,56vh)]">
            <!-- Warm light behind the ring once it closes -->
            <div class="ow-glow absolute -inset-[14%] rounded-full opacity-0" style="background: radial-gradient(circle, rgb(255 243 219 / 0.95), rgb(255 243 219 / 0) 66%)"></div>

            <svg viewBox="0 0 400 400" aria-hidden="true" class="absolute inset-0 h-full w-full overflow-visible">
                <defs>
                    <linearGradient id="ow-gold" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0" stop-color="#8f6127" />
                        <stop offset="0.32" stop-color="#d9b46c" />
                        <stop offset="0.5" stop-color="#f7e3a8" />
                        <stop offset="0.68" stop-color="#c6974d" />
                        <stop offset="1" stop-color="#8a5c22" />
                    </linearGradient>
                    <!-- A gleam of light, off the drawing until the shine moves it across -->
                    <linearGradient ref="shine" id="ow-shine" gradientUnits="userSpaceOnUse" x1="0" y1="0" x2="400" y2="400" gradientTransform="translate(-320 -320)">
                        <stop offset="0.42" stop-color="#fff8e4" stop-opacity="0" />
                        <stop offset="0.5" stop-color="#fff8e4" stop-opacity="0.95" />
                        <stop offset="0.58" stop-color="#fff8e4" stop-opacity="0" />
                    </linearGradient>
                    <radialGradient id="ow-spark">
                        <stop offset="0" stop-color="#fffdf2" />
                        <stop offset="0.22" stop-color="#ffe9a8" stop-opacity="0.9" />
                        <stop offset="1" stop-color="#f1c463" stop-opacity="0" />
                    </radialGradient>
                    <!-- Round the rings' upper crossing, soft-edged so the first ring passes over the second without a seam -->
                    <radialGradient id="ow-link-fade" gradientUnits="userSpaceOnUse" :cx="LINK[0]" :cy="LINK[1]" r="11">
                        <stop offset="0.55" stop-color="#fff" />
                        <stop offset="1" stop-color="#000" />
                    </radialGradient>
                    <mask id="ow-link" maskUnits="userSpaceOnUse" :x="LINK[0] - 12" :y="LINK[1] - 12" width="24" height="24">
                        <circle :cx="LINK[0]" :cy="LINK[1]" r="11" fill="url(#ow-link-fade)" />
                    </mask>
                    <!-- A band round the ring, for the light sweeping the wreath -->
                    <radialGradient id="ow-halo-fade" gradientUnits="userSpaceOnUse" :cx="C[0]" :cy="C[1]" :r="R + 56">
                        <stop offset="0.5" stop-color="#000" />
                        <stop offset="0.72" stop-color="#fff" />
                        <stop offset="0.88" stop-color="#fff" />
                        <stop offset="1" stop-color="#000" />
                    </radialGradient>
                    <mask id="ow-halo" maskUnits="userSpaceOnUse" x="-20" y="-20" width="440" height="440">
                        <circle :cx="C[0]" :cy="C[1]" :r="R + 56" fill="url(#ow-halo-fade)" />
                    </mask>

                    <template v-if="theme !== 'garden'">
                        <filter id="ow-fluff" x="-30%" y="-30%" width="160%" height="160%"><feGaussianBlur stdDeviation="4" /></filter>
                        <filter id="ow-fuzz" x="-50%" y="-50%" width="200%" height="200%"><feGaussianBlur stdDeviation="1.1" /></filter>
                        <filter id="ow-shadow" x="-30%" y="-30%" width="160%" height="160%">
                            <feDropShadow dx="1" dy="2" stdDeviation="2" flood-color="#5a3a26" flood-opacity="0.2" />
                        </filter>
                        <radialGradient v-for="(f, i) in boho.fans" :id="`ow-palm-${i}`" :key="`pg${i}`" gradientUnits="userSpaceOnUse" :cx="f.base[0]" :cy="f.base[1]" :r="f.length">
                            <stop offset="0" stop-color="#7a5232" />
                            <stop offset="0.55" stop-color="#b48a5c" />
                            <stop offset="1" stop-color="#e2c79c" />
                        </radialGradient>
                        <radialGradient v-for="(r, i) in boho.roses" :id="`ow-rose-${i}`" :key="`rg${i}`" gradientUnits="userSpaceOnUse" :cx="r.center[0]" :cy="r.center[1]" :r="r.radius">
                            <stop offset="0" stop-color="#d9a891" />
                            <stop offset="0.45" stop-color="#f1d9c8" />
                            <stop offset="1" stop-color="#fdf6ee" />
                        </radialGradient>
                    </template>
                </defs>
                <GardenFilters v-if="theme === 'garden'" />

                <!-- The ring and its ripple -->
                <circle class="ow-ripple" :cx="C[0]" :cy="C[1]" :r="R" fill="none" stroke="#d6b06a" stroke-width="1.2" opacity="0" />
                <path ref="ring" :d="RING" fill="none" stroke="url(#ow-gold)" stroke-width="1.8" stroke-linecap="round" />
                <path :d="RING" fill="none" stroke="url(#ow-shine)" stroke-width="1.8" />

                <!-- Boho wreath -->
                <g v-if="theme !== 'garden'">
                    <g v-for="(f, i) in boho.fans" :key="`fan${i}`" class="ow-grow" :data-ox="f.base[0]" :data-oy="f.base[1]" :data-delay="f.delay" opacity="0.95">
                        <g v-for="(s, j) in f.shapes" :key="j" class="ow-blade" :data-ox="f.base[0]" :data-oy="f.base[1]" :data-delay="f.delay" :data-spread="f.spread[j]">
                            <path :d="s.blade" :fill="`url(#ow-palm-${i})`" stroke="#6e4a2c" stroke-width="0.4" stroke-opacity="0.45" />
                            <path :d="s.rib" stroke="#f3e2c4" stroke-width="0.5" stroke-opacity="0.5" fill="none" />
                        </g>
                    </g>
                    <g v-for="(p, i) in boho.plumes" :key="`pl${i}`" class="ow-grow" :data-ox="p.base[0]" :data-oy="p.base[1]" :data-delay="p.delay" :data-turn="p.turn">
                        <path :d="p.body" fill="#d8c09b" opacity="0.42" filter="url(#ow-fluff)" />
                        <path :d="p.stem" stroke="#a88660" stroke-width="1.1" fill="none" />
                        <g fill="none" stroke-linecap="round">
                            <path v-for="(frond, k) in p.fronds" :key="k" :d="frond.d" :stroke="frond.color" :stroke-width="frond.width" :stroke-opacity="frond.opacity" />
                        </g>
                    </g>
                    <g v-for="(s, i) in boho.sprigs" :key="`sp${i}`" class="ow-grow" :data-ox="s.base[0]" :data-oy="s.base[1]" :data-delay="s.delay" :data-turn="s.turn" opacity="0.9">
                        <path :d="s.stem" :stroke="s.color" stroke-width="1" fill="none" />
                        <path v-for="(leaf, k) in s.leaves" :key="k" :d="leaf" :fill="s.color" />
                    </g>
                    <g v-for="(t, i) in boho.tails" :key="`bt${i}`" class="ow-grow" :data-ox="t.base[0]" :data-oy="t.base[1]" :data-delay="t.delay" :data-turn="t.turn">
                        <path :d="t.stem" stroke="#b39a7a" stroke-width="0.9" fill="none" />
                        <ellipse :cx="t.tip[0]" :cy="t.tip[1]" rx="8.5" ry="4" fill="#e3d3b8" filter="url(#ow-fuzz)" :transform="`rotate(${t.angle.toFixed(1)} ${t.tip[0].toFixed(1)} ${t.tip[1].toFixed(1)})`" />
                        <ellipse :cx="t.tip[0]" :cy="t.tip[1]" rx="5.8" ry="2.5" fill="#eee3d1" :transform="`rotate(${t.angle.toFixed(1)} ${t.tip[0].toFixed(1)} ${t.tip[1].toFixed(1)})`" />
                    </g>
                </g>

                <!-- Garden wreath -->
                <g v-else>
                    <g fill="none" stroke="#c09440" stroke-width="1.15" stroke-linecap="round">
                        <template v-for="(b, i) in garden.gold" :key="`gb${i}`">
                            <path class="ow-draw" :d="b.stem" :data-delay="b.delay" />
                            <template v-for="(s, k) in b.shapes" :key="k">
                                <path class="ow-draw" :d="s.d" :data-delay="b.delay + 0.2 + k * 0.08" />
                                <path class="ow-draw" :d="s.rib" stroke-width="0.7" :data-delay="b.delay + 0.35 + k * 0.08" />
                            </template>
                        </template>
                    </g>
                    <g v-for="(e, i) in garden.eucalyptus" :key="`eu${i}`" class="ow-grow" :data-ox="e.base[0]" :data-oy="e.base[1]" :data-delay="e.delay" :data-turn="e.turn">
                        <path :d="e.stem" stroke="#6f8679" stroke-width="1.3" fill="none" />
                        <circle v-for="(s, k) in e.shapes" :key="k" :cx="s.cx" :cy="s.cy" :r="s.r" fill="#a3b8ae" fill-opacity="0.9" stroke="#7f978a" stroke-width="0.6" />
                    </g>
                    <g filter="url(#garden-wash)">
                        <g v-for="(l, i) in garden.leaves" :key="`lf${i}`" class="ow-grow" :data-ox="l.base[0]" :data-oy="l.base[1]" :data-delay="l.delay" :data-turn="l.turn">
                            <path :d="l.d" :fill="l.color" />
                            <path :d="l.half" fill="#ffffff" :fill-opacity="l.tone" />
                            <path :d="l.rib" stroke="#f1f4ea" stroke-opacity="0.5" stroke-width="0.8" fill="none" />
                        </g>
                    </g>
                </g>

                <!-- The wedding rings -->
                <g v-for="(b, i) in BAND_LAYERS" :key="`band${i}`" class="ow-band" :data-from="b.from" :mask="b.link ? 'url(#ow-link)' : undefined">
                    <g :transform="`rotate(${BAND.tilt} ${b.cx} ${b.cy})`" fill="none">
                        <ellipse :cx="b.cx" :cy="b.cy" :rx="BAND.rx" :ry="BAND.ry" stroke="#7d5320" stroke-width="6.2" />
                        <ellipse :cx="b.cx" :cy="b.cy" :rx="BAND.rx" :ry="BAND.ry" stroke="url(#ow-gold)" stroke-width="4.6" />
                        <ellipse :cx="b.cx" :cy="b.cy" :rx="BAND.rx - 1.3" :ry="BAND.ry - 1.3" stroke="#fff4d2" stroke-width="0.8" stroke-opacity="0.8" />
                        <ellipse :cx="b.cx" :cy="b.cy" :rx="BAND.rx" :ry="BAND.ry" stroke="url(#ow-shine)" stroke-width="4.6" />
                    </g>
                </g>

                <!-- The initials, either side of the rings: the first a little higher, the second lower -->
                <g class="font-serif" font-weight="500" text-anchor="middle" font-size="80">
                    <g class="ow-initial" data-from="-1">
                        <text class="ow-letter" x="146" y="222" fill="url(#ow-gold)" stroke="#a87a35" stroke-width="0.8">{{ initials[0] }}</text>
                        <text x="146" y="222" fill="url(#ow-shine)">{{ initials[0] }}</text>
                    </g>
                    <g class="ow-initial" data-from="1">
                        <text class="ow-letter" x="258" y="240" fill="url(#ow-gold)" stroke="#a87a35" stroke-width="0.8">{{ initials[1] }}</text>
                        <text x="258" y="240" fill="url(#ow-shine)">{{ initials[1] }}</text>
                    </g>
                </g>

                <!-- Flowers bloom last -->
                <g v-if="theme !== 'garden'">
                    <g v-for="(r, i) in boho.roses" :key="`ro${i}`" class="ow-bloom" :data-ox="r.center[0]" :data-oy="r.center[1]" :data-delay="r.delay" filter="url(#ow-shadow)">
                        <path v-for="(petal, k) in r.petals" :key="k" :d="petal" :fill="`url(#ow-rose-${i})`" stroke="#cfa98e" stroke-width="0.5" stroke-opacity="0.7" />
                        <path :d="r.swirl" fill="none" stroke="#b07f63" stroke-width="0.8" stroke-linecap="round" />
                    </g>
                </g>
                <g v-else>
                    <g v-for="(b, i) in garden.blooms" :key="`bl${i}`" class="ow-bloom" :data-ox="b.bloom.center[0]" :data-oy="b.bloom.center[1]" :data-delay="b.delay">
                        <GardenBloom :bloom="b.bloom" :palette="b.palette" :uid="`ow-bloom-${i}`" />
                    </g>
                    <g filter="url(#garden-wash)">
                        <g v-for="(b, i) in garden.blossoms" :key="`bs${i}`" class="ow-bloom" :data-ox="b.center[0]" :data-oy="b.center[1]" :data-delay="b.delay">
                            <ellipse
                                v-for="(p, k) in b.shapes"
                                :key="k"
                                :cx="p.cx"
                                :cy="p.cy"
                                :rx="p.rx"
                                :ry="p.ry"
                                :transform="`rotate(${p.angle.toFixed(1)} ${p.cx.toFixed(1)} ${p.cy.toFixed(1)})`"
                                :fill="GARDEN_BLOSSOMS[b.color].fill"
                                :stroke="GARDEN_BLOSSOMS[b.color].line"
                                stroke-width="0.6"
                            />
                            <circle :cx="b.center[0]" :cy="b.center[1]" :r="b.radius * 0.24" :fill="GARDEN_BLOSSOMS[b.color].heart" />
                        </g>
                    </g>
                    <circle v-for="(d, i) in garden.berries" :key="`be${i}`" class="ow-dot" :cx="d.x" :cy="d.y" :r="d.r" fill="#e69a9a" stroke="#c97878" stroke-width="0.5" />
                    <circle v-for="(d, i) in garden.dots" :key="`gd${i}`" class="ow-dot" :cx="d.x" :cy="d.y" :r="d.r" fill="url(#garden-gold)" />
                </g>

                <!-- The same gleam, softer, sweeping the whole wreath (only round the ring: #ow-halo) -->
                <rect x="-20" y="-20" width="440" height="440" fill="url(#ow-shine)" mask="url(#ow-halo)" opacity="0.55" />

                <!-- Gold dust, twinkles, the glint where the rings cross, and the spark (the pen) -->
                <circle v-for="(d, i) in TRAIL" :key="`tr${i}`" class="ow-trail" :cx="d.x" :cy="d.y" :r="d.r" fill="#f4d58c" opacity="0" />
                <g v-for="(t, i) in TWINKLES" :key="`tw${i}`" :transform="`translate(${t.x.toFixed(1)} ${t.y.toFixed(1)})`">
                    <path class="ow-twinkle" :d="star(t.size)" fill="#fff2c6" opacity="0" />
                </g>
                <g :transform="`translate(${LINK[0].toFixed(1)} ${LINK[1].toFixed(1)})`">
                    <path class="ow-glint" :d="star(9)" fill="#fffaf0" opacity="0" />
                </g>
                <g ref="spark" opacity="0">
                    <circle r="18" fill="url(#ow-spark)" />
                    <path :d="star(12)" fill="#fff8e1" />
                    <circle r="2.6" fill="#ffffff" />
                </g>
            </svg>
        </div>

        <p class="ow-title mt-2 font-script text-[clamp(2.4rem,9vw,3.6rem)] leading-tight text-caramel">{{ names[0] }} &amp; {{ names[1] }}</p>
        <p v-if="date" class="ow-title mt-2 flex items-center gap-3 font-serif text-sm uppercase tracking-[0.32em] text-cocoa md:text-base">
            <span class="h-px w-8 bg-current opacity-40"></span>{{ date }}<span class="h-px w-8 bg-current opacity-40"></span>
        </p>
    </div>
</template>
