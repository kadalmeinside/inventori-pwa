<template>
  <!-- ─── Bell Button ──────────────────────────────────────────────────────── -->
  <div class="notif-bell-wrap">
    <button
      id="notif-bell-btn"
      class="notif-bell"
      :class="{ 'notif-bell--active': open }"
      @click="togglePanel"
      :aria-label="`Notifikasi${unread > 0 ? ` (${unread} belum dibaca)` : ''}`"
    >
      <!-- Bell icon -->
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
           stroke-linecap="round" stroke-linejoin="round" width="20" height="20">
        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
      </svg>

      <!-- Unread count badge -->
      <Transition name="badge-pop">
        <span v-if="unread > 0" class="notif-bell__badge">
          {{ unread > 99 ? '99+' : unread }}
        </span>
      </Transition>
    </button>

    <!-- ─── Dropdown Panel ──────────────────────────────────────────────────── -->
    <Transition name="notif-panel">
      <div v-if="open" class="notif-panel" @click.stop>
        <!-- Header -->
        <div class="notif-panel__header">
          <h3 class="notif-panel__title">Notifikasi</h3>
          <div class="notif-panel__actions">
            <button
              v-if="unread > 0"
              class="notif-panel__action-btn"
              @click="markAllRead"
              :disabled="markingAll"
            >
              {{ markingAll ? '...' : 'Tandai semua dibaca' }}
            </button>
            <Link :href="route('notifications.index')" class="notif-panel__view-all">
              Lihat semua
            </Link>
          </div>
        </div>

        <!-- List -->
        <div class="notif-panel__list" v-if="items.length > 0">
          <div
            v-for="item in items.slice(0, 8)"
            :key="item.id"
            class="notif-item"
            :class="{ 'notif-item--unread': !item.read_at }"
            @click="handleItemClick(item)"
          >
            <div class="notif-item__dot" v-if="!item.read_at" />
            <div class="notif-item__body">
              <p class="notif-item__title">{{ item.title }}</p>
              <p class="notif-item__desc">{{ item.body }}</p>
              <p class="notif-item__time">{{ timeAgo(item.created_at) }}</p>
            </div>
            <button
              class="notif-item__del"
              @click.stop="deleteItem(item)"
              title="Hapus"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                   width="12" height="12"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
          </div>
        </div>

        <!-- Empty state -->
        <div v-else class="notif-panel__empty">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
               width="36" height="36" style="opacity:.3">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
          </svg>
          <p>Belum ada notifikasi</p>
        </div>
      </div>
    </Transition>

    <!-- Backdrop -->
    <div v-if="open" class="notif-backdrop" @click="open = false" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'

// ─── State ──────────────────────────────────────────────────────────────────
const open      = ref(false)
const items     = ref([])
const loading   = ref(false)
const markingAll= ref(false)

const page  = usePage()
const unread = computed(() => page.props.unreadNotifications ?? 0)

// ─── Badge API (app icon badge) ─────────────────────────────────────────────
watch(unread, (count) => {
  if ('setAppBadge' in navigator) {
    count > 0 ? navigator.setAppBadge(count) : navigator.clearAppBadge()
  }
}, { immediate: true })

// ─── Panel toggle + fetch ────────────────────────────────────────────────────
async function togglePanel() {
  open.value = !open.value
  if (open.value && items.value.length === 0) {
    await fetchRecent()
  }
}

async function fetchRecent() {
  loading.value = true
  try {
    const res = await fetch('/notifications?page=1', {
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-Inertia': '1', 'Accept': 'application/json' },
    })
    const json = await res.json()
    items.value = json?.props?.notifications?.data ?? []
  } catch {
    items.value = []
  } finally {
    loading.value = false
  }
}

// ─── Actions ─────────────────────────────────────────────────────────────────
async function handleItemClick(item) {
  if (!item.read_at) {
    await markRead(item)
  }
  open.value = false
  if (item.url && item.url !== '/') {
    router.visit(item.url)
  }
}

async function markRead(item) {
  try {
    await fetch(`/notifications/${item.id}/read`, {
      method: 'PATCH',
      headers: { 'X-CSRF-TOKEN': csrfToken(), 'X-Requested-With': 'XMLHttpRequest' },
    })
    item.read_at = new Date().toISOString()
    router.reload({ only: ['unreadNotifications'] })
  } catch {}
}

async function markAllRead() {
  markingAll.value = true
  try {
    await fetch('/notifications/read-all', {
      method: 'PATCH',
      headers: { 'X-CSRF-TOKEN': csrfToken(), 'X-Requested-With': 'XMLHttpRequest' },
    })
    items.value.forEach(i => { i.read_at = new Date().toISOString() })
    router.reload({ only: ['unreadNotifications'] })
  } catch {} finally {
    markingAll.value = false
  }
}

async function deleteItem(item) {
  try {
    await fetch(`/notifications/${item.id}`, {
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': csrfToken(), 'X-Requested-With': 'XMLHttpRequest' },
    })
    items.value = items.value.filter(i => i.id !== item.id)
    router.reload({ only: ['unreadNotifications'] })
  } catch {}
}

// ─── Helpers ─────────────────────────────────────────────────────────────────
function csrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.content ?? ''
}

