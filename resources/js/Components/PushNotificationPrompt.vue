<template>
  <!-- ─── iOS: Belum Install → Banner "Tambahkan ke Home Screen" ──────────── -->
  <Teleport to="body">
    <Transition name="push-banner">
      <div
        v-if="needsInstallForPush && !dismissed"
        class="push-banner push-banner--ios"
        role="status"
        aria-live="polite"
      >
        <!-- Icon -->
        <div class="push-banner__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 8h1a4 4 0 0 1 0 8h-1"/>
            <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/>
            <line x1="6" y1="1" x2="6" y2="4"/>
            <line x1="10" y1="1" x2="10" y2="4"/>
            <line x1="14" y1="1" x2="14" y2="4"/>
          </svg>
        </div>

        <!-- Content -->
        <div class="push-banner__content">
          <p class="push-banner__title">Aktifkan Notifikasi</p>
          <p class="push-banner__desc">
            Tap
            <svg class="push-banner__share-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/>
              <polyline points="16 6 12 2 8 6"/>
              <line x1="12" y1="2" x2="12" y2="15"/>
            </svg>
            lalu pilih <strong>"Add to Home Screen"</strong> untuk mengaktifkan push notification.
          </p>
        </div>

        <!-- Close -->
        <button
          class="push-banner__close"
          @click="dismiss"
          aria-label="Tutup"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
            <line x1="18" y1="6" x2="6" y2="18"/>
            <line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>
      </div>
    </Transition>

    <!-- ─── Opt-in Banner (Android & iOS installed) ──────────────────────── -->
    <Transition name="push-banner">
      <div
        v-if="canSubscribe && !isSubscribed && !dismissed && showOptIn"
        class="push-banner push-banner--optin"
        role="dialog"
        aria-label="Aktifkan Notifikasi"
      >
        <!-- Icon dengan animasi pulse -->
        <div class="push-banner__icon push-banner__icon--bell">
          <span class="push-banner__bell-ring">🔔</span>
        </div>

        <!-- Content -->
        <div class="push-banner__content">
          <p class="push-banner__title">Aktifkan Push Notification</p>
          <p class="push-banner__desc">
            Terima notifikasi real-time saat ada permintaan transfer baru, stok keluar, atau update penting lainnya — bahkan saat aplikasi ditutup.
          </p>
        </div>

        <!-- Actions -->
        <div class="push-banner__actions">
          <button
            class="push-banner__btn push-banner__btn--primary"
            :disabled="isLoading"
            @click="handleSubscribe"
            id="push-subscribe-btn"
          >
            <span v-if="isLoading" class="push-banner__spinner" />
            <span v-else>Aktifkan</span>
          </button>
          <button
            class="push-banner__btn push-banner__btn--ghost"
            @click="dismiss"
            id="push-dismiss-btn"
          >
            Nanti
          </button>
        </div>
      </div>
    </Transition>

    <!-- ─── Error Toast ───────────────────────────────────────────────────── -->
    <Transition name="push-banner">
      <div v-if="error" class="push-banner push-banner--error" role="alert">
        <span>⚠️</span>
        <span>{{ error }}</span>
        <button class="push-banner__close" @click="clearError" aria-label="Tutup">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
            <line x1="18" y1="6" x2="6" y2="18"/>
            <line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { usePushNotification } from '@/Composables/usePushNotification.js'

const {
  isSubscribed,
  isLoading,
  canSubscribe,
  needsInstallForPush,
  error,
  subscribe,
} = usePushNotification()

const dismissed  = ref(false)
const showOptIn  = ref(false)

// Tampilkan opt-in banner setelah 5 detik (bukan langsung di halaman pertama)
onMounted(() => {
  // Cek apakah user pernah menolak sebelumnya
  const previouslyDismissed = localStorage.getItem('push-opt-in-dismissed')
  if (previouslyDismissed) {
    dismissed.value = true
    return
  }

  setTimeout(() => {
    showOptIn.value = true
  }, 5000)
})

async function handleSubscribe() {
  const success = await subscribe()
  if (success) {
    dismissed.value = true
  }
}

function dismiss() {
  dismissed.value = true
  // Ingat pilihan user selama 7 hari
  localStorage.setItem('push-opt-in-dismissed', Date.now().toString())
}

function clearError() {
  error.value = null
}
</script>

<style scoped>
/* ─── Push Banner Base ───────────────────────────────────────────────────── */
.push-banner {
  position: fixed;
  bottom: calc(5rem + env(safe-area-inset-bottom, 0px));
  left: 50%;
  transform: translateX(-50%);
  z-index: 2147483001;

  display: flex;
  align-items: flex-start;
  gap: 0.875rem;

  width: calc(100% - 2rem);
  max-width: 24rem;

  padding: 1rem 1rem 1rem 1rem;
  border-radius: 1.25rem;

  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(32px) saturate(200%);
  -webkit-backdrop-filter: blur(32px) saturate(200%);
  border: 1px solid rgba(255, 255, 255, 0.9);
  box-shadow:
    0 20px 60px rgba(0, 0, 0, 0.15),
    0 4px 16px rgba(0, 0, 0, 0.08),
    inset 0 1px 0 rgba(255, 255, 255, 1);
}

