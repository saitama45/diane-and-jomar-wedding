<script setup>
/*
 * The page the "Capture the Love" QR code opens. Guests pick photos on their phone and send them one at a
 * time, each with a small preview made here in the browser (the admin's photo grid loads those). The
 * couple sees everything in Admin > Guest Photos.
 */
import { Head } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import LineIcon from '@/Components/LineIcon.vue';
import BohoCorner from '@/Components/Wedding/BohoCorner.vue';

const props = defineProps({
    couple: { type: Object, required: true }, // bride, groom
    hashtag: { type: String, default: '' },
    message: { type: String, default: '' },
    open: { type: Boolean, default: true },
    maxMb: { type: Number, default: 25 },
});

const NAME_KEY = 'share-photos-name';
const remembered = () => {
    try {
        return localStorage.getItem(NAME_KEY) ?? '';
    } catch {
        return '';
    }
};

const input = ref();
const items = ref([]); // { id, file, preview, status: waiting | uploading | done | error, progress, error }
const name = ref(remembered());
const note = ref('');
const busy = ref(false);
const dragging = ref(false);

const couple = computed(() => [props.couple.bride, props.couple.groom].filter(Boolean).join(' & '));
const waiting = computed(() => items.value.filter((item) => item.status === 'waiting' || item.status === 'error'));
const sent = computed(() => items.value.filter((item) => item.status === 'done').length);
const finished = computed(() => items.value.length > 0 && !busy.value && waiting.value.length === 0);

watch(name, (value) => {
    try {
        localStorage.setItem(NAME_KEY, value);
    } catch {
        // private browsing: nothing to remember
    }
});

let nextId = 1;
function add(files) {
    for (const file of files) {
        if (!file.type.startsWith('image/') && !/\.(heic|heif)$/i.test(file.name)) continue;
        const tooBig = file.size > props.maxMb * 1024 * 1024;
        items.value.push({
            id: nextId++,
            file,
            preview: URL.createObjectURL(file),
            status: tooBig ? 'error' : 'waiting',
            progress: 0,
            error: tooBig ? `Larger than ${props.maxMb} MB` : '',
            tooBig,
        });
    }
}

function pick(event) {
    add(event.target.files ?? []);
    event.target.value = '';
}

function drop(event) {
    dragging.value = false;
    add(event.dataTransfer?.files ?? []);
}

function remove(item) {
    URL.revokeObjectURL(item.preview);
    items.value = items.value.filter((i) => i !== item);
}

/** A small JPEG preview (the photo stays untouched), turned the right way up. */
async function thumbnail(file) {
    const draw = (source, width, height) => {
        const scale = Math.min(1, 480 / Math.max(width, height));
        const canvas = document.createElement('canvas');
        canvas.width = Math.round(width * scale);
        canvas.height = Math.round(height * scale);
        canvas.getContext('2d').drawImage(source, 0, 0, canvas.width, canvas.height);
        return new Promise((resolve) => canvas.toBlob((blob) => resolve(blob ? { blob, width, height } : null), 'image/jpeg', 0.82));
    };
    try {
        const bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
        const result = await draw(bitmap, bitmap.width, bitmap.height);
        bitmap.close?.();
        return result;
    } catch {
        try {
            const img = new Image();
            img.src = URL.createObjectURL(file);
            await img.decode();
            const result = await draw(img, img.naturalWidth, img.naturalHeight);
            URL.revokeObjectURL(img.src);
            return result;
        } catch {
            return null; // e.g. a HEIC file this browser cannot open: it is still uploaded, without a preview
        }
    }
}

function xsrfToken() {
    const cookie = document.cookie.split('; ').find((c) => c.startsWith('XSRF-TOKEN='));
    return cookie ? decodeURIComponent(cookie.split('=')[1]) : '';
}

const pause = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

/** Sends one photo; when many guests upload at once and the server asks to slow down, waits and tries again. */
async function send(item) {
    for (let attempt = 1; attempt <= 4; attempt++) {
        const busyServer = await sendOnce(item);
        if (!busyServer) return;
        item.error = 'Busy, retrying…';
        await pause(4000 * attempt);
    }
}

