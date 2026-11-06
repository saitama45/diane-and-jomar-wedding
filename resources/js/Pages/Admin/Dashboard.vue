<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, usePoll } from '@inertiajs/vue3';
import LineIcon from '@/Components/LineIcon.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    stats: { type: Object, required: true },
    rsvps: { type: Array, default: () => [] },
    checkedAt: { type: String, default: '' },
});

// Live monitor: refresh the replies every 5 seconds.
usePoll(5000, { only: ['stats', 'rsvps', 'checkedAt'] });

const filter = ref('all');
const search = ref('');
const expanded = ref(null);

// Rows that arrived since the page was opened glow briefly.
const seen = new Set(props.rsvps.map((r) => r.id));
const fresh = ref(new Set());
watch(
    () => props.rsvps,
    (rows) => {
        const added = rows.filter((r) => !seen.has(r.id)).map((r) => r.id);
        added.forEach((id) => seen.add(id));
        if (added.length) {
            fresh.value = new Set(added);
            setTimeout(() => (fresh.value = new Set()), 6000);
        }
    },
);

const rows = computed(() => {
    const term = search.value.trim().toLowerCase();
    return props.rsvps.filter((r) => {
        if (filter.value === 'yes' && !r.attending) return false;
        if (filter.value === 'no' && r.attending) return false;
        if (!term) return true;
        return [r.name, r.contact, r.message, r.invitation?.guest_name, r.invitation?.code].some((v) => v?.toLowerCase().includes(term));
    });
});

const fill = computed(() => (props.stats.seats_reserved ? Math.min(100, Math.round((props.stats.seats_confirmed / props.stats.seats_reserved) * 100)) : 0));

const cards = computed(() => [
    { label: 'Guests attending', value: props.stats.attending_guests, icon: 'users', accent: true },
    { label: 'Replies received', value: props.stats.responses, icon: 'envelope' },
    { label: 'Declined', value: props.stats.declined, icon: 'close' },
    { label: 'Invitations awaiting reply', value: props.stats.awaiting, icon: 'clock' },
]);

function toggleWish(rsvp) {
    router.patch(`/admin/rsvps/${rsvp.id}`, { show_message: !rsvp.show_message }, { preserveScroll: true });
}

