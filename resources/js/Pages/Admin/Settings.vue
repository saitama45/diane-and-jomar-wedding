<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import LineIcon from '@/Components/LineIcon.vue';
import MediaField from '@/Components/Admin/MediaField.vue';
import ToggleSwitch from '@/Components/Admin/ToggleSwitch.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    groups: { type: Array, required: true },
});

// datetime-local inputs need "YYYY-MM-DDTHH:MM".
const toInput = (setting) => (setting.type === 'datetime' && setting.value ? setting.value.replace(' ', 'T').slice(0, 16) : setting.value ?? '');

const form = useForm({
    values: Object.fromEntries(props.groups.flatMap((group) => group.settings.map((s) => [s.key, toInput(s)]))),
});

const submit = () => form.put('/admin/settings', { preserveScroll: true });

const inputType = { number: 'number', date: 'date', datetime: 'datetime-local', url: 'url', email: 'email' };
</script>

<template>
    <Head title="Site Settings" />

    <form @submit.prevent="submit">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-serif text-4xl text-ink">Site Settings</h1>
                <p class="mt-1 text-sm text-ink-soft">Names, dates, photos and every line of text on the website.</p>
            </div>
            <a href="/" target="_blank" class="admin-btn-ghost"><LineIcon name="external" :size="16" /> Preview site</a>
        </div>

        <div class="mt-6 flex flex-wrap gap-2">
            <a v-for="group in groups" :key="group.key" :href="`#group-${group.key}`" class="rounded-full bg-white px-3 py-1 text-xs text-ink-soft ring-1 ring-ink/10 transition hover:text-rust hover:ring-gold">
                {{ group.title }}
            </a>
        </div>

        <div class="mt-8 space-y-6">
            <section v-for="group in groups" :id="`group-${group.key}`" :key="group.key" class="admin-card scroll-mt-6 p-6 md:p-8">
                <h2 class="font-serif text-2xl text-ink">{{ group.title }}</h2>
                <div class="mt-6 grid gap-6 md:grid-cols-2">
                    <div v-for="setting in group.settings" :key="setting.key" :class="['textarea', 'image', 'audio', 'video'].includes(setting.type) ? 'md:col-span-2' : ''">
                        <label :for="setting.key" class="admin-label">{{ setting.label }}</label>

                        <textarea v-if="setting.type === 'textarea'" :id="setting.key" v-model="form.values[setting.key]" rows="4" class="admin-input"></textarea>
                        <MediaField v-else-if="['image', 'audio', 'video'].includes(setting.type)" :id="setting.key" v-model="form.values[setting.key]" :kind="setting.type" />
                        <div v-else-if="setting.type === 'toggle'" class="flex items-center gap-3">
                            <ToggleSwitch
                                :id="setting.key"
                                :model-value="form.values[setting.key] !== '0'"
                                :label="setting.label"
                                @update:model-value="form.values[setting.key] = $event ? '1' : '0'"
                            />
                            <span class="text-sm text-ink-soft">{{ form.values[setting.key] !== '0' ? 'Shown on the website' : 'Hidden' }}</span>
                        </div>
                        <input v-else :id="setting.key" v-model="form.values[setting.key]" :type="inputType[setting.type] ?? 'text'" class="admin-input" />

                        <p v-if="setting.help" class="mt-1.5 text-xs text-ink-soft">{{ setting.help }}</p>
                        <p v-if="form.errors[`values.${setting.key}`]" class="mt-1.5 text-sm text-red-700">{{ form.errors[`values.${setting.key}`] }}</p>
                    </div>
                </div>
            </section>
        </div>

        <div class="sticky bottom-4 mt-8 flex justify-end">
            <button type="submit" class="admin-btn px-6 py-3 shadow-xl" :disabled="form.processing || !form.isDirty">
                <LineIcon name="check" :size="16" /> {{ form.processing ? 'Saving…' : 'Save changes' }}
            </button>
        </div>
    </form>
</template>
