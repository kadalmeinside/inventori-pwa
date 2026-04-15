<template>
  <form @submit.prevent="updatePassword" class="profile-form">
    <div class="field">
      <InputLabel for="current_password" value="Current Password" />
      <TextInput id="current_password" ref="currentPasswordInput" v-model="form.current_password" type="password" autocomplete="current-password" placeholder="••••••••" />
      <InputError :message="form.errors.current_password" />
    </div>
    <div class="field">
      <InputLabel for="password" value="New Password" />
      <TextInput id="password" ref="passwordInput" v-model="form.password" type="password" autocomplete="new-password" placeholder="••••••••" />
      <InputError :message="form.errors.password" />
    </div>
    <div class="field">
      <InputLabel for="password_confirmation" value="Confirm New Password" />
      <TextInput id="password_confirmation" v-model="form.password_confirmation" type="password" autocomplete="new-password" placeholder="••••••••" />
      <InputError :message="form.errors.password_confirmation" />
    </div>
    <div class="form-footer">
      <PrimaryButton :disabled="form.processing" :class="{ 'opacity-50': form.processing }">
        Update Password
      </PrimaryButton>
      <Transition enter-active-class="ease-out duration-200" enter-from-class="opacity-0 translate-y-1" leave-active-class="ease-in duration-150" leave-to-class="opacity-0 translate-y-1">
        <span v-if="form.recentlySuccessful" class="saved-msg">✓ Updated</span>
      </Transition>
    </div>
  </form>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import InputError    from '@/Components/InputError.vue';
import InputLabel    from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput     from '@/Components/TextInput.vue';

const passwordInput        = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({ current_password: '', password: '', password_confirmation: '' });

const updatePassword = () => {
  form.put(route('password.update'), {
    preserveScroll: true,
    onSuccess: () => form.reset(),
    onError: () => {
      if (form.errors.password) { form.reset('password', 'password_confirmation'); passwordInput.value.focus(); }
      if (form.errors.current_password) { form.reset('current_password'); currentPasswordInput.value.focus(); }
    },
  });
};
</script>

<style scoped>
.profile-form { display: flex; flex-direction: column; gap: 1rem; }
.field { display: flex; flex-direction: column; gap: 0.3rem; }
.form-footer { display: flex; align-items: center; gap: 1rem; padding-top: 0.5rem; }
.saved-msg { font-size: 0.8125rem; font-weight: 600; color: var(--ios-green); }
</style>
