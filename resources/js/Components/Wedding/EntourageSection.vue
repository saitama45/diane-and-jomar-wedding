<script setup>
import BohoCorner from '@/Components/Wedding/BohoCorner.vue';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { gsap, reducedMotion, vReveal } from '@/lib/motion';

defineProps({
    settings: { type: Object, required: true },
    entourage: { type: Array, default: () => [] },
    photos: { type: Array, default: () => [] },
});

const root = ref();

// Big lists span the full width; single names sit three to a row; the rest pair up.
const groupWidth = (count) => (count > 4 ? 'basis-full' : count === 1 ? 'basis-1/2 md:basis-1/3' : 'basis-full md:basis-1/2');

let ctx;

onMounted(() => {
    if (reducedMotion()) return;
    ctx = gsap.context(() => {
        gsap.from('.ent-banner', {
            xPercent: -110,
            rotate: -4,
            duration: 1.8,
            ease: 'power4.out',
            scrollTrigger: { trigger: root.value, start: 'top 75%', once: true },
        });
    }, root.value);
});

onBeforeUnmount(() => ctx?.revert());
</script>

<template>
    <section id="entourage" ref="root" class="relative isolate overflow-hidden bg-sage-deep py-24 md:py-32">
        <BohoCorner corner="bottom-left" />
        <BohoCorner corner="bottom-right" />
        <!-- Title inside a strip of film -->
        <div class="ent-banner film-strip mx-auto max-w-5xl -rotate-1 px-3 shadow-[0_30px_50px_-30px_rgb(43_29_21/0.6)]">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_3fr_1fr]">
                <img v-if="photos[0]" :src="photos[0]" alt="" loading="lazy" class="hidden h-32 w-full object-cover opacity-60 grayscale sm:block" />
                <div class="grid h-24 place-items-center bg-paper sm:h-32">
                    <h2 class="whitespace-nowrap font-script text-5xl text-ink md:text-6xl lg:text-7xl">The Entourage</h2>
                </div>
                <img v-if="photos[1]" :src="photos[1]" alt="" loading="lazy" class="hidden h-32 w-full object-cover opacity-60 grayscale sm:block" />
            </div>
        </div>

        <div class="mx-auto max-w-5xl px-5 md:px-8">
            <p v-reveal="'blur'" class="mt-14 text-center font-script text-4xl text-clay md:text-5xl">{{ settings.families_line }}</p>

            <div class="mt-14 flex flex-wrap justify-center gap-y-14">
                <div
                    v-for="group in entourage"
                    :key="group.group"
                    v-reveal="{ type: 'up', duration: 1.1 }"
                    class="px-2 text-center sm:px-4"
                    :class="groupWidth(group.names.length)"
                >
                    <h3 class="font-script text-[1.7rem] leading-tight text-gold-light sm:text-[2.1rem] md:text-4xl">{{ group.group }}</h3>
                    <ul class="mt-3 space-y-1.5" :class="{ 'gap-x-10 md:columns-2': group.names.length > 4 }">
                        <li v-for="name in group.names" :key="name" class="break-inside-avoid font-serif text-lg text-paper/85 sm:text-xl md:text-[1.35rem]">
                            {{ name }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
</template>
