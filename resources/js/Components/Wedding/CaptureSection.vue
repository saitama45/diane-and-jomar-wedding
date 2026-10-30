<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import QRCode from 'qrcode';
import LineIcon from '@/Components/LineIcon.vue';
import { copyText } from '@/lib/copy';
import { drawOnScroll, gsap, reducedMotion, vReveal } from '@/lib/motion';

const props = defineProps({
    settings: { type: Object, required: true },
});

const root = ref();
const camera = ref();
const qr = ref(null);
const copied = ref(false);

let ctx;

onMounted(async () => {
    if (props.settings.capture_upload_url) {
        qr.value = await QRCode.toDataURL(props.settings.capture_upload_url, { margin: 0, width: 360, color: { dark: '#2a2421', light: '#00000000' } });
        await nextTick(); // the QR <img> must exist before it is animated
    }

    if (reducedMotion()) return;
    ctx = gsap.context(() => {
        drawOnScroll(camera.value, { duration: 2.6, stagger: 0.18, start: 'top 75%' });
        gsap.from('.cap-qr', {
            scale: 0,
            rotate: -90,
            opacity: 0,
            duration: 1.2,
            delay: 1.4,
            ease: 'back.out(1.6)',
            scrollTrigger: { trigger: camera.value, start: 'top 75%', once: true },
        });
    }, root.value);
});

onBeforeUnmount(() => ctx?.revert());

async function copyHashtag() {
    copied.value = await copyText(props.settings.hashtag);
    setTimeout(() => (copied.value = false), 2000);
}
</script>

<template>
    <section ref="root" class="relative bg-paper py-24 md:py-32">
        <div class="mx-auto max-w-3xl px-5 text-center">
            <h2 v-reveal="{ type: 'blur', duration: 1.5 }" class="relative inline-block font-script leading-none text-ink">
                <span class="block text-6xl md:text-7xl">Capture</span>
                <span class="kicker my-1 block text-ink-soft">the</span>
                <span class="inline-flex items-center gap-2 text-6xl text-rose md:text-7xl">Love <LineIcon name="heart" :size="34" :stroke="1" /></span>
            </h2>

            <div class="relative mx-auto mt-10 w-full max-w-md">
                <svg ref="camera" viewBox="0 0 300 220" class="w-full text-ink" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M34 62h232a20 20 0 0 1 20 20v104a20 20 0 0 1-20 20H34a20 20 0 0 1-20-20V82a20 20 0 0 1 20-20z" />
                    <path d="M108 62l14-26h56l14 26" />
                    <path d="M44 62v-12a6 6 0 0 1 6-6h24a6 6 0 0 1 6 6v12" />
                    <circle cx="150" cy="136" r="66" />
                    <circle cx="150" cy="136" r="56" stroke-opacity=".5" />
                    <circle cx="252" cy="86" r="8" />
                    <path d="M30 96h60M210 96h60" stroke-opacity=".35" />
                </svg>
                <img v-if="qr" :src="qr" alt="QR code to our shared photo album" class="cap-qr absolute left-1/2 top-[61.8%] w-[26%] -translate-x-1/2 -translate-y-1/2" />
            </div>

            <p v-reveal="'fade'" class="mt-8 kicker text-ink">Share your photos with us!</p>
            <p v-reveal="'up'" class="mx-auto mt-3 max-w-md font-serif text-xl italic leading-relaxed text-ink-soft">{{ settings.capture_message }}</p>

            <div v-reveal="'up'" class="mt-8 flex flex-wrap items-center justify-center gap-3">
                <a v-if="settings.capture_upload_url" :href="settings.capture_upload_url" target="_blank" rel="noopener" class="btn btn-solid">
                    <LineIcon name="upload" :size="16" /> Upload photos
                </a>
                <button v-if="settings.hashtag" type="button" class="btn text-ink" @click="copyHashtag">
                    <LineIcon :name="copied ? 'check' : 'copy'" :size="16" /> {{ copied ? 'Copied!' : settings.hashtag }}
                </button>
            </div>
        </div>
    </section>
</template>
