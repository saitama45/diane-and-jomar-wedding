<script setup>
import BohoCorner from '@/Components/Wedding/BohoCorner.vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import SectionTitle from './SectionTitle.vue';
import { gsap, reducedMotion, vReveal } from '@/lib/motion';

const props = defineProps({
    settings: { type: Object, required: true },
    stories: { type: Array, default: () => [] },
});

const root = ref();
const reel = ref();
const track = ref();

const paragraphs = computed(() =>
    (props.settings.story_letter ?? '')
        .split(/\n\s*\n/)
        .map((p) => p.trim())
        .filter(Boolean),
);

const secondPhoto = computed(() => props.stories[0]?.image ?? props.settings.polaroid_image);

let ctx;

onMounted(() => {
    if (reducedMotion()) return;

    ctx = gsap.context(() => {
        gsap.from('.polaroid-drop', {
            y: -140,
            rotate: (i) => (i ? 28 : -24),
            opacity: 0,
            duration: 1.6,
            stagger: 0.25,
            ease: 'back.out(1.3)',
            scrollTrigger: { trigger: '.polaroid-stack', start: 'top 80%', once: true },
        });
        gsap.to('.polaroid-stack', {
            yPercent: -12,
            ease: 'none',
            scrollTrigger: { trigger: '.polaroid-stack', start: 'top bottom', end: 'bottom top', scrub: true },
        });

        // No chapters (the love story is hidden): there is no reel. Building its pinned scroll anyway threw
        // on wide screens and stopped every reveal animation below, which left the rest of the page blank.
        if (!track.value) return;

        const mm = gsap.matchMedia();

        // Desktop: pin the reel and scrub it sideways like film through a gate.
        mm.add('(min-width: 1024px)', () => {
            const distance = () => track.value.scrollWidth - window.innerWidth;
            const tween = gsap.to(track.value, {
                x: () => -distance(),
                ease: 'none',
                scrollTrigger: {
                    trigger: reel.value,
                    start: 'top top',
                    end: () => `+=${distance()}`,
                    pin: true,
                    scrub: 0.8,
                    invalidateOnRefresh: true,
                },
            });

            gsap.utils.toArray('.reel-frame').forEach((frame) => {
                gsap.from(frame.querySelector('img'), {
                    scale: 1.3,
                    filter: 'grayscale(1) sepia(.4)',
                    ease: 'none',
                    scrollTrigger: { trigger: frame, containerAnimation: tween, start: 'left right', end: 'center center', scrub: true },
                });
            });
        });

        // Mobile: frames simply rise in.
        mm.add('(max-width: 1023px)', () => {
            gsap.utils.toArray('.reel-frame').forEach((frame) => {
                gsap.from(frame, { y: 60, opacity: 0, duration: 1.1, ease: 'power3.out', scrollTrigger: { trigger: frame, start: 'top 88%', once: true } });
            });
        });
    }, root.value);
});

onBeforeUnmount(() => ctx?.revert());
</script>

