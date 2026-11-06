<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import LineIcon from '@/Components/LineIcon.vue';
import SectionTitle from './SectionTitle.vue';
import { gsap, lockScroll, reducedMotion, ScrollTrigger } from '@/lib/motion';

const props = defineProps({
    settings: { type: Object, required: true },
    photos: { type: Array, default: () => [] },
});

const root = ref();
const active = ref(null);
const current = computed(() => (active.value === null ? null : props.photos[active.value]));
const tilts = [-2, 1.5, -1, 2.2, -1.6, 1];

function open(index) {
    active.value = index;
    lockScroll(true);
}

function close() {
    active.value = null;
    lockScroll(false);
}

function go(delta) {
    active.value = (active.value + delta + props.photos.length) % props.photos.length;
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
        gsap.set('.g-item', { opacity: 0, y: 70 });
        ScrollTrigger.batch('.g-item', {
            start: 'top 92%',
            once: true,
            onEnter: (batch) => gsap.to(batch, { opacity: 1, y: 0, duration: 1.2, stagger: 0.12, ease: 'power3.out' }),
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
    <section id="gallery" ref="root" class="relative bg-linen linen py-24 md:py-32">
        <div class="mx-auto max-w-6xl px-5 md:px-8">
            <SectionTitle kicker="Moments" title="Gallery">
                <p v-if="settings.gallery_credit" class="mt-4 font-serif italic text-ink-soft">{{ settings.gallery_credit }}</p>
            </SectionTitle>

            <div class="mt-16 columns-2 gap-4 md:columns-3 md:gap-8">
                <figure
                    v-for="(photo, i) in photos"
                    :key="photo.id"
                    class="g-item mb-4 break-inside-avoid md:mb-8"
                >
                    <button
                        type="button"
                        class="group relative block w-full rotate-(--tilt) bg-[#fffdf9] p-2 pb-10 shadow-[0_25px_50px_-20px_rgb(58_42_32/0.45)] transition duration-700 ease-[var(--ease-film)] hover:z-10 hover:scale-[1.03] hover:rotate-0 md:p-3 md:pb-12"
                        :style="{ '--tilt': `${tilts[i % tilts.length]}deg` }"
                        :aria-label="`Open photo${photo.caption ? ': ' + photo.caption : ''}`"
                        @click="open(i)"
                    >
                        <span class="block overflow-hidden">
                            <img :src="photo.image" :alt="photo.caption ?? ''" loading="lazy" class="w-full transition duration-[1.4s] ease-[var(--ease-film)] group-hover:scale-110" />
                        </span>
                        <figcaption v-if="photo.caption" class="absolute inset-x-0 bottom-2 truncate px-2 text-center font-script text-xl text-ink-soft md:bottom-3 md:text-2xl">
                            {{ photo.caption }}
                        </figcaption>
                    </button>
                </figure>
            </div>
        </div>

        <!-- Lightbox -->
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
                            <img :src="current.image" :alt="current.caption ?? ''" class="max-h-[78vh] w-auto max-w-full object-contain shadow-2xl" />
                            <figcaption class="mt-4 text-center text-paper">
                                <span v-if="current.caption" class="font-script text-3xl">{{ current.caption }}</span>
                                <span class="mt-1 block kicker text-paper/50">{{ active + 1 }} / {{ photos.length }}</span>
                            </figcaption>
                        </figure>
                    </Transition>

                    <button type="button" class="absolute right-4 top-4 grid size-12 place-items-center text-paper/80 hover:text-paper" aria-label="Close" @click="close">
                        <LineIcon name="close" :size="26" />
                    </button>
                    <button type="button" class="absolute left-2 top-1/2 grid size-12 -translate-y-1/2 place-items-center text-paper/70 hover:text-paper md:left-6" aria-label="Previous photo" @click="go(-1)">
                        <LineIcon name="arrow-left" :size="28" />
                    </button>
                    <button type="button" class="absolute right-2 top-1/2 grid size-12 -translate-y-1/2 place-items-center text-paper/70 hover:text-paper md:right-6" aria-label="Next photo" @click="go(1)">
                        <LineIcon name="arrow-right" :size="28" />
                    </button>
                </div>
            </Transition>
        </Teleport>
    </section>
</template>
