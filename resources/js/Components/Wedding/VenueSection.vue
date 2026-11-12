<script setup>
import BohoCorner from '@/Components/Wedding/BohoCorner.vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import LineIcon from '@/Components/LineIcon.vue';
import SectionTitle from './SectionTitle.vue';
import { gsap, reducedMotion, vReveal } from '@/lib/motion';

const props = defineProps({
    events: { type: Array, default: () => [] },
    schedule: { type: Array, default: () => [] },
});

const root = ref();
const activeMap = ref(0);
const mapVisible = ref(false);
const mapBox = ref();
const map = computed(() => props.events[activeMap.value]);

let ctx;
let observer;

onMounted(() => {
    // Load the Google Map only when it is about to scroll into view.
    observer = new IntersectionObserver(
        ([entry]) => {
            if (entry.isIntersecting) {
                mapVisible.value = true;
                observer.disconnect();
            }
        },
        { rootMargin: '400px' },
    );
    if (mapBox.value) observer.observe(mapBox.value);

    if (reducedMotion()) return;
    ctx = gsap.context(() => {
        gsap.utils.toArray('.venue-arch').forEach((arch) => {
            gsap.timeline({ scrollTrigger: { trigger: arch, start: 'top 82%', once: true } })
                .from(arch, { clipPath: 'inset(100% 0% 0% 0% round 999px 999px 0 0)', duration: 1.6, ease: 'power3.inOut' })
                .from(arch.querySelector('img'), { scale: 1.35, duration: 2.2, ease: 'power2.out' }, 0);
        });

        // The timeline is left out when the order of the day is hidden.
        if (props.schedule.length) {
            gsap.from('.timeline-line', {
                scaleY: 0,
                ease: 'none',
                scrollTrigger: { trigger: '.timeline', start: 'top 70%', end: 'bottom 60%', scrub: true },
            });
        }
        gsap.utils.toArray('.timeline-item').forEach((item) => {
            gsap.from(item.querySelectorAll('.ti-anim'), {
                y: 30,
                opacity: 0,
                duration: 1,
                stagger: 0.12,
                ease: 'power3.out',
                scrollTrigger: { trigger: item, start: 'top 80%', once: true },
            });
            gsap.from(item.querySelector('.ti-dot'), {
                scale: 0,
                duration: 0.8,
                ease: 'back.out(2)',
                scrollTrigger: { trigger: item, start: 'top 75%', once: true },
            });
        });
    }, root.value);
});

onBeforeUnmount(() => {
    observer?.disconnect();
    ctx?.revert();
});
</script>

<template>
    <section id="details" ref="root" class="relative isolate bg-paper linen py-24 md:py-32">
        <BohoCorner corner="top-left" />
        <BohoCorner corner="top-right" />
        <div class="mx-auto max-w-6xl px-5 md:px-8">
            <SectionTitle kicker="When &amp; Where" title="The Venue" />

            <div v-if="events.length" class="mt-16 grid gap-16 md:grid-cols-2 md:gap-10 lg:gap-20">
                <article v-for="event in events" :key="event.id" class="text-center">
                    <div class="venue-arch arch relative mx-auto aspect-[3/4] w-full max-w-[340px] bg-linen shadow-[0_30px_60px_-30px_rgb(58_42_32/0.5)]">
                        <img v-if="event.image" :src="event.image" :alt="event.venue" loading="lazy" class="h-full w-full object-cover" />
                        <div class="absolute inset-3 rounded-t-full border border-paper/60"></div>
                    </div>

                    <div v-reveal="{ type: 'up', stagger: 0.1 }" class="mt-8">
                        <p class="font-script text-5xl text-cocoa">{{ event.title }}</p>
                        <p class="mt-1 font-serif text-xl italic text-ink-soft">at {{ event.time }}</p>
                        <h3 class="mt-4 font-serif text-2xl uppercase tracking-[0.15em] text-ink md:text-[1.7rem]">{{ event.venue }}</h3>
                        <p class="mt-2 text-ink-soft">{{ event.address }}</p>
                        <p v-if="event.notes" class="mx-auto mt-4 max-w-sm font-serif text-lg italic text-ink-soft/90">{{ event.notes }}</p>
                        <div class="mt-7 flex flex-wrap justify-center gap-3">
                            <a :href="event.directions" target="_blank" rel="noopener" class="btn btn-solid px-6">
                                <LineIcon name="pin" :size="16" /> Directions
                            </a>
                            <a :href="event.calendar" class="btn px-6 text-ink">
                                <LineIcon name="calendar" :size="16" /> Save
                            </a>
                        </div>
                    </div>
                </article>
            </div>

            <!-- Map -->
            <div v-if="events.length" ref="mapBox" v-reveal="{ type: 'up', duration: 1.4 }" class="mt-24">
                <div class="film-strip px-3">
                    <div class="flex items-center justify-between gap-4 bg-film px-2 pb-4 text-paper">
                        <p class="font-script text-4xl">Map</p>
                        <div class="flex gap-1" role="tablist">
                            <button
                                v-for="(event, i) in events"
                                :key="event.id"
                                type="button"
                                role="tab"
                                :aria-selected="activeMap === i"
                                class="kicker border px-4 py-2 text-[10px] transition"
                                :class="activeMap === i ? 'border-gold-light bg-gold-light text-film' : 'border-paper/30 text-paper/70 hover:border-paper'"
                                @click="activeMap = i"
                            >
                                {{ event.title }}
                            </button>
                        </div>
                    </div>
                    <div class="relative aspect-[4/5] bg-linen sm:aspect-[16/8]">
                        <iframe
                            v-if="mapVisible && map"
                            :key="map.id"
                            :src="map.embed"
                            :title="`Map to ${map.venue}`"
                            class="absolute inset-0 h-full w-full sepia-[.25] saturate-[.8] transition duration-700 hover:sepia-0 hover:saturate-100"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                        ></iframe>
                    </div>
                </div>
            </div>

            <!-- Order of the day -->
            <div v-if="schedule.length" class="mt-28">
                <SectionTitle kicker="The Program" title="Order of the Day" />

                <ol class="timeline relative mx-auto mt-16 max-w-3xl">
                    <span class="timeline-line absolute bottom-0 left-6 top-0 w-px origin-top bg-gradient-to-b from-gold via-gold/60 to-transparent md:left-1/2"></span>

                    <li
                        v-for="(item, i) in schedule"
                        :key="item.id"
                        class="timeline-item relative grid grid-cols-[3rem_1fr] gap-5 pb-12 md:grid-cols-2 md:gap-16"
                    >
                        <span class="ti-dot absolute left-6 top-1 z-10 grid size-12 -translate-x-1/2 place-items-center rounded-full border border-gold bg-paper text-gold md:left-1/2">
                            <LineIcon :name="item.icon" :size="22" />
                        </span>
                        <div class="col-start-2 md:pt-2" :class="i % 2 ? 'md:col-start-2' : 'md:col-start-1 md:text-right'">
                            <p class="ti-anim font-serif text-2xl text-gold md:text-3xl">{{ item.time_label }}</p>
                            <h4 class="ti-anim mt-1 font-serif text-2xl text-ink">{{ item.title }}</h4>
                            <p v-if="item.description" class="ti-anim mt-1 text-ink-soft">{{ item.description }}</p>
                        </div>
                    </li>
                </ol>
            </div>
        </div>
    </section>
</template>
