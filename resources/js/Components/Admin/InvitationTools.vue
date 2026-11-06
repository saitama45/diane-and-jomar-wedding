<script setup>
import { computed, ref, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import LineIcon from '@/Components/LineIcon.vue';

const props = defineProps({
    sharing: { type: Object, required: true },
    items: { type: Array, default: () => [] },
});

const page = usePage();
const address = useForm({ site_url: props.sharing.siteUrl });
const test = useForm({ test_email: '' });
const progress = ref(null); // { done, total, failed: [] } while emailing everyone
const SEND_PAUSE_MS = 8000; // between emails when sending to everyone

// The server may normalise the address (adds https://, drops a trailing slash).
watch(
    () => props.sharing.siteUrl,
    (value) => {
        address.defaults({ site_url: value });
        address.reset();
    },
);

const liveUrl = computed(() => (props.sharing.siteUrl || props.sharing.serverUrl).replace(/\/+$/, ''));
const sampleCode = computed(() => props.items[0]?.code ?? 'CODE');
const withEmail = computed(() => props.items.filter((item) => item.email));
const unsent = computed(() => withEmail.value.filter((item) => !item.emailed_at));
const sending = computed(() => progress.value !== null && progress.value.done < progress.value.total);

function saveAddress() {
    address.put('/admin/invitations/site-url', { preserveScroll: true });
}

function sendTest() {
    test.post('/admin/invitations/email-test', { preserveScroll: true });
}

// One request per guest, in order: no queue worker needed and each result shows up as it happens.
function post(url) {
    return new Promise((resolve) => {
        let ok = false;
        router.post(url, {}, { preserveScroll: true, preserveState: true, onSuccess: () => (ok = true), onFinish: () => resolve(ok) });
    });
}

async function sendAll() {
    const queue = [...unsent.value];
    if (!queue.length || !confirm(`Email ${queue.length} ${queue.length === 1 ? 'invitation' : 'invitations'} now?`)) return;

    progress.value = { done: 0, total: queue.length, failed: [] };
    for (const [i, item] of queue.entries()) {
        if (!(await post(`/admin/invitations/${item.id}/email`))) progress.value.failed.push(item.guest_name);
        progress.value.done++;
        // A burst of near-identical emails looks like bulk mail to Gmail and to the guests' inboxes.
        if (i < queue.length - 1) await new Promise((resolve) => setTimeout(resolve, SEND_PAUSE_MS));
    }
}
</script>

<template>
    <section class="mt-8 grid gap-5 lg:grid-cols-2">
        <!-- Website address -->
        <form class="admin-card p-6" @submit.prevent="saveAddress">
            <div class="flex items-center gap-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-full bg-linen text-rust"><LineIcon name="link" :size="18" /></span>
                <div>
                    <h2 class="font-serif text-2xl leading-tight text-ink">Website address</h2>
                    <p class="text-xs text-ink-soft">Every invitation link and email uses this address.</p>
                </div>
            </div>

            <label for="site-url" class="admin-label mt-5">Address guests open</label>
            <div class="flex gap-2">
                <input id="site-url" v-model="address.site_url" type="text" inputmode="url" autocomplete="url" class="admin-input" :placeholder="sharing.serverUrl" />
                <button type="submit" class="admin-btn shrink-0" :disabled="address.processing || !address.isDirty">
                    <LineIcon name="check" :size="16" /> Save
                </button>
            </div>
            <p v-if="address.errors.site_url" class="mt-1.5 text-sm text-red-700">{{ address.errors.site_url }}</p>
            <p class="mt-3 text-xs leading-relaxed text-ink-soft">
                Links look like
                <a :href="`${liveUrl}/?invite=${sampleCode}`" target="_blank" class="break-all font-mono text-rust underline-offset-2 hover:underline">{{ liveUrl }}/?invite={{ sampleCode }}</a>.
                Change it any time, for example when the site moves to its own domain. Leave it empty to use this server.
            </p>
        </form>

        <!-- Invitation email -->
        <div class="admin-card p-6">
            <div class="flex items-center gap-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-full bg-linen text-rust"><LineIcon name="envelope" :size="18" /></span>
                <div class="min-w-0">
                    <h2 class="font-serif text-2xl leading-tight text-ink">Invitation email</h2>
                    <p class="truncate text-xs text-ink-soft">From {{ sharing.fromName }} &lt;{{ sharing.from }}&gt;</p>
                </div>
            </div>

            <p v-if="!withEmail.length" class="mt-5 text-sm text-ink-soft">
                No guest has an email address yet. Edit an invitation below and add one to send it by email.
            </p>
            <p v-else class="mt-5 text-sm text-ink-soft">
                <strong class="font-medium text-ink">{{ withEmail.length - unsent.length }} of {{ withEmail.length }}</strong>
                {{ withEmail.length === 1 ? 'guest' : 'guests' }} with an email address {{ withEmail.length === 1 ? 'has' : 'have' }} been sent their invitation<template v-if="items.length > withEmail.length">;
                    {{ items.length - withEmail.length }} {{ items.length - withEmail.length === 1 ? 'has' : 'have' }} no email yet</template>.
            </p>

            <div class="mt-4 flex flex-wrap gap-2">
                <button v-if="withEmail.length" type="button" class="admin-btn" :disabled="!unsent.length || sending" @click="sendAll">
                    <LineIcon name="envelope" :size="16" />
                    {{ unsent.length ? `Email ${unsent.length} not yet sent` : 'Everyone has been emailed' }}
                </button>
                <a href="/admin/invitations/email-preview" target="_blank" class="admin-btn-ghost"><LineIcon name="eye" :size="16" /> Preview</a>
            </div>

            <div v-if="progress" class="mt-4">
                <div class="h-1.5 overflow-hidden rounded-full bg-linen">
                    <div class="h-full rounded-full bg-gold transition-all duration-500" :style="{ width: `${(progress.done / progress.total) * 100}%` }"></div>
                </div>
                <p class="mt-1.5 text-xs text-ink-soft">
                    {{ sending ? `Sending ${progress.done + 1} of ${progress.total}… (a short pause between emails keeps them out of spam)` : `Done: ${progress.total - progress.failed.length} of ${progress.total} sent.` }}
                    <span v-if="progress.failed.length" class="text-red-700">Not sent: {{ progress.failed.join(', ') }}.</span>
                </p>
            </div>

            <form class="mt-5 border-t border-ink/10 pt-4" @submit.prevent="sendTest">
                <label for="test-email" class="admin-label">Send yourself a test</label>
                <div class="flex gap-2">
                    <input id="test-email" v-model="test.test_email" type="email" required class="admin-input" placeholder="you@example.com" />
                    <button type="submit" class="admin-btn-ghost shrink-0" :disabled="test.processing">
                        {{ test.processing ? 'Sending…' : 'Send test' }}
                    </button>
                </div>
                <p v-if="test.errors.test_email" class="mt-1.5 text-sm text-red-700">{{ test.errors.test_email }}</p>
            </form>

            <p v-if="page.props.errors?.mail" class="mt-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-800">{{ page.props.errors.mail }}</p>
            <p v-if="sharing.mailer === 'log'" class="mt-4 text-xs text-ink-soft">MAIL_MAILER is “log”: emails are written to storage/logs, not sent.</p>
        </div>
    </section>
</template>
