<template>
  <AppLayout title="Profile">
    <div class="page-wrap">

      <!-- Page header -->
      <div class="page-header">
        <div>
          <p class="page-eyebrow">Account</p>
          <h1 class="page-title">My Profile</h1>
        </div>
        <!-- User avatar -->
        <div class="profile-avatar">{{ userInitials }}</div>
      </div>

      <!-- Profile Info Form -->
      <section class="glass-section">
        <div class="section-label">
          <div class="section-label__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          </div>
          Profile Information
        </div>
        <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" />
      </section>

      <!-- Password Form -->
      <section class="glass-section">
        <div class="section-label">
          <div class="section-label__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
          </div>
          Change Password
        </div>
        <UpdatePasswordForm />
      </section>

    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';

defineProps({ mustVerifyEmail: Boolean, status: String });

const page = usePage();
const userInitials = computed(() => {
  const name = page.props.auth?.user?.name ?? 'U';
  return name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase();
});
</script>

<style scoped>
.page-wrap { padding: 2rem 1.25rem 7rem; max-width: 680px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.25rem; }

.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.25rem; }
.page-eyebrow { font-size: 0.7rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: rgba(0,0,0,0.35); margin: 0 0 0.2rem; }
.page-title { font-size: 1.75rem; font-weight: 800; letter-spacing: -0.04em; color: rgba(0,0,0,0.85); margin: 0; line-height: 1; }

.profile-avatar {
  width: 3.25rem;
  height: 3.25rem;
  background: linear-gradient(145deg, #007AFF, #5856D6);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  font-weight: 700;
  color: white;
  box-shadow: 0 4px 16px rgba(0, 122, 255, 0.35);
  flex-shrink: 0;
}

.glass-section {
  background: rgba(255, 255, 255, 0.62);
  backdrop-filter: blur(28px) saturate(180%);
  -webkit-backdrop-filter: blur(28px) saturate(180%);
  border: 1px solid rgba(255, 255, 255, 0.82);
  border-radius: 1.5rem;
  box-shadow: 0 8px 40px rgba(0, 80, 200, 0.10), inset 0 1px 0 rgba(255,255,255,0.95);
  padding: 1.5rem;
}

.section-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  font-weight: 700;
  color: rgba(0, 0, 0, 0.65);
  letter-spacing: -0.01em;
  margin-bottom: 1.25rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}

.section-label__icon {
  width: 1.75rem;
  height: 1.75rem;
  background: rgba(0, 122, 255, 0.10);
  border: 1px solid rgba(0, 122, 255, 0.18);
  border-radius: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #007AFF;
}
</style>
