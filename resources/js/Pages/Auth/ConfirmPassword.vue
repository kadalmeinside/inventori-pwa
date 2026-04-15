<script setup>
import GuestLayout   from '@/Layouts/GuestLayout.vue';
import InputError    from '@/Components/InputError.vue';
import InputLabel    from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput     from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({ password: '' });
const submit = () => form.post(route('password.confirm'), { onFinish: () => form.reset() });
</script>

<template>
    <GuestLayout>
        <Head title="Confirm Password — Inventori" />

        <div class="confirm-wrap">
            <p class="confirm-hint">
                This is a secure area. Please confirm your password to continue.
            </p>

            <form @submit.prevent="submit" class="form-fields">
                <div class="field">
                    <InputLabel for="password" value="Current Password" />
                    <TextInput id="password" type="password" v-model="form.password" required autocomplete="current-password" autofocus placeholder="••••••••" />
                    <InputError :message="form.errors.password" />
                </div>
                <PrimaryButton class="submit-btn" :class="{ 'opacity-50': form.processing }" :disabled="form.processing">
                    Confirm
                </PrimaryButton>
            </form>
        </div>
    </GuestLayout>
</template>

<style scoped>
.confirm-wrap { display: flex; flex-direction: column; gap: 1rem; }
.confirm-hint { font-size: 0.8125rem; color: rgba(0,0,0,0.45); line-height: 1.6; }
.form-fields { display: flex; flex-direction: column; gap: 1rem; }
.field { display: flex; flex-direction: column; gap: 0.3rem; }
.submit-btn { width: 100%; padding: 0.8rem; font-size: 0.9375rem; font-weight: 700; border-radius: 0.875rem; box-shadow: 0 4px 20px rgba(0,122,255,0.38); }
</style>
