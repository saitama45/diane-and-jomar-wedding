<script setup>
/*
 * A full-screen look at one generated picture (Admin > Print Cards > Preview): zoom in and out, drag to look
 * around, and download it. While `src` is empty it shows a "preparing" spinner, or `error` if that failed.
 */
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import LineIcon from '@/Components/LineIcon.vue';

const props = defineProps({
    src: { type: String, default: null },
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    filename: { type: String, required: true },
    error: { type: String, default: null },
});

const emit = defineEmits(['close']);

const ZOOM_MIN = 1;
const ZOOM_MAX = 6;

const zoom = ref(1);
const pan = ref({ x: 0, y: 0 });
const dragging = ref(false);

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
const onWheel = (event) => setZoom(zoom.value * (event.deltaY < 0 ? 1.12 : 1 / 1.12));

let start = null;

function onPointerDown(event) {
    if (zoom.value <= 1) return;
    start = { x: event.clientX, y: event.clientY, pan: { ...pan.value } };
    dragging.value = true;
    event.currentTarget.setPointerCapture?.(event.pointerId);
}

function onPointerMove(event) {
    if (start) pan.value = { x: start.pan.x + (event.clientX - start.x), y: start.pan.y + (event.clientY - start.y) };
}

function onPointerUp() {
    start = null;
    dragging.value = false;
}

function onKey(event) {
    const actions = { Escape: () => emit('close'), '+': zoomIn, '=': zoomIn, '-': zoomOut, 0: resetZoom };
    actions[event.key]?.();
}

watch(() => props.src, resetZoom);

onMounted(() => {
    window.addEventListener('keydown', onKey);
    document.documentElement.style.overflow = 'hidden';
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKey);
    document.documentElement.style.overflow = '';
});
</script>

<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-[90] flex flex-col bg-film/95 text-paper backdrop-blur-sm" role="dialog" aria-modal="true" :aria-label="`${title} preview`">
            <header class="flex flex-wrap items-center gap-x-4 gap-y-2 px-4 py-3 md:px-6">
                <div class="min-w-0 flex-1">
                    <p class="truncate font-serif text-xl">{{ title }}</p>
                    <p v-if="subtitle" class="truncate text-xs text-paper/60">{{ subtitle }}</p>
                </div>
                <div class="flex items-center gap-1">
                    <template v-if="src">
                        <button type="button" class="grid size-10 place-items-center rounded-lg hover:bg-paper/10 disabled:opacity-40" :disabled="zoom <= ZOOM_MIN" aria-label="Zoom out" title="Zoom out (−)" @click="zoomOut">
                            <LineIcon name="zoom-out" :size="20" />
                        </button>
                        <button type="button" class="min-w-14 rounded-lg px-2 py-2 text-sm tabular-nums hover:bg-paper/10" title="Fit to screen (0)" @click="resetZoom">{{ Math.round(zoom * 100) }}%</button>
                        <button type="button" class="grid size-10 place-items-center rounded-lg hover:bg-paper/10 disabled:opacity-40" :disabled="zoom >= ZOOM_MAX" aria-label="Zoom in" title="Zoom in (+)" @click="zoomIn">
                            <LineIcon name="zoom-in" :size="20" />
                        </button>
                        <a :href="src" :download="filename" class="ml-2 inline-flex items-center gap-2 rounded-lg bg-gold-light px-3 py-2 text-sm font-medium text-film hover:bg-paper">
                            <LineIcon name="download" :size="16" /> Download JPG
                        </a>
                    </template>
                    <button type="button" class="grid size-10 place-items-center rounded-lg hover:bg-paper/10" aria-label="Close" title="Close (Esc)" @click="emit('close')">
                        <LineIcon name="close" :size="22" />
                    </button>
                </div>
            </header>

            <div
                v-if="src"
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
                    :src="src"
                    :alt="title"
                    draggable="false"
                    class="absolute inset-0 m-auto max-h-full max-w-full object-contain"
                    :class="dragging ? '' : 'transition-transform duration-200'"
                    :style="{ transform: `translate(${pan.x}px, ${pan.y}px) scale(${zoom})` }"
                />
            </div>
            <div v-else class="grid flex-1 place-items-center px-6 text-center">
                <div v-if="error">
                    <p class="font-serif text-2xl">The preview could not be made.</p>
                    <p class="mt-2 text-sm text-paper/60">{{ error }}</p>
                </div>
                <div v-else>
                    <span class="mx-auto block size-10 animate-spin rounded-full border-2 border-paper/20 border-t-gold-light"></span>
                    <p class="mt-4 font-serif text-xl">Preparing your preview…</p>
                    <p class="mt-1 text-sm text-paper/60">Drawing the card at print quality, this takes a few seconds.</p>
                </div>
            </div>

            <p v-if="src" class="pb-3 text-center text-xs text-paper/45">Scroll or double-click to zoom · drag to look around · Esc to close</p>
        </div>
    </Teleport>
</template>