function remove(rsvp) {
    if (confirm(`Remove the RSVP from ${rsvp.name}? This cannot be undone.`)) {
        router.delete(`/admin/rsvps/${rsvp.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="RSVP Monitor" />

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-serif text-4xl text-ink">RSVP Monitor</h1>
            <p class="mt-1 flex items-center gap-2 text-sm text-ink-soft">
                <span class="relative flex size-2.5">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-500 opacity-60"></span>
                    <span class="relative inline-flex size-2.5 rounded-full bg-emerald-500"></span>
                </span>
                Live · updates every 5 seconds · last checked {{ checkedAt }}
            </p>
        </div>
        <a href="/admin/rsvps/export" class="admin-btn-ghost"><LineIcon name="download" :size="16" /> Export CSV</a>
    </div>

    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div v-for="card in cards" :key="card.label" class="admin-card p-5" :class="card.accent ? 'bg-film! text-paper' : ''">
            <div class="flex items-center justify-between">
                <p class="text-sm" :class="card.accent ? 'text-paper/70' : 'text-ink-soft'">{{ card.label }}</p>
                <LineIcon :name="card.icon" :size="18" :class="card.accent ? 'text-gold-light' : 'text-gold'" />
            </div>
            <p class="mt-3 font-serif text-5xl font-light tabular-nums">{{ card.value }}</p>
        </div>
    </div>

    <div v-if="stats.seats_reserved" class="admin-card mt-4 p-5">
        <div class="flex flex-wrap items-baseline justify-between gap-2 text-sm">
            <p class="text-ink-soft">
                Invitation seats confirmed: {{ stats.seats_confirmed }} of {{ stats.seats_reserved }} reserved on {{ stats.invitations }} invitations
            </p>
            <p class="font-medium text-ink">{{ fill }}%</p>
        </div>
        <div class="mt-3 h-2 overflow-hidden rounded-full bg-linen">
            <div class="h-full rounded-full bg-gradient-to-r from-gold to-rust transition-all duration-1000" :style="{ width: `${fill}%` }"></div>
        </div>
    </div>

    <div class="admin-card mt-8 overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-ink/10 p-4">
            <div class="flex rounded-lg bg-linen p-1 text-sm">
                <button
                    v-for="option in [{ key: 'all', label: 'All' }, { key: 'yes', label: 'Attending' }, { key: 'no', label: 'Declined' }]"
                    :key="option.key"
                    type="button"
                    class="rounded-md px-3.5 py-1.5 transition"
                    :class="filter === option.key ? 'bg-white text-ink shadow-sm' : 'text-ink-soft hover:text-ink'"
                    @click="filter = option.key"
                >
                    {{ option.label }}
                </button>
            </div>
            <label class="relative w-full sm:w-72">
                <LineIcon name="search" :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-ink-soft/60" />
                <input v-model="search" type="search" placeholder="Search name, contact, code…" class="admin-input pl-9" />
            </label>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="bg-linen/60 text-xs uppercase tracking-wider text-ink-soft">
                    <tr>
                        <th class="px-4 py-3 font-medium">Guest</th>
                        <th class="px-4 py-3 font-medium">Reply</th>
                        <th class="px-4 py-3 font-medium">Message</th>
                        <th class="px-4 py-3 font-medium">Replied</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <TransitionGroup tag="tbody" name="fade">
                    <tr
                        v-for="rsvp in rows"
                        :key="rsvp.id"
                        class="border-t border-ink/5 align-top transition-colors duration-1000"
                        :class="fresh.has(rsvp.id) ? 'bg-gold/15' : 'hover:bg-linen/40'"
                    >
                        <td class="px-4 py-3.5">
                            <p class="font-medium text-ink">{{ rsvp.name }}</p>
                            <p v-if="rsvp.contact" class="text-ink-soft">{{ rsvp.contact }}</p>
                            <p v-if="rsvp.invitation" class="mt-1 inline-flex items-center gap-1 rounded bg-linen px-1.5 py-0.5 text-xs text-ink-soft">
                                <LineIcon name="ticket" :size="12" /> {{ rsvp.invitation.guest_name }} · {{ rsvp.invitation.code }}
                            </p>
                        </td>
                        <td class="px-4 py-3.5">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                                :class="rsvp.attending ? 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-600/20' : 'bg-rose-50 text-rose-800 ring-1 ring-rose-600/20'"
                            >
                                {{ rsvp.attending ? `Attending · ${rsvp.guests}` : 'Declined' }}
                            </span>
                        </td>
                        <td class="max-w-sm px-4 py-3.5 text-ink-soft">
                            <button
                                v-if="rsvp.message"
                                type="button"
                                class="text-left italic"
                                :class="expanded === rsvp.id ? '' : 'line-clamp-2'"
                                @click="expanded = expanded === rsvp.id ? null : rsvp.id"
                            >
                                “{{ rsvp.message }}”
                            </button>
                            <span v-else class="text-ink-soft/40">—</span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3.5 text-ink-soft" :title="rsvp.replied_at">{{ rsvp.replied_ago }}</td>
                        <td class="whitespace-nowrap px-4 py-3.5 text-right">
                            <button
                                v-if="rsvp.message"
                                type="button"
                                class="inline-grid size-9 place-items-center rounded-lg transition hover:bg-linen"
                                :class="rsvp.show_message ? 'text-gold' : 'text-ink-soft/50'"
                                :title="rsvp.show_message ? 'Shown on the Love Notes wall — click to hide' : 'Hidden — click to show on the Love Notes wall'"
                                @click="toggleWish(rsvp)"
                            >
                                <LineIcon :name="rsvp.show_message ? 'eye' : 'eye-off'" :size="18" />
                            </button>
                            <button type="button" class="inline-grid size-9 place-items-center rounded-lg text-ink-soft/50 transition hover:bg-rose-50 hover:text-rose-700" title="Remove" @click="remove(rsvp)">
                                <LineIcon name="trash" :size="17" />
                            </button>
                        </td>
                    </tr>
                </TransitionGroup>
            </table>
            <p v-if="!rows.length" class="p-10 text-center text-ink-soft">No replies match.</p>
        </div>
    </div>
</template>
