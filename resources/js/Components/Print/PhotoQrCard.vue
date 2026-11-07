<script setup>
/*
 * The printable photo-sharing sign (Admin > Print Cards > Photo QR sign): the website's "Capture the Love"
 * section on paper. Guests scan the QR code in the camera lens to upload their photos (Admin > Guest Photos).
 * Same look settings as the save-the-date card (`look`: colours, bold / italic, text size); the QR code's
 * size is the "QR code" line in "Each line". Built for html2canvas: the QR code is drawn inside the camera
 * SVG itself, so it can never slip out of the lens.
 */
import QRCode from 'qrcode';
import { computed, inject, nextTick, onMounted, ref, watch } from 'vue';
import BohoCorner from '@/Components/Wedding/BohoCorner.vue';

const props = defineProps({
    card: { type: Object, required: true },
    width: { type: Number, required: true }, // design size in CSS pixels
    height: { type: Number, required: true },
    background: { type: String, default: 'cream' },
    hashtag: { type: Boolean, default: true },
    look: { type: Object, default: () => ({}) },
});

const PADDING = 64;
const COLORS = { names: '#a9714b', accent: '#8e3b1f', details: '#7b5539' };
const LENS = { x: 150, y: 136 }; // the lens centre in the camera drawing (viewBox 0 0 300 220)

const content = ref();
const scale = ref(1);
const qr = ref(null); // { path, size } in QR modules

const k = computed(() => (props.look.text_size ?? 100) / 100);
const kNames = computed(() => (props.look.names_size ?? 100) / 100);
const item = (key) => (props.look.item_sizes?.[key] ?? 100) / 100;
const ink = computed(() => props.look.details_color ?? COLORS.details);

// `key`: the line's own size in "Each line" (item_sizes), on top of the group's size.
function text(group, size, { weight = 400, italic = false, soft = false, names = false, key = null } = {}) {
    return {
        fontSize: `${size * (names ? kNames.value : k.value) * (key ? item(key) : 1)}px`,
        color: props.look[`${group}_color`] ?? COLORS[group],
        fontWeight: props.look[`${group}_bold`] ? 700 : weight,
        fontStyle: props.look[`${group}_italic`] || italic ? 'italic' : 'normal',
        opacity: soft ? 0.82 : 1,
    };
}

