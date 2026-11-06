<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import LineIcon from '@/Components/LineIcon.vue';
import BohoCorner from '@/Components/Wedding/BohoCorner.vue';
import { gsap, reducedMotion } from '@/lib/motion';

defineProps({
    settings: { type: Object, required: true },
    wedding: { type: Object, required: true },
    venue: { type: String, default: null }, // the ceremony venue
    guestName: { type: String, default: null },
});

const emit = defineEmits(['open', 'reveal', 'done']);

const root = ref();
const scene = ref();
const content = ref();
const leader = ref();
const sweep = ref();
const flash = ref();
const count = ref(3);
const opening = ref(false);

let ctx;

onMounted(() => {
    if (reducedMotion()) return;

    ctx = gsap.context(() => {
        gsap.timeline({ defaults: { ease: 'power3.out' } })
            .from('.ig-flora', { opacity: 0, scale: 0.88, duration: 2.2, stagger: 0.25, ease: 'power2.out' }, 0)
            .from('.ig-reveal', { y: 24, opacity: 0, filter: 'blur(8px)', duration: 1.3, stagger: 0.12, clearProps: 'filter' }, 0.35);
    }, root.value);
});

onBeforeUnmount(() => ctx?.revert());

function open() {
    if (opening.value) return;
    opening.value = true;

    // Emitted synchronously inside the click so the browser lets the music start.
    emit('open');

    if (reducedMotion()) {
        emit('reveal');
        emit('done');
        return;
    }

    const tl = gsap.timeline({ onComplete: () => emit('done') });

    tl.to(content.value, { opacity: 0, y: -24, filter: 'blur(10px)', duration: 0.6, ease: 'power2.in' })
        .set(leader.value, { display: 'grid' })
        .fromTo(leader.value, { opacity: 0 }, { opacity: 1, duration: 0.3 }, '-=0.1');

    [3, 2, 1].forEach((n) => {
        tl.call(() => (count.value = n)).fromTo(
            sweep.value,
            { '--sweep': '0deg' },
            { '--sweep': '360deg', duration: 0.72, ease: 'none' },
        );
    });

    tl.to(flash.value, { opacity: 1, duration: 0.14, ease: 'power1.in' })
        .set([scene.value, leader.value], { opacity: 0 })
        .call(() => emit('reveal'))
        .to(flash.value, { opacity: 0, duration: 1.1, ease: 'power2.out' })
        .set(root.value, { pointerEvents: 'none' }, '<');
}
</script>

