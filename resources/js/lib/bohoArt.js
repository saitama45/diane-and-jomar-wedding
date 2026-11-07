/*
 * Painted-look boho botanicals drawn as SVG paths: pampas plumes, dried palm spears, cream roses,
 * dried-leaf sprigs and bunny-tail grass. Used by BohoCorner.vue and PampasWreath.vue.
 *
 * Everything is deterministic: a painter draws from a seeded random, so an arrangement looks
 * identical on every visit. Call the painter's helpers in the same order to get the same picture.
 */

export const PAMPAS_COLORS = ['#e9dcc4', '#dcc6a3', '#cfb48a', '#c3a477', '#efe5d3', '#b8966a'];

const rad = (deg) => (deg * Math.PI) / 180;
const toward = (deg) => [Math.cos(rad(deg)), Math.sin(rad(deg))];
const add = (a, b, k = 1) => [a[0] + b[0] * k, a[1] + b[1] * k];
const fmt = ([x, y]) => `${x.toFixed(1)},${y.toFixed(1)}`;
const rotate = ([x, y], deg) => [x * Math.cos(rad(deg)) - y * Math.sin(rad(deg)), x * Math.sin(rad(deg)) + y * Math.cos(rad(deg))];
const normalOf = ([x, y]) => [-y, x];

/** A point on a circle (SVG coordinates: 0° points right, 90° points down). */
export const onCircle = (center, radius, deg) => add(center, toward(deg), radius);

function seeded(seed) {
    return () => {
        seed = (seed + 0x6d2b79f5) | 0;
        let t = Math.imul(seed ^ (seed >>> 15), 1 | seed);
        t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
        return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
    };
}

/** A quadratic stem from `base` heading `deg`, bowed sideways by `bend`. */
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

