<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref } from 'vue';
import LineIcon from '@/Components/LineIcon.vue';
import MediaField from '@/Components/Admin/MediaField.vue';
import ToggleSwitch from '@/Components/Admin/ToggleSwitch.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { createMusicBox } from '@/lib/musicBox';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    library: { type: Array, required: true }, // suggested recordings: file, title, composer, performer, mood, length, source
    current: { type: String, default: '' }, // music_url: '' = the built-in music box
    enabled: { type: Boolean, default: true },
});

const saving = ref(false);
const isLibrary = (url) => props.library.some((track) => track.file === url);
const own = ref(props.current && !isLibrary(props.current) ? props.current : '');

const nowPlaying = computed(() => {
    if (!props.current) return { title: 'Music box', detail: 'Pachelbel’s Canon on the built-in music box' };
    const track = props.library.find((t) => t.file === props.current);
    if (track) return { title: track.title, detail: `${track.composer} · ${track.performer}` };
    return { title: 'Your own song', detail: decodeURIComponent(props.current.split('/').pop() ?? '') };
});

function choose(url) {
    stopPreview();
    router.put('/admin/music', { music_url: url }, {
        preserveScroll: true,
        onStart: () => (saving.value = true),
        onFinish: () => (saving.value = false),
    });
}

function setEnabled(enabled) {
    router.put('/admin/music', { enabled }, { preserveScroll: true });
}

// ---- Listening before choosing: one preview at a time ----

const previewing = ref(null); // a file URL, 'box' or 'own'
let player = null;

function stopPreview() {
    player?.stop();
    player = null;
    previewing.value = null;
}

async function preview(key, url) {
    if (previewing.value === key) return stopPreview();
    stopPreview();

    if (key === 'box') {
        const box = createMusicBox();
        player = { stop: () => box.destroy() };
        previewing.value = key;
        await box.play();
        return;
    }

    const audio = new Audio(url);
    audio.volume = 0.8;
    audio.addEventListener('ended', stopPreview);
    player = { stop: () => audio.pause() };
    previewing.value = key;
    try {
        await audio.play();
    } catch {
        stopPreview();
    }
}

onBeforeUnmount(stopPreview);
</script>

