<script setup>
/*
 * The printable save-the-date card (Admin > Print Cards), modelled on a pampas vellum card: the heading,
 * a pampas wreath around the monogram, the date between two rules, the venues and an RSVP block with a
 * QR code to the website. It is drawn at a "design size" in CSS pixels (see lib/pdf.js designSize) and
 * the content shrinks to fit whatever paper proportions are chosen.
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
});

const PADDING = 64;

const content = ref();
const scale = ref(1);
const qrImage = ref(null);

const initials = computed(() => [props.card.bride, props.card.groom].map((name) => (name ?? '').trim().charAt(0).toUpperCase()));
const flora = computed(() => Math.round(Math.min(props.width, props.height) * 0.46));
const host = computed(() => (props.card.siteUrl ?? '').replace(/^https?:\/\//, ''));

async function makeQr() {
    qrImage.value = props.qr && props.card.siteUrl
        ? await QRCode.toDataURL(props.card.siteUrl, { margin: 0, width: 400, color: { dark: '#5a3a26', light: '#00000000' } })
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

watch(() => [props.width, props.height, props.qr, props.background, props.card], ready);
onMounted(ready);
defineExpose({ ready });
</script>

<template>
    <div
        class="print-card relative isolate overflow-hidden text-center text-ink"
        :class="background === 'white' ? 'bg-white' : 'boho-paper'"
        :style="{ width: `${width}px`, height: `${height}px` }"
    >
        <div v-if="background !== 'white'" class="linen absolute inset-0 -z-10 opacity-60" aria-hidden="true"></div>
        <BohoCorner corner="top-right" :style="{ width: `${flora}px`, height: `${flora}px`, opacity: 1 }" />
        <BohoCorner corner="bottom-left" :style="{ width: `${flora}px`, height: `${flora}px`, opacity: 1 }" />

        <div class="absolute inset-0 flex items-center justify-center">
            <div ref="content" class="w-[520px] shrink-0" :style="{ transform: `scale(${scale})` }">
                <p class="font-serif text-[22px] font-semibold uppercase tracking-[0.34em] text-rust">{{ card.heading }}</p>

                <PampasWreath class="mx-auto mt-3 size-[230px]">
                    <span class="flex items-baseline font-serif text-[66px] font-medium leading-none text-rust">
                        {{ initials[0] }}<span class="mx-1.5 font-script text-[40px] text-caramel">&amp;</span>{{ initials[1] }}
                    </span>
                </PampasWreath>

                <p class="mt-1 font-script text-[48px] leading-tight text-caramel">{{ card.bride }} &amp; {{ card.groom }}</p>

                <!-- Weekday and time | day | month and year -->
                <div class="mt-7 grid grid-cols-[1fr_auto_1fr] items-center font-serif uppercase text-cocoa">
                    <div class="pr-6 text-right text-[18px] leading-snug tracking-[0.2em]">
                        <p class="font-semibold">{{ card.weekday }}</p>
                        <p>{{ card.time }}</p>
                    </div>
                    <p class="border-x border-cocoa/50 px-7 text-[74px] font-semibold leading-none tabular-nums text-rust">{{ card.day }}</p>
                    <div class="pl-6 text-left text-[18px] leading-snug tracking-[0.2em]">
                        <p class="font-semibold">{{ card.month }}</p>
                        <p>{{ card.year }}</p>
                    </div>
                </div>

                <template v-if="card.ceremony">
                    <p class="mt-9 font-sans text-[13px] uppercase tracking-[0.3em] text-ink-soft">{{ card.ceremonyLine }}</p>
                    <p class="mt-2 font-serif text-[30px] font-semibold uppercase leading-tight tracking-[0.1em] text-cocoa">{{ card.ceremony.venue }}</p>
                    <p v-if="card.ceremony.address" class="mt-1 font-serif text-[18px] italic text-ink-soft">{{ card.ceremony.address }}</p>
                </template>
                <p v-else-if="card.city" class="mt-9 font-serif text-[26px] font-semibold uppercase tracking-[0.12em] text-cocoa">{{ card.city }}</p>

                <template v-if="card.reception">
                    <p class="mt-5 font-script text-[36px] leading-tight text-caramel">{{ card.receptionLine }}</p>
                    <p class="font-serif text-[26px] font-semibold uppercase leading-tight tracking-[0.1em] text-cocoa">{{ card.reception.venue }}</p>
                    <p v-if="card.reception.address" class="mt-1 font-serif text-[18px] italic text-ink-soft">{{ card.reception.address }}</p>
                    <p class="mt-1.5 font-serif text-[15px] font-semibold tracking-[0.25em] text-cocoa">{{ card.reception.time }}</p>
                </template>

                <div class="mt-9 flex items-center justify-center gap-5">
                    <img v-if="qr && qrImage" :src="qrImage" alt="" width="88" height="88" class="size-[88px]" />
                    <div :class="qr && qrImage ? 'text-left' : 'text-center'">
                        <p class="font-serif text-[26px] font-semibold tracking-[0.24em] text-rust">RSVP</p>
                        <p v-if="card.deadline" class="font-serif text-[16px] italic text-ink-soft">Kindly reply by {{ card.deadline }}</p>
                        <p v-if="host" class="mt-0.5 font-sans text-[12px] tracking-[0.1em] text-ink-soft">{{ host }}</p>
                    </div>
                </div>
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
