<script setup>
import { computed } from 'vue';
import SectionTitle from './SectionTitle.vue';

const props = defineProps({
    wishes: { type: Array, default: () => [] },
});

// Two rows drifting in opposite directions; each row is doubled so the loop is seamless.
const rows = computed(() => {
    const half = Math.ceil(props.wishes.length / 2);
    const split = props.wishes.length > 3 ? [props.wishes.slice(0, half), props.wishes.slice(half)] : [props.wishes];
    return split.map((row) => {
        const filled = row.length < 4 ? [...row, ...row, ...row] : row;
        return [...filled, ...filled];
    });
});
</script>

<template>
    <section v-if="wishes.length" class="relative overflow-hidden bg-linen py-24 md:py-28">
        <div class="px-5">
            <SectionTitle kicker="From our guests" title="Love Notes" />
        </div>

        <div class="mt-14 space-y-6 [--marquee-hover:paused]">
            <div v-for="(row, r) in rows" :key="r" class="overflow-hidden">
                <div class="marquee flex w-max gap-6 px-3" :class="{ 'marquee-reverse': r % 2 }" :style="{ '--marquee-duration': `${row.length * 6}s` }">
                    <figure v-for="(wish, i) in row" :key="`${wish.id}-${i}`" class="paper-card relative w-[300px] shrink-0 px-7 pb-6 pt-10 md:w-[360px]">
                        <span class="absolute left-5 top-0 font-serif text-7xl leading-none text-blush">“</span>
                        <blockquote class="font-serif text-lg italic leading-relaxed text-ink-soft">{{ wish.message }}</blockquote>
                        <figcaption class="mt-4 font-script text-3xl text-mauve">— {{ wish.name }}</figcaption>
                    </figure>
                </div>
            </div>
        </div>
    </section>
</template>
