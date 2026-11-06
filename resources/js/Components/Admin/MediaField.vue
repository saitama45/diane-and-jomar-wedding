<script setup>
import { computed, ref } from 'vue';
import LineIcon from '@/Components/LineIcon.vue';

const model = defineModel({ type: String, default: '' });

const props = defineProps({
    kind: { type: String, default: 'image' }, // image | audio | video
    id: { type: String, default: undefined },
});

// Videos go up in pieces: the hosting refuses requests over 40 MB (see VideoUploadController).
const VIDEO_CHUNK = 8 * 1024 * 1024;
const VIDEO_MAX_MB = 250;

const ACCEPT = { image: 'image/*', audio: 'audio/*', video: 'video/mp4,video/webm,video/quicktime,.mp4,.m4v,.webm,.mov' };
const PLACEHOLDER = { image: '/images/… or https://…', audio: 'https://…/song.mp3', video: 'https://youtu.be/… or upload a video' };

const input = ref();
const uploading = ref(false);
const progress = ref(0);
const error = ref('');

const isYoutube = computed(() => /youtu\.?be/.test(model.value ?? ''));

function xsrfToken() {
    const cookie = document.cookie.split('; ').find((c) => c.startsWith('XSRF-TOKEN='));
    return cookie ? decodeURIComponent(cookie.split('=')[1]) : '';
}

async function post(url, body) {
    const response = await fetch(url, {
        method: 'POST',
        body,
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-XSRF-TOKEN': xsrfToken() },
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
        const first = data.errors && Object.values(data.errors)[0]?.[0];
        throw new Error(first ?? data.message ?? 'Upload failed.');
    }
    return data;
}

async function uploadVideo(file) {
    if (file.size > VIDEO_MAX_MB * 1024 * 1024) {
        throw new Error(`Videos can be up to ${VIDEO_MAX_MB} MB. For a longer film, upload it to YouTube and paste the link instead.`);
    }
    const upload = crypto.randomUUID();
    const extension = (file.name.split('.').pop() ?? '').toLowerCase();
    const total = Math.max(1, Math.ceil(file.size / VIDEO_CHUNK));

    for (let index = 0; index < total; index++) {
        const body = new FormData();
        body.append('upload', upload);
        body.append('index', index);
        body.append('total', total);
        body.append('extension', extension);
        body.append('chunk', file.slice(index * VIDEO_CHUNK, (index + 1) * VIDEO_CHUNK), `part-${index}`);
        const data = await post('/admin/upload/video', body);
        progress.value = Math.round(((index + 1) / total) * 100);
        if (data.done) return data.url;
    }
    throw new Error('Upload failed.');
}

async function upload(event) {
    const file = event.target.files?.[0];
    if (!file) return;

    uploading.value = true;
    progress.value = 0;
    error.value = '';

    try {
        if (props.kind === 'video') {
            model.value = await uploadVideo(file);
        } else {
            const body = new FormData();
            body.append('file', file);
            body.append('kind', props.kind);
            model.value = (await post('/admin/upload', body)).url;
        }
    } catch (e) {
        error.value = e.message;
    } finally {
        uploading.value = false;
        event.target.value = '';
    }
}
</script>

<template>
    <div class="flex gap-4">
        <div v-if="kind === 'image'" class="grid size-20 shrink-0 place-items-center overflow-hidden rounded-lg bg-linen ring-1 ring-ink/10">
            <img v-if="model" :src="model" alt="" class="h-full w-full object-cover" />
            <LineIcon v-else name="image" :size="22" class="text-ink-soft/50" />
        </div>
        <div class="min-w-0 flex-1 space-y-2">
            <input :id="id" v-model="model" type="text" class="admin-input" :placeholder="PLACEHOLDER[kind]" />
            <div class="flex flex-wrap items-center gap-3">
                <button type="button" class="admin-btn-ghost" :disabled="uploading" @click="input.click()">
                    <LineIcon name="upload" :size="15" />
                    {{ uploading ? (kind === 'video' ? `Uploading… ${progress}%` : 'Uploading…') : `Upload ${kind}` }}
                </button>
                <button v-if="model && !uploading" type="button" class="text-sm text-ink-soft underline-offset-4 hover:text-rust hover:underline" @click="model = ''">
                    Clear
                </button>
                <audio v-if="kind === 'audio' && model" :src="model" controls class="h-9 max-w-full"></audio>
            </div>
            <div v-if="kind === 'video' && uploading" class="h-1.5 overflow-hidden rounded-full bg-ink/10" role="progressbar" :aria-valuenow="progress" aria-valuemin="0" aria-valuemax="100">
                <div class="h-full rounded-full bg-cocoa transition-[width] duration-300" :style="{ width: `${progress}%` }"></div>
            </div>
            <video v-if="kind === 'video' && model && !isYoutube && !uploading" :src="model" controls preload="metadata" class="max-h-56 w-full max-w-md rounded-lg bg-film"></video>
            <p v-if="kind === 'video' && isYoutube" class="text-xs text-ink-soft">YouTube link: the film plays from YouTube on your website.</p>
            <p v-if="error" class="text-sm text-red-700">{{ error }}</p>
            <input ref="input" type="file" class="hidden" :accept="ACCEPT[kind]" @change="upload" />
        </div>
    </div>
</template>
