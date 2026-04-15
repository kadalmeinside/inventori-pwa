<script setup>
import Checkbox       from '@/Components/Checkbox.vue';
import GuestLayout    from '@/Layouts/GuestLayout.vue';
import InputError     from '@/Components/InputError.vue';
import InputLabel     from '@/Components/InputLabel.vue';
import PrimaryButton  from '@/Components/PrimaryButton.vue';
import TextInput      from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: Boolean,
    status:           String,
});

const form = useForm({ email: '', password: '', remember: false });

const submit = () => form.post(route('login'), { onFinish: () => form.reset('password') });
</script>

<template>
    <GuestLayout>
        <Head title="Sign In — Inventori" />

        <!-- Status message -->
        <p v-if="status" class="status-msg">{{ status }}</p>

        <form @submit.prevent="submit" class="login-form">

            <!-- Email -->
            <div class="field">
                <InputLabel for="email" value="Email" />
                <TextInput
                    id="email"
                    type="email"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="you@example.com"
                />
                <InputError :message="form.errors.email" />
            </div>

            <!-- Password -->
            <div class="field">
                <div class="field__row">
                    <InputLabel for="password" value="Password" />
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="forgot-link"
                    >
                        Forgot password?
                    </Link>
                </div>
                <TextInput
                    id="password"
                    type="password"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                />
                <InputError :message="form.errors.password" />
            </div>

            <!-- Remember me -->
            <label class="remember-row">
                <Checkbox name="remember" v-model:checked="form.remember" />
                <span class="remember-row__label">Keep me signed in</span>
            </label>

            <!-- Submit -->
            <PrimaryButton
                class="login-btn"
                :class="{ 'opacity-50': form.processing }"
                :disabled="form.processing"
            >
                <svg v-if="form.processing" class="spin-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                <span>{{ form.processing ? 'Signing in…' : 'Sign In' }}</span>
            </PrimaryButton>

        </form>
    </GuestLayout>
</template>

<style scoped>
.login-form {
    display: flex;
    flex-direction: column;
    gap: 1.125rem;
}

.status-msg {
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--ios-green);
    background: rgba(52, 199, 89, 0.10);
    border: 1px solid rgba(52, 199, 89, 0.20);
    border-radius: 0.625rem;
    padding: 0.625rem 0.875rem;
    margin-bottom: 0.5rem;
}

.field {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.field__row {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.forgot-link {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--ios-blue);
    text-decoration: none;
    transition: opacity 0.15s;
}
.forgot-link:hover { opacity: 0.7; }

.remember-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

.remember-row__label {
    font-size: 0.8125rem;
    font-weight: 500;
    color: rgba(0, 0, 0, 0.55);
    user-select: none;
}

/* Full-width button */
.login-btn {
    width: 100%;
    padding: 0.8rem 1.5rem;
    font-size: 0.9375rem;
    font-weight: 700;
    gap: 0.5rem;
    margin-top: 0.25rem;
    border-radius: 0.875rem;
    box-shadow: 0 4px 20px rgba(0, 122, 255, 0.40);
}

.spin-icon {
    width: 1rem;
    height: 1rem;
    animation: spin 0.8s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }
</style>
