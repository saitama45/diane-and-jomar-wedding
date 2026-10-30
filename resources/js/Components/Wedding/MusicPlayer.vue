<script setup>
import { onBeforeUnmount, ref } from 'vue';
import { createMusicBox } from '@/lib/musicBox';

const props = defineProps({
    src: { type: String, default: '' },
});

const playing = ref(false);
let player = null;

// An uploaded song plays through <audio>; otherwise the built-in music box.
function source() {
    if (player) return player;

    if (props.src) {
        const audio = new Audio(props.src);
        audio.loop = true;
        audio.volume = 0.6;
        player = {
            play: () => audio.play(),
            pause: () => audio.pause(),
            destroy: () => audio.pause(),
        };
    } else {
        player = createMusicBox();
    }

    return player;
}

async function play() {
    try {
        await source().play();
        playing.value = true;
    } catch {
        playing.value = false; // the browser blocked autoplay; the button still works
    }
}

function pause() {
    player?.pause();
    playing.value = false;
}

const toggle = () => (playing.value ? pause() : play());

defineExpose({ play, pause });

onBeforeUnmount(() => player?.destroy());
</script>

<template>
    <button
        type="button"
        class="group fixed bottom-5 left-5 z-50 grid size-14 place-items-center rounded-full bg-film/85 text-gold-light shadow-[0_10px_30px_-10px_rgb(0_0_0/0.6)] ring-1 ring-gold-light/30 backdrop-blur transition hover:scale-105 md:bottom-8 md:left-8"
        :aria-label="playing ? 'Pause music' : 'Play music'"
        :aria-pressed="playing"
        @click="toggle"
    >
        <!-- A little vinyl record that spins while music plays -->
        <span
            class="absolute inset-1.5 rounded-full bg-[repeating-radial-gradient(circle,#1d1917_0_2px,#2b2522_2px_3px)] [animation:spin-slow_4s_linear_infinite]"
            :style="{ animationPlayState: playing ? 'running' : 'paused' }"
        >
            <span class="absolute inset-[34%] rounded-full bg-gold/90"></span>
        </span>
        <span class="relative flex h-4 items-end gap-[3px]">
            <span
                v-for="bar in 4"
                :key="bar"
                class="w-[3px] origin-bottom rounded-full bg-paper"
                :class="playing ? 'h-4' : 'h-1.5'"
                :style="playing ? { animation: `eq ${0.7 + bar * 0.17}s ease-in-out ${bar * 0.1}s infinite` } : {}"
            ></span>
        </span>
    </button>
</template>
