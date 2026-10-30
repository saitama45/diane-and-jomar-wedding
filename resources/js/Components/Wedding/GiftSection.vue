<script setup>
import { onMounted, ref } from 'vue';
import QRCode from 'qrcode';
import LineIcon from '@/Components/LineIcon.vue';
import { copyText } from '@/lib/copy';
import { vReveal } from '@/lib/motion';

const props = defineProps({
    settings: { type: Object, required: true },
    // One account only: the first gift option in the admin list.
    gift: { type: Object, default: null },
});

// Sample QR code while the account has no uploaded QR image yet.
const generated = ref(null);
const copied = ref(false);

onMounted(async () => {
    if (!props.gift || props.gift.qr_image) return;
    const { label, account_name, account_number } = props.gift;
    generated.value = await QRCode.toDataURL(`${label} | ${account_name} | ${account_number}`, {
        margin: 1,
        width: 360,
        color: { dark: '#2a2421', light: '#ffffff' },
    });
});

async function copy() {
    if (await copyText(props.gift.account_number.replace(/\s+/g, ''))) {
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
    }
}
</script>

<template>
    <section id="gifts" class="relative overflow-hidden bg-cream py-24 md:py-32">
        <div class="mx-auto grid max-w-6xl items-center gap-14 px-5 md:px-8 lg:grid-cols-[0.9fr_1.1fr] lg:gap-20">
            <div v-reveal="{ type: 'clip', duration: 1.6 }" class="arch relative mx-auto aspect-[3/4] w-full max-w-sm shadow-[0_30px_60px_-30px_rgb(42_36_33/0.5)]">
                <img :src="settings.gift_image" alt="" loading="lazy" class="h-full w-full object-cover" />
                <div class="absolute inset-3 rounded-t-full border border-paper/60"></div>
            </div>

            <div class="text-center lg:text-left">
                <h2 v-reveal="{ type: 'blur', duration: 1.5 }" class="inline-flex flex-col items-start">
                    <span class="font-serif text-7xl font-light leading-none text-ink md:text-8xl">Gift</span>
                    <span class="ml-10 mt-2 font-script text-5xl leading-tight text-rose md:ml-14 md:text-6xl">registry</span>
                </h2>
                <p v-reveal="'up'" class="mx-auto mt-8 max-w-lg font-serif text-xl italic leading-relaxed text-ink-soft lg:mx-0">
                    {{ settings.gift_message }}
                </p>

                <article
                    v-if="gift"
                    v-reveal="'up'"
                    class="paper-card mx-auto mt-10 flex max-w-lg flex-col items-center gap-6 p-6 sm:flex-row sm:gap-8 sm:text-left lg:mx-0"
                >
                    <div class="relative size-44 shrink-0 bg-white p-2 ring-1 ring-ink/10">
                        <img v-if="gift.qr_image || generated" :src="gift.qr_image || generated" :alt="`${gift.label} QR code`" class="h-full w-full object-contain" />
                        <span v-if="!gift.qr_image" class="absolute -right-2 -top-2 rotate-6 bg-wine px-2 py-0.5 text-[9px] uppercase tracking-widest text-paper">Sample</span>
                    </div>
                    <div class="min-w-0">
                        <p class="kicker text-gold">{{ gift.label }}</p>
                        <p class="mt-3 font-serif text-2xl text-ink">{{ gift.account_name }}</p>
                        <button
                            type="button"
                            class="mt-1 inline-flex items-center gap-2 font-mono text-sm tracking-wider text-ink-soft transition hover:text-wine"
                            @click="copy"
                        >
                            {{ gift.account_number }}
                            <LineIcon :name="copied ? 'check' : 'copy'" :size="15" />
                        </button>
                        <p class="h-5 text-xs text-gold">{{ copied ? 'Copied to clipboard' : gift.note }}</p>
                        <p class="mt-3 text-pretty text-sm text-ink-soft">Scan the code, or tap the number to copy it.</p>
                    </div>
                </article>
            </div>
        </div>
    </section>
</template>
