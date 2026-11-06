<script setup>
/*
 * The printable save-the-date card (Admin > Print Cards), modelled on a pampas vellum card: the heading,
 * a pampas wreath around the monogram, the date between two rules, the venues, an RSVP block with a
 * QR code to the website, and the wedding hashtag. It is drawn at a "design size" in CSS pixels (see
 * lib/pdf.js designSize) and the content shrinks to fit whatever paper proportions are chosen.
 *
 * `look` styles three text groups: names (the script lines), accent (heading, monogram, day, RSVP,
 * hashtag) and details (everything else). Each has a colour and bold / italic switches; the names and
 * the rest have their own size (percent of the designed size).
 */
import QRCode from 'qrcode';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import BohoCorner from '@/Components/Wedding/BohoCorner.vue';
import PampasWreath from '@/Components/Wedding/PampasWreath.vue';

const props = defineProps({
    card: { type: Object, required: true },
    width: { type: Number, required: true }, // design size in CSS pixels
    height: { type: Number, required: true },
    background: { type: String, default: 'cream' }, // cream (printed) | white (vellum or coloured card stock)
    qr: { type: Boolean, default: true },
    hashtag: { type: Boolean, default: true },
    look: { type: Object, default: () => ({}) },
});

const PADDING = 64;
const COLORS = { names: '#a9714b', accent: '#8e3b1f', details: '#7b5539' };

const content = ref();
const scale = ref(1);
const qrImage = ref(null);

const k = computed(() => (props.look.text_size ?? 100) / 100);
const kNames = computed(() => (props.look.names_size ?? 100) / 100);
const details = computed(() => props.look.details_color ?? COLORS.details);

/**
 * Inline style for one piece of text: its group's colour, the size (design px, scaled), and the designed
 * weight and style unless the group is switched to bold / italic.
 */
function text(group, size, { weight = 400, italic = false, soft = false } = {}) {
    return {
        fontSize: `${size * (group === 'names' ? kNames.value : k.value)}px`,
        color: props.look[`${group}_color`] ?? COLORS[group],
        fontWeight: props.look[`${group}_bold`] ? 700 : weight,
        fontStyle: props.look[`${group}_italic`] || italic ? 'italic' : 'normal',
        opacity: soft ? 0.82 : 1,
    };
}
const px = (size) => `${size * k.value}px`;

