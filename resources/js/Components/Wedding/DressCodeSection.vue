<script setup>
import BohoCorner from '@/Components/Wedding/BohoCorner.vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import LineIcon from '@/Components/LineIcon.vue';
import SectionTitle from './SectionTitle.vue';
import { drawOnScroll, gsap, lockScroll, reducedMotion, vReveal } from '@/lib/motion';

const props = defineProps({
    settings: { type: Object, required: true },
    colors: { type: Array, default: () => [] },
    samples: { type: Array, default: () => [] }, // outfit inspiration pictures
});

const root = ref();
const barong = ref();
const gown = ref();

const AUDIENCES = { guests: 'Our Guests', sponsors: 'Principal Sponsors' };
const WEARERS = { ladies: 'Ladies', gentlemen: 'Gentlemen' }; // "none" (or anything else) shows no label
const wearer = (sample) => WEARERS[sample?.wearer] ?? '';

// Guests first (most visitors), then sponsors; the viewer pages through them in this same order.
const sampleGroups = computed(() =>
    Object.entries(AUDIENCES)
        .map(([key, title]) => ({ key, title, items: props.samples.filter((sample) => sample.audience === key) }))
        .filter((group) => group.items.length),
);
const ordered = computed(() => sampleGroups.value.flatMap((group) => group.items));

// The gift guide: the gift message (Site Settings > Gift Registry) at the end of the dress code.
const giftMessage = computed(() => (props.settings.gift_message ?? '').trim());
const giftTitle = computed(() => (props.settings.gift_guide_title ?? '').trim());
const showGift = computed(() => props.settings.show_gift_guide !== '0' && !!giftMessage.value);

const active = ref(null);
const current = computed(() => (active.value === null ? null : ordered.value[active.value]));

function open(sample) {
    active.value = ordered.value.indexOf(sample);
    lockScroll(true);
}

function close() {
    active.value = null;
    lockScroll(false);
}

function go(delta) {
    active.value = (active.value + delta + ordered.value.length) % ordered.value.length;
}

function onKey(event) {
    if (active.value === null) return;
    if (event.key === 'Escape') close();
    if (event.key === 'ArrowRight') go(1);
    if (event.key === 'ArrowLeft') go(-1);
}

let touchX = null;
const onTouchStart = (event) => (touchX = event.changedTouches[0].clientX);
function onTouchEnd(event) {
    const dx = event.changedTouches[0].clientX - touchX;
    if (Math.abs(dx) > 50) go(dx < 0 ? 1 : -1);
}

let ctx;

onMounted(() => {
    window.addEventListener('keydown', onKey);
    if (reducedMotion()) return;
    ctx = gsap.context(() => {
        drawOnScroll(barong.value, { duration: 2.4, stagger: 0.15 });
        drawOnScroll(gown.value, { duration: 2.4, stagger: 0.15 });

        gsap.from('.swatch', {
            y: 40,
            scale: 0.4,
            opacity: 0,
            duration: 1.1,
            stagger: 0.12,
            ease: 'back.out(2)',
            scrollTrigger: { trigger: '.swatches', start: 'top 85%', once: true },
        });
    }, root.value);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKey);
    ctx?.revert();
    if (active.value !== null) lockScroll(false);
});
</script>

