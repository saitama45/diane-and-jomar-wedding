<script setup>
/*
 * The printable save-the-date card (Admin > Print Cards), modelled on a pampas vellum card: the heading,
 * a pampas wreath around the monogram, the names (stacked "Jomar / and / Diana", or on one line), the date
 * between two rules, the venues, an RSVP block with a QR code to the website, and the wedding hashtag.
 * It is drawn at a "design size" in CSS pixels (see lib/pdf.js designSize) and the content shrinks to fit
 * whatever paper proportions are chosen.
 *
 * `look` styles three text groups: names (the script lines), accent (heading, monogram, day, RSVP,
 * hashtag) and details (everything else), each with a colour and bold / italic switches. The names and
 * the rest have their own size, and every line can be fine-tuned (`item_sizes`, percent per line).
 */
import QRCode from 'qrcode';
import { computed, inject, nextTick, onMounted, ref, watch } from 'vue';
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
const qrSvg = ref('');

const k = computed(() => (props.look.text_size ?? 100) / 100);
const kNames = computed(() => (props.look.names_size ?? 100) / 100);
const stacked = computed(() => (props.look.names_layout ?? 'stacked') === 'stacked');
const details = computed(() => props.look.details_color ?? COLORS.details);

/** A line's own fine-tuning, 1 = as designed. */
const item = (key) => (props.look.item_sizes?.[key] ?? 100) / 100;

/**
 * Inline style for one line of text: its group's colour, its size (design px × the group's size × the
 * line's own fine-tuning) and the designed weight and style unless the group is switched to bold / italic.
 */
function text(key, group, size, { weight = 400, italic = false, soft = false, names = false } = {}) {
    return {
        fontSize: `${size * (names ? kNames.value : k.value) * item(key)}px`,
        color: props.look[`${group}_color`] ?? COLORS[group],
        fontWeight: props.look[`${group}_bold`] ? 700 : weight,
        fontStyle: props.look[`${group}_italic`] || italic ? 'italic' : 'normal',
        opacity: soft ? 0.82 : 1,
    };
}
const px = (size, key = null) => `${size * k.value * (key ? item(key) : 1)}px`;

