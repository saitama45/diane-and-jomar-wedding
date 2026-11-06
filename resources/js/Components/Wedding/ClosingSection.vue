<script setup>
import BohoCorner from '@/Components/Wedding/BohoCorner.vue';
import { computed } from 'vue';
import FilmStrip from './FilmStrip.vue';
import { scrollToTarget, vReveal } from '@/lib/motion';

const props = defineProps({
    settings: { type: Object, required: true },
    wedding: { type: Object, required: true },
    photos: { type: Array, default: () => [] },
});

const initials = computed(() => [props.settings.bride_name, props.settings.groom_name].map((name) => (name ?? '').charAt(0)));
</script>

<template>
    <section class="relative isolate overflow-hidden bg-cream linen pt-24 md:pt-32">
        <BohoCorner corner="top-right" />
        <div class="mx-auto grid max-w-6xl items-center gap-12 px-5 md:px-8 lg:grid-cols-2 lg:gap-20">
            <div v-reveal="{ type: 'left', duration: 1.4 }" class="film-strip px-3 shadow-[0_30px_60px_-30px_rgb(43_29_21/0.6)]">
                <img :src="settings.closing_image" alt="" loading="lazy" class="aspect-[4/5] w-full object-cover grayscale transition duration-[1.5s] hover:grayscale-0" />
            </div>

            <div class="text-center lg:text-left">
                <h2 v-reveal="{ type: 'blur', duration: 1.5 }" class="font-serif text-5xl font-light leading-tight text-ink md:text-6xl">
                    {{ settings.closing_title }}
                </h2>
                <p v-reveal="'up'" class="mt-8 font-serif text-xl italic leading-relaxed text-ink-soft">{{ settings.closing_message }}</p>
                <p v-reveal="'fade'" class="mt-10 font-script text-5xl text-rust md:text-6xl">
                    {{ settings.bride_name }} &amp; {{ settings.groom_name }}
                </p>
                <p v-reveal="'fade'" class="mt-3 kicker text-gold">{{ settings.hashtag }}</p>
            </div>
        </div>

        <FilmStrip v-if="photos.length" :images="photos" class="mt-24" :duration="90" height="h-20 md:h-28" />

        <footer class="bg-film px-5 pb-12 pt-14 text-center text-paper">
            <button type="button" class="mx-auto grid size-20 place-items-center rounded-full border border-gold-light/50 font-serif text-2xl text-gold-light transition hover:border-gold-light" aria-label="Back to top" @click="scrollToTarget(0)">
                <span>{{ initials[0] }}<span class="mx-0.5 font-script text-lg">&amp;</span>{{ initials[1] }}</span>
            </button>
            <p class="mt-6 font-serif text-lg tracking-[0.3em]">{{ wedding.month_num }} · {{ wedding.day }} · {{ wedding.year }}</p>
            <p class="mt-2 font-serif italic text-paper/60">{{ settings.footer_note }}</p>
            <p class="mt-8 text-[11px] uppercase tracking-[0.3em] text-paper/35">
                © {{ wedding.year }} {{ settings.bride_name }} &amp; {{ settings.groom_name }}
            </p>
        </footer>
    </section>
</template>
