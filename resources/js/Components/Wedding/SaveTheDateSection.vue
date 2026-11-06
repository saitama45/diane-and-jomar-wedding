<script setup>
import { computed, ref } from 'vue';
import LineIcon from '@/Components/LineIcon.vue';
import SectionTitle from './SectionTitle.vue';
import { vReveal } from '@/lib/motion';

const props = defineProps({
    settings: { type: Object, required: true },
    wedding: { type: Object, required: true },
    firstEvent: { type: Object, default: null },
});

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
    <section class="relative bg-linen py-24 md:py-32">
        <div class="mx-auto grid max-w-6xl items-center gap-14 px-5 md:px-8 lg:grid-cols-2 lg:gap-16">
            <!-- Save-the-date card -->
            <div v-reveal="{ type: 'left', duration: 1.4 }" class="relative mx-auto w-full max-w-md">
                <div class="relative overflow-hidden bg-film px-8 py-14 text-center text-paper shadow-[0_40px_80px_-30px_rgb(43_29_21/0.7)] md:px-12 md:py-16">
                    <img :src="settings.cover_image" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover opacity-35" />
                    <div class="absolute inset-0 bg-gradient-to-b from-film/40 to-film/80"></div>
                    <div class="absolute inset-3 border border-gold-light/40"></div>
                    <div class="absolute inset-[18px] border border-gold-light/20"></div>

                    <div class="relative">
                        <p class="gold-foil font-script text-5xl md:text-6xl">Save the Date</p>
                        <p class="mt-5 font-serif text-3xl font-light tracking-[0.12em]">The Wedding</p>
                        <p class="mt-1 kicker text-paper/60">When &amp; Where</p>
                        <div class="mx-auto my-7 h-px w-20 bg-gold-light/50"></div>
                        <p class="font-serif text-xl uppercase tracking-[0.3em] text-paper/80">{{ wedding.weekday }}</p>
                        <p class="gold-foil mt-1 font-serif text-6xl font-light leading-none md:text-7xl">{{ wedding.day }}</p>
                        <p class="mt-1 font-serif text-xl uppercase tracking-[0.3em]">{{ wedding.month }} {{ wedding.year }}</p>
                        <p class="mt-4 font-serif text-lg italic text-paper/75">at {{ wedding.time }}</p>
                        <p v-if="firstEvent" class="mt-6 kicker text-gold-light">{{ firstEvent.venue }}</p>
                        <p class="mt-1 text-sm text-paper/60">{{ settings.city_line }}</p>
                    </div>
                </div>
                <div class="absolute -bottom-4 -right-4 -z-10 h-full w-full border border-gold/50"></div>
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