<template>
    <section id="attire" ref="root" class="relative isolate bg-latte/35 py-24 md:py-32">
        <BohoCorner corner="top-left" />
        <BohoCorner corner="top-right" />
        <div class="mx-auto max-w-5xl px-5 md:px-8">
            <SectionTitle kicker="What to wear" title="The Dress Code" />

            <!-- Outfit pictures uploaded in the admin (Attire Samples) -->
            <div v-if="sampleGroups.length" class="mt-12 text-center">
                <p v-reveal="'fade'" class="kicker text-ink-soft">Tap a picture to see it up close</p>

                <div v-for="group in sampleGroups" :key="group.key" class="mt-8">
                    <p v-if="sampleGroups.length > 1" class="kicker text-gold">{{ group.title }}</p>
                    <div class="mt-5 grid gap-6" :class="group.items.length > 1 ? 'md:grid-cols-2' : 'mx-auto max-w-2xl'">
                        <button
                            v-for="sample in group.items"
                            :key="sample.id"
                            v-reveal="{ type: 'up' }"
                            type="button"
                            class="group paper-card block p-3 text-left transition duration-500 ease-[var(--ease-film)] hover:-translate-y-1"
                            :aria-label="`View the ${wearer(sample) ? `${wearer(sample)} ` : ''}outfit picture full size`"
                            @click="open(sample)"
                        >
                            <span class="block overflow-hidden">
                                <img :src="sample.image" :alt="wearer(sample) ? `${wearer(sample)} dress code` : 'Dress code'" loading="lazy" class="w-full object-cover transition duration-700 group-hover:scale-[1.03]" />
                            </span>
                            <span v-if="wearer(sample) || sample.caption" class="mt-3 flex flex-wrap items-baseline justify-between gap-x-4 px-1">
                                <span v-if="wearer(sample)" class="font-script text-3xl text-cocoa">{{ wearer(sample) }}</span>
                                <span v-if="sample.caption" class="font-serif text-base italic text-ink-soft">{{ sample.caption }}</span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Palette -->
            <div v-if="colors.length" class="mt-20 text-center">
                <p v-reveal="'fade'" class="font-script text-4xl text-ink md:text-5xl">Our Palette</p>
                <p v-reveal="'fade'" class="mt-2 kicker text-ink-soft">We'd love to see you in these tones</p>

                <ul class="swatches mt-10 flex flex-wrap justify-center gap-6 md:gap-10">
                    <li v-for="color in colors" :key="color.id" class="swatch group w-20 md:w-24">
                        <span
                            class="relative mx-auto block size-16 rounded-full shadow-[inset_0_-6px_12px_rgb(0_0_0/0.12),0_12px_24px_-10px_rgb(58_42_32/0.45)] ring-1 ring-ink/10 transition duration-500 ease-[var(--ease-film)] group-hover:-translate-y-2 md:size-20"
                            :style="{ backgroundColor: color.hex }"
                        >
                            <span class="absolute inset-0 rounded-full bg-[radial-gradient(circle_at_30%_25%,rgb(255_255_255/0.55),transparent_45%)]"></span>
                        </span>
                        <span class="mt-3 block font-serif text-lg leading-tight text-ink">{{ color.name }}</span>
                        <span class="block font-mono text-[10px] uppercase tracking-widest text-ink-soft opacity-0 transition group-hover:opacity-100">{{ color.hex }}</span>
                    </li>
                </ul>
            </div>

            <!-- The written dress code, under the pictures and the palette -->
            <div class="mt-20 grid gap-8 md:grid-cols-2">
                <!-- Principal sponsors -->
                <article v-reveal="{ type: 'up' }" class="paper-card px-8 py-10 text-center">
                    <svg ref="barong" viewBox="0 0 120 160" class="mx-auto h-40 text-ink" fill="none" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M60 22c-1-7 9-8 9-2 0 4-5 4-9 8" />
                        <path d="M60 28 22 46h76z" />
                        <path d="M34 46 14 122l10 3 14-52v74h44V73l14 52 10-3-20-76" />
                        <path d="M50 46l10 12 10-12" />
                        <path d="M60 58v42" />
                        <path d="M46 74q14 9 28 0M46 84q14 9 28 0M48 94q12 7 24 0" stroke-opacity=".55" />
                        <path d="M38 147h44" />
                    </svg>
                    <p class="mt-6 kicker text-gold">Principal Sponsors</p>
                    <p class="mt-2 font-serif text-3xl text-ink">{{ settings.dress_sponsors_attire }}</p>
                    <dl class="mt-6 space-y-4 font-serif text-lg text-ink-soft">
                        <div>
                            <dt class="font-script text-3xl text-cocoa">Gentlemen</dt>
                            <dd>{{ settings.dress_gentlemen }}</dd>
                        </div>
                        <div>
                            <dt class="font-script text-3xl text-cocoa">Ladies</dt>
                            <dd>{{ settings.dress_ladies }}</dd>
                        </div>
                    </dl>
                </article>

                <!-- Guests -->
                <article v-reveal="{ type: 'up', delay: 0.15 }" class="paper-card px-8 py-10 text-center">
                    <svg ref="gown" viewBox="0 0 120 160" class="mx-auto h-40 text-ink" fill="none" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M60 22c-1-7 9-8 9-2 0 4-5 4-9 8" />
                        <path d="M60 28 22 46h76z" />
                        <path d="M44 46l4 16M76 46l-4 16" />
                        <path d="M48 62q12 8 24 0l-2 24q-10 4-20 0z" />
                        <path d="M50 86q-20 34-32 64 42 9 84 0-12-30-32-64" />
                        <path d="M56 92q-6 28-10 58M64 92q6 28 10 58M60 92v62" stroke-opacity=".55" />
                    </svg>
                    <p class="mt-6 kicker text-gold">Our Guests</p>
                    <p class="mt-2 font-serif text-3xl text-ink">{{ settings.dress_guests_attire }}</p>
                    <p class="mx-auto mt-6 max-w-sm font-serif text-lg italic leading-relaxed text-ink-soft">{{ settings.dress_guests_note }}</p>
                </article>
            </div>

            <!-- Gift guide -->
            <article v-if="showGift" v-reveal="{ type: 'up' }" class="paper-card mt-8 px-8 py-10 text-center">
                <span class="mx-auto grid size-14 place-items-center rounded-full ring-1 ring-gold/40 text-gold">
                    <LineIcon name="gift" :size="26" />
                </span>
                <p v-if="giftTitle" class="mt-5 kicker text-gold">{{ giftTitle }}</p>
                <p class="mx-auto mt-4 max-w-2xl text-pretty font-serif text-xl italic leading-relaxed text-ink-soft">{{ giftMessage }}</p>
            </article>
        </div>

        <!-- Full-size viewer -->
        <Teleport to="body">
            <Transition name="fade">
                <div
                    v-if="current"
                    class="fixed inset-0 z-[90] flex items-center justify-center bg-film/95 p-4 backdrop-blur-sm md:p-12"
                    role="dialog"
                    aria-modal="true"
                    @click.self="close"
                    @touchstart.passive="onTouchStart"
                    @touchend="onTouchEnd"
                >
                    <Transition name="fade" mode="out-in">
                        <figure :key="current.id" class="flex max-h-full flex-col items-center">
                            <img :src="current.image" :alt="wearer(current) ? `${wearer(current)} dress code` : 'Dress code'" class="max-h-[80vh] w-auto max-w-full object-contain shadow-2xl" />
                            <figcaption class="mt-4 text-center text-paper">
                                <span v-if="wearer(current)" class="font-script text-3xl">{{ wearer(current) }}</span>
                                <span v-if="current.caption" class="ml-3 font-serif italic text-paper/80">{{ current.caption }}</span>
                                <span class="mt-1 block kicker text-paper/50">{{ AUDIENCES[current.audience] }} · {{ active + 1 }} / {{ ordered.length }}</span>
                            </figcaption>
                        </figure>
                    </Transition>

                    <button type="button" class="absolute right-4 top-4 grid size-12 place-items-center text-paper/80 hover:text-paper" aria-label="Close" @click="close">
                        <LineIcon name="close" :size="26" />
                    </button>
                    <template v-if="ordered.length > 1">
                        <button type="button" class="absolute left-2 top-1/2 grid size-12 -translate-y-1/2 place-items-center text-paper/70 hover:text-paper md:left-6" aria-label="Previous picture" @click="go(-1)">
                            <LineIcon name="arrow-left" :size="28" />
                        </button>
                        <button type="button" class="absolute right-2 top-1/2 grid size-12 -translate-y-1/2 place-items-center text-paper/70 hover:text-paper md:right-6" aria-label="Next picture" @click="go(1)">
                            <LineIcon name="arrow-right" :size="28" />
                        </button>
                    </template>
                </div>
            </Transition>
        </Teleport>
    </section>
</template>
