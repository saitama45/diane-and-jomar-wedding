<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed, provide } from 'vue';
import LineIcon from '@/Components/LineIcon.vue';
import ToggleSwitch from '@/Components/Admin/ToggleSwitch.vue';
import PhotoQrCard from '@/Components/Print/PhotoQrCard.vue';
import SaveTheDateCard from '@/Components/Print/SaveTheDateCard.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { designSize, printDpi } from '@/lib/pdf';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    papers: { type: Array, required: true }, // presets: key, label, note, width, height (mm, portrait)
    limits: { type: Object, required: true }, // custom size limits in mm, text size limits in %
    designs: { type: Array, required: true }, // save-the-date | photo-qr: key, label
    items: { type: Array, required: true }, // the lines whose size can be fine-tuned: key, label
    defaults: { type: Object, required: true }, // the designed sizes and colours, for "Reset"
    options: { type: Object, required: true }, // the saved choices
    card: { type: Object, required: true },
});

// Text colours: the theme's browns and golds, plus any colour from the picker.
const SWATCHES = [
    { name: 'Rust', hex: '#8e3b1f' },
    { name: 'Terracotta', hex: '#c2714f' },
    { name: 'Caramel', hex: '#a9714b' },
    { name: 'Gold', hex: '#b9873f' },
    { name: 'Cocoa', hex: '#7b5539' },
    { name: 'Espresso', hex: '#4a3226' },
    { name: 'Charcoal', hex: '#2f2a28' },
];

const COLOR_FIELDS = [
    { key: 'names_color', group: 'names', label: 'Names', note: 'Your names and the script lines' },
    { key: 'accent_color', group: 'accent', label: 'Accents', note: 'Heading, monogram, day, RSVP and hashtag' },
    { key: 'details_color', group: 'details', label: 'Details', note: 'Date, venues, addresses and small lines' },
];

// Bold / italic switches per text group (off = the designed mix of weights and styles).
const STYLE_FIELDS = COLOR_FIELDS.flatMap(({ group }) => [`${group}_bold`, `${group}_italic`]);

const SIZE_FIELDS = [
    { key: 'names_size', label: 'Names size' },
    { key: 'text_size', label: 'Text size', note: 'Everything else on the card' },
];

provide('decorImage', computed(() => props.card.decorImage));
provide('decorTheme', computed(() => props.card.theme ?? 'boho'));

const form = useForm({
    ...props.options,
    width: String(props.options.width),
    height: String(props.options.height),
    // Every line at its saved percent (100 = as designed).
    item_sizes: Object.fromEntries(props.items.map(({ key }) => [key, Number(props.options.item_sizes?.[key] ?? 100)])),
});

const isQrSign = computed(() => form.design === 'photo-qr');

