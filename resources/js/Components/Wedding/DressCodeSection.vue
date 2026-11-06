<script setup>
import BohoBouquet from '@/Components/Wedding/BohoBouquet.vue';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import SectionTitle from './SectionTitle.vue';
import { drawOnScroll, gsap, reducedMotion, vReveal } from '@/lib/motion';

defineProps({
    settings: { type: Object, required: true },
    colors: { type: Array, default: () => [] },
});

const root = ref();
const barong = ref();
const gown = ref();

let ctx;

onMounted(() => {
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

onBeforeUnmount(() => ctx?.revert());
</script>

<template>
    <section id="attire" ref="root" class="relative isolate bg-clay/45 py-24 md:py-32">
        <BohoBouquet class="pointer-events-none absolute -z-10 h-36 w-28 opacity-40 md:h-72 md:w-52 md:opacity-60 top-8 left-0" />
        <BohoBouquet flip class="pointer-events-none absolute -z-10 h-36 w-28 opacity-40 md:h-72 md:w-52 md:opacity-60 top-8 right-0" />
        <div class="mx-auto max-w-5xl px-5 md:px-8">
            <SectionTitle kicker="What to wear" title="The Dress Code" />

            <div class="mt-16 grid gap-8 md:grid-cols-2">
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
                            <dt class="font-script text-3xl text-sage">Gentlemen</dt>
                            <dd>{{ settings.dress_gentlemen }}</dd>
                        </div>
                        <div>
                            <dt class="font-script text-3xl text-sage">Ladies</dt>
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
        </div>
    </section>
</template>
