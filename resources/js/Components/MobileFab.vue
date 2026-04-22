<template>
  <Teleport to="body">
    <!--
      FAB hanya render jika:
      - Ada actions terdaftar oleh halaman
      - Viewport mobile (CSS @media mengatur display)
    -->
    <div v-if="actions.length > 0" class="mobile-fab-root" :class="{ 'mobile-fab-root--hidden': sheetOpen }">

      <!-- Backdrop FAB (dismiss menu) -->
      <Transition name="fab-backdrop">
        <div v-if="open" class="fab-backdrop" @click="open = false" />
      </Transition>

      <!-- Action items — expand ke atas -->
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
import { router } from '@inertiajs/vue3'
import { isSheetOpen } from '@/Composables/useMobileSheet.js'

const actions   = useFabActions()
const open      = ref(false)
const sheetOpen = isSheetOpen

// Tutup menu saat actions berubah (navigasi halaman)
watch(actions, () => { open.value = false }, { deep: true })

// Tutup FAB menu saat More sheet dibuka
watch(sheetOpen, (val) => { if (val) open.value = false })

// Tutup menu saat navigasi Inertia terjadi
router.on('start', () => { open.value = false })

function handleAction(item) {
  open.value = false
  // Delay kecil agar animasi tutup terlihat dulu
  setTimeout(() => { item.action?.() }, 80)
}
</script>

<style scoped>
/* Root container — default hidden, hanya tampil di mobile */
.mobile-fab-root {
  display: none;
}

/* Saat More sheet terbuka — sembunyikan FAB sepenuhnya */
.mobile-fab-root--hidden .fab-btn,
.mobile-fab-root--hidden .fab-actions {
  opacity: 0 !important;
  pointer-events: none !important;
  transform: scale(0.85) !important;
}

@media (max-width: 767px) {
  /*
    PENTING: .mobile-fab-root TIDAK boleh punya transform/filter/perspective
    karena akan merusak position:fixed pada child (.fab-btn, .fab-actions).
    CSS spec: elemen dengan transform menjadi containing block untuk fixed descendants.
  */
  .mobile-fab-root {
    display: block;
    pointer-events: none;
  }

  /* Elemen aktif di dalam tetap bisa diklik */
  .fab-btn,
  .fab-actions,
  .fab-backdrop {
    pointer-events: auto;
  }

  /* ─── Main FAB button ──────────────────────────────────────────────────── */
  .fab-btn {
    position: fixed;
    right: 1.25rem;
    /* Di atas mobile nav bar (nav ≈ 4.5rem + safe-area) */
    bottom: calc(5rem + env(safe-area-inset-bottom, 0px));
    z-index: 150;   /* Di atas nav (100), di bawah More sheet (450) */
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
    /* Gabungkan semua transisi: interaktif + hide/show saat sheet buka */
    transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1),
                box-shadow 0.2s ease,
                background 0.2s ease,
                opacity 0.2s ease;
    -webkit-tap-highlight-color: transparent;
  }

  .fab-btn:active { transform: scale(0.92); }

  .fab-btn__icon {
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
  }

  /* + berputar jadi × saat terbuka */
  .fab-btn--open {
    background: linear-gradient(145deg, #FF3B30, #C0392B);
    box-shadow: 0 6px 24px rgba(255, 59, 48, 0.45), 0 2px 8px rgba(0,0,0,0.18);
  }
  .fab-btn--open .fab-btn__icon { transform: rotate(45deg); }

  /* ─── Action items ─────────────────────────────────────────────────────── */
  .fab-actions {
    position: fixed;
    right: 1.25rem;
    bottom: calc(5rem + 3.75rem + env(safe-area-inset-bottom, 0px));
    z-index: 150;
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
    color: rgba(0,0,0,0.85);
    white-space: nowrap;
    transition: transform 0.18s cubic-bezier(0.34, 1.56, 0.64, 1),
                box-shadow 0.18s ease;
    -webkit-tap-highlight-color: transparent;
    font-family: inherit;
  }
  .fab-action-item:active { transform: scale(0.95); }

  .fab-action-item__label {
    font-size: 0.875rem;
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

  /* ─── Backdrop FAB ─────────────────────────────────────────────────────── */
  .fab-backdrop {
    position: fixed;
    inset: 0;
    z-index: 140;
    background: rgba(0,0,0,0.20);
    backdrop-filter: blur(2px);
    -webkit-backdrop-filter: blur(2px);
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
