<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import LineIcon from '@/Components/LineIcon.vue';
import { gsap, reducedMotion } from '@/lib/motion';

const props = defineProps({
    settings: { type: Object, required: true },
    wedding: { type: Object, required: true },
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

const initials = computed(() =>
    [props.settings.bride_name, props.settings.groom_name].map((name) => (name ?? '').trim().charAt(0)),
);

let ctx;

onMounted(() => {
    if (reducedMotion()) return;

    ctx = gsap.context(() => {
        gsap.timeline({ defaults: { ease: 'power3.out' } })
            .from('.ig-bg', { scale: 1.3, duration: 3.4, ease: 'power2.out' }, 0)
            .from('.ig-ring', { strokeDashoffset: 365, duration: 2.4, ease: 'power2.inOut' }, 0.2)
            .from('.ig-reveal', { y: 28, opacity: 0, filter: 'blur(10px)', duration: 1.3, stagger: 0.13, clearProps: 'filter' }, 0.45)
            .from('.ig-corner', { opacity: 0, scale: 1.4, duration: 1.2, stagger: 0.08 }, 0.3);
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
    <div ref="root" class="fixed inset-0 z-[100] overflow-hidden text-paper" role="dialog" aria-label="Invitation">
        <div ref="scene" class="absolute inset-0 bg-film">
            <img :src="settings.cover_image" alt="" class="ig-bg absolute inset-0 h-full w-full object-cover opacity-55" />
            <div class="absolute inset-0 bg-gradient-to-b from-film/80 via-film/25 to-film/90"></div>
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,transparent_35%,rgb(43_29_21/0.85)_100%)]"></div>
            <div class="light-leak -left-1/4 top-0 h-[60vh] w-[70vw] bg-[#ff9a5a]/60"></div>
            <div class="light-leak -right-1/4 bottom-0 h-[50vh] w-[60vw] bg-[#e0704a]/40 [animation-delay:-7s]"></div>

            <!-- Frame corners -->
            <span class="ig-corner absolute left-5 top-5 h-10 w-10 border-l border-t border-paper/40 md:left-10 md:top-10"></span>
            <span class="ig-corner absolute right-5 top-5 h-10 w-10 border-r border-t border-paper/40 md:right-10 md:top-10"></span>
            <span class="ig-corner absolute bottom-5 left-5 h-10 w-10 border-b border-l border-paper/40 md:bottom-10 md:left-10"></span>
            <span class="ig-corner absolute bottom-5 right-5 h-10 w-10 border-b border-r border-paper/40 md:bottom-10 md:right-10"></span>

            <div ref="content" class="relative z-10 flex h-full flex-col items-center justify-center px-6 text-center">
                <div class="ig-reveal relative mb-8 grid size-28 place-items-center md:size-32">
                    <svg class="absolute inset-0 -rotate-90 text-gold-light" viewBox="0 0 120 120" aria-hidden="true">
                        <circle class="ig-ring" cx="60" cy="60" r="58" fill="none" stroke="currentColor" stroke-width="0.7" stroke-dasharray="365" />
                        <circle cx="60" cy="60" r="52" fill="none" stroke="currentColor" stroke-width="0.4" opacity="0.5" />
                    </svg>
                    <span class="font-serif text-3xl font-light tracking-[0.12em] text-gold-light md:text-4xl">
                        {{ initials[0] }}<span class="mx-1 font-script text-2xl">&amp;</span>{{ initials[1] }}
                    </span>
                </div>

                <p class="ig-reveal kicker text-gold-light">{{ settings.cover_kicker }}</p>
                <h1 class="ig-reveal mt-3 font-script text-[clamp(3.4rem,13vw,7.5rem)] leading-[1.1]">
                    {{ settings.bride_name }} <span class="text-gold-light">&amp;</span> {{ settings.groom_name }}
                </h1>
                <p class="ig-reveal mt-4 font-serif text-lg tracking-[0.4em] text-paper/85">
                    {{ wedding.month_num }} · {{ wedding.day }} · {{ wedding.year }}
                </p>

                <div v-if="guestName" class="ig-reveal mt-10">
                    <p class="kicker text-paper/55">Specially prepared for</p>
                    <p class="mt-2 font-serif text-2xl italic md:text-3xl">{{ guestName }}</p>
                </div>
                <p v-else class="ig-reveal mt-8 max-w-md font-serif text-lg italic leading-relaxed text-paper/75">
                    {{ settings.invite_line }}
                </p>

                <button
                    type="button"
                    class="ig-reveal btn group mt-10 border-paper/50 text-paper hover:border-gold-light hover:text-gold-light"
                    :disabled="opening"
                    @click="open"
                >
                    <LineIcon name="envelope" :size="18" class="transition-transform duration-500 group-hover:-rotate-6" />
                    Open Invitation
                </button>
                <p class="ig-reveal mt-6 text-[10px] uppercase tracking-[0.3em] text-paper/40">Best experienced with sound on</p>
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
