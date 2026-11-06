<script setup>
import BohoCorner from '@/Components/Wedding/BohoCorner.vue';
import { ref } from 'vue';
import LineIcon from '@/Components/LineIcon.vue';
import { vReveal } from '@/lib/motion';

defineProps({
    faqs: { type: Array, default: () => [] },
});

const open = ref(0);
</script>

<template>
    <section v-if="faqs.length" id="faq" class="relative isolate bg-paper linen py-24 md:py-32">
        <BohoCorner corner="bottom-left" />
        <BohoCorner corner="bottom-right" />
        <div class="mx-auto max-w-3xl px-5 md:px-8">
            <header class="text-center">
                <h2 v-reveal="{ type: 'blur', duration: 1.5 }" class="leading-none">
                    <span class="block font-script text-6xl text-sage md:text-7xl">Your Questions,</span>
                    <span class="mt-2 block font-serif text-5xl font-light uppercase tracking-[0.15em] text-ink md:text-6xl">Answered</span>
                </h2>
            </header>

            <div v-reveal="{ type: 'up', stagger: 0.08 }" class="mt-14 border-t border-ink/15">
                <div v-for="(faq, i) in faqs" :key="faq.id" class="border-b border-ink/15">
                    <h3>
                        <button
                            type="button"
                            class="group flex w-full items-center justify-between gap-6 py-6 text-left"
                            :aria-expanded="open === i"
                            @click="open = open === i ? null : i"
                        >
                            <span class="font-serif text-xl text-ink transition group-hover:text-rust md:text-2xl">{{ faq.question }}</span>
                            <span
                                class="grid size-9 shrink-0 place-items-center rounded-full border border-gold text-gold transition duration-500"
                                :class="open === i ? 'rotate-45 bg-gold text-paper' : ''"
                            >
                                <LineIcon name="plus" :size="16" />
                            </span>
                        </button>
                    </h3>
                    <div class="grid transition-[grid-template-rows] duration-500 ease-[var(--ease-film)]" :class="open === i ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'">
                        <div class="overflow-hidden">
                            <p class="pb-7 pr-12 font-serif text-lg italic leading-relaxed text-ink-soft md:text-xl">{{ faq.answer }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
