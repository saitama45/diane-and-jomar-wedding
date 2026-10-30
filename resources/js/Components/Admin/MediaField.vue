<script setup>
import { ref } from 'vue';
import LineIcon from '@/Components/LineIcon.vue';

const model = defineModel({ type: String, default: '' });

const props = defineProps({
    kind: { type: String, default: 'image' }, // image | audio
    id: { type: String, default: undefined },
});

const input = ref();
const uploading = ref(false);
const error = ref('');

function xsrfToken() {
    const cookie = document.cookie.split('; ').find((c) => c.startsWith('XSRF-TOKEN='));
    return cookie ? decodeURIComponent(cookie.split('=')[1]) : '';
}

async function upload(event) {
    const file = event.target.files?.[0];
    if (!file) return;

    uploading.value = true;
    error.value = '';
    const body = new FormData();
    body.append('file', file);
    body.append('kind', props.kind);

    try {
        const response = await fetch('/admin/upload', {
            method: 'POST',
            body,
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-XSRF-TOKEN': xsrfToken() },
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.errors?.file?.[0] ?? data.message ?? 'Upload failed.');
        model.value = data.url;
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
            <input :id="id" v-model="model" type="text" class="admin-input" :placeholder="kind === 'image' ? '/images/… or https://…' : 'https://…/song.mp3'" />
            <div class="flex flex-wrap items-center gap-3">
                <button type="button" class="admin-btn-ghost" :disabled="uploading" @click="input.click()">
                    <LineIcon name="upload" :size="15" /> {{ uploading ? 'Uploading…' : `Upload ${kind}` }}
                </button>
                <audio v-if="kind === 'audio' && model" :src="model" controls class="h-9 max-w-full"></audio>
            </div>
            <p v-if="error" class="text-sm text-red-700">{{ error }}</p>
            <input ref="input" type="file" class="hidden" :accept="kind === 'image' ? 'image/*' : 'audio/*'" @change="upload" />
        </div>
    </div>
</template>
