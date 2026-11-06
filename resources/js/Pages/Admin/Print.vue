<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed, provide } from 'vue';
import LineIcon from '@/Components/LineIcon.vue';
import ToggleSwitch from '@/Components/Admin/ToggleSwitch.vue';
import SaveTheDateCard from '@/Components/Print/SaveTheDateCard.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { designSize, printDpi } from '@/lib/pdf';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    papers: { type: Array, required: true }, // presets: key, label, note, width, height (mm, portrait)
    limits: { type: Object, required: true }, // custom size limits in mm
    options: { type: Object, required: true }, // the saved choices
    card: { type: Object, required: true },
});

provide('decorImage', computed(() => props.card.decorImage));

const form = useForm({ ...props.options, width: String(props.options.width), height: String(props.options.height) });

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

// Opens the PDF in a new tab straight away (inside the click, so it is not blocked) and remembers the choices.
function openPdf() {
    if (sizeError.value) return;
    const query = new URLSearchParams({
        paper: form.paper,
        orientation: form.orientation,
        width: form.width,
        height: form.height,
        unit: form.unit,
        background: form.background,
        qr: form.qr ? '1' : '0',
    });
    window.open(`/admin/print/save-the-date?${query}`, '_blank');
    form.put('/admin/print', { preserveScroll: true });
}
</script>

<template>
    <Head title="Print Cards" />

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-serif text-4xl text-ink">Print Cards</h1>
            <p class="mt-1 max-w-2xl text-sm text-ink-soft">
                A save-the-date card to print, made from your names, wedding date and venues. Choose the paper, then open it as a PDF in a new tab.
            </p>
        </div>
        <button type="button" class="admin-btn" :disabled="!!sizeError" @click="openPdf">
            <LineIcon name="printer" :size="16" /> Open PDF
        </button>
    </div>

    <div class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1fr)_auto]">
        <div class="space-y-6">
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

                <div class="mt-5 flex items-start gap-3">
                    <ToggleSwitch v-model="form.qr" label="RSVP QR code" />
                    <div>
                        <p class="text-sm font-medium text-ink">RSVP QR code</p>
                        <p class="text-xs text-ink-soft">Guests scan it to open your website and reply. It points to {{ card.siteUrl }}.</p>
                    </div>
                </div>
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
                    <SaveTheDateCard :card="card" :width="design.width" :height="design.height" :background="form.background" :qr="form.qr" />
                </div>
            </div>
        </div>
    </div>
</template>