function timeAgo(dateStr) {
  const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000)
  if (diff < 60)   return `${diff}d yang lalu`
  if (diff < 3600) return `${Math.floor(diff / 60)}m yang lalu`
  if (diff < 86400)return `${Math.floor(diff / 3600)}j yang lalu`
  return `${Math.floor(diff / 86400)} hari yang lalu`
}

// Close on Escape
function onKeydown(e) { if (e.key === 'Escape') open.value = false }
onMounted(() => window.addEventListener('keydown', onKeydown))
onUnmounted(() => window.removeEventListener('keydown', onKeydown))
</script>

<style scoped>
.notif-bell-wrap { position: relative; }

.notif-bell {
  position: relative;
  width: 2.25rem;
  height: 2.25rem;
  border-radius: 0.75rem;
  border: none;
  background: rgba(0,0,0,0.06);
  color: rgba(0,0,0,0.55);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background 0.2s, color 0.2s, transform 0.2s;
  flex-shrink: 0;
}
.notif-bell:hover, .notif-bell--active {
  background: rgba(0,122,255,0.12);
  color: #007AFF;
  transform: scale(1.06);
}

/* Badge */
.notif-bell__badge {
  position: absolute;
  top: -0.3rem;
  right: -0.3rem;
  min-width: 1.1rem;
  height: 1.1rem;
  padding: 0 0.2rem;
  border-radius: 999px;
  background: #FF3B30;
  color: white;
  font-size: 0.6rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid white;
  line-height: 1;
}

/* Dropdown panel */
.notif-panel {
  position: absolute;
  top: calc(100% + 0.75rem);
  right: 0;
  width: min(360px, calc(100vw - 2rem));
  background: rgba(255,255,255,0.97);
  backdrop-filter: blur(24px) saturate(180%);
  -webkit-backdrop-filter: blur(24px) saturate(180%);
  border: 1px solid rgba(255,255,255,0.9);
  border-radius: 1.25rem;
  box-shadow: 0 20px 60px rgba(0,0,0,0.18), 0 4px 16px rgba(0,0,0,0.10);
  overflow: hidden;
  z-index: 9999;
  max-height: calc(100vh - 6rem);
  overflow-y: auto;
}

.notif-panel__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem 0.75rem;
  border-bottom: 1px solid rgba(0,0,0,0.06);
}
.notif-panel__title {
  font-size: 0.9rem;
  font-weight: 700;
  color: rgba(0,0,0,0.8);
  margin: 0;
}
.notif-panel__actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}
.notif-panel__action-btn {
  font-size: 0.7rem;
  font-weight: 600;
  color: #007AFF;
  background: none;
  border: none;
  cursor: pointer;
  padding: 0;
}
.notif-panel__action-btn:disabled { opacity: 0.5; }
.notif-panel__view-all {
  font-size: 0.7rem;
  font-weight: 600;
  color: rgba(0,0,0,0.4);
  text-decoration: none;
}
.notif-panel__view-all:hover { color: #007AFF; }

/* List */
.notif-panel__list { max-height: 360px; overflow-y: auto; }

.notif-item {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.875rem 1.25rem;
  cursor: pointer;
  border-bottom: 1px solid rgba(0,0,0,0.04);
  transition: background 0.15s;
  position: relative;
}
.notif-item:hover { background: rgba(0,122,255,0.04); }
.notif-item--unread { background: rgba(0,122,255,0.03); }

.notif-item__dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #007AFF;
  flex-shrink: 0;
  margin-top: 0.35rem;
}

.notif-item__body { flex: 1; min-width: 0; }
.notif-item__title {
  font-size: 0.8rem;
  font-weight: 600;
  color: rgba(0,0,0,0.8);
  margin: 0 0 0.15rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.notif-item__desc {
  font-size: 0.73rem;
  color: rgba(0,0,0,0.5);
  margin: 0 0 0.25rem;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.notif-item__time {
  font-size: 0.65rem;
  color: rgba(0,0,0,0.3);
  margin: 0;
}

.notif-item__del {
  background: none;
  border: none;
  cursor: pointer;
  color: rgba(0,0,0,0.2);
  padding: 0.25rem;
  border-radius: 0.35rem;
  display: flex;
  align-items: center;
  opacity: 0;
  transition: opacity 0.15s, color 0.15s;
  flex-shrink: 0;
}
.notif-item:hover .notif-item__del { opacity: 1; }
.notif-item__del:hover { color: #FF3B30; }

/* Empty state */
.notif-panel__empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  padding: 2.5rem 1.5rem;
  color: rgba(0,0,0,0.35);
  font-size: 0.8rem;
}

/* Backdrop */
.notif-backdrop {
  position: fixed;
  inset: 0;
  z-index: 9998;
}

/* Transitions */
.badge-pop-enter-active { animation: badgePop 0.3s cubic-bezier(0.34,1.56,0.64,1); }
.badge-pop-leave-active { animation: badgePop 0.2s reverse; }
@keyframes badgePop {
  from { transform: scale(0); opacity: 0; }
  to   { transform: scale(1); opacity: 1; }
}

.notif-panel-enter-active { animation: panelSlide 0.25s cubic-bezier(0.34,1.56,0.64,1); }
.notif-panel-leave-active { animation: panelSlide 0.2s reverse ease-in; }
@keyframes panelSlide {
  from { opacity: 0; transform: translateY(-8px) scale(0.97); }
  to   { opacity: 1; transform: translateY(0)   scale(1); }
}
</style>
