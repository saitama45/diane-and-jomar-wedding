<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import confetti from 'canvas-confetti';
import LineIcon from '@/Components/LineIcon.vue';
import { gsap, reducedMotion, vReveal } from '@/lib/motion';

const props = defineProps({
    settings: { type: Object, required: true },
    invitation: { type: Object, default: null },
    maxGuests: { type: Number, default: 2 },
    deadline: { type: String, default: null },
    closed: { type: Boolean, default: false },
});

const root = ref();
const envelope = ref();
const seats = computed(() => Number(props.invitation?.seats ?? props.maxGuests));
const previous = props.invitation?.reply ?? null;
const thanks = ref(null);

const form = useForm({
    name: previous?.name ?? props.invitation?.guest_name ?? '',
    contact: previous?.contact ?? '',
    attending: previous ? previous.attending : null,
    guests: previous?.attending ? Number(previous.guests) : seats.value,
    message: previous?.message ?? '',
    invite: props.invitation?.code ?? null,
});

const message = computed(() => (props.settings.rsvp_message ?? '').replace('{deadline}', props.deadline ?? 'the date above'));
const showForm = computed(() => !props.closed);

function step(delta) {
    form.guests = Math.min(seats.value, Math.max(1, form.guests + delta));
}

function submit() {
    form.post('/rsvp', {
        preserveScroll: true,
        onSuccess: (page) => {
            thanks.value = page.props.flash.rsvp ?? { name: form.name, attending: form.attending, guests: form.guests };
            if (thanks.value.attending) celebrate();
        },
    });
}

function editAgain() {
    thanks.value = null;
}

function celebrate() {
    if (reducedMotion()) return;

    const heart = confetti.shapeFromPath({
        path: 'M167 72c19-38 37-56 75-56 42 0 76 33 76 75 0 76-76 151-151 227-76-76-151-151-151-227 0-42 33-75 75-75 38 0 57 18 76 56z',
    });
    const colors = ['#B4935E', '#DCC59B', '#EBCFC8', '#B27A7F', '#F7F2EA'];
    const fire = (options) =>
        confetti({ particleCount: 60, spread: 70, startVelocity: 48, ticks: 280, scalar: 1.15, colors, shapes: [heart, 'circle'], zIndex: 200, ...options });

    fire({ origin: { x: 0.1, y: 0.75 }, angle: 60 });
    fire({ origin: { x: 0.9, y: 0.75 }, angle: 120 });
    setTimeout(() => fire({ origin: { x: 0.5, y: 0.45 }, spread: 140, particleCount: 90, startVelocity: 35 }), 380);
}

let ctx;

onMounted(() => {
    if (reducedMotion()) return;
    ctx = gsap.context(() => {
        gsap.timeline({ scrollTrigger: { trigger: envelope.value, start: 'top 75%', once: true } })
            .to('.env-flap-closed', { scaleY: 0, svgOrigin: '60 30', duration: 0.5, ease: 'power2.in' })
            .fromTo('.env-flap-open', { scaleY: 0 }, { scaleY: 1, svgOrigin: '60 30', duration: 0.5, ease: 'power2.out' })
            .to('.env-letter', { y: -40, duration: 1.1, ease: 'power3.out' }, '-=0.1');

        gsap.fromTo(
            '.rsvp-bg',
            { yPercent: -10 },
            { yPercent: 10, ease: 'none', scrollTrigger: { trigger: root.value, start: 'top bottom', end: 'bottom top', scrub: true } },
        );
    }, root.value);
});

onBeforeUnmount(() => ctx?.revert());
</script>