const initials = computed(() => [props.card.bride, props.card.groom].map((name) => (name ?? '').trim().charAt(0).toUpperCase()));
const flora = computed(() => Math.round(Math.min(props.width, props.height) * 0.46));
const host = computed(() => (props.card.siteUrl ?? '').replace(/^https?:\/\//, ''));
const showHashtag = computed(() => props.hashtag && !!props.card.hashtag);

async function makeQr() {
    // The QR code stays dark brown whatever the text colours, so phones can always read it.
    qrImage.value = props.qr && props.card.siteUrl
        ? await QRCode.toDataURL(props.card.siteUrl, { margin: 0, width: 400, color: { dark: '#4a3226', light: '#00000000' } })
        : null;
}

// Shrink the content (never enlarge it) so it fits inside the paper's margins.
async function fit() {
    await nextTick();
    const el = content.value;
    if (!el) return;
    scale.value = Math.min(1, (props.height - PADDING * 2) / el.offsetHeight, (props.width - PADDING * 2) / el.offsetWidth);
}

/** Resolves when the QR code and fonts are in place and the content is fitted: call before making the PDF. */
async function ready() {
    await makeQr();
    await document.fonts.ready;
    await fit();
}

watch(() => [props.width, props.height, props.qr, props.hashtag, props.background, props.card, { ...props.look }], ready, { deep: true });
onMounted(ready);
defineExpose({ ready });
</script>

<template>
    <div
        class="print-card relative isolate overflow-hidden text-center"
        :class="background === 'white' ? 'bg-white' : 'boho-paper'"
        :style="{ width: `${width}px`, height: `${height}px` }"
    >
        <div v-if="background !== 'white'" class="linen absolute inset-0 -z-10 opacity-60" aria-hidden="true"></div>
        <BohoCorner corner="top-right" :style="{ width: `${flora}px`, height: `${flora}px`, opacity: 1 }" />
        <BohoCorner corner="bottom-left" :style="{ width: `${flora}px`, height: `${flora}px`, opacity: 1 }" />

        <div class="absolute inset-0 flex items-center justify-center">
            <div ref="content" class="shrink-0" :style="{ width: px(520), transform: `scale(${scale})` }">
                <p class="font-serif uppercase tracking-[0.34em]" :style="text('accent', 22, { weight: 600 })">{{ card.heading }}</p>

                <PampasWreath class="mx-auto mt-3" :style="{ width: px(230), height: px(230) }">
                    <span class="flex items-baseline font-serif leading-none" :style="text('accent', 66, { weight: 500 })">
                        {{ initials[0] }}<span class="mx-1.5 font-script" :style="text('names', 40 * k / kNames)">&amp;</span>{{ initials[1] }}
                    </span>
                </PampasWreath>

                <p class="mt-1 font-script leading-tight" :style="text('names', 48)">{{ card.bride }} &amp; {{ card.groom }}</p>

                <!-- Weekday and time | day | month and year -->
                <div class="mt-7 grid grid-cols-[1fr_auto_1fr] items-center font-serif uppercase">
                    <div class="pr-6 text-right leading-snug tracking-[0.2em]">
                        <p :style="text('details', 18, { weight: 600 })">{{ card.weekday }}</p>
                        <p :style="text('details', 18)">{{ card.time }}</p>
                    </div>
                    <p class="border-x px-7 leading-none tabular-nums" :style="{ ...text('accent', 74, { weight: 600 }), borderColor: `${details}80` }">{{ card.day }}</p>
                    <div class="pl-6 text-left leading-snug tracking-[0.2em]">
                        <p :style="text('details', 18, { weight: 600 })">{{ card.month }}</p>
                        <p :style="text('details', 18)">{{ card.year }}</p>
                    </div>
                </div>

                <template v-if="card.ceremony">
                    <p class="mt-9 font-sans uppercase tracking-[0.3em]" :style="text('details', 13, { soft: true })">{{ card.ceremonyLine }}</p>
                    <p class="mt-2 font-serif uppercase leading-tight tracking-[0.1em]" :style="text('details', 30, { weight: 600 })">{{ card.ceremony.venue }}</p>
                    <p v-if="card.ceremony.address" class="mt-1 font-serif" :style="text('details', 18, { italic: true, soft: true })">{{ card.ceremony.address }}</p>
                </template>
                <p v-else-if="card.city" class="mt-9 font-serif uppercase tracking-[0.12em]" :style="text('details', 26, { weight: 600 })">{{ card.city }}</p>

                <template v-if="card.reception">
                    <p class="mt-5 font-script leading-tight" :style="text('names', 36 * k / kNames)">{{ card.receptionLine }}</p>
                    <p class="font-serif uppercase leading-tight tracking-[0.1em]" :style="text('details', 26, { weight: 600 })">{{ card.reception.venue }}</p>
                    <p v-if="card.reception.address" class="mt-1 font-serif" :style="text('details', 18, { italic: true, soft: true })">{{ card.reception.address }}</p>
                    <p class="mt-1.5 font-serif tracking-[0.25em]" :style="text('details', 15, { weight: 600 })">{{ card.reception.time }}</p>
                </template>

                <div class="mt-9 flex items-center justify-center gap-5">
                    <img v-if="qr && qrImage" :src="qrImage" alt="" :style="{ width: px(88), height: px(88) }" />
                    <div :class="qr && qrImage ? 'text-left' : 'text-center'">
                        <p class="font-serif tracking-[0.24em]" :style="text('accent', 26, { weight: 600 })">RSVP</p>
                        <p v-if="card.deadline" class="font-serif" :style="text('details', 16, { italic: true, soft: true })">Kindly reply by {{ card.deadline }}</p>
                        <p v-if="host" class="mt-0.5 font-sans tracking-[0.1em]" :style="text('details', 12, { soft: true })">{{ host }}</p>
                    </div>
                </div>

                <p v-if="showHashtag" class="mt-6 font-serif tracking-[0.08em]" :style="text('accent', 20, { weight: 600 })">{{ card.hashtag }}</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* A printed card stands still: no swaying pampas. */
.print-card :deep(.boho-breeze) {
    animation: none;
}
</style>