// The camera grows with the QR code's own size setting (100 % = the QR fills the inner lens ring).
const cameraWidth = computed(() => 560 * Math.min(1.3, item('qr')));
const qrSide = computed(() => 78 * Math.min(1.25, item('qr'))); // in camera units; the inner ring fits 79
const host = computed(() => (props.card.shareUrl ?? '').replace(/^https?:\/\//, ''));
const theme = inject('decorTheme', computed(() => 'boho'));
const flora = computed(() => Math.round(Math.min(props.width, props.height) * (theme.value === 'garden' ? 0.36 : 0.42)));

async function makeQr() {
    if (!props.card.shareUrl) {
        qr.value = null;
        return;
    }
    // The QR code as one SVG path, so it sits inside the camera drawing.
    const svg = await QRCode.toString(props.card.shareUrl, { type: 'svg', margin: 0, errorCorrectionLevel: 'M' });
    const size = Number(svg.match(/viewBox="0 0 (\d+) \d+"/)?.[1] ?? 0);
    // Only the dark modules (the stroked path); the library also draws a filled white background square.
    const path = [...svg.matchAll(/<path stroke="[^"]*" d="([^"]+)"/g)].map((m) => m[1]).join(' ');
    qr.value = size && path ? { size, path } : null;
}

async function fit() {
    await nextTick();
    const el = content.value;
    if (!el) return;
    scale.value = Math.min(1, (props.height - PADDING * 2) / el.offsetHeight, (props.width - PADDING * 2) / el.offsetWidth);
}

async function ready() {
    await makeQr();
    await document.fonts.ready;
    await fit();
}

watch(() => [props.width, props.height, props.hashtag, props.background, props.card, JSON.stringify(props.look)], ready);
onMounted(ready);
defineExpose({ ready });
</script>

<template>
    <div
        class="print-card relative isolate overflow-hidden text-center"
        :class="background === 'white' ? 'bg-white' : 'boho-paper'"
        :style="{ width: `${width}px`, height: `${height}px` }"
    >
        <BohoCorner corner="top-right" :style="{ width: `${flora}px`, height: `${flora}px`, opacity: 1 }" />
        <BohoCorner corner="bottom-left" :style="{ width: `${flora}px`, height: `${flora}px`, opacity: 1 }" />
        <template v-if="theme === 'garden'">
            <BohoCorner corner="top-left" :style="{ width: `${flora * 0.85}px`, height: `${flora * 0.85}px`, opacity: 1 }" />
            <BohoCorner corner="bottom-right" :style="{ width: `${flora * 0.85}px`, height: `${flora * 0.85}px`, opacity: 1 }" />
        </template>

        <div class="absolute inset-0 flex items-center justify-center">
            <!-- Wide enough for the camera whatever the text size (the camera has its own size: "QR code" in Each line) -->
            <div ref="content" class="shrink-0" :style="{ width: `${Math.max(600 * k, cameraWidth + 40)}px`, transform: `scale(${scale})` }">
                <!-- Capture / the / Love -->
                <h2 class="font-script font-normal leading-[1.02]">
                    <span class="block" :style="text('names', 92, { names: true })">Capture</span>
                    <span class="my-1 block font-sans uppercase tracking-[0.4em]" :style="text('details', 16)">the</span>
                    <span class="block" :style="text('accent', 92, { names: true })">Love <span :style="{ fontSize: '0.45em' }">♡</span></span>
                </h2>

                <!-- The camera, with the QR code in its lens -->
                <svg
                    viewBox="0 30 300 182"
                    class="mx-auto mt-4 block"
                    :style="{ width: `${cameraWidth}px`, height: `${(cameraWidth * 182) / 300}px` }"
                    fill="none"
                    :stroke="ink"
                    stroke-width="1.6"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <path d="M34 62h232a20 20 0 0 1 20 20v104a20 20 0 0 1-20 20H34a20 20 0 0 1-20-20V82a20 20 0 0 1 20-20z" />
                    <path d="M108 62l14-26h56l14 26" />
                    <path d="M44 62v-12a6 6 0 0 1 6-6h24a6 6 0 0 1 6 6v12" />
                    <circle :cx="LENS.x" :cy="LENS.y" r="66" fill="#ffffff" fill-opacity="0.55" />
                    <!-- a solid backing behind the QR code, so nothing shows through it -->
                    <circle :cx="LENS.x" :cy="LENS.y" r="56" fill="#fffaf2" stroke-opacity=".5" />
                    <circle cx="252" cy="86" r="8" />
                    <path d="M30 96h60M210 96h60" stroke-opacity=".35" />
                    <svg
                        v-if="qr"
                        :x="LENS.x - qrSide / 2"
                        :y="LENS.y - qrSide / 2"
                        :width="qrSide"
                        :height="qrSide"
                        :viewBox="`0 0 ${qr.size} ${qr.size}`"
                        shape-rendering="crispEdges"
                    >
                        <!-- square ends: the camera's rounded line ends would turn the modules into blobs phones cannot read -->
                        <path :d="qr.path" stroke="#3a2a20" stroke-width="1" stroke-linecap="butt" stroke-linejoin="miter" fill="none" />
                    </svg>
                </svg>

                <p class="mt-7 font-sans uppercase tracking-[0.34em]" :style="text('details', 17, { weight: 500 })">Share your photos with us!</p>
                <p v-if="card.captureMessage" class="mx-auto mt-3 max-w-[520px] font-serif leading-relaxed" :style="text('details', 22, { italic: true, soft: true })">
                    {{ card.captureMessage }}
                </p>
                <p v-if="host" class="mt-4 font-sans tracking-[0.12em]" :style="text('details', 15, { soft: true })">{{ host }}</p>
                <p v-if="hashtag && card.hashtag" class="mt-4 font-serif tracking-[0.08em]" :style="text('accent', 26, { weight: 600, key: 'hashtag' })">{{ card.hashtag }}</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.print-card :deep(.boho-breeze) {
    animation: none;
}
</style>