@media (min-width: 768px) {
  .push-banner {
    bottom: 1.5rem;
    left: auto;
    right: 1.5rem;
    transform: none;
  }
}

/* ─── Variant: iOS install prompt ───────────────────────────────────────── */
.push-banner--ios {
  border-left: 3px solid #007AFF;
}

/* ─── Variant: opt-in ───────────────────────────────────────────────────── */
.push-banner--optin {
  flex-direction: column;
  gap: 0.75rem;
  border-left: 3px solid #34C759;
}

/* ─── Variant: error ────────────────────────────────────────────────────── */
.push-banner--error {
  border-left: 3px solid #FF3B30;
  align-items: center;
  gap: 0.5rem;
  bottom: calc(5.5rem + env(safe-area-inset-bottom, 0px));
  font-size: 0.875rem;
  color: rgba(0, 0, 0, 0.75);
}

/* ─── Icon ───────────────────────────────────────────────────────────────── */
.push-banner__icon {
  flex-shrink: 0;
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 0.875rem;
  background: linear-gradient(135deg, rgba(0, 122, 255, 0.12), rgba(88, 86, 214, 0.12));
  display: flex;
  align-items: center;
  justify-content: center;
  color: #007AFF;
}
.push-banner__icon svg {
  width: 1.25rem;
  height: 1.25rem;
}

.push-banner__icon--bell {
  background: linear-gradient(135deg, rgba(52, 199, 89, 0.12), rgba(0, 122, 255, 0.08));
}
.push-banner__bell-ring {
  font-size: 1.25rem;
  animation: bell-shake 2.5s ease-in-out infinite;
  display: inline-block;
}
@keyframes bell-shake {
  0%, 100% { transform: rotate(0deg); }
  10%       { transform: rotate(15deg); }
  20%       { transform: rotate(-12deg); }
  30%       { transform: rotate(10deg); }
  40%       { transform: rotate(-8deg); }
  50%       { transform: rotate(5deg); }
  60%       { transform: rotate(0deg); }
}

/* ─── Content ────────────────────────────────────────────────────────────── */
.push-banner__content {
  flex: 1;
  min-width: 0;
}

.push-banner__title {
  font-size: 0.875rem;
  font-weight: 700;
  color: rgba(0, 0, 0, 0.85);
  margin: 0 0 0.25rem;
  line-height: 1.3;
}

.push-banner__desc {
  font-size: 0.78rem;
  color: rgba(0, 0, 0, 0.55);
  margin: 0;
  line-height: 1.5;
}

.push-banner__share-icon {
  width: 0.875rem;
  height: 0.875rem;
  vertical-align: middle;
  margin-inline: 0.15rem;
  color: #007AFF;
}

/* ─── Actions ────────────────────────────────────────────────────────────── */
.push-banner__actions {
  display: flex;
  gap: 0.5rem;
  width: 100%;
}

.push-banner__btn {
  flex: 1;
  padding: 0.625rem 1rem;
  border-radius: 0.75rem;
  font-size: 0.8rem;
  font-weight: 600;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.push-banner__btn--primary {
  background: linear-gradient(135deg, #34C759, #30D158);
  color: white;
  box-shadow: 0 2px 8px rgba(52, 199, 89, 0.35);
}
.push-banner__btn--primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(52, 199, 89, 0.45);
}
.push-banner__btn--primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
}

.push-banner__btn--ghost {
  background: rgba(0, 0, 0, 0.06);
  color: rgba(0, 0, 0, 0.55);
}
.push-banner__btn--ghost:hover {
  background: rgba(0, 0, 0, 0.10);
  color: rgba(0, 0, 0, 0.75);
}

/* ─── Close button ───────────────────────────────────────────────────────── */
.push-banner__close {
  flex-shrink: 0;
  background: transparent;
  border: none;
  cursor: pointer;
  padding: 0.25rem;
  color: rgba(0, 0, 0, 0.30);
  display: flex;
  align-items: center;
  border-radius: 0.5rem;
  transition: color 0.15s, background 0.15s;
}
.push-banner__close:hover {
  color: rgba(0, 0, 0, 0.65);
  background: rgba(0, 0, 0, 0.06);
}
.push-banner__close svg {
  width: 0.875rem;
  height: 0.875rem;
}

/* ─── Spinner ────────────────────────────────────────────────────────────── */
.push-banner__spinner {
  width: 1rem;
  height: 1rem;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
  display: inline-block;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ─── Transitions ────────────────────────────────────────────────────────── */
.push-banner-enter-active {
  transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.push-banner-leave-active {
  transition: all 0.3s ease-in;
}
.push-banner-enter-from {
  opacity: 0;
  transform: translateX(-50%) translateY(1.5rem) scale(0.95);
}
.push-banner-leave-to {
  opacity: 0;
  transform: translateX(-50%) translateY(1rem) scale(0.97);
}
@media (min-width: 768px) {
  .push-banner-enter-from,
  .push-banner-leave-to {
    transform: translateY(1rem);
  }
}
</style>
