<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import LineIcon from '@/Components/LineIcon.vue';
import { gsap, reducedMotion, vReveal } from '@/lib/motion';

const props = defineProps({
    settings: { type: Object, required: true },
    wedding: { type: Object, required: true },
    calendarUrl: { type: String, default: null },
});

const root = ref();
const now = ref(Date.now());
const target = computed(() => new Date(props.wedding.iso).getTime());
const remaining = computed(() => Math.max(0, target.value - now.value));
const isDone = computed(() => remaining.value === 0);

const units = computed(() => {
    const s = Math.floor(remaining.value / 1000);
    return [
        { label: 'Days', value: String(Math.floor(s / 86400)).padStart(2, '0') },
        { label: 'Hours', value: String(Math.floor((s % 86400) / 3600)).padStart(2, '0') },
        { label: 'Minutes', value: String(Math.floor((s % 3600) / 60)).padStart(2, '0') },
        { label: 'Seconds', value: String(s % 60).padStart(2, '0') },
    ];
});

let timer;
let ctx;

onMounted(() => {
    timer = setInterval(() => (now.value = Date.now()), 1000);

    if (reducedMotion()) return;
    ctx = gsap.context(() => {
        gsap.fromTo(
            '.cd-bg',
            { yPercent: -12 },
            { yPercent: 12, ease: 'none', scrollTrigger: { trigger: root.value, start: 'top bottom', end: 'bottom top', scrub: true } },
        );
        gsap.from('.cd-strip', {
            xPercent: (i) => (i ? 100 : -100),
            duration: 1.6,
            ease: 'power3.out',
            scrollTrigger: { trigger: root.value, start: 'top 75%', once: true },
        });
    }, root.value);
});

onBeforeUnmount(() => {
    clearInterval(timer);
    ctx?.revert();
});
</script>

<template>
    <section ref="root" class="relative overflow-hidden bg-terracotta py-28 md:py-40">
        <img :src="settings.countdown_image" alt="" loading="lazy" class="cd-bg absolute inset-0 h-[125%] w-full -translate-y-[10%] object-cover opacity-45 mix-blend-luminosity" />
        <div class="absolute inset-0 bg-gradient-to-b from-rust/45 via-transparent to-rust/65"></div>

        <div class="cd-strip film-strip absolute inset-x-0 top-0 h-[26px] p-0! opacity-90"></div>
        <div class="cd-strip film-strip absolute inset-x-0 bottom-0 h-[26px] p-0! opacity-90"></div>

        <div class="relative mx-auto max-w-3xl px-5">
            <div
                v-reveal="{ type: 'zoom', duration: 1.4 }"
                class="border border-white/60 bg-paper/80 px-6 py-12 text-center shadow-[0_40px_80px_-30px_rgb(0_0_0/0.6)] backdrop-blur-md md:px-14 md:py-16"
            >
                <p class="font-script text-3xl text-sage md:text-4xl">{{ settings.countdown_heading }}</p>
                <h2 class="mt-3 font-serif text-4xl font-light uppercase tracking-[0.18em] text-rust md:text-6xl">
                    {{ settings.bride_name }} <span class="font-script normal-case tracking-normal text-gold">&amp;</span> {{ settings.groom_name }}
                </h2>

                <div v-if="!isDone" class="mt-10 flex items-start justify-center gap-2 md:gap-5" aria-live="off">
                    <template v-for="(unit, u) in units" :key="unit.label">
                        <div class="w-16 md:w-24">
                            <div class="flex justify-center font-serif text-5xl font-light tabular-nums text-ink [perspective:400px] md:text-7xl">
                                <span v-for="(digit, d) in unit.value.split('')" :key="d" class="relative inline-block w-[0.6em] overflow-hidden">
                                    <Transition name="flip" mode="out-in">
                                        <span :key="digit" class="inline-block">{{ digit }}</span>
                                    </Transition>
                                </span>
                            </div>
                            <p class="mt-2 kicker text-[9px] text-ink-soft md:text-[10px]">{{ unit.label }}</p>
                        </div>
                        <span v-if="u < units.length - 1" class="pt-2 font-serif text-4xl font-light text-gold md:pt-3 md:text-6xl">:</span>
                    </template>
                </div>
                <p v-else class="mt-10 font-script text-5xl text-rust">We're married!</p>

                <p class="mt-10 font-serif text-xl italic text-ink-soft">{{ wedding.weekday }}, {{ wedding.long }} · {{ wedding.time }}</p>
                <a v-if="calendarUrl" :href="calendarUrl" class="btn mt-8 text-ink">
                    <LineIcon name="calendar" :size="16" /> Add to calendar
                </a>
            </div>
        </div>
    </section>
</template>
