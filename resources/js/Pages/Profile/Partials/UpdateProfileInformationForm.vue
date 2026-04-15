<template>
  <form @submit.prevent="form.patch(route('profile.update'))" class="profile-form">
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
    <div class="form-footer">
      <PrimaryButton :disabled="form.processing" :class="{ 'opacity-50': form.processing }">
        Save Changes
      </PrimaryButton>
      <Transition enter-active-class="ease-out duration-200" enter-from-class="opacity-0 translate-y-1" leave-active-class="ease-in duration-150" leave-to-class="opacity-0 translate-y-1">
        <span v-if="form.recentlySuccessful" class="saved-msg">✓ Saved</span>
      </Transition>
    </div>
  </form>
</template>

<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import InputError    from '@/Components/InputError.vue';
import InputLabel    from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput     from '@/Components/TextInput.vue';

const user = usePage().props.auth.user;
const form = useForm({ name: user.name, email: user.email });
</script>

<style scoped>
.profile-form { display: flex; flex-direction: column; gap: 1rem; }
.field { display: flex; flex-direction: column; gap: 0.3rem; }
.form-footer { display: flex; align-items: center; gap: 1rem; padding-top: 0.5rem; }
.saved-msg { font-size: 0.8125rem; font-weight: 600; color: var(--ios-green); }
</style>
