<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, provide, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import CaptureSection from '@/Components/Wedding/CaptureSection.vue';
import ClosingSection from '@/Components/Wedding/ClosingSection.vue';
import CountdownSection from '@/Components/Wedding/CountdownSection.vue';
import DressCodeSection from '@/Components/Wedding/DressCodeSection.vue';
import EntourageSection from '@/Components/Wedding/EntourageSection.vue';
import FaqSection from '@/Components/Wedding/FaqSection.vue';
import FilmStrip from '@/Components/Wedding/FilmStrip.vue';
import GallerySection from '@/Components/Wedding/GallerySection.vue';
import GiftSection from '@/Components/Wedding/GiftSection.vue';
import HeroSection from '@/Components/Wedding/HeroSection.vue';
import IntroGate from '@/Components/Wedding/IntroGate.vue';
import MusicPlayer from '@/Components/Wedding/MusicPlayer.vue';
import PetalField from '@/Components/Wedding/PetalField.vue';
import RsvpSection from '@/Components/Wedding/RsvpSection.vue';
import SaveTheDateSection from '@/Components/Wedding/SaveTheDateSection.vue';
import ScrollProgress from '@/Components/Wedding/ScrollProgress.vue';
import SiteNav from '@/Components/Wedding/SiteNav.vue';
import StorySection from '@/Components/Wedding/StorySection.vue';
import VenueSection from '@/Components/Wedding/VenueSection.vue';
import WishesSection from '@/Components/Wedding/WishesSection.vue';
import { lockScroll, ScrollTrigger, startSmoothScroll, stopSmoothScroll } from '@/lib/motion';

const props = defineProps({
    settings: { type: Object, required: true },
    wedding: { type: Object, required: true },
    rsvpDeadline: { type: String, default: null },
    rsvpClosed: { type: Boolean, default: false },
    stories: { type: Array, default: () => [] },
    events: { type: Array, default: () => [] },
    schedule: { type: Array, default: () => [] },
    entourage: { type: Array, default: () => [] },
    colors: { type: Array, default: () => [] },
    attireSamples: { type: Array, default: () => [] },
    gallery: { type: Array, default: () => [] },
    faqs: { type: Array, default: () => [] },
    gift: { type: Object, default: null },
    wishes: { type: Array, default: () => [] },
    invitation: { type: Object, default: null },
    guestName: { type: String, default: null },
    maxGuests: { type: Number, default: 2 },
    shareUrl: { type: String, default: '' },
});

// Custom corner flowers uploaded in Site Settings > Decorations replace the drawn ones (BohoCorner.vue).
provide('decorImage', computed(() => props.settings.decor_corner_image || null));
// The flower theme (Admin > Theme): BohoCorner, PampasWreath and SectionTitle draw by it.
provide('decorTheme', computed(() => props.settings.decor_theme || 'boho'));

const showIntro = ref(true);
const revealed = ref(false);
const music = ref();

const title = computed(() => `${props.settings.bride_name} & ${props.settings.groom_name} — ${props.wedding.long}`);
const photos = computed(() => props.gallery.map((photo) => photo.image));

// Content the admin hid arrives as empty lists, so those sections (and their menu links) disappear.
const hasDetails = computed(() => props.events.length > 0 || props.schedule.length > 0);
const hiddenLinks = computed(() => [
    !hasDetails.value && '#details',
    !props.entourage.length && '#entourage',
    !props.gallery.length && '#gallery',
    !props.gift && '#gifts',
    !props.faqs.length && '#faq',
].filter(Boolean));

onMounted(() => {
    startSmoothScroll();
    lockScroll(true);
    window.scrollTo(0, 0);
});

onBeforeUnmount(() => {
    lockScroll(false);
    stopSmoothScroll();
    ScrollTrigger.getAll().forEach((trigger) => trigger.kill());
});

function onOpen() {
    music.value?.play();
}

async function onReveal() {
    revealed.value = true;
    lockScroll(false);
    await nextTick();
    ScrollTrigger.refresh();
}
</script>

<template>
    <Head :title="title" />

    <IntroGate v-if="showIntro" :settings="settings" :wedding="wedding" :venue="events[0]?.venue" :guest-name="guestName" @open="onOpen" @reveal="onReveal" @done="showIntro = false" />

    <div class="grain" aria-hidden="true"></div>
    <PetalField v-if="revealed" />
    <ScrollProgress v-if="revealed" />
    <SiteNav :settings="settings" :visible="revealed" :hidden-links="hiddenLinks" />

    <main>
        <HeroSection :settings="settings" :wedding="wedding" :active="revealed" />
        <FilmStrip v-if="photos.length" :images="photos" :tilt="-1.5" class="-mt-6 relative z-10" :duration="80" />
        <StorySection :settings="settings" :stories="stories" />
        <CountdownSection :settings="settings" :wedding="wedding" :calendar-url="events[0]?.calendar" />
        <SaveTheDateSection :settings="settings" :wedding="wedding" :events="events" @film-play="music?.pause()" />
        <VenueSection v-if="hasDetails" :events="events" :schedule="schedule" />
        <EntourageSection v-if="entourage.length" :settings="settings" :entourage="entourage" :photos="photos.slice(2, 4)" />
        <DressCodeSection :settings="settings" :colors="colors" :samples="attireSamples" />
        <RsvpSection
            :settings="settings"
            :invitation="invitation"
            :max-guests="maxGuests"
            :deadline="rsvpDeadline"
            :closed="rsvpClosed"
        />
        <GallerySection v-if="gallery.length" :settings="settings" :photos="gallery" />
        <GiftSection v-if="gift" :settings="settings" :gift="gift" />
        <CaptureSection :settings="settings" :share-url="shareUrl" />
        <WishesSection :wishes="wishes" />
        <FaqSection :faqs="faqs" />
        <ClosingSection :settings="settings" :wedding="wedding" :photos="photos.slice().reverse()" />
    </main>

    <MusicPlayer v-if="settings.music_enabled !== '0'" v-show="revealed" ref="music" :src="settings.music_url ?? ''" />
</template>
