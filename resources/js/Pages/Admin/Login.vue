<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import LineIcon from '@/Components/LineIcon.vue';

const page = usePage();
const form = useForm({ email: '', password: '', remember: true });

const submit = () => form.post('/admin/login', { onFinish: () => form.reset('password') });
</script>

<template>
    <Head title="Admin sign in" />

    <div class="relative grid min-h-screen place-items-center overflow-hidden bg-film px-5 py-12">
        <img src="/images/dummy/cover.jpg" alt="" class="absolute inset-0 h-full w-full object-cover opacity-30" />
        <div class="absolute inset-0 bg-gradient-to-b from-film/60 to-film/95"></div>
        <div class="light-leak -left-1/4 top-0 h-[50vh] w-[60vw] bg-[#ff9a5a]/30"></div>

        <div class="relative w-full max-w-md">
            <div class="text-center text-paper">
                <p class="font-script text-5xl text-gold-light">{{ page.props.appName }}</p>
                <p class="kicker mt-2 text-paper/60">Wedding Admin</p>
            </div>

            <form class="mt-10 rounded-2xl bg-paper p-8 shadow-2xl" @submit.prevent="submit">
                <div>
                    <label for="email" class="admin-label">Email</label>
                    <input id="email" v-model="form.email" type="email" autocomplete="username" required autofocus class="admin-input" />
                    <p v-if="form.errors.email" class="mt-1.5 text-sm text-red-700">{{ form.errors.email }}</p>
                </div>
                <div class="mt-5">
                    <label for="password" class="admin-label">Password</label>
                    <input id="password" v-model="form.password" type="password" autocomplete="current-password" required class="admin-input" />
                </div>
                <label class="mt-5 flex items-center gap-2 text-sm text-ink-soft">
                    <input v-model="form.remember" type="checkbox" class="size-4 accent-[#b4935e]" /> Keep me signed in
                </label>
                <button type="submit" class="admin-btn mt-7 w-full py-3" :disabled="form.processing">
                    <LineIcon name="logout" :size="16" class="rotate-180" /> Sign in
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-paper/50">
                <a href="/" class="hover:text-paper">← Back to the website</a>
            </p>
        </div>
    </div>
</template>
