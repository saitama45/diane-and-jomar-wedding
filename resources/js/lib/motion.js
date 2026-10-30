import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { SplitText } from 'gsap/SplitText';
import Lenis from 'lenis';

gsap.registerPlugin(ScrollTrigger, SplitText);

export { gsap, ScrollTrigger, SplitText };

export const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/* ---------- Smooth scrolling (one Lenis instance for the public site) ---------- */

let lenis = null;
const tick = (time) => lenis?.raf(time * 1000);

export function startSmoothScroll() {
    if (lenis || reducedMotion()) return lenis;

    lenis = new Lenis({ lerp: 0.085, wheelMultiplier: 0.95 });
    lenis.on('scroll', ScrollTrigger.update);
    gsap.ticker.add(tick);
    gsap.ticker.lagSmoothing(0);

    return lenis;
}

export function stopSmoothScroll() {
    gsap.ticker.remove(tick);
    lenis?.destroy();
    lenis = null;
}

export function lockScroll(locked) {
    if (lenis) {
        locked ? lenis.stop() : lenis.start();
    }
    document.documentElement.style.overflow = locked ? 'hidden' : '';
}

export function scrollToTarget(target, offset = -72) {
    const el = typeof target === 'string' ? document.querySelector(target) : target;
    if (!el && target !== 0) return;

    if (lenis) {
        lenis.scrollTo(el ?? 0, { offset, duration: 1.6 });
    } else {
        const top = el ? el.getBoundingClientRect().top + window.scrollY + offset : 0;
        window.scrollTo({ top, behavior: reducedMotion() ? 'auto' : 'smooth' });
    }
}

/* ---------- v-reveal: animate an element (or its children) in when it scrolls into view ---------- */

const PRESETS = {
    up: { y: 48, opacity: 0 },
    down: { y: -32, opacity: 0 },
    fade: { opacity: 0 },
    left: { x: -64, opacity: 0 },
    right: { x: 64, opacity: 0 },
    zoom: { scale: 0.9, opacity: 0 },
    blur: { y: 24, opacity: 0, filter: 'blur(14px)' },
    clip: { clipPath: 'inset(100% 0% 0% 0%)' },
    line: { scaleX: 0, transformOrigin: 'center' },
};

export const vReveal = {
    mounted(el, binding) {
        if (reducedMotion()) return;

        const options = typeof binding.value === 'object' ? binding.value : { type: binding.value };
        const type = options.type ?? 'up';
        const targets = options.stagger ? Array.from(el.children) : el;

        el._reveal = gsap.from(targets, {
            ...PRESETS[type],
            duration: options.duration ?? 1.25,
            delay: options.delay ?? 0,
            ease: options.ease ?? 'power3.out',
            stagger: options.stagger ?? 0,
            clearProps: 'filter,clipPath',
            scrollTrigger: { trigger: el, start: options.start ?? 'top 86%', once: true },
        });
    },
    unmounted(el) {
        el._reveal?.scrollTrigger?.kill();
        el._reveal?.kill();
    },
};

/* ---------- Line-art that draws itself (SVG strokes) ---------- */

export function drawOnScroll(svg, { duration = 2.2, stagger = 0.12, start = 'top 80%' } = {}) {
    if (!svg || reducedMotion()) return null;

    const paths = svg.querySelectorAll('path, circle, rect, line, polyline, ellipse');
    paths.forEach((path) => {
        const length = path.getTotalLength?.() ?? 400;
        path.style.strokeDasharray = `${length}`;
        path.style.strokeDashoffset = `${length}`;
    });

    return gsap.to(paths, {
        strokeDashoffset: 0,
        duration,
        stagger,
        ease: 'power2.inOut',
        scrollTrigger: { trigger: svg, start, once: true },
    });
}
