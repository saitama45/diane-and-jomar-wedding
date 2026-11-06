<script setup>
/*
 * Opened in a new tab from Admin > Print Cards: draws the save-the-date card at print resolution,
 * turns it into a PDF of the chosen paper size and shows it (download or print from the viewer).
 */
import { Head } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, provide, ref } from 'vue';
import LineIcon from '@/Components/LineIcon.vue';
import SaveTheDateCard from '@/Components/Print/SaveTheDateCard.vue';
import { cardToPdf, designSize, printDpi } from '@/lib/pdf';

const props = defineProps({
    card: { type: Object, required: true },
    paper: { type: Object, required: true }, // label, width_mm, height_mm, background, qr, hashtag
    look: { type: Object, default: () => ({}) }, // text sizes and colours
    filename: { type: String, required: true },
});

// The admin's own corner artwork (Site Settings > Decorations) replaces the painted flowers here too.
provide('decorImage', computed(() => props.card.decorImage));

const size = computed(() => designSize(props.paper.width_mm, props.paper.height_mm));
const dpi = computed(() => printDpi(props.paper.width_mm, props.paper.height_mm));
const mm = (value) => Number(value.toFixed(1));

const cardRef = ref();
const pdfUrl = ref(null);
const error = ref(null);

onMounted(async () => {
    try {
        await cardRef.value.ready();
        const blob = await cardToPdf(cardRef.value.$el, {
            widthMm: props.paper.width_mm,
            heightMm: props.paper.height_mm,
            title: props.filename.replace(/\.pdf$/, ''),
        });
        pdfUrl.value = URL.createObjectURL(blob);
    } catch (e) {
        error.value = e?.message ?? String(e);
    }
});

onBeforeUnmount(() => pdfUrl.value && URL.revokeObjectURL(pdfUrl.value));
</script>

<template>
    <Head :title="filename" />

    <div class="flex h-dvh flex-col bg-[#4a3a30]">
        <header class="flex flex-wrap items-center justify-between gap-3 bg-film px-4 py-3 text-paper md:px-6">
            <div>
                <p class="font-serif text-xl">Save the Date</p>
                <p class="text-xs text-paper/60">{{ paper.label }} · {{ mm(paper.width_mm) }} × {{ mm(paper.height_mm) }} mm · {{ dpi }} dpi</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a v-if="pdfUrl" :href="pdfUrl" :download="filename" class="inline-flex items-center gap-2 rounded-lg bg-gold-light px-4 py-2 text-sm font-medium text-film hover:bg-paper">
                    <LineIcon name="download" :size="16" /> Download PDF
                </a>
                <a href="/admin/print" class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm text-paper/80 ring-1 ring-paper/25 hover:text-paper">
                    <LineIcon name="sliders" :size="16" /> Change paper size
                </a>
            </div>
        </header>

        <iframe v-if="pdfUrl" :src="pdfUrl" :title="filename" class="w-full flex-1 border-0"></iframe>
        <div v-else class="grid flex-1 place-items-center px-6 text-center text-paper">
            <div v-if="error">
                <p class="font-serif text-2xl">The PDF could not be made.</p>
                <p class="mt-2 text-sm text-paper/60">{{ error }}</p>
            </div>
            <div v-else>
                <span class="mx-auto block size-10 animate-spin rounded-full border-2 border-paper/20 border-t-gold-light"></span>
                <p class="mt-4 font-serif text-xl">Preparing your PDF…</p>
                <p class="mt-1 text-sm text-paper/60">Drawing the card at print quality, this takes a few seconds.</p>
            </div>
        </div>
    </div>

    <!-- The card the PDF is drawn from, kept off screen at its full design size. -->
    <div class="pointer-events-none fixed left-[-30000px] top-0" aria-hidden="true">
        <SaveTheDateCard ref="cardRef" :card="card" :width="size.width" :height="size.height" :background="paper.background" :qr="paper.qr" :hashtag="paper.hashtag" :look="look" />
    </div>
</template>
