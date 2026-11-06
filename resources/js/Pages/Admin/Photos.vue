<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import LineIcon from '@/Components/LineIcon.vue';
import ToggleSwitch from '@/Components/Admin/ToggleSwitch.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { copyText } from '@/lib/copy';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    photos: { type: Array, default: () => [] }, // newest first: id, url, thumb_url, download_name, uploader_name, message, size, created_at
    totalSize: { type: Number, default: 0 },
    open: { type: Boolean, default: true },
    shareUrl: { type: String, required: true },
    canZip: { type: Boolean, default: true },
});

const copied = ref(false);

const megabytes = (bytes) => (bytes >= 1024 * 1024 * 1024 ? `${(bytes / 1024 ** 3).toFixed(1)} GB` : `${Math.max(0.1, bytes / 1024 ** 2).toFixed(1)} MB`);
const when = (iso) => new Date(iso).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });

function setOpen(open) {
    router.put('/admin/photos/uploads', { open }, { preserveScroll: true });
}

async function copyLink() {
    copied.value = await copyText(props.shareUrl);
    setTimeout(() => (copied.value = false), 2000);
}

function remove(photo) {
    if (!confirm('Remove this photo? It is deleted for good.')) return;
    router.delete(`/admin/photos/${photo.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            if (index.value === null) return;
            if (!props.photos.length) close();
            else index.value = Math.min(index.value, props.photos.length - 1);
        },
    });
}

// ---- The viewer: previous / next, zoom in and out, drag to look around, download ----

const index = ref(null);
const zoom = ref(1);
const pan = ref({ x: 0, y: 0 });
const dragging = ref(false);
const current = computed(() => (index.value === null ? null : props.photos[index.value]));
const ZOOM_MIN = 1;
const ZOOM_MAX = 6;

function show(i) {
    index.value = i;
    resetZoom();
    document.documentElement.style.overflow = 'hidden';
}

function close() {
    index.value = null;
    document.documentElement.style.overflow = '';
}

function go(step) {
    if (!props.photos.length) return;
    index.value = (index.value + step + props.photos.length) % props.photos.length;
    resetZoom();
}

function resetZoom() {
    zoom.value = 1;
    pan.value = { x: 0, y: 0 };
}

function setZoom(value) {
    zoom.value = Math.min(ZOOM_MAX, Math.max(ZOOM_MIN, Math.round(value * 100) / 100));
    if (zoom.value === 1) pan.value = { x: 0, y: 0 };
}

const zoomIn = () => setZoom(zoom.value * 1.25);
const zoomOut = () => setZoom(zoom.value / 1.25);

function onWheel(event) {
    setZoom(zoom.value * (event.deltaY < 0 ? 1.12 : 1 / 1.12));
}

let start = null;
function onPointerDown(event) {
    start = { x: event.clientX, y: event.clientY, pan: { ...pan.value }, time: Date.now() };
    dragging.value = zoom.value > 1;
    event.currentTarget.setPointerCapture?.(event.pointerId);
}

function onPointerMove(event) {
    if (!start || zoom.value <= 1) return;
    pan.value = { x: start.pan.x + (event.clientX - start.x), y: start.pan.y + (event.clientY - start.y) };
}

function onPointerUp(event) {
    if (start && zoom.value <= 1) {
        // A quick sideways swipe (phones) pages through the photos.
        const dx = event.clientX - start.x;
        if (Math.abs(dx) > 60 && Date.now() - start.time < 600) go(dx < 0 ? 1 : -1);
    }
    start = null;
    dragging.value = false;
}

function onKey(event) {
    if (index.value === null) return;
    const actions = { Escape: close, ArrowRight: () => go(1), ArrowLeft: () => go(-1), '+': zoomIn, '=': zoomIn, '-': zoomOut, 0: resetZoom };
    actions[event.key]?.();
}

// Load the neighbours in advance, so next / previous is instant.
watch(index, async (i) => {
    if (i === null) return;
    await nextTick();
    for (const step of [1, -1]) {
        const photo = props.photos[(i + step + props.photos.length) % props.photos.length];
        if (photo) new Image().src = photo.url;
    }
});

onMounted(() => window.addEventListener('keydown', onKey));
onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKey);
    document.documentElement.style.overflow = '';
});
</script>

<template>
    <Head title="Guest Photos" />

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-serif text-4xl text-ink">Guest Photos</h1>
            <p class="mt-1 max-w-2xl text-sm text-ink-soft">
                Photos your guests shared by scanning the QR code in “Capture the Love”.
                <span v-if="photos.length">{{ photos.length }} {{ photos.length === 1 ? 'photo' : 'photos' }} · {{ megabytes(totalSize) }}</span>
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-3 rounded-xl bg-white px-4 py-2 ring-1 ring-ink/10">
                <ToggleSwitch :model-value="open" label="Guests can upload photos" @update:model-value="setOpen" />
                <span class="text-sm" :class="open ? 'text-ink' : 'text-ink-soft'">{{ open ? 'Uploads open' : 'Uploads closed' }}</span>
            </div>
            <a v-if="canZip && photos.length" href="/admin/photos/download" class="admin-btn">
                <LineIcon name="download" :size="16" /> Download all (ZIP)
            </a>
        </div>
    </div>

    <!-- Where guests upload -->
    <div class="mt-6 flex flex-wrap items-center gap-3 rounded-2xl bg-white px-5 py-4 ring-1 ring-ink/10">
        <span class="grid size-10 shrink-0 place-items-center rounded-full bg-[#f3eadd] text-cocoa"><LineIcon name="link" :size="18" /></span>
        <div class="min-w-0 flex-1">
            <p class="text-sm font-medium text-ink">The upload page (the QR code opens it)</p>
            <p class="truncate text-sm text-ink-soft">{{ shareUrl }}</p>
        </div>
        <button type="button" class="admin-btn-ghost" @click="copyLink"><LineIcon :name="copied ? 'check' : 'copy'" :size="15" /> {{ copied ? 'Copied' : 'Copy link' }}</button>
        <a :href="shareUrl" target="_blank" rel="noopener" class="admin-btn-ghost"><LineIcon name="external" :size="15" /> Open</a>
    </div>

    <!-- Grid -->
    <div v-if="photos.length" class="mt-6 grid grid-cols-[repeat(auto-fill,minmax(150px,1fr))] gap-3">
        <figure v-for="(photo, i) in photos" :key="photo.id" class="group relative overflow-hidden rounded-xl bg-white ring-1 ring-ink/10">
            <button type="button" class="block aspect-square w-full overflow-hidden bg-linen" :aria-label="`View photo ${i + 1}`" @click="show(i)">
                <img :src="photo.thumb_url || photo.url" alt="" loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" />
            </button>
            <figcaption class="px-2.5 py-2">
                <p class="truncate text-sm text-ink">{{ photo.uploader_name || 'A guest' }}</p>
                <p class="truncate text-xs text-ink-soft">{{ when(photo.created_at) }}</p>
            </figcaption>
            <div class="absolute right-1.5 top-1.5 flex gap-1 opacity-100 transition sm:opacity-0 sm:group-hover:opacity-100">
                <a :href="photo.url" :download="photo.download_name" class="grid size-8 place-items-center rounded-full bg-white/90 text-ink shadow hover:text-cocoa" aria-label="Download this photo" title="Download">
                    <LineIcon name="download" :size="15" />
                </a>
                <button type="button" class="grid size-8 place-items-center rounded-full bg-white/90 text-ink shadow hover:text-red-700" aria-label="Remove this photo" title="Remove" @click="remove(photo)">
                    <LineIcon name="trash" :size="15" />
                </button>
            </div>
        </figure>
    </div>

    <div v-else class="mt-10 flex flex-col items-center rounded-2xl bg-white px-6 py-14 text-center ring-1 ring-ink/10">
        <span class="grid size-14 place-items-center rounded-full bg-[#f3eadd] text-cocoa"><LineIcon name="camera" :size="26" /></span>
        <p class="mt-4 font-serif text-2xl text-ink">No photos yet</p>
        <p class="mt-1 max-w-md text-sm text-ink-soft">When guests scan the QR code in “Capture the Love” on your website and send their photos, they appear here.</p>
    </div>

    <!-- Viewer -->
    <Teleport to="body">
        <Transition name="fade">
            <div v-if="current" class="fixed inset-0 z-[90] flex flex-col bg-film/95 text-paper backdrop-blur-sm" role="dialog" aria-modal="true" aria-label="Photo viewer">
                <header class="flex flex-wrap items-center gap-x-4 gap-y-2 px-4 py-3 md:px-6">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm"><span class="tabular-nums text-gold-light">{{ index + 1 }} / {{ photos.length }}</span> · {{ current.uploader_name || 'A guest' }} · <span class="text-paper/60">{{ when(current.created_at) }}</span></p>
                        <p v-if="current.message" class="truncate font-serif text-base italic text-paper/75">“{{ current.message }}”</p>
                    </div>
                    <div class="flex items-center gap-1">
                        <button type="button" class="grid size-10 place-items-center rounded-lg hover:bg-paper/10 disabled:opacity-40" :disabled="zoom <= 1" aria-label="Zoom out" title="Zoom out (−)" @click="zoomOut">
                            <LineIcon name="zoom-out" :size="20" />
                        </button>
                        <button type="button" class="min-w-14 rounded-lg px-2 py-2 text-sm tabular-nums hover:bg-paper/10" title="Fit to screen (0)" @click="resetZoom">{{ Math.round(zoom * 100) }}%</button>
                        <button type="button" class="grid size-10 place-items-center rounded-lg hover:bg-paper/10 disabled:opacity-40" :disabled="zoom >= 6" aria-label="Zoom in" title="Zoom in (+)" @click="zoomIn">
                            <LineIcon name="zoom-in" :size="20" />
                        </button>
                        <a :href="current.url" :download="current.download_name" class="ml-2 inline-flex items-center gap-2 rounded-lg bg-gold-light px-3 py-2 text-sm font-medium text-film hover:bg-paper">
                            <LineIcon name="download" :size="16" /> Download
                        </a>
                        <button type="button" class="grid size-10 place-items-center rounded-lg text-paper/70 hover:bg-paper/10 hover:text-red-300" aria-label="Remove this photo" title="Remove" @click="remove(current)">
                            <LineIcon name="trash" :size="18" />
                        </button>
                        <button type="button" class="grid size-10 place-items-center rounded-lg hover:bg-paper/10" aria-label="Close" title="Close (Esc)" @click="close">
                            <LineIcon name="close" :size="22" />
                        </button>
                    </div>
                </header>

                <div
                    class="relative flex-1 touch-none select-none overflow-hidden"
                    :class="zoom > 1 ? (dragging ? 'cursor-grabbing' : 'cursor-grab') : 'cursor-zoom-in'"
                    @wheel.prevent="onWheel"
                    @pointerdown="onPointerDown"
                    @pointermove="onPointerMove"
                    @pointerup="onPointerUp"
                    @pointercancel="onPointerUp"
                    @dblclick="zoom > 1 ? resetZoom() : setZoom(2.5)"
                >
                    <img
                        :key="current.id"
                        :src="current.url"
                        :alt="`Photo ${index + 1}`"
                        draggable="false"
                        class="absolute inset-0 m-auto max-h-full max-w-full object-contain"
                        :class="dragging ? '' : 'transition-transform duration-200'"
                        :style="{ transform: `translate(${pan.x}px, ${pan.y}px) scale(${zoom})` }"
                    />
                </div>

                <template v-if="photos.length > 1">
                    <button type="button" class="absolute left-2 top-1/2 grid size-12 -translate-y-1/2 place-items-center rounded-full bg-film/60 text-paper/80 hover:text-paper md:left-5" aria-label="Previous photo" title="Previous (←)" @click="go(-1)">
                        <LineIcon name="arrow-left" :size="26" />
                    </button>
                    <button type="button" class="absolute right-2 top-1/2 grid size-12 -translate-y-1/2 place-items-center rounded-full bg-film/60 text-paper/80 hover:text-paper md:right-5" aria-label="Next photo" title="Next (→)" @click="go(1)">
                        <LineIcon name="arrow-right" :size="26" />
                    </button>
                </template>
                <p class="pb-3 text-center text-xs text-paper/45">Scroll or double-click to zoom · drag to look around · ← → for the next photo</p>
            </div>
        </Transition>
    </Teleport>
</template>