export function painter(seed) {
    const random = seeded(seed);
    const pick = (list) => list[Math.floor(random() * list.length)];

    function pampas(base, deg, length, bend, fluff = 1) {
        const stem = curve(base, deg, length, bend);
        const fronds = [];
        const outline = [[], []];
        for (let t = 0.3; t <= 0.99; t += 0.011) {
            const p = stem.at(t);
            const along = stem.along(t);
            const reach = ((Math.sin((Math.PI * (t - 0.27)) / 0.75) ** 0.8) * 30 + 3) * fluff;
            for (const side of [-1, 1]) {
                const length = reach * (0.65 + random() * 0.55);
                const out = rotate(along, side * (26 + random() * 20));
                const end = add(p, out, length);
                const bendTo = add(add(p, out, length * 0.55), along, length * 0.3);
                fronds.push({
                    d: `M${fmt(p)} Q${fmt(bendTo)} ${fmt(end)}`,
                    color: pick(PAMPAS_COLORS),
                    width: (0.8 + random() * 0.9).toFixed(2),
                    opacity: (0.55 + random() * 0.4).toFixed(2),
                });
            }
            outline[0].push(add(p, normalOf(along), reach * 0.7));
            outline[1].unshift(add(p, normalOf(along), -reach * 0.7));
        }
        const body = `M${[...outline[0], ...outline[1]].map(fmt).join(' L')} Z`;
        return { base, stem: stem.d, body, fronds };
    }

    function palmFan(base, deg, span, length, blades) {
        const shapes = [];
        for (let i = 0; i < blades; i++) {
            const angle = deg - span / 2 + (span * i) / (blades - 1);
            const dir = toward(angle);
            const normal = normalOf(dir);
            const len = length * (0.72 + 0.28 * Math.sin((Math.PI * i) / (blades - 1))) * (0.92 + random() * 0.14);
            const width = (6.2 + random() * 2.4) * Math.min(1, length / 160);
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

    function leafSprig(base, deg, length, bend, color, leafSize = 1) {
        const stem = curve(base, deg, length, bend);
        const leaves = [];
        let side = 1;
        for (let t = 0.2; t <= 0.97; t += 0.085) {
            const p = stem.at(t);
            const dir = rotate(stem.along(t), side * (38 + random() * 14));
            const normal = normalOf(dir);
            const len = (24 + random() * 8) * (1 - t * 0.35) * leafSize;
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

    return { pampas, palmFan, rose, leafSprig, bunnyTail };
}

/*
 * The "Watercolor Garden" theme: big peach blooms with a teal and ochre eye, lilac blossoms, small rust
 * flowers, sage and eucalyptus leaves, gold line-art leaves and gold dots. Same rules as `painter`:
 * seeded, so it is identical on every visit, and drawn for the top-left corner (turned for the others).
 */
export function gardenPainter(seed) {
    const random = seeded(seed);
    const jitter = (amount) => (random() - 0.5) * 2 * amount;

    /** A pointed petal from `center` toward `deg`: `inner` and `outer` are fractions of `radius`. */
    function petal(center, deg, radius, { inner = 0.1, outer = 1, width = 0.3 } = {}) {
        const dir = toward(deg);
        const normal = normalOf(dir);
        const r = radius * outer;
        const w = r * width;
        const start = add(center, dir, radius * inner);
        const tip = add(center, dir, r);
        const shoulder = add(center, dir, r * 0.55);
        return (
            `M${fmt(start)} C${fmt(add(shoulder, normal, w))} ${fmt(add(add(center, dir, r * 0.92), normal, w * 0.45))} ${fmt(tip)} ` +
            `C${fmt(add(add(center, dir, r * 0.92), normal, -w * 0.45))} ${fmt(add(shoulder, normal, -w))} ${fmt(start)} Z`
        );
    }

    /** A gerbera-like bloom: three rings of pointed petals around a teal-ringed ochre eye. */
    function bloom(center, radius, turn) {
        const ring = (count, outer, width, offset) =>
            Array.from({ length: count }, (_, i) =>
                petal(center, turn + offset + (360 * i) / count + jitter(5), radius, { outer: outer * (0.92 + random() * 0.14), width: width * (0.9 + random() * 0.2) }),
            );
        const dots = Array.from({ length: 14 }, () => {
            const p = onCircle(center, radius * (0.05 + random() * 0.13), random() * 360);
            return { x: p[0], y: p[1], r: radius * (0.012 + random() * 0.012) };
        });
        return {
            center,
            radius,
            outer: ring(13, 1, 0.4, 0),
            middle: ring(11, 0.76, 0.42, 14),
            inner: ring(9, 0.5, 0.46, 5),
            dots,
        };
    }

    /** A small five-petal blossom with round petals. */
    function blossom(center, radius, turn, petals = 5) {
        const shapes = Array.from({ length: petals }, (_, i) => {
            const deg = turn + (360 * i) / petals + jitter(6);
            const c = onCircle(center, radius * 0.52, deg);
            return { cx: c[0], cy: c[1], rx: radius * 0.52, ry: radius * 0.36, angle: deg };
        });
        return { center, radius, shapes };
    }

    /** One filled leaf along a curved midrib. */
    function leaf(base, deg, length, width, bend = 0) {
        const dir = toward(deg);
        const normal = normalOf(dir);
        const tip = add(base, dir, length);
        const mid = add(add(base, dir, length * 0.5), normal, bend);
        return {
            d:
                `M${fmt(base)} Q${fmt(add(mid, normal, width))} ${fmt(tip)} ` +
                `Q${fmt(add(mid, normal, -width * 0.8))} ${fmt(base)} Z`,
            // one half of the leaf, painted lighter for a two-tone watercolour leaf
            half: `M${fmt(base)} Q${fmt(add(mid, normal, width))} ${fmt(tip)} Q${fmt(mid)} ${fmt(base)} Z`,
            rib: `M${fmt(base)} Q${fmt(mid)} ${fmt(add(base, dir, length * 0.92))}`,
        };
    }

    /** A stem with leaves on both sides (`outline` draws them as gold line art). */
    function branch(base, deg, length, bend, { leaves = 9, size = 1, round = false } = {}) {
        const stem = curve(base, deg, length, bend);
        const shapes = [];
        let side = 1;
        for (let i = 0; i < leaves; i++) {
            const t = 0.16 + (0.8 * i) / Math.max(1, leaves - 1);
            const p = stem.at(t);
            const along = stem.along(t);
            const angle = (Math.atan2(along[1], along[0]) * 180) / Math.PI + side * (round ? 70 : 42 + random() * 12);
            const len = (round ? 15 : 30 + random() * 8) * (1 - t * 0.35) * size;
            if (round) {
                const c = onCircle(p, len * 0.75, angle);
                shapes.push({ round: true, cx: c[0], cy: c[1], r: len * 0.62 });
            } else {
                shapes.push(leaf(p, angle, len, len * 0.32, side * len * 0.06));
            }
            side = -side;
        }
        const tip = stem.at(1);
        shapes.push(round ? { round: true, cx: tip[0], cy: tip[1], r: 6 * size } : leaf(tip, (Math.atan2(stem.along(1)[1], stem.along(1)[0]) * 180) / Math.PI, 24 * size, 7 * size));
        return { stem: stem.d, shapes };
    }

    /** Gold dots scattered along an arc around the arrangement. */
    function dots(count, center, from, to, minR, maxR) {
        return Array.from({ length: count }, () => {
            const p = onCircle(center, minR + random() * (maxR - minR), from + random() * (to - from));
            return { x: p[0], y: p[1], r: 1.6 + random() * 3.6 };
        });
    }

    /** A thin flower stem; `tip` is where its flower goes. */
    function stem(base, deg, length, bend) {
        const c = curve(base, deg, length, bend);
        return { d: c.d, tip: c.at(1) };
    }

    /** A small cluster of round berries or buds around `center`. */
    function berries(center, count, spread, size) {
        return Array.from({ length: count }, () => {
            const p = onCircle(center, random() * spread, random() * 360);
            return { x: p[0], y: p[1], r: size * (0.7 + random() * 0.5) };
        });
    }

    return { bloom, blossom, leaf, branch, dots, petal, stem, berries, random };
}
