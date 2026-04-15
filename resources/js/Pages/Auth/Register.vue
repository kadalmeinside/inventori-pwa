<script setup>
import GuestLayout   from '@/Layouts/GuestLayout.vue';
import InputError    from '@/Components/InputError.vue';
import InputLabel    from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput     from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({ name: '', email: '', password: '', password_confirmation: '' });
const submit = () => form.post(route('register'), { onFinish: () => form.reset('password', 'password_confirmation') });
</script>

<template>
    <GuestLayout>
        <Head title="Register — Inventori" />

        <form @submit.prevent="submit" class="form-fields">
            <div class="field">
                <InputLabel for="name" value="Full Name" />
                <TextInput id="name" type="text" v-model="form.name" required autofocus autocomplete="name" placeholder="Your full name" />
                <InputError :message="form.errors.name" />
            </div>
            <div class="field">
                <InputLabel for="email" value="Email Address" />
                <TextInput id="email" type="email" v-model="form.email" required autocomplete="username" placeholder="you@example.com" />
                <InputError :message="form.errors.email" />
            </div>
            <div class="field">
                <InputLabel for="password" value="Password" />
                <TextInput id="password" type="password" v-model="form.password" required autocomplete="new-password" placeholder="••••••••" />
                <InputError :message="form.errors.password" />
            </div>
            <div class="field">
                <InputLabel for="password_confirmation" value="Confirm Password" />
                <TextInput id="password_confirmation" type="password" v-model="form.password_confirmation" required autocomplete="new-password" placeholder="••••••••" />
                <InputError :message="form.errors.password_confirmation" />
            </div>

            <div class="form-footer">
                <Link :href="route('login')" class="back-link">Already registered?</Link>
                <PrimaryButton class="submit-btn" :class="{ 'opacity-50': form.processing }" :disabled="form.processing">
                    Create Account
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>

<style scoped>
.form-fields { display: flex; flex-direction: column; gap: 1rem; }
.field { display: flex; flex-direction: column; gap: 0.3rem; }
.form-footer { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; padding-top: 0.25rem; flex-wrap: wrap; }
.back-link { font-size: 0.8rem; font-weight: 500; color: var(--ios-blue); text-decoration: none; opacity: 0.8; }
.back-link:hover { opacity: 1; }
.submit-btn { padding: 0.75rem 1.25rem; font-weight: 700; border-radius: 0.875rem; box-shadow: 0 4px 16px rgba(0,122,255,0.35); }
</style>