<template>
    <div ref="root" class="fixed inset-0 z-[100] overflow-hidden text-ink" role="dialog" aria-label="Invitation">
        <!-- A cream boho invitation: painted florals in two corners, watercolour washes in the other two -->
        <div ref="scene" class="boho-paper absolute inset-0">
            <div class="linen absolute inset-0 opacity-60" aria-hidden="true"></div>
            <BohoCorner corner="top-left" :behind="false" class="ig-flora size-[25vh]! opacity-100! sm:size-[36vh]! lg:size-[50vh]!" />
            <BohoCorner corner="bottom-right" :behind="false" class="ig-flora size-[25vh]! opacity-100! sm:size-[36vh]! lg:size-[50vh]!" />

            <div ref="content" class="relative z-10 h-full overflow-y-auto">
                <div class="flex min-h-full flex-col items-center justify-center px-6 py-10 text-center">
                    <div v-if="guestName" class="ig-reveal mb-6">
                        <p class="kicker text-ink-soft">Specially prepared for</p>
                        <p class="mt-1 font-serif text-2xl italic text-ink md:text-3xl">{{ guestName }}</p>
                    </div>

                    <p class="ig-reveal max-w-xs font-sans text-[0.7rem] uppercase leading-[2] tracking-[0.32em] text-ink-soft sm:max-w-md md:text-xs">{{ settings.cover_kicker }}</p>

                    <h1 class="mt-2 font-script font-normal leading-[1.05] text-caramel">
                        <span class="ig-reveal block text-[clamp(3.6rem,14vw,7rem)] [@media(max-height:700px)]:text-[3.4rem]">{{ settings.bride_name }}</span>
                        <span class="ig-reveal my-1 flex items-center justify-center gap-4 text-[clamp(1.7rem,5vw,2.5rem)] text-cocoa">
                            <span class="h-px w-12 bg-current opacity-50 md:w-20"></span>and<span class="h-px w-12 bg-current opacity-50 md:w-20"></span>
                        </span>
                        <span class="ig-reveal block text-[clamp(3.6rem,14vw,7rem)] [@media(max-height:700px)]:text-[3.4rem]">{{ settings.groom_name }}</span>
                    </h1>

                    <p class="ig-reveal mt-5 max-w-sm font-sans text-[0.7rem] uppercase leading-[2] tracking-[0.3em] text-ink-soft md:max-w-lg md:text-xs">{{ settings.invite_line }}</p>

                    <!-- Month and time | day | weekday and year -->
                    <div class="ig-reveal mt-6 grid grid-cols-[1fr_auto_1fr] items-center font-serif uppercase text-cocoa md:mt-8">
                        <div class="pr-3 text-right text-[0.8rem] leading-relaxed tracking-[0.16em] sm:pr-5 sm:text-base sm:tracking-[0.22em]">
                            <p class="font-semibold">{{ wedding.month }}</p>
                            <p>{{ wedding.time }}</p>
                        </div>
                        <p class="border-x border-cocoa/50 px-3 text-[3.4rem] font-light leading-none tabular-nums sm:px-6 sm:text-7xl">{{ wedding.day }}</p>
                        <div class="pl-3 text-left text-[0.8rem] leading-relaxed tracking-[0.16em] sm:pl-5 sm:text-base sm:tracking-[0.22em]">
                            <p class="font-semibold">{{ wedding.weekday }}</p>
                            <p>{{ wedding.year }}</p>
                        </div>
                    </div>

                    <div class="ig-reveal mt-6 md:mt-8 [@media(max-height:640px)]:hidden">
                        <p v-if="venue" class="font-serif text-lg font-semibold uppercase tracking-[0.14em] text-cocoa md:text-xl">{{ venue }}</p>
                        <p class="font-serif text-base italic text-ink-soft md:text-lg">{{ settings.city_line }}</p>
                    </div>

                    <p v-if="settings.hashtag" class="ig-reveal mt-4 font-serif text-base font-semibold tracking-[0.1em] text-rust md:mt-5 md:text-lg [@media(max-height:600px)]:hidden">
                        {{ settings.hashtag }}
                    </p>

                    <!-- The entrance animation moves this wrapper: the button's own transform transition would leave it stuck mid-way. -->
                    <div class="ig-reveal mt-8 md:mt-10">
                        <button
                            type="button"
                            class="btn group border-cocoa/60 text-cocoa hover:border-rust hover:text-rust"
                            :disabled="opening"
                            @click="open"
                        >
                            <LineIcon name="envelope" :size="18" class="transition-transform duration-500 group-hover:-rotate-6" />
                            Open Invitation
                        </button>
                    </div>
                    <p class="ig-reveal mt-4 text-[10px] uppercase tracking-[0.3em] text-ink-soft/70">Best experienced with sound on</p>
                </div>
            </div>
        </div>

        <!-- Projector countdown leader -->
        <div ref="leader" class="absolute inset-0 hidden place-items-center overflow-hidden bg-[#d2bc9c] text-ink">
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,transparent_30%,rgb(43_29_21/0.75)_100%)]"></div>
            <div
                ref="sweep"
                class="absolute aspect-square w-[min(78vw,78vh)] rounded-full [--sweep:0deg]"
                style="background: conic-gradient(rgb(58 42 32 / 0.5) var(--sweep), transparent var(--sweep))"
            ></div>
            <svg class="absolute aspect-square w-[min(78vw,78vh)]" viewBox="0 0 400 400" aria-hidden="true">
                <g fill="none" stroke="#3a2a20" stroke-opacity="0.85">
                    <circle cx="200" cy="200" r="196" stroke-width="2.5" />
                    <circle cx="200" cy="200" r="168" stroke-width="1.5" />
                </g>
            </svg>
            <div class="absolute inset-x-0 top-1/2 h-0.5 bg-ink/70"></div>
            <div class="absolute inset-y-0 left-1/2 w-0.5 bg-ink/70"></div>
            <span class="relative font-serif text-[min(38vw,38vh)] font-light leading-none">{{ count }}</span>

            <!-- Scratches and flicker -->
            <span class="absolute inset-y-0 left-[18%] w-px animate-[grain_0.4s_steps(2)_infinite] bg-ink/30"></span>
            <span class="absolute inset-y-0 left-[71%] w-px animate-[grain_0.6s_steps(3)_infinite] bg-paper/60"></span>
            <span class="absolute inset-0 animate-[rec-blink_0.18s_steps(1)_infinite] bg-paper/10"></span>
            <p class="absolute bottom-8 kicker text-ink/60">A Love Story · Reel One</p>
        </div>

        <div ref="flash" class="pointer-events-none absolute inset-0 bg-cream opacity-0"></div>
    </div>
</template>
