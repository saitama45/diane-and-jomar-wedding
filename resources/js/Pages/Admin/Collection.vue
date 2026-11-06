<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import LineIcon from '@/Components/LineIcon.vue';
import InvitationTools from '@/Components/Admin/InvitationTools.vue';
import MediaField from '@/Components/Admin/MediaField.vue';
import ToggleSwitch from '@/Components/Admin/ToggleSwitch.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { copyText } from '@/lib/copy';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    collection: { type: Object, required: true },
    items: { type: Array, default: () => [] },
    suggestions: { type: Object, default: () => ({}) },
    sharing: { type: Object, default: null }, // invitations only: website address + email tools
});

const editing = ref(null); // null = closed, 'new' or an item
const copied = ref(null);
const sendingId = ref(null);
const togglingId = ref(null);
const sectionBusy = ref(false);
const inputType = { datetime: 'datetime-local', number: 'number', email: 'email' };
const blank = () => Object.fromEntries(props.collection.fields.map((f) => [f.name, f.type === 'color' ? '#b9873f' : f.type === 'select' ? Object.keys(f.options)[0] : '']));

const form = useForm(blank());
const isNew = computed(() => editing.value === 'new');

function openForm(item = null) {
    editing.value = item ?? 'new';
    form.clearErrors();
    form.defaults(item ? Object.fromEntries(props.collection.fields.map((f) => [f.name, item[f.name] ?? ''])) : blank());
    form.reset();
}

function close() {
    editing.value = null;
}

function submit() {
    const url = `/admin/content/${props.collection.slug}`;
    const options = { preserveScroll: true, onSuccess: close };
    isNew.value ? form.post(url, options) : form.put(`${url}/${editing.value.id}`, options);
}

function remove(item) {
    if (confirm(`Remove this ${props.collection.singular}? This cannot be undone.`)) {
        router.delete(`/admin/content/${props.collection.slug}/${item.id}`, { preserveScroll: true });
    }
}

// Hide or show content on the website without deleting it: one item, or the whole section.
function setVisible(item, visible) {
    router.patch(`/admin/content/${props.collection.slug}/${item.id}/visibility`, { is_active: visible }, {
        preserveScroll: true,
        onStart: () => (togglingId.value = item.id),
        onFinish: () => (togglingId.value = null),
    });
}

function setSectionVisible(visible) {
    router.put(`/admin/content/${props.collection.slug}/section`, { visible }, {
        preserveScroll: true,
        onStart: () => (sectionBusy.value = true),
        onFinish: () => (sectionBusy.value = false),
    });
}

function sendInvite(item) {
    if (item.emailed_at && !confirm(`Send the invitation to ${item.guest_name} again?`)) return;
    router.post(`/admin/invitations/${item.id}/email`, {}, {
        preserveScroll: true,
        onStart: () => (sendingId.value = item.id),
        onFinish: () => (sendingId.value = null),
    });
}

async function copy(item) {
    if (await copyText(item[props.collection.copy])) {
        copied.value = item.id;
        setTimeout(() => (copied.value = null), 2000);
    }
}

function display(item, column) {
    const value = item[column];
    if (value && /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/.test(value)) {
        return new Date(value).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
    }
    return value ?? '—';
}
</script>