const initials = computed(() => [props.card.bride, props.card.groom].map((name) => (name ?? '').trim().charAt(0).toUpperCase()));
// The garden flowers are denser than the pampas, so their corners print a little smaller.
const theme = inject('decorTheme', computed(() => 'boho'));
const flora = computed(() => Math.round(Math.min(props.width, props.height) * (theme.value === 'garden' ? 0.4 : 0.46)));
const host = computed(() => (props.card.siteUrl ?? '').replace(/^https?:\/\//, ''));
const showHashtag = computed(() => props.hashtag && !!props.card.hashtag);

async function makeQr() {
    // An inline SVG, not an <img>: it always comes through when the card is turned into a PDF (Safari
    // left <img> QR codes out). It stays dark brown whatever the text colours, so phones can read it.
    qrSvg.value = props.qr && props.card.siteUrl
        ? await QRCode.toString(props.card.siteUrl, { type: 'svg', margin: 0, color: { dark: '#4a3226', light: '#0000' } })
        : '';
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

watch(() => [props.width, props.height, props.qr, props.hashtag, props.background, props.card, JSON.stringify(props.look)], ready);
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
            <div ref="content" class="shrink-0" :style="{ width: px(520), transform: `scale(${scale})` }">
                <p class="font-serif uppercase tracking-[0.34em]" :style="text('heading', 'accent', 22, { weight: 600 })">{{ card.heading }}</p>

                <PampasWreath class="mx-auto mt-3" :style="{ width: px(200, 'monogram'), height: px(200, 'monogram') }">
                    <span class="flex items-baseline font-serif leading-none" :style="text('monogram', 'accent', 58, { weight: 500 })">
                        {{ initials[0] }}<span class="mx-1.5 font-script" :style="text('monogram', 'names', 35)">&amp;</span>{{ initials[1] }}
                    </span>
                </PampasWreath>

                <!-- The names: stacked like a printed invitation, or on one line -->
                <h3 v-if="stacked" class="mt-1 font-script font-normal leading-[1.08]">
                    <span class="block" :style="text('names', 'names', 74, { names: true })">{{ card.bride }}</span>
                    <span class="my-0.5 flex items-center justify-center gap-4" :style="text('and', 'names', 32, { names: true })">
                        <span class="h-px w-[2.2em] bg-current opacity-50"></span>and<span class="h-px w-[2.2em] bg-current opacity-50"></span>
                    </span>
                    <span class="block" :style="text('names', 'names', 74, { names: true })">{{ card.groom }}</span>
                </h3>
                <p v-else class="mt-1 font-script leading-tight" :style="text('names', 'names', 52, { names: true })">
                    {{ card.bride }} <span :style="text('and', 'names', 52, { names: true })">&amp;</span> {{ card.groom }}
                </p>

                <!-- Weekday and time | day | month and year -->
                <div class="mt-6 grid grid-cols-[1fr_auto_1fr] items-center font-serif uppercase">
                    <div class="pr-6 text-right leading-snug tracking-[0.2em]">
                        <p :style="text('date', 'details', 18, { weight: 600 })">{{ card.weekday }}</p>
                        <p :style="text('date', 'details', 18)">{{ card.time }}</p>
                    </div>
                    <p class="border-x px-7 leading-none tabular-nums" :style="{ ...text('day', 'accent', 74, { weight: 600 }), borderColor: `${details}80` }">{{ card.day }}</p>
                    <div class="pl-6 text-left leading-snug tracking-[0.2em]">
                        <p :style="text('date', 'details', 18, { weight: 600 })">{{ card.month }}</p>
                        <p :style="text('date', 'details', 18)">{{ card.year }}</p>
                    </div>
                </div>

                <!-- Both venues share one designed size (the couple asked for them to match) -->
                <template v-if="card.ceremony">
                    <p class="mt-8 font-sans uppercase tracking-[0.3em]" :style="text('ceremony_line', 'details', 13, { soft: true })">{{ card.ceremonyLine }}</p>
                    <p class="mt-2 font-serif uppercase leading-tight tracking-[0.1em]" :style="text('ceremony_venue', 'details', 26, { weight: 600 })">{{ card.ceremony.venue }}</p>
                    <p v-if="card.ceremony.address" class="mt-1 font-serif" :style="text('ceremony_address', 'details', 18, { italic: true, soft: true })">{{ card.ceremony.address }}</p>
                </template>
                <p v-else-if="card.city" class="mt-8 font-serif uppercase tracking-[0.12em]" :style="text('ceremony_venue', 'details', 26, { weight: 600 })">{{ card.city }}</p>

                <template v-if="card.reception">
                    <p class="mt-5 font-script leading-tight" :style="text('reception_line', 'names', 36)">{{ card.receptionLine }}</p>
                    <p class="font-serif uppercase leading-tight tracking-[0.1em]" :style="text('reception_venue', 'details', 26, { weight: 600 })">{{ card.reception.venue }}</p>
                    <p v-if="card.reception.address" class="mt-1 font-serif" :style="text('reception_address', 'details', 18, { italic: true, soft: true })">{{ card.reception.address }}</p>
                    <p class="mt-1.5 font-serif tracking-[0.25em]" :style="text('reception_time', 'details', 15, { weight: 600 })">{{ card.reception.time }}</p>
                </template>

                <div class="mt-8 flex items-center justify-center gap-5">
                    <!-- eslint-disable-next-line vue/no-v-html -- an SVG made by the qrcode library from the site address -->
                    <!-- a solid cream backing: nothing behind it (corner flowers) may show through, or phones cannot read it -->
                    <span v-if="qr && qrSvg" class="block shrink-0 [&_svg]:size-full" :style="{ width: px(88, 'qr'), height: px(88, 'qr'), padding: px(6), background: '#fffaf2', boxSizing: 'content-box' }" v-html="qrSvg"></span>
                    <div :class="qr && qrSvg ? 'text-left' : 'text-center'">
                        <p class="font-serif tracking-[0.24em]" :style="text('rsvp', 'accent', 26, { weight: 600 })">RSVP</p>
                        <p v-if="card.deadline" class="font-serif" :style="text('reply_by', 'details', 16, { italic: true, soft: true })">Kindly reply by {{ card.deadline }}</p>
                        <p v-if="host" class="mt-0.5 font-sans tracking-[0.1em]" :style="text('website', 'details', 12, { soft: true })">{{ host }}</p>
                    </div>
                </div>

                <p v-if="showHashtag" class="mt-5 font-serif tracking-[0.08em]" :style="text('hashtag', 'accent', 20, { weight: 600 })">{{ card.hashtag }}</p>
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
