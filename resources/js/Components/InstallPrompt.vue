<template>
  <Transition name="slide-up">
    <div
      v-if="canInstall && !dismissed"
      id="pwa-install-prompt"
      class="install-banner"
      role="complementary"
      aria-label="Install App Banner"
    >
      <!-- Icon -->
      <div class="install-banner__icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 16v-8m0 8l-3-3m3 3l3-3M3 17v2a2 2 0 002 2h14a2 2 0 002-2v-2"/>
        </svg>
      </div>

      <!-- Text -->
      <div class="install-banner__text">
        <strong>Install Inventori</strong>
        <span>Add to Home Screen for fast access</span>
      </div>

      <!-- Actions -->
      <div class="install-banner__actions">
        <button
          id="btn-install-app"
          class="btn-install"
          @click="handleInstall"
          :disabled="installing"
        >
          <span v-if="!installing">Install</span>
          <span v-else class="spinner" />
        </button>

        <button
          id="btn-dismiss-install"
          class="btn-dismiss"
          @click="dismissed = true"
          aria-label="Dismiss"
        >
          ✕
        </button>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref } from 'vue'
import { usePwa } from '@/Composables/usePwa'

const { canInstall, installApp } = usePwa()

const dismissed  = ref(false)
const installing = ref(false)

const handleInstall = async () => {
  installing.value = true
  const outcome = await installApp()
  installing.value = false
  if (outcome === 'accepted') dismissed.value = true
}
</script>

<style scoped>
.install-banner {
  position: fixed;
  bottom: calc(88px + env(safe-area-inset-bottom, 0px));
  left: 1rem;
  right: 1rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.875rem 1rem;
  border-radius: 1.25rem;
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(28px) saturate(200%);
  -webkit-backdrop-filter: blur(28px) saturate(200%);
  border: 1px solid rgba(255, 255, 255, 0.90);
  box-shadow:
    0 8px 32px rgba(0, 80, 200, 0.14),
    0 2px 8px rgba(0, 0, 0, 0.07),
    inset 0 1px 0 rgba(255, 255, 255, 1);
  z-index: 1000;
  max-width: 420px;
  margin: 0 auto;
}

.install-banner__icon {
  flex-shrink: 0;
  width: 2.25rem;
  height: 2.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(145deg, #007AFF, #5856D6);
  border-radius: 0.75rem;
  color: white;
  box-shadow: 0 4px 10px rgba(0, 122, 255, 0.30);
}

.install-banner__icon svg { width: 1.125rem; height: 1.125rem; }

.install-banner__text {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
  overflow: hidden;
}

.install-banner__text strong {
  font-size: 0.875rem;
  font-weight: 700;
  color: rgba(0, 0, 0, 0.82);
  line-height: 1.3;
}

.install-banner__text span {
  font-size: 0.72rem;
  color: rgba(0, 0, 0, 0.45);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.install-banner__actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-shrink: 0;
}

.btn-install {
  padding: 0.5rem 1rem;
  background: var(--ios-blue);
  color: white;
  border: none;
  border-radius: 0.625rem;
  font-size: 0.8125rem;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.2s, transform 0.15s cubic-bezier(0.34, 1.56, 0.64, 1);
  min-width: 64px;
  display: flex;
  align-items: center;
  justify-content: center;
  height: 2rem;
  box-shadow: 0 4px 12px rgba(0, 122, 255, 0.30);
  font-family: var(--font-sans);
}

.btn-install:hover:not(:disabled) { opacity: 0.9; }
.btn-install:active:not(:disabled) { transform: scale(0.95); }
.btn-install:disabled { opacity: 0.6; cursor: not-allowed; }

.btn-dismiss {
  width: 1.625rem;
  height: 1.625rem;
  background: rgba(0, 0, 0, 0.06);
  border: 1px solid rgba(0, 0, 0, 0.10);
  border-radius: 50%;
  color: rgba(0, 0, 0, 0.40);
  font-size: 0.65rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.15s, color 0.15s;
  font-family: var(--font-sans);
}

.btn-dismiss:hover {
  background: rgba(255, 59, 48, 0.10);
  color: var(--ios-red);
  border-color: rgba(255, 59, 48, 0.20);
}

.spinner {
  display: inline-block;
  width: 1rem;
  height: 1rem;
  border: 2px solid rgba(255, 255, 255, 0.35);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

.slide-up-enter-active,
.slide-up-leave-active { transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.3s ease; }
.slide-up-enter-from,
.slide-up-leave-to { transform: translateY(1.5rem); opacity: 0; }

@keyframes spin { to { transform: rotate(360deg); } }
</style>
