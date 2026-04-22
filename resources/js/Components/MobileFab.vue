<template>
  <!-- Only shown on mobile -->
  <Teleport to="body">
    <div v-if="actions.length > 0" class="mobile-fab-root">

      <!-- Backdrop (dismiss menu) -->
      <Transition name="fab-backdrop">
        <div v-if="open" class="fab-backdrop" @click="open = false" />
      </Transition>

      <!-- Action items (expand upward from FAB) -->
      <Transition name="fab-actions">
        <div v-if="open" class="fab-actions">
          <button
            v-for="(item, i) in actions"
            :key="i"
            class="fab-action-item"
            :style="item.color ? `--item-color: ${item.color}` : ''"
            @click="handleAction(item)"
          >
            <span class="fab-action-item__label">{{ item.label }}</span>
            <span class="fab-action-item__icon">{{ item.icon }}</span>
          </button>
        </div>
      </Transition>

      <!-- Main FAB button -->
      <button
        class="fab-btn"
        :class="{ 'fab-btn--open': open }"
        @click="open = !open"
        aria-label="Menu Aksi"
      >
        <svg
          viewBox="0 0 24 24" fill="none" stroke="currentColor"
          stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
          width="22" height="22"
          class="fab-btn__icon"
        >
          <line x1="12" y1="5" x2="12" y2="19" />
          <line x1="5" y1="12" x2="19" y2="12" />
        </svg>
      </button>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useFabActions } from '@/Composables/useMobileFab.js'

const actions = useFabActions()
const open    = ref(false)

// Auto-close saat actions berubah (halaman berganti)
watch(actions, () => { open.value = false })

function handleAction(item) {
  open.value = false
  item.action?.()
}
</script>

<style scoped>
/* Hanya tampil di mobile */
.mobile-fab-root {
  display: none;
}

@media (max-width: 767px) {
  .mobile-fab-root {
    display: block;
  }

  /* ─── Main FAB button ──────────────────────────────────────────────────── */
  .fab-btn {
    position: fixed;
    right: 1.25rem;
    /* Di atas mobile nav (nav ≈ 4.5rem + safe-area) */
    bottom: calc(5rem + env(safe-area-inset-bottom, 0px));
    z-index: 250;
    width: 3.25rem;
    height: 3.25rem;
    border-radius: 999px;
    border: none;
    background: linear-gradient(145deg, #007AFF, #0055D4);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 6px 24px rgba(0, 122, 255, 0.45), 0 2px 8px rgba(0,0,0,0.18);
    cursor: pointer;
    transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1),
                box-shadow 0.2s ease,
                background 0.2s ease;
    -webkit-tap-highlight-color: transparent;
  }

  .fab-btn:active {
    transform: scale(0.92);
  }

  .fab-btn__icon {
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
  }

  /* Rotate + to × when open */
  .fab-btn--open {
    background: linear-gradient(145deg, #FF3B30, #C0392B);
    box-shadow: 0 6px 24px rgba(255, 59, 48, 0.45), 0 2px 8px rgba(0,0,0,0.18);
  }
  .fab-btn--open .fab-btn__icon {
    transform: rotate(45deg);
  }

  /* ─── Action items ─────────────────────────────────────────────────────── */
  .fab-actions {
    position: fixed;
    right: 1.25rem;
    bottom: calc(5rem + 3.75rem + env(safe-area-inset-bottom, 0px));
    z-index: 250;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.625rem;
  }

  .fab-action-item {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    border: none;
    background: white;
    border-radius: 999px;
    padding: 0.6rem 1rem 0.6rem 0.875rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.14), 0 1px 6px rgba(0,0,0,0.10);
    cursor: pointer;
    font-size: 0.875rem;
    font-weight: 600;
    color: rgba(0,0,0,0.85);
    white-space: nowrap;
    transition: transform 0.18s cubic-bezier(0.34, 1.56, 0.64, 1),
                box-shadow 0.18s ease;
    -webkit-tap-highlight-color: transparent;
    font-family: inherit;
  }

  .fab-action-item:active {
    transform: scale(0.95);
  }

  .fab-action-item__label {
    font-size: 0.85rem;
    font-weight: 600;
    letter-spacing: -0.01em;
  }

  .fab-action-item__icon {
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    background: var(--item-color, #007AFF);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
    line-height: 1;
  }

  /* ─── Backdrop ─────────────────────────────────────────────────────────── */
  .fab-backdrop {
    position: fixed;
    inset: 0;
    z-index: 240;
    background: rgba(0, 0, 0, 0.25);
    backdrop-filter: blur(2px);
  }

  /* ─── Transitions ──────────────────────────────────────────────────────── */
  .fab-backdrop-enter-active,
  .fab-backdrop-leave-active { transition: opacity 0.2s ease; }
  .fab-backdrop-enter-from,
  .fab-backdrop-leave-to     { opacity: 0; }

  .fab-actions-enter-active {
    transition: opacity 0.22s ease, transform 0.22s cubic-bezier(0.34, 1.56, 0.64, 1);
  }
  .fab-actions-leave-active {
    transition: opacity 0.15s ease, transform 0.15s ease;
  }
  .fab-actions-enter-from,
  .fab-actions-leave-to {
    opacity: 0;
    transform: translateY(0.75rem) scale(0.92);
  }
}
</style>