<template>
    <section id="story" ref="root" class="relative isolate overflow-hidden bg-paper linen">
        <BohoCorner corner="top-left" />
        <BohoCorner corner="top-right" />
        <div class="mx-auto max-w-6xl px-5 pb-16 pt-24 md:px-8 md:pt-32">
            <SectionTitle kicker="Chapter One" title="Our Love Story" />

            <div class="mt-16 grid items-center gap-16 lg:grid-cols-12 lg:gap-10">
                <!-- The letter -->
                <article v-reveal="{ type: 'up', duration: 1.4 }" class="paper-card relative -rotate-[0.6deg] px-7 py-12 md:px-14 md:py-16 lg:col-span-7">
                    <span class="tape -top-3 left-8 -rotate-6"></span>
                    <span class="tape -top-3 right-8 rotate-6"></span>

                    <p class="font-script text-3xl text-cocoa md:text-4xl">Dearest family &amp; friends,</p>
                    <div v-reveal="{ type: 'blur', stagger: 0.25, start: 'top 75%' }" class="mt-6 space-y-5">
                        <p v-for="(paragraph, i) in paragraphs" :key="i" class="font-serif text-lg italic leading-relaxed text-ink-soft md:text-xl">
                            {{ paragraph }}
                        </p>
                    </div>
                    <div v-reveal="{ type: 'fade', delay: 0.4 }" class="mt-10 text-right">
                        <p class="font-serif text-lg italic text-ink-soft">{{ settings.story_signature }}</p>
                        <p class="font-script text-4xl text-rust md:text-5xl">{{ settings.bride_name }} &amp; {{ settings.groom_name }}</p>
                    </div>
                </article>

                <!-- Polaroids -->
                <div class="polaroid-stack relative mx-auto h-[440px] w-full max-w-[380px] lg:col-span-5">
                    <figure class="polaroid-drop polaroid absolute left-0 top-6 w-[72%] rotate-[-7deg]">
                        <img :src="secondPhoto" alt="" loading="lazy" class="aspect-square w-full object-cover sepia-[.2]" />
                        <figcaption class="absolute inset-x-0 bottom-3 text-center font-script text-2xl text-ink-soft">since day one</figcaption>
                    </figure>
                    <figure class="polaroid-drop polaroid absolute right-0 top-24 w-[76%] rotate-[5deg]">
                        <span class="tape -top-3 left-1/2 -translate-x-1/2 rotate-2"></span>
                        <img :src="settings.polaroid_image" alt="" loading="lazy" class="aspect-square w-full object-cover" />
                        <figcaption class="absolute inset-x-0 bottom-3 text-center font-script text-3xl text-ink">
                            {{ settings.polaroid_caption }}
                        </figcaption>
                    </figure>
                </div>
            </div>
        </div>

        <!-- The story reel -->
        <div v-if="stories.length" ref="reel" class="relative bg-film py-16 lg:flex lg:h-screen lg:items-center lg:py-0">
            <div class="pointer-events-none absolute left-5 top-8 z-10 text-paper md:left-10 lg:top-12">
                <p class="kicker text-gold-light">The Reel</p>
                <p class="mt-1 font-script text-4xl md:text-5xl">How it happened</p>
            </div>

            <div ref="track" class="mt-24 flex flex-col gap-10 px-5 lg:mt-0 lg:w-max lg:flex-row lg:items-center lg:gap-0 lg:px-[12vw]">
                <article
                    v-for="(story, i) in stories"
                    :key="story.id"
                    class="reel-frame film-strip film-strip-still relative shrink-0 px-3 lg:w-[min(46vw,620px)] lg:px-3"
                >
                    <div class="grid gap-0 bg-[#22170f] sm:grid-cols-2">
                        <div class="relative aspect-[4/3] overflow-hidden sm:aspect-auto">
                            <img v-if="story.image" :src="story.image" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover" />
                        </div>
                        <div class="p-6 text-paper md:p-8">
                            <p class="font-mono text-[10px] tracking-[0.3em] text-[#e7a64a]">SCENE {{ String(i + 1).padStart(2, '0') }} · {{ story.date_label }}</p>
                            <h3 class="mt-3 font-serif text-3xl font-light leading-tight">{{ story.title }}</h3>
                            <p class="mt-4 font-serif text-lg italic leading-relaxed text-paper/75">{{ story.body }}</p>
                        </div>
                    </div>
                    <span class="absolute -top-0.5 left-6 font-mono text-[8px] tracking-[0.25em] text-[#e7a64a]/70">▸ {{ i * 2 + 12 }}A</span>
                </article>

                <div class="hidden shrink-0 px-[8vw] text-center text-paper lg:block">
                    <p class="font-script text-6xl text-gold-light">to be continued…</p>
                    <p class="mt-3 kicker text-paper/60">at the altar</p>
                </div>
            </div>
        </div>
    </section>
</template>