<template>
    <Head :title="collection.title" />

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-serif text-4xl text-ink">{{ collection.title }}</h1>
            <p v-if="collection.description" class="mt-1 max-w-2xl text-sm text-ink-soft">{{ collection.description }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <div v-if="collection.section" class="flex items-center gap-3 rounded-xl bg-white px-4 py-2 ring-1 ring-ink/10">
                <ToggleSwitch
                    :model-value="collection.section.visible"
                    :label="`Show ${collection.title} on the website`"
                    :disabled="sectionBusy"
                    @update:model-value="setSectionVisible"
                />
                <span class="text-sm" :class="collection.section.visible ? 'text-ink' : 'text-ink-soft'">
                    {{ collection.section.visible ? 'Shown on the website' : 'Hidden from the website' }}
                </span>
            </div>
            <button type="button" class="admin-btn" @click="openForm()">
                <LineIcon name="plus" :size="16" /> Add {{ collection.singular }}
            </button>
        </div>
    </div>

    <p v-if="collection.section && !collection.section.visible" class="mt-6 flex items-start gap-3 rounded-xl bg-clay/45 px-4 py-3 text-sm text-ink">
        <LineIcon name="eye-off" :size="18" class="mt-0.5 shrink-0 text-rust" />
        This whole section is hidden from the website. Switch it back on above; items you hid one by one stay hidden.
    </p>

    <InvitationTools v-if="sharing" :sharing="sharing" :items="items" />

    <div class="admin-card mt-8 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm" :class="sharing ? 'min-w-[1000px]' : 'min-w-[640px]'">
                <thead class="bg-linen/60 text-xs uppercase tracking-wider text-ink-soft">
                    <tr>
                        <th v-if="collection.section" class="w-20 px-4 py-3 font-medium">Visible</th>
                        <th v-for="(label, column) in collection.columns" :key="column" class="px-4 py-3 font-medium">{{ label }}</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in items" :key="item.id" class="border-t border-ink/5 hover:bg-linen/40">
                        <td v-if="collection.section" class="px-4 py-3">
                            <ToggleSwitch
                                :model-value="item.is_active"
                                :label="item.is_active ? 'Shown on the website. Click to hide.' : 'Hidden from the website. Click to show.'"
                                :disabled="togglingId === item.id"
                                @update:model-value="setVisible(item, $event)"
                            />
                        </td>
                        <td
                            v-for="(label, column) in collection.columns"
                            :key="column"
                            class="px-4 py-3 transition-opacity"
                            :class="[column === 'image' || column === 'hex' ? 'w-16' : '', collection.section && !item.is_active ? 'opacity-45' : '']"
                        >
                            <img v-if="column === 'image'" :src="item.image" alt="" class="size-12 rounded-md object-cover ring-1 ring-ink/10" />
                            <span v-else-if="column === 'hex'" class="block size-9 rounded-full ring-1 ring-ink/10" :style="{ backgroundColor: item.hex }"></span>
                            <span v-else class="line-clamp-2 text-ink">{{ display(item, column) }}</span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <button
                                v-if="sharing && item.email"
                                type="button"
                                class="admin-btn-ghost mr-2 px-2.5 py-1.5 text-xs"
                                :disabled="sendingId !== null"
                                @click="sendInvite(item)"
                            >
                                <LineIcon name="envelope" :size="14" /> {{ sendingId === item.id ? 'Sending…' : item.emailed_at ? 'Resend' : 'Send' }}
                            </button>
                            <button v-if="collection.copy" type="button" class="admin-btn-ghost mr-2 px-2.5 py-1.5 text-xs" @click="copy(item)">
                                <LineIcon :name="copied === item.id ? 'check' : 'link'" :size="14" /> {{ copied === item.id ? 'Copied' : 'Copy link' }}
                            </button>
                            <button type="button" class="inline-grid size-9 place-items-center rounded-lg text-ink-soft transition hover:bg-linen hover:text-ink" title="Edit" @click="openForm(item)">
                                <LineIcon name="edit" :size="17" />
                            </button>
                            <button type="button" class="inline-grid size-9 place-items-center rounded-lg text-ink-soft/60 transition hover:bg-rose-50 hover:text-rose-700" title="Remove" @click="remove(item)">
                                <LineIcon name="trash" :size="17" />
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p v-if="!items.length" class="p-10 text-center text-ink-soft">Nothing here yet.</p>
        </div>
    </div>

    <!-- Editor drawer -->
    <Teleport to="body">
        <Transition name="fade">
            <div v-if="editing" class="fixed inset-0 z-50 bg-film/50 backdrop-blur-sm" @click.self="close">
                <form class="absolute inset-y-0 right-0 flex w-full max-w-xl flex-col bg-[#f3eadd] shadow-2xl" @submit.prevent="submit">
                    <header class="flex items-center justify-between border-b border-ink/10 px-6 py-5">
                        <h2 class="font-serif text-2xl text-ink">{{ isNew ? 'Add' : 'Edit' }} {{ collection.singular }}</h2>
                        <button type="button" class="grid size-10 place-items-center rounded-lg hover:bg-linen" aria-label="Close" @click="close">
                            <LineIcon name="close" :size="22" />
                        </button>
                    </header>

                    <div class="flex-1 space-y-5 overflow-y-auto px-6 py-6">
                        <div v-for="field in collection.fields" :key="field.name">
                            <label :for="`f-${field.name}`" class="admin-label">{{ field.label }}</label>

                            <textarea v-if="field.type === 'textarea'" :id="`f-${field.name}`" v-model="form[field.name]" rows="5" class="admin-input"></textarea>
                            <MediaField v-else-if="field.type === 'image'" :id="`f-${field.name}`" v-model="form[field.name]" />
                            <select v-else-if="field.type === 'select'" :id="`f-${field.name}`" v-model="form[field.name]" class="admin-input">
                                <option v-for="(label, value) in field.options" :key="value" :value="value">{{ label }}</option>
                            </select>
                            <div v-else-if="field.type === 'color'" class="flex items-center gap-3">
                                <input v-model="form[field.name]" type="color" class="h-11 w-14 cursor-pointer rounded-lg border border-ink/15 bg-white p-1" />
                                <input :id="`f-${field.name}`" v-model="form[field.name]" type="text" class="admin-input font-mono uppercase" maxlength="7" />
                            </div>
                            <template v-else>
                                <input
                                    :id="`f-${field.name}`"
                                    v-model="form[field.name]"
                                    :type="inputType[field.type] ?? 'text'"
                                    :placeholder="field.placeholder"
                                    :list="field.suggest ? `list-${field.name}` : undefined"
                                    class="admin-input"
                                />
                                <datalist v-if="field.suggest" :id="`list-${field.name}`">
                                    <option v-for="option in suggestions[field.name] ?? []" :key="option" :value="option"></option>
                                </datalist>
                            </template>

                            <p v-if="field.help" class="mt-1.5 text-xs text-ink-soft">{{ field.help }}</p>
                            <p v-if="form.errors[field.name]" class="mt-1.5 text-sm text-red-700">{{ form.errors[field.name] }}</p>
                        </div>
                    </div>

                    <footer class="flex justify-end gap-3 border-t border-ink/10 px-6 py-4">
                        <button type="button" class="admin-btn-ghost" @click="close">Cancel</button>
                        <button type="submit" class="admin-btn" :disabled="form.processing">
                            <LineIcon name="check" :size="16" /> {{ form.processing ? 'Saving…' : 'Save' }}
                        </button>
                    </footer>
                </form>
            </div>
        </Transition>
    </Teleport>
</template>
