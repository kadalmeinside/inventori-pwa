<script setup>
import GuestLayout   from '@/Layouts/GuestLayout.vue';
import InputError    from '@/Components/InputError.vue';
import InputLabel    from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput     from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({ status: String });

const form = useForm({ email: '' });
const submit = () => form.post(route('password.email'));
</script>

<template>
    <GuestLayout>
        <Head title="Forgot Password — Inventori" />

        <div class="auth-form">
            <p class="auth-hint">
                Forgot your password? Enter your email and we'll send a reset link.
            </p>

            <div v-if="status" class="status-msg">{{ status }}</div>

            <form @submit.prevent="submit" class="form-fields">
                <div class="field">
                    <InputLabel for="email" value="Email Address" />
                    <TextInput id="email" type="email" v-model="form.email" required autofocus autocomplete="username" placeholder="you@example.com" />
                    <InputError :message="form.errors.email" />
                </div>
                <PrimaryButton class="submit-btn" :class="{ 'opacity-50': form.processing }" :disabled="form.processing">
                    Send Reset Link
                </PrimaryButton>
            </form>
        </div>
    </GuestLayout>
</template>

<style scoped>
.auth-form { display: flex; flex-direction: column; gap: 1.125rem; }
.auth-hint { font-size: 0.8125rem; color: rgba(0,0,0,0.45); line-height: 1.6; }
.status-msg { font-size: 0.8125rem; font-weight: 600; color: var(--ios-green); background: rgba(52,199,89,0.10); border: 1px solid rgba(52,199,89,0.20); border-radius: 0.625rem; padding: 0.625rem 0.875rem; }
.form-fields { display: flex; flex-direction: column; gap: 1rem; }
.field { display: flex; flex-direction: column; gap: 0.3rem; }
.submit-btn { width: 100%; padding: 0.8rem 1.5rem; font-size: 0.9375rem; font-weight: 700; border-radius: 0.875rem; box-shadow: 0 4px 20px rgba(0,122,255,0.40); }
</style>