async function sendOnce(item) {
    item.status = 'uploading';
    item.progress = 0;
    item.error = '';

    const body = new FormData();
    body.append('photo', item.file, item.file.name);
    const thumb = await thumbnail(item.file);
    if (thumb) {
        body.append('thumb', thumb.blob, 'thumb.jpg');
        body.append('width', thumb.width);
        body.append('height', thumb.height);
    }
    if (name.value.trim()) body.append('name', name.value.trim());
    if (note.value.trim()) body.append('message', note.value.trim());

    return new Promise((resolve) => {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/share');
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('X-XSRF-TOKEN', xsrfToken());
        xhr.upload.onprogress = (event) => event.lengthComputable && (item.progress = Math.round((event.loaded / event.total) * 100));
        xhr.onload = () => {
            if (xhr.status === 429) {
                item.status = 'error';
                resolve(true); // too many uploads at once: try again shortly
                return;
            }
            if (xhr.status >= 200 && xhr.status < 300) {
                item.status = 'done';
                item.progress = 100;
                item.error = '';
            } else {
                let data = {};
                try {
                    data = JSON.parse(xhr.responseText);
                } catch {
                    // not JSON
                }
                item.status = 'error';
                item.error = (data.errors && Object.values(data.errors)[0]?.[0]) || data.message || (xhr.status === 413 ? 'This photo is too large.' : 'Could not upload. Try again.');
            }
            resolve();
        };
        xhr.onerror = () => {
            item.status = 'error';
            item.error = 'No connection. Try again.';
            resolve();
        };
        xhr.send(body);
    });
}

async function uploadAll() {
    busy.value = true;
    for (const item of items.value.filter((i) => (i.status === 'waiting' || i.status === 'error') && !i.tooBig)) {
        await send(item);
    }
    busy.value = false;
}

function startOver() {
    items.value.forEach((item) => URL.revokeObjectURL(item.preview));
    items.value = [];
    note.value = '';
}

onBeforeUnmount(() => items.value.forEach((item) => URL.revokeObjectURL(item.preview)));
</script>

