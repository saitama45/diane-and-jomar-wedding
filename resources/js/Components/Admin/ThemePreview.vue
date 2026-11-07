<script setup>
/*
 * A small invitation drawn with one theme's flowers (Admin > Theme), whatever the site's current theme.
 * Always shows the drawn flowers, even when the couple uploaded their own corner artwork.
 */
import { computed, provide } from 'vue';
import BohoCorner from '@/Components/Wedding/BohoCorner.vue';
import PampasWreath from '@/Components/Wedding/PampasWreath.vue';
import SectionTitle from '@/Components/Wedding/SectionTitle.vue';

const props = defineProps({
    theme: { type: String, required: true },
    couple: { type: Object, required: true }, // bride, groom
});

provide('decorTheme', computed(() => props.theme));
provide('decorImage', computed(() => null));

const initials = computed(() => [props.couple.bride, props.couple.groom].map((name) => (name ?? '').trim().charAt(0).toUpperCase()));
</script>

<template>
    <div class="boho-paper relative isolate aspect-[3/4] overflow-hidden text-center">
        <BohoCorner corner="top-left" class="size-40! opacity-100!" />
        <BohoCorner corner="bottom-right" class="size-40! opacity-100!" />
        <template v-if="theme === 'garden'">
            <BohoCorner corner="top-right" class="size-32! opacity-100!" />
            <BohoCorner corner="bottom-left" class="size-32! opacity-100!" />
        </template>
        <div class="absolute inset-0 flex flex-col items-center justify-center px-6">
            <PampasWreath class="size-24">
                <span class="font-serif text-2xl text-rust">{{ initials[0] }}<span class="mx-0.5 font-script text-base text-caramel">&amp;</span>{{ initials[1] }}</span>
            </PampasWreath>
            <p class="mt-1 font-script text-4xl leading-tight text-caramel">{{ couple.bride }}</p>
            <p class="font-script text-lg text-cocoa">and</p>
            <p class="font-script text-4xl leading-tight text-caramel">{{ couple.groom }}</p>
            <div class="pointer-events-none mt-1 origin-top scale-75 [&_h2]:hidden [&_p]:hidden">
                <SectionTitle title=" " />
            </div>
        </div>
    </div>
</template>