<template>
    <section id="rsvp" ref="root" class="relative overflow-hidden bg-film py-24 text-paper md:py-32">
        <img :src="settings.rsvp_image" alt="" loading="lazy" class="rsvp-bg absolute inset-0 h-[120%] w-full -translate-y-[8%] object-cover opacity-30 grayscale" />
        <div class="absolute inset-0 bg-gradient-to-br from-film via-film/80 to-[#2b1c1e]/90"></div>
        <div class="light-leak -right-[10%] top-[10%] h-[50vh] w-[40vw] bg-[#ff7a5a]/25"></div>
        <div class="light-leak -left-[10%] bottom-0 h-[40vh] w-[40vw] bg-[#c85b7a]/25 [animation-delay:-6s]"></div>

        <div class="relative mx-auto grid max-w-6xl items-center gap-14 px-5 md:px-8 lg:grid-cols-[1fr_1.1fr] lg:gap-20">
            <!-- Invitation copy -->
            <div class="text-center lg:text-left">
                <svg ref="envelope" viewBox="0 -30 120 115" class="mx-auto h-36 text-gold-light lg:mx-0" aria-hidden="true">
                    <rect x="10" y="30" width="100" height="52" fill="#1f1a18" />
                    <path class="env-flap-open" d="M10 30 60 2l50 28z" fill="#1f1a18" stroke="currentColor" stroke-width="1" stroke-linejoin="round" />
                    <g class="env-letter">
                        <rect x="20" y="34" width="80" height="46" fill="#fbf8f2" />
                        <path d="M32 46h56M32 53h40M32 60h48" stroke="#b4935e" stroke-width="1" stroke-linecap="round" />
                        <path d="M60 74s-5-3-5-6.4a2.6 2.6 0 0 1 5-1 2.6 2.6 0 0 1 5 1C65 71 60 74 60 74z" fill="#b27a7f" />
                    </g>
                    <path d="M10 30 60 62l50-32v52H10z" fill="#161312" stroke="currentColor" stroke-width="1" stroke-linejoin="round" />
                    <path d="M10 82 48 54M110 82 72 54" stroke="currentColor" stroke-width=".6" opacity=".6" />
                    <path class="env-flap-closed" d="M10 30 60 62l50-32z" fill="#1f1a18" stroke="currentColor" stroke-width="1" stroke-linejoin="round" />
                </svg>

                <p v-reveal="'fade'" class="mt-8 kicker text-gold-light">{{ settings.rsvp_kicker }}</p>
                <h2 v-reveal="{ type: 'blur', duration: 1.5 }" class="mt-2 leading-none">
                    <span class="block font-script text-5xl text-blush md:text-6xl">Kindly</span>
                    <span class="block font-serif text-7xl font-light tracking-[0.12em] md:text-8xl">RSVP</span>
                </h2>

                <div v-if="invitation" v-reveal="'up'" class="mt-8">
                    <p class="font-serif text-2xl italic">Dear {{ invitation.guest_name }},</p>
                    <p class="mt-2 text-paper/70">
                        We have reserved <span class="text-gold-light">{{ invitation.seats }} {{ invitation.seats === 1 ? 'seat' : 'seats' }}</span> in your honor.
                    </p>
                </div>
                <p v-reveal="'up'" class="mx-auto mt-6 max-w-md font-serif text-xl italic leading-relaxed text-paper/75 lg:mx-0">{{ message }}</p>
                <p v-if="previous && !thanks" class="mt-6 inline-flex items-center gap-2 border border-gold-light/40 px-4 py-2 text-sm text-gold-light">
                    <LineIcon name="check" :size="16" />
                    You already replied — you can update your answer anytime.
                </p>
            </div>

            <!-- Form / thank you -->
            <div v-reveal="{ type: 'up', duration: 1.3 }" class="relative border border-paper/15 bg-paper/[0.06] p-6 shadow-[0_40px_80px_-40px_rgb(0_0_0/0.8)] backdrop-blur-md md:p-10">
                <Transition name="fade" mode="out-in">
                    <div v-if="thanks" key="thanks" class="py-10 text-center">
                        <span class="mx-auto grid size-20 place-items-center rounded-full border border-gold-light text-gold-light">
                            <LineIcon :name="thanks.attending ? 'heart' : 'envelope'" :size="34" :stroke="1" />
                        </span>
                        <p class="mt-6 font-script text-6xl text-blush">{{ thanks.attending ? 'Thank you!' : "We'll miss you" }}</p>
                        <p class="mx-auto mt-4 max-w-sm font-serif text-xl italic leading-relaxed text-paper/80">
                            <template v-if="thanks.attending">
                                {{ thanks.name }}, we can't wait to celebrate with you — we've saved
                                {{ thanks.guests }} {{ thanks.guests === 1 ? 'seat' : 'seats' }} for your party.
                            </template>
                            <template v-else>Thank you for letting us know, {{ thanks.name }}. You will be in our hearts on our big day.</template>
                        </p>
                        <button type="button" class="btn mt-8 text-paper/80" @click="editAgain">
                            <LineIcon name="edit" :size="15" /> Change my reply
                        </button>
                    </div>

                    <div v-else-if="!showForm" key="closed" class="py-10 text-center">
                        <p class="font-script text-5xl text-blush">RSVP is closed</p>
                        <p class="mt-4 font-serif text-xl italic text-paper/75">For any changes, please reach out to the couple directly.</p>
                    </div>

                    <form v-else key="form" class="space-y-7" novalidate @submit.prevent="submit">
                        <div>
                            <label for="rsvp-name" class="kicker text-paper/60">Full name</label>
                            <input
                                id="rsvp-name"
                                v-model="form.name"
                                type="text"
                                autocomplete="name"
                                required
                                class="mt-2 w-full border-0 border-b border-paper/30 bg-transparent px-0 py-2 font-serif text-2xl text-paper placeholder:text-paper/30 focus:border-gold-light focus:outline-none"
                                placeholder="Juan Dela Cruz"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-[#ffb4a8]">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label for="rsvp-contact" class="kicker text-paper/60">Email or mobile <span class="normal-case tracking-normal">(optional)</span></label>
                            <input
                                id="rsvp-contact"
                                v-model="form.contact"
                                type="text"
                                autocomplete="email"
                                class="mt-2 w-full border-0 border-b border-paper/30 bg-transparent px-0 py-2 text-lg text-paper placeholder:text-paper/30 focus:border-gold-light focus:outline-none"
                                placeholder="you@email.com · 0917 000 0000"
                            />
                        </div>

                        <fieldset>
                            <legend class="kicker text-paper/60">Will you celebrate with us?</legend>
                            <div class="mt-3 grid grid-cols-2 gap-3">
                                <button
                                    v-for="option in [
                                        { value: true, title: 'Joyfully accepts', icon: 'heart' },
                                        { value: false, title: 'Regretfully declines', icon: 'envelope' },
                                    ]"
                                    :key="String(option.value)"
                                    type="button"
                                    role="radio"
                                    :aria-checked="form.attending === option.value"
                                    class="group flex flex-col items-center gap-2 border px-3 py-5 text-center transition duration-500"
                                    :class="
                                        form.attending === option.value
                                            ? 'border-gold-light bg-paper text-ink'
                                            : 'border-paper/25 text-paper/80 hover:border-paper/60'
                                    "
                                    @click="form.attending = option.value"
                                >
                                    <LineIcon :name="option.icon" :size="24" :class="form.attending === option.value ? 'text-wine' : ''" />
                                    <span class="font-serif text-lg leading-tight md:text-xl">{{ option.title }}</span>
                                </button>
                            </div>
                            <p v-if="form.errors.attending" class="mt-1 text-sm text-[#ffb4a8]">{{ form.errors.attending }}</p>
                        </fieldset>

                        <Transition name="fade">
                            <div v-if="form.attending" class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="kicker text-paper/60">Guests in your party</p>
                                    <p class="mt-1 text-sm text-paper/50">Up to {{ seats }} reserved {{ seats === 1 ? 'seat' : 'seats' }}</p>
                                </div>
                                <div class="flex items-center border border-paper/30">
                                    <button type="button" class="grid size-11 place-items-center transition hover:bg-paper/10 disabled:opacity-30" :disabled="form.guests <= 1" aria-label="Fewer guests" @click="step(-1)">
                                        <LineIcon name="minus" :size="16" />
                                    </button>
                                    <span class="w-10 text-center font-serif text-2xl tabular-nums">{{ form.guests }}</span>
                                    <button type="button" class="grid size-11 place-items-center transition hover:bg-paper/10 disabled:opacity-30" :disabled="form.guests >= seats" aria-label="More guests" @click="step(1)">
                                        <LineIcon name="plus" :size="16" />
                                    </button>
                                </div>
                            </div>
                        </Transition>

                        <div>
                            <label for="rsvp-message" class="kicker text-paper/60">A note for the couple <span class="normal-case tracking-normal">(optional)</span></label>
                            <textarea
                                id="rsvp-message"
                                v-model="form.message"
                                rows="3"
                                maxlength="1000"
                                class="mt-2 w-full resize-none border border-paper/20 bg-transparent p-3 font-serif text-lg italic text-paper placeholder:text-paper/30 focus:border-gold-light focus:outline-none"
                                placeholder="Your wishes, a favorite memory, a song request…"
                            ></textarea>
                        </div>

                        <button type="submit" class="btn w-full border-gold-light bg-gold-light py-4 text-film hover:bg-paper" :disabled="form.processing">
                            <LineIcon name="envelope" :size="16" />
                            {{ form.processing ? 'Sending…' : 'Send my reply' }}
                        </button>
                    </form>
                </Transition>
            </div>
        </div>
    </section>
</template>
