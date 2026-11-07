<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import LineIcon from '@/Components/LineIcon.vue';
import ThemePreview from '@/Components/Admin/ThemePreview.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

defineProps({
    themes: { type: Array, required: true }, // key, label, description
    current: { type: String, required: true },
    couple: { type: Object, required: true },
    customCorner: { type: Boolean, default: false }, // the couple uploaded their own corner flowers
});

const saving = ref(null);

function choose(theme) {
    router.put('/admin/theme', { theme }, {
        preserveScroll: true,
        onStart: () => (saving.value = theme),
        onFinish: () => (saving.value = null),
    });
}
</script>

<template>
    <Head title="Theme" />

    <div>
        <h1 class="font-serif text-4xl text-ink">Theme</h1>
        <p class="mt-1 max-w-2xl text-sm text-ink-soft">
            The flowers that decorate everything: the opening screen, every section of the website, the photo-upload page, both print cards and the invitation email.
        </p>
    </div>

    <p v-if="customCorner" class="mt-6 flex items-start gap-3 rounded-xl bg-clay/45 px-4 py-3 text-sm text-ink">
        <LineIcon name="image" :size="18" class="mt-0.5 shrink-0 text-rust" />
        You uploaded your own corner flowers (Site Settings → Decorations), so the website shows those in the corners whatever the theme. Clear that picture to use the theme's flowers.
    </p>

    <div class="mt-8 grid gap-6 md:grid-cols-2">
        <article
            v-for="theme in themes"
            :key="theme.key"
            class="overflow-hidden rounded-2xl bg-white ring-1 transition"
            :class="current === theme.key ? 'ring-2 ring-cocoa' : 'ring-ink/10'"
        >
            <ThemePreview :theme="theme.key" :couple="couple" />
            <div class="flex flex-wrap items-center gap-4 px-5 py-4">
                <div class="min-w-0 flex-1">
                    <h2 class="font-serif text-2xl text-ink">{{ theme.label }}</h2>
                    <p class="text-sm text-ink-soft">{{ theme.description }}</p>
                </div>
                <span v-if="current === theme.key" class="inline-flex shrink-0 items-center gap-1 text-sm font-medium text-cocoa">
                    <LineIcon name="check" :size="16" /> Your theme
                </span>
                <button v-else type="button" class="admin-btn shrink-0" :disabled="saving !== null" @click="choose(theme.key)">
                    {{ saving === theme.key ? 'Applying…' : 'Use this theme' }}
                </button>
            </div>
        </article>
    </div>
</template>
