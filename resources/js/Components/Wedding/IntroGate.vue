<script setup>
import { computed, inject, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import LineIcon from '@/Components/LineIcon.vue';
import BohoCorner from '@/Components/Wedding/BohoCorner.vue';
import OpeningWreath from '@/Components/Wedding/OpeningWreath.vue';
import { gsap, reducedMotion } from '@/lib/motion';

const props = defineProps({
    settings: { type: Object, required: true },
    wedding: { type: Object, required: true },
    venue: { type: String, default: null }, // the ceremony venue
    guestName: { type: String, default: null },
});

// The garden theme decorates all four corners, like a printed floral invitation.
const theme = inject('decorTheme', computed(() => 'boho'));

// The heading on every invitation (Site Settings > Invitation Wording) wins over this screen's own.
const heading = computed(() => (props.settings.invite_heading ?? '').trim() || (props.settings.cover_kicker ?? '').trim());

const emit = defineEmits(['open', 'reveal', 'done']);

const root = ref();
const scene = ref();
const flora = ref();
const content = ref();
const film = ref();
const wreath = ref();
const skipButton = ref();
const opening = ref(false);

let ctx;
let filmTimeline;

onMounted(() => {
    if (reducedMotion()) return;

    ctx = gsap.context(() => {
        gsap.timeline({ defaults: { ease: 'power3.out' } })
            .from('.ig-flora', { opacity: 0, scale: 0.88, duration: 2.2, stagger: 0.25, ease: 'power2.out' }, 0)
            .from('.ig-reveal', { y: 24, opacity: 0, filter: 'blur(8px)', duration: 1.3, stagger: 0.12, clearProps: 'filter' }, 0.35);
    }, root.value);
});

onBeforeUnmount(() => {
    ctx?.revert();
    filmTimeline?.kill();
});

async function open() {
    if (opening.value) return;
    opening.value = true;

    // Emitted synchronously inside the click so the browser lets the music start.
    emit('open');

    if (reducedMotion()) {
        emit('reveal');
        emit('done');
        return;
    }

    // The motion graphic (OpeningWreath) is only rendered now, so the first visit stays light.
    await nextTick();

    const tl = gsap.timeline({ onComplete: () => emit('done') });
    filmTimeline = tl;

    // The invitation's words and corner flowers clear, leaving the cream paper; the wreath plays on it,
    // then flies toward the guest as the paper fades and the website appears behind.
    tl.to(content.value, { opacity: 0, y: -24, filter: 'blur(10px)', duration: 0.6, ease: 'power2.in' })
        .to(flora.value, { opacity: 0, scale: 1.04, duration: 0.9, ease: 'power2.inOut' }, 0)
        .set(film.value, { autoAlpha: 1 }, 0.5)
        .add(wreath.value.timeline(), 0.55)
        .addLabel('exit', '+=0.5')
        .call(() => {
            tl.timeScale(1);
            emit('reveal');
        }, null, 'exit')
        .set(root.value, { pointerEvents: 'none' }, 'exit')
        .to(skipButton.value, { autoAlpha: 0, duration: 0.3 }, 'exit')
        .to(wreath.value.$el, { scale: 2.6, opacity: 0, filter: 'blur(4px)', duration: 1.3, ease: 'power3.in' }, 'exit')
        .to(scene.value, { opacity: 0, duration: 1.1, ease: 'power2.inOut' }, 'exit+=0.2');
}

// Skip fast-forwards to the way out rather than cutting, so the website still arrives the same way.
function skip() {
    filmTimeline?.timeScale(8);
}
</script>

<template>
    <div ref="root" class="fixed inset-0 z-[100] overflow-hidden text-ink" role="dialog" aria-label="Invitation">
        <!-- A cream boho invitation: painted florals in two corners, watercolour washes in the other two -->
        <div ref="scene" class="boho-paper absolute inset-0">
            <div class="linen absolute inset-0 opacity-60" aria-hidden="true"></div>
            <div ref="flora" class="absolute inset-0">
                <BohoCorner corner="top-left" :behind="false" class="ig-flora size-[25vh]! opacity-100! sm:size-[36vh]! lg:size-[50vh]!" />
                <BohoCorner corner="bottom-right" :behind="false" class="ig-flora size-[25vh]! opacity-100! sm:size-[36vh]! lg:size-[50vh]!" />
                <template v-if="theme === 'garden'">
                    <BohoCorner corner="top-right" :behind="false" class="ig-flora size-[22vh]! opacity-100! sm:size-[32vh]! lg:size-[44vh]!" />
                    <BohoCorner corner="bottom-left" :behind="false" class="ig-flora size-[22vh]! opacity-100! sm:size-[32vh]! lg:size-[44vh]!" />
                </template>
            </div>

            <div ref="content" class="relative z-10 h-full overflow-y-auto">
                <div class="flex min-h-full flex-col items-center justify-center px-6 py-10 text-center">
                    <div v-if="guestName" class="ig-reveal mb-6">
                        <p class="kicker text-ink-soft">Specially prepared for</p>
                        <p class="mt-1 font-serif text-2xl italic text-ink md:text-3xl">{{ guestName }}</p>
                    </div>

                    <p v-if="heading" class="ig-reveal max-w-xs whitespace-pre-line font-sans text-[0.7rem] uppercase leading-[2] tracking-[0.32em] text-ink-soft sm:max-w-md md:text-xs">{{ heading }}</p>

                    <h1 class="mt-2 font-script font-normal leading-[1.05] text-caramel">
                        <span class="ig-reveal block text-[clamp(3.6rem,14vw,7rem)] [@media(max-height:700px)]:text-[3.4rem]">{{ settings.bride_name }}</span>
                        <span class="ig-reveal my-1 flex items-center justify-center gap-4 text-[clamp(1.7rem,5vw,2.5rem)] text-cocoa">
                            <span class="h-px w-12 bg-current opacity-50 md:w-20"></span>and<span class="h-px w-12 bg-current opacity-50 md:w-20"></span>
                        </span>
                        <span class="ig-reveal block text-[clamp(3.6rem,14vw,7rem)] [@media(max-height:700px)]:text-[3.4rem]">{{ settings.groom_name }}</span>
                    </h1>

                    <p v-if="settings.invite_line" class="ig-reveal mt-5 max-w-sm font-sans text-[0.7rem] uppercase leading-[2] tracking-[0.3em] text-ink-soft md:max-w-lg md:text-xs">{{ settings.invite_line }}</p>

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

        <!-- The opening motion graphic: a gold ring, the wreath, the rings and initials (OpeningWreath.vue) -->
        <div v-if="opening" ref="film" class="invisible absolute inset-0 z-20 grid place-items-center overflow-hidden">
            <OpeningWreath ref="wreath" :names="[settings.bride_name, settings.groom_name]" :date="wedding.long" />
            <button ref="skipButton" type="button" class="absolute bottom-5 right-5 px-3 py-2 kicker text-ink-soft/70 transition-colors hover:text-rust" @click="skip">
                Skip
            </button>
        </div>
    </div>
</template>
