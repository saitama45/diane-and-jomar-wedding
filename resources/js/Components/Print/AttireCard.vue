<script setup>
/*
 * The printable Attire & Motif card (Admin > Print Cards): the heading, the motif colours (Admin > Motif
 * Colors), the outfit pictures (Admin > Attire Samples) and the written dress code (Site Settings > Dress
 * Code). `attire` holds its wording, who it is for and what it shows; `look` the text colours and sizes
 * (`item_sizes` per line). The content shrinks to fit whatever paper is chosen.
 */
import { computed, inject, nextTick, onMounted, ref, watch } from 'vue';
import BohoCorner from '@/Components/Wedding/BohoCorner.vue';

const props = defineProps({
    card: { type: Object, required: true },
    width: { type: Number, required: true }, // design size in CSS pixels
    height: { type: Number, required: true },
    background: { type: String, default: 'cream' },
    hashtag: { type: Boolean, default: true },
    look: { type: Object, default: () => ({}) },
    attire: { type: Object, default: () => ({}) },
});

const PADDING = 64;
const COLORS = { names: '#a9714b', accent: '#8e3b1f', details: '#7b5539' };
const AUDIENCES = { guests: 'Our Guests', sponsors: 'Principal Sponsors' };
const WEARERS = { ladies: 'Ladies', gentlemen: 'Gentlemen' };

const content = ref();
const scale = ref(1);

const k = computed(() => (props.look.text_size ?? 100) / 100);
const kNames = computed(() => (props.look.names_size ?? 100) / 100);
const item = (key) => (props.look.item_sizes?.[key] ?? 100) / 100;

function text(group, size, { weight = 400, italic = false, soft = false, names = false, key = null } = {}) {
    return {
        fontSize: `${size * (names ? kNames.value : k.value) * (key ? item(key) : 1)}px`,
        color: props.look[`${group}_color`] ?? COLORS[group],
        fontWeight: props.look[`${group}_bold`] ? 700 : weight,
        fontStyle: props.look[`${group}_italic`] || italic ? 'italic' : 'normal',
        opacity: soft ? 0.82 : 1,
    };
}

const theme = inject('decorTheme', computed(() => 'boho'));
const flora = computed(() => Math.round(Math.min(props.width, props.height) * (theme.value === 'garden' ? 0.3 : 0.34)));

const audience = computed(() => props.attire.attire_audience ?? 'all');
const showFor = (who) => audience.value === 'all' || audience.value === who;

const colors = computed(() => (props.attire.attire_palette === false ? [] : (props.card.attire?.colors ?? [])));
const swatch = computed(() => 64 * item('attire_swatches'));

// The pictures take the card's width; a bigger "Outfit pictures" size widens the content, and the whole card
// then shrinks to fit, so they grow against everything else without spilling off the paper.
const baseWidth = computed(() => Math.min(props.width - PADDING * 2, 1040));
const picturesWidth = computed(() => baseWidth.value * item('attire_pictures'));
const contentWidth = computed(() => Math.max(baseWidth.value, picturesWidth.value));
const columns = computed(() => Number(props.attire.attire_columns ?? 2));

const groups = computed(() => {
    if (props.attire.attire_pictures === false) return [];
    return Object.entries(AUDIENCES)
        .filter(([key]) => showFor(key))
        .map(([key, title]) => ({ key, title, items: (props.card.attire?.samples ?? []).filter((sample) => sample.audience === key) }))
        .filter((group) => group.items.length)
        .map((group) => {
            const across = Math.min(columns.value, group.items.length);
            return { ...group, across, width: (picturesWidth.value * across) / columns.value };
        });
});

const dress = computed(() => (props.attire.attire_text === false ? {} : (props.card.attire ?? {})));
const showSponsors = computed(() => showFor('sponsors') && !!(dress.value.sponsors || dress.value.gentlemen || dress.value.ladies));
const showGuests = computed(() => showFor('guests') && !!(dress.value.guests || dress.value.guestsNote));
const showHashtag = computed(() => props.hashtag && !!props.card.hashtag);

// Shrink the content (never enlarge it) so it fits inside the paper's margins.
async function fit() {
    await nextTick();
    const el = content.value;
    if (!el) return;
    scale.value = Math.min(1, (props.height - PADDING * 2) / el.offsetHeight, (props.width - PADDING * 2) / el.offsetWidth);
}

/** Resolves when the fonts and outfit pictures are in place and the content is fitted: call before making the PDF. */
async function ready() {
    await nextTick();
    await document.fonts.ready;
    await Promise.all([...(content.value?.querySelectorAll('img') ?? [])].map((img) => img.decode?.().catch(() => {})));
    await fit();
}