// What each fine-tunable line says on this card (lines the card does not show are left out).
const itemText = computed(() => {
    const c = props.card;
    return {
        heading: c.heading,
        monogram: [c.bride, c.groom].map((n) => (n ?? '').charAt(0)).join(' & '),
        and: form.names_layout === 'stacked' ? 'and' : '&',
        date: `${c.weekday} · ${c.time} · ${c.month} ${c.year}`,
        day: c.day,
        ceremony_line: c.ceremony ? c.ceremonyLine : null,
        ceremony_venue: c.ceremony?.venue ?? c.city,
        ceremony_address: c.ceremony?.address || null,
        reception_line: c.reception ? c.receptionLine : null,
        reception_venue: c.reception?.venue ?? null,
        reception_address: c.reception?.address || null,
        reception_time: c.reception?.time ?? null,
        rsvp: 'RSVP',
        reply_by: c.deadline ? `Kindly reply by ${c.deadline}` : null,
        website: c.siteUrl.replace(/^https?:\/\//, ''),
        qr: isQrSign.value ? 'The camera and its QR code' : form.qr ? 'Scan to open your website' : null,
        hashtag: form.hashtag && c.hashtag ? c.hashtag : null,
    };
});
// The QR sign has only two adjustable lines: its QR code (with the camera) and the hashtag.
const shownItems = computed(() => props.items.filter(({ key }) => itemText.value[key] && (!isQrSign.value || ['qr', 'hashtag'].includes(key))));
const itemsChanged = computed(() => Object.values(form.item_sizes).some((percent) => Number(percent) !== 100));

function resetItems() {
    Object.keys(form.item_sizes).forEach((key) => (form.item_sizes[key] = 100));
}

// Only the lines that differ from the design travel in the link and are saved.
const itemOverrides = () => Object.fromEntries(Object.entries(form.item_sizes).filter(([, percent]) => Number(percent) !== 100).map(([key, percent]) => [key, Number(percent)]));

const isCustom = computed(() => form.paper === 'custom');

// The paper in millimetres, as the PDF will be made.
const paperMm = computed(() => {
    if (isCustom.value) {
        const toMm = form.unit === 'in' ? 25.4 : 1;
        return { width: Number(form.width) * toMm, height: Number(form.height) * toMm };
    }
    const paper = props.papers.find((p) => p.key === form.paper) ?? props.papers[0];
    return form.orientation === 'landscape' ? { width: paper.height, height: paper.width } : { width: paper.width, height: paper.height };
});

const sizeError = computed(() => {
    if (!isCustom.value) return null;
    const { width, height } = paperMm.value;
    const ok = (mm) => Number.isFinite(mm) && mm >= props.limits.min && mm <= props.limits.max;
    return ok(width) && ok(height) ? null : 'Each side must be between 50 mm (2 in) and 1000 mm (39 in).';
});

const design = computed(() => (sizeError.value ? designSize(148, 210) : designSize(paperMm.value.width, paperMm.value.height)));
const previewScale = computed(() => Math.min(380 / design.value.width, 540 / design.value.height));
const dpi = computed(() => (sizeError.value ? null : printDpi(paperMm.value.width, paperMm.value.height)));
const describe = computed(() => {
    const { width, height } = paperMm.value;
    const inches = (mm) => (mm / 25.4).toFixed(2).replace(/\.?0+$/, '');
    return `${Math.round(width * 10) / 10} × ${Math.round(height * 10) / 10} mm  ·  ${inches(width)} × ${inches(height)} in`;
});

const look = computed(() => ({
    text_size: Number(form.text_size),
    names_size: Number(form.names_size),
    accent_color: form.accent_color,
    names_color: form.names_color,
    details_color: form.details_color,
    ...Object.fromEntries(STYLE_FIELDS.map((key) => [key, !!form[key]])),
    names_layout: form.names_layout,
    item_sizes: itemOverrides(),
}));

const LOOK_KEYS = [...SIZE_FIELDS.map((f) => f.key), ...COLOR_FIELDS.map((f) => f.key), ...STYLE_FIELDS, 'names_layout'];
const lookChanged = computed(() => LOOK_KEYS.some((key) => String(form[key]).toLowerCase() !== String(props.defaults[key]).toLowerCase()) || itemsChanged.value);

function resetLook() {
    LOOK_KEYS.forEach((key) => (form[key] = props.defaults[key]));
    resetItems();
}

// Opens the PDF in a new tab straight away (inside the click, so it is not blocked) and remembers the choices.
function openPdf() {
    if (sizeError.value) return;
    const { item_sizes: overrides, ...flat } = look.value;
    const query = new URLSearchParams({
        paper: form.paper,
        orientation: form.orientation,
        width: form.width,
        height: form.height,
        unit: form.unit,
        background: form.background,
        design: form.design,
        qr: form.qr ? '1' : '0',
        hashtag: form.hashtag ? '1' : '0',
        ...Object.fromEntries(Object.entries(flat).map(([key, value]) => [key, typeof value === 'boolean' ? (value ? '1' : '0') : value])),
        ...Object.fromEntries(Object.entries(overrides).map(([key, percent]) => [`item_sizes[${key}]`, percent])),
    });
    window.open(`/admin/print/save-the-date?${query}`, '_blank');
    form.transform((data) => ({ ...data, item_sizes: overrides })).put('/admin/print', { preserveScroll: true });
}
</script>

<template>
    <Head title="Print Cards" />

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-serif text-4xl text-ink">Print Cards</h1>
            <p class="mt-1 max-w-2xl text-sm text-ink-soft">
                Cards to print, made from your names, wedding details and website. Choose the design and the paper, then open it as a PDF in a new tab.
            </p>
        </div>
        <button type="button" class="admin-btn" :disabled="!!sizeError" @click="openPdf">
            <LineIcon name="printer" :size="16" /> Open PDF
        </button>
    </div>

    <div class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1fr)_auto]">
        <div class="space-y-6">
            <!-- Which design -->
            <section class="rounded-2xl bg-white p-5 ring-1 ring-ink/10">
                <h2 class="font-serif text-2xl text-ink">Design</h2>
                <div class="mt-4 grid gap-2 sm:grid-cols-2">
                    <label
                        v-for="option in designs"
                        :key="option.key"
                        class="flex cursor-pointer items-center gap-3 rounded-xl px-4 py-3 ring-1 transition"
                        :class="form.design === option.key ? 'bg-[#f3eadd] ring-cocoa' : 'ring-ink/10 hover:ring-ink/30'"
                    >
                        <input v-model="form.design" type="radio" name="design" :value="option.key" class="sr-only" />
                        <span class="grid size-10 shrink-0 place-items-center rounded-full bg-white text-cocoa ring-1 ring-ink/10">
                            <LineIcon :name="option.key === 'photo-qr' ? 'camera' : 'envelope'" :size="18" />
                        </span>
                        <span>
                            <span class="block text-sm font-medium text-ink">{{ option.label }}</span>
                            <span class="block text-xs text-ink-soft">{{ option.key === 'photo-qr' ? 'For the tables: guests scan it to send you their photos' : 'Your names, the date and the venues' }}</span>
                        </span>
                    </label>
                </div>
            </section>

            <!-- Paper size -->
            <section class="rounded-2xl bg-white p-5 ring-1 ring-ink/10">
                <h2 class="font-serif text-2xl text-ink">Paper size</h2>
                <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3 xl:grid-cols-5">
                    <label
                        v-for="paper in [...papers, { key: 'custom', label: 'Custom', note: 'Any size' }]"
                        :key="paper.key"
                        class="cursor-pointer rounded-xl px-3 py-2.5 ring-1 transition"
                        :class="form.paper === paper.key ? 'bg-[#f3eadd] ring-cocoa' : 'ring-ink/10 hover:ring-ink/30'"
                    >
                        <input v-model="form.paper" type="radio" name="paper" :value="paper.key" class="sr-only" />
                        <span class="block text-sm font-medium text-ink">{{ paper.label }}</span>
                        <span class="block text-xs text-ink-soft">{{ paper.note }}</span>
                    </label>
                </div>

                <div v-if="isCustom" class="mt-5 grid max-w-lg grid-cols-[1fr_auto_1fr_auto] items-end gap-3">
                    <div>
                        <label for="p-width" class="admin-label">Width</label>
                        <input id="p-width" v-model="form.width" type="number" min="0" step="0.1" class="admin-input" />
                    </div>
                    <span class="pb-3 text-ink-soft">×</span>
                    <div>
                        <label for="p-height" class="admin-label">Height</label>
                        <input id="p-height" v-model="form.height" type="number" min="0" step="0.1" class="admin-input" />
                    </div>
                    <div>
                        <label for="p-unit" class="admin-label">Unit</label>
                        <select id="p-unit" v-model="form.unit" class="admin-input">
                            <option value="in">inches</option>
                            <option value="mm">mm</option>
                        </select>
                    </div>
                </div>

                <div v-else class="mt-5 inline-flex rounded-xl bg-[#f3eadd] p-1 text-sm">
                    <button
                        v-for="orientation in ['portrait', 'landscape']"
                        :key="orientation"
                        type="button"
                        class="rounded-lg px-4 py-1.5 capitalize transition"
                        :class="form.orientation === orientation ? 'bg-white text-ink shadow-sm' : 'text-ink-soft hover:text-ink'"
                        @click="form.orientation = orientation"
                    >
                        {{ orientation }}
                    </button>
                </div>

                <p v-if="sizeError" class="mt-3 text-sm text-red-700">{{ sizeError }}</p>
                <p v-else class="mt-3 text-sm text-ink-soft">{{ describe }} · printed at {{ dpi }} dpi</p>
                <p v-for="field in ['paper', 'width', 'height']" v-show="form.errors[field]" :key="field" class="mt-1 text-sm text-red-700">{{ form.errors[field] }}</p>
            </section>

            <!-- Look -->
            <section class="rounded-2xl bg-white p-5 ring-1 ring-ink/10">
                <h2 class="font-serif text-2xl text-ink">Look</h2>
                <div class="mt-4 grid gap-2 sm:grid-cols-2">
                    <label
                        v-for="option in [
                            { key: 'cream', label: 'Cream paper', note: 'Prints the cream colour, like the website.' },
                            { key: 'white', label: 'White', note: 'For vellum, kraft or coloured card stock.' },
                        ]"
                        :key="option.key"
                        class="cursor-pointer rounded-xl px-4 py-3 ring-1 transition"
                        :class="form.background === option.key ? 'bg-[#f3eadd] ring-cocoa' : 'ring-ink/10 hover:ring-ink/30'"
                    >
                        <input v-model="form.background" type="radio" name="background" :value="option.key" class="sr-only" />
                        <span class="block text-sm font-medium text-ink">{{ option.label }}</span>
                        <span class="block text-xs text-ink-soft">{{ option.note }}</span>
                    </label>
                </div>

                <div v-if="!isQrSign" class="mt-5 flex items-start gap-3">
                    <ToggleSwitch v-model="form.qr" label="RSVP QR code" />
                    <div>
                        <p class="text-sm font-medium text-ink">RSVP QR code</p>
                        <p class="text-xs text-ink-soft">Guests scan it to open your website and reply. It points to {{ card.siteUrl }}.</p>
                    </div>
                </div>

                <div class="mt-4 flex items-start gap-3">
                    <ToggleSwitch v-model="form.hashtag" label="Wedding hashtag" :disabled="!card.hashtag" />
                    <div>
                        <p class="text-sm font-medium text-ink">Wedding hashtag <span v-if="card.hashtag" class="font-normal text-ink-soft">· {{ card.hashtag }}</span></p>
                        <p class="text-xs text-ink-soft">
                            {{ card.hashtag ? 'Printed at the bottom of the card.' : 'No hashtag yet: add one in Site Settings → The Couple → Wedding hashtag.' }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- Text size and colour -->
            <section class="rounded-2xl bg-white p-5 ring-1 ring-ink/10">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="font-serif text-2xl text-ink">Text</h2>
                    <button v-if="lookChanged" type="button" class="text-sm text-ink-soft underline-offset-4 hover:text-rust hover:underline" @click="resetLook">
                        Reset text style
                    </button>
                </div>

                <!-- How the names sit -->
                <p v-if="!isQrSign" class="mt-4 text-sm font-medium text-ink">Names</p>
                <div v-if="!isQrSign" class="mt-2 grid gap-2 sm:grid-cols-2">
                    <label
                        v-for="layout in [
                            { key: 'stacked', label: 'Stacked', lines: [card.bride, '— and —', card.groom] },
                            { key: 'line', label: 'One line', lines: [`${card.bride} & ${card.groom}`] },
                        ]"
                        :key="layout.key"
                        class="flex cursor-pointer items-center gap-4 rounded-xl px-4 py-3 ring-1 transition"
                        :class="form.names_layout === layout.key ? 'bg-[#f3eadd] ring-cocoa' : 'ring-ink/10 hover:ring-ink/30'"
                    >
                        <input v-model="form.names_layout" type="radio" name="names_layout" :value="layout.key" class="sr-only" />
                        <span class="w-28 shrink-0 text-center font-script leading-tight text-caramel">
                            <span v-for="(line, i) in layout.lines" :key="i" class="block" :class="line === '— and —' ? 'text-sm' : 'text-xl'">{{ line }}</span>
                        </span>
                        <span class="text-sm font-medium text-ink">{{ layout.label }}</span>
                    </label>
                </div>

                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div v-for="field in SIZE_FIELDS" :key="field.key">
                        <div class="flex items-baseline justify-between gap-3">
                            <label :for="`size-${field.key}`" class="admin-label mb-0">{{ field.label }}</label>
                            <span class="text-sm tabular-nums text-ink">{{ form[field.key] }}%</span>
                        </div>
                        <input
                            :id="`size-${field.key}`"
                            v-model.number="form[field.key]"
                            type="range"
                            :min="limits.minSize"
                            :max="limits.maxSize"
                            step="5"
                            class="mt-2 w-full accent-[#7b5539]"
                        />
                        <p v-if="field.note" class="text-xs text-ink-soft">{{ field.note }}</p>
                    </div>
                </div>
                <p class="mt-2 text-xs text-ink-soft">If the text no longer fits the paper, the whole card shrinks a little to keep it inside.</p>

                <div class="mt-6 space-y-4">
                    <div v-for="field in COLOR_FIELDS" :key="field.key" class="flex flex-wrap items-center gap-x-4 gap-y-2">
                        <div class="w-44">
                            <p class="text-sm font-medium text-ink">{{ field.label }}</p>
                            <p class="text-xs text-ink-soft">{{ field.note }}</p>
                        </div>
                        <div class="flex items-center gap-1">
                            <button
                                v-for="style in [
                                    { key: `${field.group}_bold`, label: 'Bold', letter: 'B', class: 'font-bold' },
                                    { key: `${field.group}_italic`, label: 'Italic', letter: 'I', class: 'italic' },
                                ]"
                                :key="style.key"
                                type="button"
                                class="grid size-8 place-items-center rounded-md font-serif text-base ring-1 transition"
                                :class="[style.class, form[style.key] ? 'bg-ink text-paper ring-ink' : 'text-ink ring-ink/15 hover:ring-ink/40']"
                                :aria-pressed="form[style.key] ? 'true' : 'false'"
                                :aria-label="`${field.label}: ${style.label.toLowerCase()}`"
                                :title="style.label"
                                @click="form[style.key] = !form[style.key]"
                            >
                                {{ style.letter }}
                            </button>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                v-for="swatch in SWATCHES"
                                :key="swatch.hex"
                                type="button"
                                class="size-7 rounded-full ring-1 ring-ink/15 transition hover:scale-110"
                                :class="form[field.key].toLowerCase() === swatch.hex ? 'ring-2 ring-offset-2 ring-ink' : ''"
                                :style="{ backgroundColor: swatch.hex }"
                                :title="swatch.name"
                                :aria-label="`${field.label}: ${swatch.name}`"
                                @click="form[field.key] = swatch.hex"
                            ></button>
                            <label class="flex items-center gap-2 rounded-lg px-2 py-1 ring-1 ring-ink/10" :title="`${field.label}: any colour`">
                                <input v-model="form[field.key]" type="color" class="size-6 cursor-pointer rounded border-0 bg-transparent p-0" :aria-label="`${field.label}: any colour`" />
                                <span class="font-mono text-xs uppercase text-ink-soft">{{ form[field.key] }}</span>
                            </label>
                        </div>
                    </div>
                </div>
                <p v-for="field in [...SIZE_FIELDS, ...COLOR_FIELDS]" v-show="form.errors[field.key]" :key="`e-${field.key}`" class="mt-1 text-sm text-red-700">{{ form.errors[field.key] }}</p>
            </section>

            <!-- Fine-tune every line -->
            <section class="rounded-2xl bg-white p-5 ring-1 ring-ink/10">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="font-serif text-2xl text-ink">Each line</h2>
                        <p class="text-sm text-ink-soft">Make any single line bigger or smaller, on top of the sizes above. 100% is the design.</p>
                    </div>
                    <button v-if="itemsChanged" type="button" class="text-sm text-ink-soft underline-offset-4 hover:text-rust hover:underline" @click="resetItems">
                        Reset every line
                    </button>
                </div>

                <div class="mt-4 divide-y divide-ink/10">
                    <div v-for="line in shownItems" :key="line.key" class="grid items-center gap-x-4 gap-y-1 py-2.5 sm:grid-cols-[minmax(0,1fr)_14rem_3.5rem]">
                        <div class="min-w-0">
                            <label :for="`item-${line.key}`" class="block text-sm font-medium text-ink">{{ line.label }}</label>
                            <p class="truncate text-xs text-ink-soft">{{ itemText[line.key] }}</p>
                        </div>
                        <input
                            :id="`item-${line.key}`"
                            v-model.number="form.item_sizes[line.key]"
                            type="range"
                            :min="limits.minItem"
                            :max="limits.maxItem"
                            step="5"
                            class="w-full accent-[#7b5539]"
                        />
                        <button
                            type="button"
                            class="justify-self-end text-sm tabular-nums"
                            :class="form.item_sizes[line.key] === 100 ? 'text-ink-soft' : 'font-medium text-cocoa hover:text-rust'"
                            :title="form.item_sizes[line.key] === 100 ? '' : 'Back to 100%'"
                            @click="form.item_sizes[line.key] = 100"
                        >
                            {{ form.item_sizes[line.key] }}%
                        </button>
                    </div>
                </div>
                <p v-if="form.errors.item_sizes" class="mt-1 text-sm text-red-700">{{ form.errors.item_sizes }}</p>
            </section>

            <section class="rounded-2xl bg-[#f3eadd] p-5 text-sm text-ink-soft">
                <p class="font-medium text-ink">Printing tips</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    <li>In the print window choose <strong>Actual size</strong> (or 100%), not “Fit to page”, and the same paper size as here.</li>
                    <li>The names, date and venues come from Site Settings and Ceremony &amp; Reception; the heading and the lines before each venue from Site Settings → Save the Date.</li>
                    <li>A print shop can take the downloaded PDF as it is.</li>
                </ul>
            </section>
        </div>

        <!-- Live preview -->
        <div class="lg:sticky lg:top-8 lg:self-start">
            <p class="admin-label">Preview</p>
            <div
                class="relative overflow-hidden shadow-[0_20px_50px_-20px_rgb(58_42_32/0.5)] ring-1 ring-ink/10"
                :style="{ width: `${design.width * previewScale}px`, height: `${design.height * previewScale}px` }"
            >
                <div class="absolute left-0 top-0 origin-top-left" :style="{ transform: `scale(${previewScale})` }">
                    <PhotoQrCard v-if="isQrSign" :card="card" :width="design.width" :height="design.height" :background="form.background" :hashtag="form.hashtag" :look="look" />
                    <SaveTheDateCard v-else :card="card" :width="design.width" :height="design.height" :background="form.background" :qr="form.qr" :hashtag="form.hashtag" :look="look" />
                </div>
            </div>
        </div>
    </div>
</template>
