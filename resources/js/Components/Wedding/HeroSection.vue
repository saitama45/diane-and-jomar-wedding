<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { gsap, reducedMotion, scrollToTarget, SplitText } from '@/lib/motion';

const props = defineProps({
    settings: { type: Object, required: true },
    wedding: { type: Object, required: true },
    active: { type: Boolean, default: false },
});

const root = ref();
const names = ref();
const timecode = ref('00:00:00:00');

let ctx;
let frame;
let started = 0;

function runTimecode(now) {
    if (!started) started = now;
    const elapsed = (now - started) / 1000;
    const pad = (n) => String(Math.floor(n)).padStart(2, '0');
    timecode.value = `${pad(elapsed / 3600)}:${pad((elapsed / 60) % 60)}:${pad(elapsed % 60)}:${pad((elapsed * 24) % 24)}`;
    frame = requestAnimationFrame(runTimecode);
}

function play() {
    if (reducedMotion()) return;

    ctx = gsap.context(() => {
        const split = new SplitText(names.value.querySelectorAll('.hero-name'), { type: 'chars' });

        gsap.timeline({ defaults: { ease: 'power3.out' } })
            .fromTo('.hero-img', { scale: 1.35 }, { scale: 1.08, duration: 3, ease: 'power2.out' }, 0)
            .from('.vf-corner', { opacity: 0, scale: 1.5, duration: 1.1, stagger: 0.07 }, 0.2)
            .from('.vf-meta', { opacity: 0, y: 8, duration: 0.8, stagger: 0.08 }, 0.6)
            .from('.hero-kicker', { opacity: 0, letterSpacing: '0.9em', duration: 1.6 }, 0.4)
            .from(split.chars, { opacity: 0, y: 40, rotate: -8, filter: 'blur(12px)', duration: 1.2, stagger: 0.05, clearProps: 'filter' }, 0.7)
            .from('.hero-amp', { opacity: 0, scale: 0.2, rotate: -40, duration: 1.4, ease: 'back.out(1.6)' }, 1.1)
            .from('.hero-line', { scaleX: 0, duration: 1.2, stagger: 0.1, ease: 'power2.inOut' }, 1.5)
            .from('.hero-meta', { opacity: 0, y: 16, duration: 1, stagger: 0.12 }, 1.7)
            .from('.hero-cue', { opacity: 0, duration: 1 }, 2.4);

        // A slow, endless Ken Burns drift once the entrance settles.
        gsap.to('.hero-img', { scale: 1.16, xPercent: -2, duration: 22, ease: 'sine.inOut', yoyo: true, repeat: -1, delay: 3 });

        // Parallax out on scroll.
        gsap.to('.hero-content', {
            yPercent: -35,
            opacity: 0,
            ease: 'none',
            scrollTrigger: { trigger: root.value, start: 'top top', end: 'bottom top', scrub: true },
        });
        gsap.to('.hero-media', {
            yPercent: 18,
            ease: 'none',
            scrollTrigger: { trigger: root.value, start: 'top top', end: 'bottom top', scrub: true },
        });
    }, root.value);
}

onMounted(() => {
    frame = requestAnimationFrame(runTimecode);
    if (props.active) play();
});

watch(
    () => props.active,
    (active) => active && !ctx && play(),
);

onBeforeUnmount(() => {
    cancelAnimationFrame(frame);
    ctx?.revert();
});
</script>

