<script setup>
import { computed, ref } from 'vue';
import LineIcon from '@/Components/LineIcon.vue';
import BohoCorner from './BohoCorner.vue';
import PampasWreath from './PampasWreath.vue';
import SectionTitle from './SectionTitle.vue';
import { vReveal } from '@/lib/motion';

const props = defineProps({
    settings: { type: Object, required: true },
    wedding: { type: Object, required: true },
    events: { type: Array, default: () => [] }, // ceremony first, then the reception
});

// A wax seal's uneven rim: points around a circle, joined smoothly.
const SEAL = (() => {
    const rim = [31, 29.6, 32.1, 30.2, 31.4, 29.1, 32.4, 30.5, 31.1, 29.8, 32.2, 30.1, 31.7, 29.3, 32, 30.7, 31.2, 29.5];
    const points = rim.map((r, i) => {
        const angle = (i / rim.length) * Math.PI * 2;
        return [40 + r * Math.cos(angle), 40 + r * Math.sin(angle)];
    });
    const mid = (a, b) => [(a[0] + b[0]) / 2, (a[1] + b[1]) / 2];
    const f = ([x, y]) => `${x.toFixed(1)},${y.toFixed(1)}`;
    let d = `M${f(mid(points.at(-1), points[0]))}`;
    points.forEach((point, i) => (d += ` Q${f(point)} ${f(mid(point, points[(i + 1) % points.length]))}`));
    return `${d} Z`;
})();

const text = (key, fallback) => (props.settings[key] ?? '').trim() || fallback;
const initials = computed(() => [props.settings.bride_name, props.settings.groom_name].map((name) => (name ?? '').trim().charAt(0).toUpperCase()));
const ceremony = computed(() => props.events[0] ?? null);
const reception = computed(() => props.events[1] ?? null);

const playing = ref(false);
const comingSoon = ref(false);

const video = computed(() => {
    const url = (props.settings.std_video_url ?? '').trim();
    if (!url) return null;

    const youtube = url.match(/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([\w-]{11})/);
    if (youtube) return { type: 'youtube', src: `https://www.youtube-nocookie.com/embed/${youtube[1]}?autoplay=1&rel=0` };

    return { type: 'file', src: url };
});

function play() {
    if (video.value) {
        playing.value = true;
    } else {
        comingSoon.value = true;
        setTimeout(() => (comingSoon.value = false), 3200);
    }
}
</script>

