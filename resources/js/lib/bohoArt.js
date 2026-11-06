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
