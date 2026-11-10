<script setup>
/*
 * The printable Families & Sponsors card (Admin > Print Cards): the website's "Our Families and Sponsors"
 * section on paper. The heading, the families line (Site Settings > The Couple), then every visible group
 * from Admin > Entourage in group order, laid out like the website: long lists in columns, a lone name
 * three to a row, the rest two to a row. `entourage` holds the wording, which groups to leave off and the
 * columns for long lists; `look` the text colours and sizes. The content shrinks to fit the paper.
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
    entourage: { type: Object, default: () => ({}) },
});

const PADDING = 64;
const COLORS = { names: '#a9714b', accent: '#8e3b1f', details: '#7b5539' };

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

const contentWidth = computed(() => Math.min(props.width - PADDING * 2, 1040));
const columns = computed(() => Number(props.entourage.entourage_columns ?? 2));
const hidden = computed(() => new Set(props.entourage.entourage_hidden ?? []));

// Long lists take the full width in columns, a single name sits three to a row, the rest two to a row.
const groups = computed(() =>
    (props.card.entourage?.groups ?? [])
        .filter((group) => !hidden.value.has(group.group))
        .map((group) => {
            const count = group.names.length;
            const long = count > 4;
            return { ...group, long, width: long ? '100%' : count === 1 ? '33.333%' : '50%' };
        }),
);

const heading = computed(() => (props.entourage.entourage_heading ?? '').trim());
const families = computed(() => (props.entourage.entourage_families === false ? '' : (props.card.entourage?.families ?? '').trim()));
const showHashtag = computed(() => props.hashtag && !!props.card.hashtag);

// Shrink the content (never enlarge it) so it fits inside the paper's margins.
async function fit() {
    await nextTick();
    const el = content.value;
    if (!el) return;
    scale.value = Math.min(1, (props.height - PADDING * 2) / el.offsetHeight, (props.width - PADDING * 2) / el.offsetWidth);
}

/** Resolves when the fonts are in place and the content is fitted: call before making the PDF. */
async function ready() {
    await nextTick();
    await document.fonts.ready;
    await fit();
}

watch(() => [props.width, props.height, props.hashtag, props.background, props.card, JSON.stringify(props.look), JSON.stringify(props.entourage)], ready);
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
                <h2 v-if="heading" class="text-balance font-script font-normal leading-[1.1]" :style="text('names', 58, { names: true, key: 'entourage_heading' })">{{ heading }}</h2>
                <p v-if="families" class="mt-2 font-script leading-tight" :style="text('accent', 34, { key: 'entourage_families' })">{{ families }}</p>

                <div class="mt-6 flex flex-wrap justify-center gap-y-5">
                    <div v-for="group in groups" :key="group.group" class="px-2" :style="{ width: group.width }">
                        <p class="font-script leading-tight" :style="text('names', 28, { key: 'entourage_groups' })">{{ group.group }}</p>
                        <ul class="mt-1 space-y-0.5" :style="group.long ? { columnCount: columns, columnGap: '28px' } : {}">
                            <li v-for="(name, i) in group.names" :key="i" class="break-inside-avoid font-serif leading-snug" :style="text('details', 17, { key: 'entourage_names' })">
                                {{ name }}
                            </li>
                        </ul>
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
