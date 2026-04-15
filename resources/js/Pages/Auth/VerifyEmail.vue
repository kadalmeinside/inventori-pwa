<script setup>
import { computed } from 'vue';
import GuestLayout   from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ status: String });
const form  = useForm({});
const submit = () => form.post(route('verification.send'));
const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <GuestLayout>
        <Head title="Verify Email — Inventori" />

        <div class="verify-wrap">
            <div class="verify-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            </div>

            <p class="verify-hint">
                Thanks for signing up! Before getting started, please verify your email address by clicking the link we sent you.
            </p>

            <div v-if="verificationLinkSent" class="status-msg">
                ✓ A new verification link has been sent to your email.
            </div>

            <form @submit.prevent="submit" class="verify-actions">
                <PrimaryButton class="resend-btn" :class="{ 'opacity-50': form.processing }" :disabled="form.processing">
                    Resend Verification Email
                </PrimaryButton>
                <Link :href="route('logout')" method="post" as="button" class="logout-link">
                    Sign out
                </Link>
            </form>
        </div>
    </GuestLayout>
</template>

<style scoped>
.verify-wrap { display: flex; flex-direction: column; align-items: center; gap: 1rem; text-align: center; }
.verify-icon { width: 3.5rem; height: 3.5rem; background: rgba(0,122,255,0.10); border: 1px solid rgba(0,122,255,0.18); border-radius: 1.125rem; display: flex; align-items: center; justify-content: center; color: var(--ios-blue); margin-bottom: 0.25rem; }
.verify-hint { font-size: 0.8125rem; color: rgba(0,0,0,0.45); line-height: 1.7; }
.status-msg { font-size: 0.8125rem; font-weight: 600; color: var(--ios-green); background: rgba(52,199,89,0.10); border: 1px solid rgba(52,199,89,0.20); border-radius: 0.625rem; padding: 0.625rem 0.875rem; width: 100%; }
.verify-actions { display: flex; flex-direction: column; align-items: center; gap: 0.875rem; width: 100%; }
.resend-btn { width: 100%; padding: 0.8rem; font-size: 0.9375rem; font-weight: 700; border-radius: 0.875rem; box-shadow: 0 4px 20px rgba(0,122,255,0.38); }
.logout-link { font-size: 0.8rem; font-weight: 500; color: rgba(0,0,0,0.40); text-decoration: none; border: none; background: none; cursor: pointer; font-family: inherit; }
.logout-link:hover { color: var(--ios-red); }
</style>
