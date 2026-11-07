<script setup>
import { computed, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import LineIcon from '@/Components/LineIcon.vue';

const page = usePage();
const mobileOpen = ref(false);
const toast = ref(null);

const nav = [
    { href: '/admin', label: 'RSVP Monitor', icon: 'users' },
    { href: '/admin/content/invitations', label: 'Invitations', icon: 'ticket' },
    { href: '/admin/photos', label: 'Guest Photos', icon: 'camera' },
    { href: '/admin/settings', label: 'Site Settings', icon: 'sliders' },
    { href: '/admin/theme', label: 'Theme', icon: 'sparkle' },
    { href: '/admin/music', label: 'Music', icon: 'music' },
    { href: '/admin/print', label: 'Print Cards', icon: 'printer' },
    { heading: 'Content' },
    { href: '/admin/content/love-story', label: 'Love Story', icon: 'heart' },
    { href: '/admin/content/events', label: 'Ceremony & Reception', icon: 'church' },
    { href: '/admin/content/program', label: 'Order of the Day', icon: 'clock' },
    { href: '/admin/content/entourage', label: 'Entourage', icon: 'users' },
    { href: '/admin/content/motif', label: 'Motif Colors', icon: 'palette' },
    { href: '/admin/content/attire', label: 'Attire Samples', icon: 'hanger' },
    { href: '/admin/content/gallery', label: 'Gallery', icon: 'image' },
    { href: '/admin/content/gifts', label: 'Gift Registry', icon: 'gift' },
    { href: '/admin/content/faqs', label: 'FAQs', icon: 'question' },
];

const path = computed(() => page.url.split('?')[0]);
const isActive = (href) => (href === '/admin' ? path.value === '/admin' : path.value.startsWith(href));

let timer;
watch(
    () => page.props.flash?.success,
    (message) => {
        if (!message) return;
        toast.value = message;
        clearTimeout(timer);
        timer = setTimeout(() => (toast.value = null), 3200);
    },
    { immediate: true },
);

watch(path, () => (mobileOpen.value = false));
</script>

<template>
    <div class="min-h-screen bg-[#f3eadd] font-sans text-ink">
        <!-- Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-40 flex w-72 flex-col bg-film text-paper transition-transform duration-500 lg:translate-x-0"
            :class="mobileOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="film-strip film-strip-still h-[26px] p-0!"></div>
            <div class="px-6 pb-4 pt-6">
                <p class="font-script text-3xl text-gold-light">{{ page.props.appName }}</p>
                <p class="kicker mt-1 text-[9px] text-paper/50">Wedding Admin</p>
            </div>
            <nav class="flex-1 overflow-y-auto px-3 pb-6">
                <template v-for="item in nav" :key="item.href ?? item.heading">
                    <p v-if="item.heading" class="kicker mb-2 mt-6 px-3 text-[9px] text-paper/40">{{ item.heading }}</p>
                    <Link
                        v-else
                        :href="item.href"
                        class="mb-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition"
                        :class="isActive(item.href) ? 'bg-paper/10 text-gold-light' : 'text-paper/70 hover:bg-paper/5 hover:text-paper'"
                    >
                        <LineIcon :name="item.icon" :size="18" />
                        {{ item.label }}
                    </Link>
                </template>
            </nav>
            <div class="space-y-1 border-t border-paper/10 p-3">
                <a href="/" target="_blank" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-paper/70 transition hover:bg-paper/5 hover:text-paper">
                    <LineIcon name="external" :size="18" /> View website
                </a>
                <Link href="/admin/logout" method="post" as="button" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-paper/70 transition hover:bg-paper/5 hover:text-paper">
                    <LineIcon name="logout" :size="18" /> Sign out
                </Link>
            </div>
        </aside>
        <div v-if="mobileOpen" class="fixed inset-0 z-30 bg-film/50 lg:hidden" @click="mobileOpen = false"></div>

        <!-- Main -->
        <div class="lg:pl-72">
            <header class="sticky top-0 z-20 flex items-center gap-3 border-b border-ink/10 bg-[#f3eadd]/90 px-5 py-3 backdrop-blur lg:hidden">
                <button type="button" class="grid size-10 place-items-center" aria-label="Open menu" @click="mobileOpen = true">
                    <LineIcon name="menu" :size="24" />
                </button>
                <p class="font-script text-2xl">{{ page.props.appName }}</p>
            </header>
            <main class="mx-auto max-w-6xl px-5 py-8 md:px-10 md:py-12">
                <slot />
            </main>
        </div>

        <Transition name="fade">
            <div v-if="toast" class="fixed bottom-6 right-6 z-50 flex items-center gap-3 rounded-xl bg-film px-5 py-3.5 text-sm text-paper shadow-2xl">
                <LineIcon name="check" :size="18" class="text-gold-light" />
                {{ toast }}
            </div>
        </Transition>
    </div>
</template>