<template>
    <section class="relative overflow-hidden bg-linen linen py-24 md:py-32">
        <div class="mx-auto grid max-w-6xl items-center gap-14 px-5 md:px-8 lg:grid-cols-2 lg:gap-16">
            <!-- Save-the-date card: cream paper with painted corner florals, like a printed boho invitation -->
            <div v-reveal="{ type: 'left', duration: 1.4 }" class="relative mx-auto w-full max-w-md">
                <!-- a second sheet peeking out behind, as in a stationery suite -->
                <div class="absolute inset-0 translate-x-2 rotate-2 bg-sand shadow-[0_30px_60px_-30px_rgb(74_50_38/0.5)]" aria-hidden="true"></div>

                <article class="boho-paper relative isolate overflow-hidden px-7 pb-20 pt-12 text-center shadow-[0_40px_80px_-30px_rgb(74_50_38/0.6)] sm:px-11">
                    <div class="linen absolute inset-0 -z-10 opacity-60" aria-hidden="true"></div>
                    <BohoCorner corner="top-left" class="size-36! opacity-100! sm:size-48!" />
                    <BohoCorner corner="bottom-right" class="size-36! opacity-100! sm:size-48!" />

                    <PampasWreath class="mx-auto size-40 sm:size-44">
                        <span class="flex items-baseline font-serif text-[2.5rem] font-medium leading-none text-rust sm:text-[2.8rem]" aria-hidden="true">
                            {{ initials[0] }}<span class="mx-1 font-script text-3xl text-caramel">&amp;</span>{{ initials[1] }}
                        </span>
                    </PampasWreath>

                    <p class="mt-3 font-serif text-[0.95rem] font-semibold uppercase tracking-[0.34em] text-rust">{{ text('std_heading', 'Save the Date') }}</p>
                    <p class="mt-6 whitespace-pre-line font-sans text-[0.68rem] uppercase leading-[2] tracking-[0.3em] text-ink-soft">{{ text('std_intro', 'Together with our families') }}</p>

                    <h3 class="mt-2 font-script font-normal leading-[1.05] text-caramel">
                        <span class="block text-[3.6rem] sm:text-7xl">{{ settings.bride_name }}</span>
                        <span class="my-1 flex items-center justify-center gap-3 text-[1.7rem] text-cocoa sm:text-3xl">
                            <span class="h-px w-10 bg-current opacity-50 sm:w-14"></span>and<span class="h-px w-10 bg-current opacity-50 sm:w-14"></span>
                        </span>
                        <span class="block text-[3.6rem] sm:text-7xl">{{ settings.groom_name }}</span>
                    </h3>

                    <p class="mx-auto mt-5 max-w-xs font-sans text-[0.68rem] uppercase leading-[2] tracking-[0.3em] text-ink-soft">{{ text('std_invite', 'Invite you to celebrate our wedding') }}</p>

                    <!-- Month and time | day | weekday and year -->
                    <div class="mt-7 grid grid-cols-[1fr_auto_1fr] items-center font-serif uppercase text-cocoa">
                        <div class="pr-3 text-right text-[0.8rem] leading-relaxed tracking-[0.16em] sm:pr-4 sm:text-[0.95rem] sm:tracking-[0.22em]">
                            <p class="font-semibold">{{ wedding.month }}</p>
                            <p>{{ wedding.time }}</p>
                        </div>
                        <p class="border-x border-cocoa/50 px-3 text-[3.4rem] font-light leading-none tabular-nums sm:px-5 sm:text-7xl">{{ wedding.day }}</p>
                        <div class="pl-3 text-left text-[0.8rem] leading-relaxed tracking-[0.16em] sm:pl-4 sm:text-[0.95rem] sm:tracking-[0.22em]">
                            <p class="font-semibold">{{ wedding.weekday }}</p>
                            <p>{{ wedding.year }}</p>
                        </div>
                    </div>

                    <template v-if="ceremony">
                        <p class="mt-8 font-sans text-[0.68rem] uppercase tracking-[0.3em] text-ink-soft">{{ text('std_ceremony_line', 'Ceremony will be held at') }}</p>
                        <p class="mt-1.5 font-serif text-xl font-semibold uppercase tracking-[0.12em] text-cocoa sm:text-[1.4rem]">{{ ceremony.venue }}</p>
                        <p v-if="ceremony.address" class="mt-0.5 font-serif text-base italic text-ink-soft">{{ ceremony.address }}</p>
                    </template>
                    <p v-else class="mt-8 font-serif text-xl font-semibold uppercase tracking-[0.12em] text-cocoa">{{ settings.city_line }}</p>

                    <template v-if="reception">
                        <p class="mt-6 font-script text-[2rem] leading-tight text-caramel">{{ text('std_reception_line', 'Reception follows at') }}</p>
                        <p class="font-serif text-xl font-semibold uppercase tracking-[0.12em] text-cocoa sm:text-[1.4rem]">{{ reception.venue }}</p>
                        <p v-if="reception.address" class="mt-0.5 font-serif text-base italic text-ink-soft">{{ reception.address }}</p>
                        <p class="mt-2 font-serif text-sm font-semibold tracking-[0.25em] text-cocoa">{{ reception.time }}</p>
                    </template>
                </article>

                <!-- Wax seal on the edge of the card -->
                <svg viewBox="0 0 80 80" class="absolute -left-3 top-[61%] size-[4.5rem] -rotate-12 drop-shadow-[0_6px_6px_rgb(58_34_18/0.4)] sm:-left-7 sm:size-20" aria-hidden="true">
                    <defs>
                        <radialGradient id="std-wax" cx="38%" cy="32%" r="75%">
                            <stop offset="0" stop-color="#b98652" />
                            <stop offset="0.45" stop-color="#8a5a32" />
                            <stop offset="1" stop-color="#5a3519" />
                        </radialGradient>
                    </defs>
                    <path :d="SEAL" fill="url(#std-wax)" />
                    <circle cx="40" cy="40" r="22.5" fill="none" stroke="#4a2a12" stroke-opacity="0.55" stroke-width="2" />
                    <circle cx="40" cy="40" r="22.5" fill="none" stroke="#d9ad78" stroke-opacity="0.35" stroke-width="0.8" transform="translate(-0.6 -0.8)" />
                    <text x="40" y="47.5" text-anchor="middle" font-family="Cormorant Garamond, Georgia, serif" font-size="20" font-weight="600" fill="#4a2a12" fill-opacity="0.75">{{ initials.join('') }}</text>
                    <text x="39.4" y="46.8" text-anchor="middle" font-family="Cormorant Garamond, Georgia, serif" font-size="20" font-weight="600" fill="#e2b886" fill-opacity="0.35">{{ initials.join('') }}</text>
                </svg>
            </div>

            <!-- Save-the-date film -->
            <div v-reveal="{ type: 'right', duration: 1.4 }">
                <SectionTitle kicker="Save-the-date · Now showing" title="Our Little Film" class="lg:text-left lg:[&_div]:justify-start" />

                <div class="film-strip mt-10 px-3 shadow-[0_30px_60px_-30px_rgb(43_29_21/0.6)]">
                    <div class="relative aspect-video overflow-hidden bg-black">
                        <template v-if="playing && video">
                            <iframe
                                v-if="video.type === 'youtube'"
                                :src="video.src"
                                class="absolute inset-0 h-full w-full"
                                allow="autoplay; encrypted-media; picture-in-picture"
                                allowfullscreen
                                title="Save-the-date film"
                            ></iframe>
                            <video v-else :src="video.src" class="absolute inset-0 h-full w-full" controls autoplay playsinline></video>
                        </template>

                        <button v-else type="button" class="group absolute inset-0 grid place-items-center" aria-label="Play our save-the-date film" @click="play">
                            <img :src="settings.std_image" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover opacity-80 transition duration-[1.5s] group-hover:scale-105 group-hover:opacity-100" />
                            <span class="absolute inset-0 bg-film/30"></span>
                            <span class="relative grid size-20 place-items-center rounded-full border border-paper/80 text-paper backdrop-blur-sm transition group-hover:scale-110 group-hover:bg-paper/15 md:size-24">
                                <span class="absolute inset-0 rounded-full border border-paper/60 [animation:pulse-ring_2.2s_ease-out_infinite]"></span>
                                <LineIcon name="play" :size="28" class="translate-x-0.5" />
                            </span>
                            <Transition name="fade">
                                <span v-if="comingSoon" class="absolute inset-x-6 bottom-6 bg-film/80 px-4 py-3 text-center font-serif text-lg italic text-paper backdrop-blur">
                                    Our film premieres soon — stay tuned ✦
                                </span>
                            </Transition>
                        </button>
                    </div>
                </div>
                <p class="mt-4 text-center font-mono text-[10px] tracking-[0.3em] text-ink-soft lg:text-left">▸ PRESS PLAY · A FILM BY THE TWO OF US</p>
            </div>
        </div>
    </section>
</template>