watch(() => [props.width, props.height, props.hashtag, props.background, props.card, JSON.stringify(props.look), JSON.stringify(props.attire)], ready);
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
            <div ref="content" class="shrink-0" :style="{ width: `${contentWidth}px`, transform: `scale(${scale})` }">
                <h2 v-if="attire.attire_heading" class="font-script font-normal leading-[1.1]" :style="text('names', 62, { names: true, key: 'attire_heading' })">{{ attire.attire_heading }}</h2>

                <!-- The motif colours -->
                <p v-if="attire.attire_message && colors.length" class="mx-auto mt-3 font-serif leading-snug" :style="text('details', 22, { italic: true, soft: true, key: 'attire_message' })">{{ attire.attire_message }}</p>
                <ul v-if="colors.length" class="mt-5 flex flex-wrap items-start justify-center gap-x-6 gap-y-4">
                    <li v-for="color in colors" :key="color.id" :style="{ width: `${Math.max(swatch, 96)}px` }">
                        <span class="mx-auto block rounded-full" :style="{ width: `${swatch}px`, height: `${swatch}px`, backgroundColor: color.hex, border: '1px solid rgb(58 42 32 / 0.18)' }"></span>
                        <span v-if="attire.attire_color_names !== false" class="mt-1.5 block font-serif leading-tight" :style="text('details', 17, { key: 'attire_color_names' })">{{ color.name }}</span>
                    </li>
                </ul>

                <!-- The outfit pictures -->
                <div v-for="group in groups" :key="group.key" class="mx-auto mt-6" :style="{ width: `${group.width}px` }">
                    <p v-if="groups.length > 1" class="mb-2 font-sans uppercase tracking-[0.3em]" :style="text('accent', 14, { weight: 500, key: 'attire_titles' })">{{ group.title }}</p>
                    <div class="grid items-start gap-4" :style="{ gridTemplateColumns: `repeat(${group.across}, minmax(0, 1fr))` }">
                        <figure v-for="sample in group.items" :key="sample.id" class="bg-white p-1.5" style="border: 1px solid rgb(58 42 32 / 0.14)">
                            <img :src="sample.image" alt="" class="block h-auto w-full" @load="fit" />
                            <figcaption v-if="attire.attire_labels !== false && (WEARERS[sample.wearer] || sample.caption)" class="px-1 pb-1 pt-1.5 leading-tight">
                                <span v-if="WEARERS[sample.wearer]" class="block font-script" :style="text('names', 30, { key: 'attire_labels' })">{{ WEARERS[sample.wearer] }}</span>
                                <span v-if="sample.caption" class="block font-serif" :style="text('details', 15, { italic: true, soft: true, key: 'attire_labels' })">{{ sample.caption }}</span>
                            </figcaption>
                        </figure>
                    </div>
                </div>

                <!-- The written dress code -->
                <div v-if="showSponsors || showGuests" class="mt-7 grid items-start gap-8" :class="showSponsors && showGuests ? 'grid-cols-2' : 'grid-cols-1'">
                    <div v-if="showSponsors">
                        <p class="font-sans uppercase tracking-[0.3em]" :style="text('accent', 14, { weight: 500, key: 'attire_titles' })">Principal Sponsors</p>
                        <p v-if="dress.sponsors" class="mt-1 font-serif leading-tight" :style="text('details', 32, { key: 'attire_styles' })">{{ dress.sponsors }}</p>
                        <template v-if="dress.gentlemen">
                            <p class="mt-2 font-script leading-tight" :style="text('names', 30, { key: 'attire_details' })">Gentlemen</p>
                            <p class="font-serif leading-snug" :style="text('details', 18, { soft: true, key: 'attire_details' })">{{ dress.gentlemen }}</p>
                        </template>
                        <template v-if="dress.ladies">
                            <p class="mt-2 font-script leading-tight" :style="text('names', 30, { key: 'attire_details' })">Ladies</p>
                            <p class="font-serif leading-snug" :style="text('details', 18, { soft: true, key: 'attire_details' })">{{ dress.ladies }}</p>
                        </template>
                    </div>
                    <div v-if="showGuests">
                        <p class="font-sans uppercase tracking-[0.3em]" :style="text('accent', 14, { weight: 500, key: 'attire_titles' })">Our Guests</p>
                        <p v-if="dress.guests" class="mt-1 font-serif leading-tight" :style="text('details', 32, { key: 'attire_styles' })">{{ dress.guests }}</p>
                        <p v-if="dress.guestsNote" class="mt-2 font-serif leading-snug" :style="text('details', 18, { italic: true, soft: true, key: 'attire_details' })">{{ dress.guestsNote }}</p>
                    </div>
                </div>

                <p v-if="showHashtag" class="mt-6 font-serif tracking-[0.08em]" :style="text('accent', 22, { weight: 600, key: 'hashtag' })">{{ card.hashtag }}</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.print-card :deep(.boho-breeze) {
    animation: none;
}
</style>
