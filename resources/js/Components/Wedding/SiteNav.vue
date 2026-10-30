<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import LineIcon from '@/Components/LineIcon.vue';
import { gsap, lockScroll, reducedMotion, scrollToTarget } from '@/lib/motion';

const props = defineProps({
    settings: { type: Object, required: true },
    visible: { type: Boolean, default: true },
});

const links = [
    { href: '#story', label: 'Our Story' },
    { href: '#details', label: 'Details' },
    { href: '#entourage', label: 'Entourage' },
    { href: '#attire', label: 'Attire' },
    { href: '#gallery', label: 'Gallery' },
    { href: '#gifts', label: 'Gifts' },
    { href: '#faq', label: 'FAQ' },
];

const solid = ref(false);
const menuOpen = ref(false);
const menu = ref();
const initials = computed(() => [props.settings.bride_name, props.settings.groom_name].map((name) => (name ?? '').charAt(0)));

const onScroll = () => (solid.value = window.scrollY > window.innerHeight * 0.75);

onMounted(() => {
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
});

onBeforeUnmount(() => window.removeEventListener('scroll', onScroll));

async function toggleMenu(open = !menuOpen.value) {
    menuOpen.value = open;
    lockScroll(open);
    if (open && !reducedMotion()) {
        await nextTick();
        gsap.from(menu.value.querySelectorAll('.menu-item'), { y: 50, opacity: 0, duration: 0.9, stagger: 0.06, ease: 'power3.out', delay: 0.15 });
    }
}

function go(href) {
    if (menuOpen.value) toggleMenu(false);
    scrollToTarget(href);
}
</script>

<template>
    <header
        class="fixed inset-x-0 top-0 z-50 transition-all duration-700 ease-[var(--ease-film)]"
        :class="[
            solid ? 'border-b border-ink/10 bg-paper/85 py-3 text-ink backdrop-blur-md' : 'py-5 text-paper',
            visible ? 'translate-y-0 opacity-100' : 'pointer-events-none -translate-y-full opacity-0',
        ]"
    >
        <nav class="mx-auto flex max-w-7xl items-center justify-between px-5 md:px-8" aria-label="Main">
            <button type="button" class="font-serif text-2xl tracking-[0.1em]" aria-label="Back to top" @click="go(0)">
                {{ initials[0] }}<span class="mx-0.5 font-script text-xl text-gold">&amp;</span>{{ initials[1] }}
            </button>

            <ul class="hidden items-center gap-7 lg:flex">
                <li v-for="link in links" :key="link.href">
                    <a :href="link.href" class="kicker relative py-1 text-[10px] after:absolute after:inset-x-0 after:-bottom-0.5 after:h-px after:origin-right after:scale-x-0 after:bg-current after:transition-transform after:duration-500 hover:after:origin-left hover:after:scale-x-100" @click.prevent="go(link.href)">
                        {{ link.label }}
                    </a>
                </li>
            </ul>

            <div class="flex items-center gap-3">
                <a href="#rsvp" class="btn px-5 py-2.5 text-[10px]" :class="solid ? 'border-ink' : 'border-paper/70'" @click.prevent="go('#rsvp')">RSVP</a>
                <button type="button" class="grid size-10 place-items-center lg:hidden" aria-label="Open menu" @click="toggleMenu(true)">
                    <LineIcon name="menu" :size="26" />
                </button>
            </div>
        </nav>
    </header>

    <Teleport to="body">
        <Transition name="fade">
            <div v-if="menuOpen" ref="menu" class="fixed inset-0 z-[80] flex flex-col bg-film text-paper" role="dialog" aria-modal="true" aria-label="Menu">
                <div class="film-strip film-strip-still h-[26px] p-0!"></div>
                <div class="flex items-center justify-between px-5 py-4">
                    <span class="font-serif text-2xl text-gold-light">{{ initials[0] }}<span class="font-script text-xl">&amp;</span>{{ initials[1] }}</span>
                    <button type="button" class="grid size-10 place-items-center" aria-label="Close menu" @click="toggleMenu(false)">
                        <LineIcon name="close" :size="26" />
                    </button>
                </div>
                <ul class="flex flex-1 flex-col justify-center gap-2 px-8">
                    <li v-for="(link, i) in [...links, { href: '#rsvp', label: 'RSVP' }]" :key="link.href" class="menu-item">
                        <a :href="link.href" class="flex items-baseline gap-4 py-2 font-serif text-4xl font-light transition hover:text-gold-light" @click.prevent="go(link.href)">
                            <span class="font-mono text-xs text-gold-light/70">{{ String(i + 1).padStart(2, '0') }}</span>
                            {{ link.label }}
                        </a>
                    </li>
                </ul>
                <p class="menu-item px-8 pb-10 font-script text-3xl text-gold-light">{{ settings.hashtag }}</p>
                <div class="film-strip film-strip-still h-[26px] p-0!"></div>
            </div>
        </Transition>
    </Teleport>
</template>