<template>
    <Head :title="`Share your photos — ${couple}`" />

    <main class="boho-paper relative isolate min-h-dvh overflow-hidden px-5 pb-16 pt-14 text-center text-ink sm:pt-20">
        <BohoCorner corner="top-left" class="size-40! opacity-100! sm:size-64!" />
        <BohoCorner corner="bottom-right" class="fixed! size-40! opacity-100! sm:size-64!" />

        <div class="relative mx-auto max-w-xl">
            <p class="kicker text-ink-soft">Capture the love</p>
            <h1 class="mt-3 font-script text-6xl leading-tight text-caramel sm:text-7xl">Share your photos</h1>
            <p class="mt-2 font-serif text-xl text-cocoa">with {{ couple }}</p>
            <p v-if="hashtag" class="mt-3 font-serif text-lg font-semibold tracking-[0.1em] text-rust">{{ hashtag }}</p>

            <div v-if="!open" class="mt-10 rounded-2xl bg-white/70 px-6 py-8 shadow-sm ring-1 ring-ink/10">
                <p class="font-serif text-2xl text-ink">Photo sharing is closed.</p>
                <p class="mt-2 text-ink-soft">Thank you for celebrating with us!</p>
            </div>

            <template v-else>
                <p v-if="message" class="mx-auto mt-5 max-w-md font-serif text-lg italic leading-relaxed text-ink-soft">{{ message }}</p>

                <!-- Pick photos -->
                <button
                    type="button"
                    class="mt-8 flex w-full flex-col items-center gap-2 rounded-2xl border-2 border-dashed px-6 py-9 transition"
                    :class="dragging ? 'border-cocoa bg-white/80' : 'border-cocoa/35 bg-white/50 hover:border-cocoa hover:bg-white/70'"
                    :disabled="busy"
                    @click="input.click()"
                    @dragover.prevent="dragging = true"
                    @dragleave="dragging = false"
                    @drop.prevent="drop"
                >
                    <span class="grid size-14 place-items-center rounded-full bg-cocoa text-paper"><LineIcon name="camera" :size="26" /></span>
                    <span class="mt-1 font-serif text-2xl text-ink">{{ items.length ? 'Add more photos' : 'Choose photos' }}</span>
                    <span class="text-sm text-ink-soft">Pick as many as you like · up to {{ maxMb }} MB each</span>
                </button>
                <input ref="input" type="file" accept="image/*" multiple class="hidden" @change="pick" />

                <!-- Chosen photos -->
                <ul v-if="items.length" class="mt-6 grid grid-cols-3 gap-2 sm:grid-cols-4">
                    <li v-for="item in items" :key="item.id" class="relative aspect-square overflow-hidden rounded-lg bg-linen ring-1 ring-ink/10">
                        <img :src="item.preview" alt="" class="h-full w-full object-cover" />
                        <div v-if="item.status === 'uploading'" class="absolute inset-x-0 bottom-0 h-1.5 bg-ink/20">
                            <div class="h-full bg-cocoa transition-[width]" :style="{ width: `${item.progress}%` }"></div>
                        </div>
                        <span v-if="item.status === 'done'" class="absolute right-1.5 top-1.5 grid size-7 place-items-center rounded-full bg-cocoa text-paper"><LineIcon name="check" :size="15" /></span>
                        <span v-if="item.status === 'error'" class="absolute inset-x-0 bottom-0 bg-rust/90 px-1 py-1 text-[11px] leading-tight text-paper">{{ item.error }}</span>
                        <button
                            v-if="item.status === 'waiting' || (item.status === 'error' && !busy)"
                            type="button"
                            class="absolute right-1 top-1 grid size-7 place-items-center rounded-full bg-film/70 text-paper"
                            aria-label="Remove this photo"
                            @click="remove(item)"
                        >
                            <LineIcon name="close" :size="14" />
                        </button>
                    </li>
                </ul>

                <!-- Who is sending -->
                <div v-if="items.length && !finished" class="mt-6 space-y-3 text-left">
                    <label class="block">
                        <span class="text-sm text-ink-soft">Your name <span class="opacity-70">(optional)</span></span>
                        <input v-model="name" type="text" maxlength="100" class="mt-1 w-full rounded-lg border border-ink/15 bg-white/80 px-3 py-2.5 text-ink outline-none focus:border-cocoa" placeholder="So they know who took them" />
                    </label>
                    <label class="block">
                        <span class="text-sm text-ink-soft">A note for {{ couple }} <span class="opacity-70">(optional)</span></span>
                        <textarea v-model="note" rows="2" maxlength="500" class="mt-1 w-full rounded-lg border border-ink/15 bg-white/80 px-3 py-2.5 text-ink outline-none focus:border-cocoa"></textarea>
                    </label>
                </div>

                <button
                    v-if="items.length && !finished"
                    type="button"
                    class="btn btn-solid mt-6 w-full"
                    :disabled="busy || !waiting.filter((i) => !i.tooBig).length"
                    @click="uploadAll"
                >
                    <LineIcon name="upload" :size="16" />
                    {{ busy ? `Sending ${sent + 1} of ${items.filter((i) => !i.tooBig).length}…` : `Send ${waiting.filter((i) => !i.tooBig).length} ${waiting.filter((i) => !i.tooBig).length === 1 ? 'photo' : 'photos'}` }}
                </button>

                <div v-if="finished" class="mt-8 rounded-2xl bg-white/70 px-6 py-7 shadow-sm ring-1 ring-ink/10">
                    <p class="font-script text-4xl text-caramel">Thank you!</p>
                    <p class="mt-1 text-ink-soft">{{ sent }} {{ sent === 1 ? 'photo' : 'photos' }} sent to {{ couple }}.</p>
                    <div class="mt-5 flex flex-wrap justify-center gap-3">
                        <button type="button" class="btn text-ink" @click="startOver(); input.click()"><LineIcon name="camera" :size="16" /> Share more</button>
                        <a href="/" class="btn text-ink"><LineIcon name="envelope" :size="16" /> Back to the invitation</a>
                    </div>
                </div>
            </template>
        </div>
    </main>
</template>