<template>
    <section id="home" ref="root" class="relative h-[100svh] min-h-[640px] overflow-hidden bg-film text-paper">
        <div class="hero-media absolute inset-0">
            <img :src="settings.hero_image" alt="" class="hero-img h-full w-full object-cover" fetchpriority="high" />
        </div>
        <div class="absolute inset-0 bg-gradient-to-b from-film/55 via-film/20 to-film/85"></div>
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,transparent_40%,rgb(43_29_21/0.7)_100%)]"></div>
        <div class="light-leak -left-[20%] -top-[10%] h-[70vh] w-[60vw] bg-[#ffb071]/45"></div>
        <div class="light-leak -right-[15%] top-[30%] h-[55vh] w-[45vw] bg-[#e8875a]/30 [animation-delay:-5s]"></div>

        <!-- Camera viewfinder -->
        <div class="pointer-events-none absolute inset-x-5 bottom-5 top-20 font-mono text-[10px] tracking-[0.2em] text-paper/80 md:inset-x-10 md:bottom-10 md:top-24 md:text-[11px]" aria-hidden="true">
            <span class="vf-corner absolute left-0 top-0 h-8 w-8 border-l border-t border-paper/70 md:h-12 md:w-12"></span>
            <span class="vf-corner absolute right-0 top-0 h-8 w-8 border-r border-t border-paper/70 md:h-12 md:w-12"></span>
            <span class="vf-corner absolute bottom-0 left-0 h-8 w-8 border-b border-l border-paper/70 md:h-12 md:w-12"></span>
            <span class="vf-corner absolute bottom-0 right-0 h-8 w-8 border-b border-r border-paper/70 md:h-12 md:w-12"></span>

            <span class="vf-meta absolute left-4 top-3 flex items-center gap-2 md:left-6 md:top-5">
                <span class="size-2 rounded-full bg-[#ff3b3b] shadow-[0_0_10px_#ff3b3b] [animation:rec-blink_1.2s_infinite]"></span>REC
            </span>
            <span class="vf-meta absolute right-4 top-3 md:right-6 md:top-5">{{ wedding.month_num }}.{{ wedding.day }}.{{ wedding.year }}</span>
            <span class="vf-meta absolute bottom-3 right-4 tabular-nums md:bottom-5 md:left-6 md:right-auto">{{ timecode }}</span>
            <span class="vf-meta absolute bottom-3 right-4 hidden md:bottom-5 md:right-6 md:block">4K · 24 FPS · ISO 400</span>
            <span class="vf-meta absolute left-1/2 top-1/2 hidden -translate-x-1/2 -translate-y-1/2 text-base font-light text-paper/40 md:block">+</span>
        </div>

        <div class="hero-content relative z-10 flex h-full flex-col items-center justify-center px-6 text-center">
            <p class="hero-kicker kicker text-gold-light">We're getting married</p>

            <h1 ref="names" class="mt-4 font-script text-[clamp(3.8rem,13vw,10rem)] leading-[1.05] drop-shadow-[0_4px_30px_rgb(0_0_0/0.35)]">
                <span class="hero-name inline-block">{{ settings.bride_name }}</span>
                <span class="hero-amp mx-2 inline-block text-gold-light md:mx-4">&amp;</span>
                <span class="hero-name inline-block">{{ settings.groom_name }}</span>
            </h1>

            <div class="mt-6 flex items-center gap-4 md:gap-6">
                <span class="hero-line h-px w-10 origin-right bg-gold-light/70 md:w-20"></span>
                <p class="hero-meta font-serif text-lg tracking-[0.3em] md:text-xl">
                    {{ wedding.weekday }}
                </p>
                <span class="hero-line h-px w-10 origin-left bg-gold-light/70 md:w-20"></span>
            </div>
            <p class="hero-meta mt-3 font-serif text-3xl font-light tracking-[0.12em] md:text-4xl">
                {{ wedding.day }} {{ wedding.month }} {{ wedding.year }}
            </p>
            <p class="hero-meta mt-2 kicker text-paper/75">{{ settings.city_line }}</p>
            <p class="hero-meta mt-8 max-w-lg font-serif text-lg italic text-paper/80 md:text-xl">{{ settings.hero_tagline }}</p>
        </div>

        <button
            type="button"
            class="hero-cue absolute bottom-10 left-1/2 z-10 flex -translate-x-1/2 flex-col items-center gap-3 text-paper/70 transition hover:text-paper md:bottom-14"
            @click="scrollToTarget('#story')"
        >
            <span class="kicker text-[9px]">Scroll</span>
            <span class="h-12 w-px bg-current [animation:scroll-cue_2.2s_ease-in-out_infinite]"></span>
        </button>
    </section>
</template>