<template>
    <Head title="Music" />

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-serif text-4xl text-ink">Music</h1>
            <p class="mt-1 max-w-2xl text-sm text-ink-soft">
                The song that plays when guests open your invitation. They can pause it any time with the little record button.
            </p>
        </div>
        <div class="flex items-center gap-3 rounded-xl bg-white px-4 py-2 ring-1 ring-ink/10">
            <ToggleSwitch :model-value="enabled" label="Play music on the website" @update:model-value="setEnabled" />
            <span class="text-sm" :class="enabled ? 'text-ink' : 'text-ink-soft'">{{ enabled ? 'Music on' : 'Music off' }}</span>
        </div>
    </div>

    <p v-if="!enabled" class="mt-6 flex items-start gap-3 rounded-xl bg-clay/45 px-4 py-3 text-sm text-ink">
        <LineIcon name="eye-off" :size="18" class="mt-0.5 shrink-0 text-rust" />
        Music is switched off: the website plays nothing and shows no record button. Your choice below is kept for when you switch it back on.
    </p>

    <!-- Now playing -->
    <section class="mt-8 flex flex-wrap items-center gap-4 rounded-2xl bg-film px-5 py-4 text-paper">
        <span class="grid size-12 shrink-0 place-items-center rounded-full bg-paper/10 text-gold-light"><LineIcon name="music" :size="22" /></span>
        <div class="min-w-0 flex-1">
            <p class="kicker text-paper/50">On your website</p>
            <p class="truncate font-serif text-2xl">{{ nowPlaying.title }}</p>
            <p class="truncate text-sm text-paper/60">{{ nowPlaying.detail }}</p>
        </div>
        <button
            type="button"
            class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm ring-1 ring-paper/25 hover:text-gold-light"
            @click="current ? preview(current, current) : preview('box')"
        >
            <LineIcon :name="previewing && (previewing === current || (!current && previewing === 'box')) ? 'pause' : 'play'" :size="16" />
            {{ previewing && (previewing === current || (!current && previewing === 'box')) ? 'Stop' : 'Listen' }}
        </button>
    </section>

    <!-- Suggestions -->
    <section class="mt-8">
        <h2 class="font-serif text-2xl text-ink">Suggested music</h2>
        <p class="mt-1 text-sm text-ink-soft">Wedding classics, free to use on your website (public-domain recordings). Press play to listen, then choose one.</p>

        <div class="mt-4 grid gap-3 md:grid-cols-2">
            <article
                v-for="track in library"
                :key="track.file"
                class="flex items-center gap-4 rounded-2xl bg-white p-4 ring-1 transition"
                :class="current === track.file ? 'ring-2 ring-cocoa' : 'ring-ink/10'"
            >
                <button
                    type="button"
                    class="grid size-12 shrink-0 place-items-center rounded-full transition"
                    :class="previewing === track.file ? 'bg-cocoa text-paper' : 'bg-[#f3eadd] text-cocoa hover:bg-cocoa hover:text-paper'"
                    :aria-label="`${previewing === track.file ? 'Stop' : 'Listen to'} ${track.title}`"
                    @click="preview(track.file, track.file)"
                >
                    <LineIcon :name="previewing === track.file ? 'pause' : 'play'" :size="18" :class="previewing === track.file ? '' : 'translate-x-px'" />
                </button>
                <div class="min-w-0 flex-1">
                    <p class="font-serif text-xl leading-tight text-ink">{{ track.title }}</p>
                    <p class="truncate text-sm text-ink-soft">{{ track.composer }} · {{ track.performer }}</p>
                    <p class="text-xs text-ink-soft/80">{{ track.mood }} · {{ track.length }}</p>
                </div>
                <span v-if="current === track.file" class="inline-flex shrink-0 items-center gap-1 text-sm font-medium text-cocoa">
                    <LineIcon name="check" :size="16" /> Chosen
                </span>
                <button v-else type="button" class="admin-btn-ghost shrink-0" :disabled="saving" @click="choose(track.file)">Use this</button>
            </article>

            <!-- The built-in music box -->
            <article class="flex items-center gap-4 rounded-2xl bg-white p-4 ring-1 transition" :class="!current ? 'ring-2 ring-cocoa' : 'ring-ink/10'">
                <button
                    type="button"
                    class="grid size-12 shrink-0 place-items-center rounded-full transition"
                    :class="previewing === 'box' ? 'bg-cocoa text-paper' : 'bg-[#f3eadd] text-cocoa hover:bg-cocoa hover:text-paper'"
                    :aria-label="previewing === 'box' ? 'Stop the music box' : 'Listen to the music box'"
                    @click="preview('box')"
                >
                    <LineIcon :name="previewing === 'box' ? 'pause' : 'play'" :size="18" />
                </button>
                <div class="min-w-0 flex-1">
                    <p class="font-serif text-xl leading-tight text-ink">Music box</p>
                    <p class="truncate text-sm text-ink-soft">Pachelbel’s Canon, played in the browser</p>
                    <p class="text-xs text-ink-soft/80">Tinkling and light · loops</p>
                </div>
                <span v-if="!current" class="inline-flex shrink-0 items-center gap-1 text-sm font-medium text-cocoa">
                    <LineIcon name="check" :size="16" /> Chosen
                </span>
                <button v-else type="button" class="admin-btn-ghost shrink-0" :disabled="saving" @click="choose('')">Use this</button>
            </article>
        </div>
    </section>

    <!-- Own music -->
    <section class="mt-8 rounded-2xl bg-white p-5 ring-1 ring-ink/10">
        <h2 class="font-serif text-2xl text-ink">Your own song</h2>
        <p class="mt-1 text-sm text-ink-soft">
            Upload the song you love (MP3 plays on every phone; up to 30 MB), or paste a link to an .mp3 file. Then press “Use this song”.
        </p>
        <div class="mt-4">
            <MediaField id="own-music" v-model="own" kind="audio" />
        </div>
        <div class="mt-4 flex flex-wrap items-center gap-3">
            <button type="button" class="admin-btn" :disabled="saving || !own || own === current" @click="choose(own)">
                <LineIcon name="check" :size="16" /> Use this song
            </button>
            <span v-if="own && own === current" class="text-sm text-cocoa">This song is playing on your website.</span>
        </div>
        <p class="mt-3 text-xs text-ink-soft">Use a song you have the right to share online.</p>
    </section>
</template>
