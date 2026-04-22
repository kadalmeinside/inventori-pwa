<template>
  <AppLayout title="Notifikasi">
    <div class="notif-page">

      <!-- Page header -->
      <div class="page-header">
        <div>
          <p class="page-eyebrow">Inbox</p>
          <h1 class="page-title">Notifikasi</h1>
        </div>
        <div class="header-actions">
          <button
            v-if="hasUnread"
            class="btn-ghost"
            @click="markAllRead"
            :disabled="markingAll"
          >
            {{ markingAll ? 'Memproses...' : '✅ Tandai semua dibaca' }}
          </button>
          <button
            v-if="hasRead"
            class="btn-ghost btn-ghost--danger"
            @click="clearRead"
            :disabled="clearing"
          >
            {{ clearing ? 'Memproses...' : '🗑 Hapus yang dibaca' }}
          </button>
        </div>
      </div>

      <!-- Filter tabs -->
      <div class="filter-tabs">
        <button
          class="filter-tab"
          :class="{ 'filter-tab--active': filter === 'all' }"
          @click="filter = 'all'"
        >Semua <span class="tab-count">{{ notifications.total }}</span></button>
        <button
          class="filter-tab"
          :class="{ 'filter-tab--active': filter === 'unread' }"
          @click="filter = 'unread'"
        >Belum Dibaca <span class="tab-count tab-count--blue">{{ unreadCount }}</span></button>
      </div>

      <!-- Notification list -->
      <div class="notif-list" v-if="filteredItems.length > 0">
        <TransitionGroup name="notif-row" tag="div">
          <div
            v-for="item in filteredItems"
            :key="item.id"
            class="notif-card"
            :class="{ 'notif-card--unread': !item.read_at }"
          >
            <!-- Type indicator -->
            <div class="notif-card__indicator" :class="`notif-card__indicator--${item.type}`" />

            <!-- Content -->
            <div class="notif-card__content" @click="handleClick(item)">
              <div class="notif-card__icon">
                <span v-if="item.type === 'success'">✅</span>
                <span v-else-if="item.type === 'error'">❌</span>
                <span v-else-if="item.type === 'warning'">⚠️</span>
                <span v-else>🔔</span>
              </div>
              <div class="notif-card__body">
                <div class="notif-card__row">
                  <p class="notif-card__title">{{ item.title }}</p>
                  <span v-if="!item.read_at" class="unread-dot" />
                </div>
                <p class="notif-card__desc">{{ item.body }}</p>
                <p class="notif-card__time">{{ timeAgo(item.created_at) }}</p>
              </div>
            </div>

            <!-- Actions -->
            <div class="notif-card__actions">
              <button
                v-if="!item.read_at"
                class="action-btn action-btn--read"
                @click="markRead(item)"
                title="Tandai dibaca"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                     width="13" height="13"><polyline points="20 6 9 17 4 12"/></svg>
              </button>
              <button
                class="action-btn action-btn--del"
                @click="deleteItem(item)"
                title="Hapus"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     width="13" height="13"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
              </button>
            </div>
          </div>
        </TransitionGroup>
      </div>

      <!-- Empty state -->
      <div v-else class="notif-empty">
        <div class="notif-empty__icon">🔔</div>
        <h3 class="notif-empty__title">
          {{ filter === 'unread' ? 'Semua sudah dibaca!' : 'Belum ada notifikasi' }}
        </h3>
        <p class="notif-empty__desc">
          {{ filter === 'unread'
            ? 'Tidak ada notifikasi baru saat ini.'
            : 'Notifikasi akan muncul di sini saat ada aktivitas penting.' }}
        </p>
      </div>

      <!-- Pagination -->
      <div class="pagination" v-if="notifications.last_page > 1">
        <button
          v-for="p in notifications.last_page"
          :key="p"
          class="page-btn"
          :class="{ 'page-btn--active': p === notifications.current_page }"
          @click="goPage(p)"
        >{{ p }}</button>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  notifications: Object,
})

const page       = usePage()
const filter     = ref('all')
const markingAll = ref(false)
const clearing   = ref(false)
const localItems = ref([...props.notifications.data])

const unreadCount = computed(() => localItems.value.filter(i => !i.read_at).length)
const hasUnread   = computed(() => unreadCount.value > 0)
const hasRead     = computed(() => localItems.value.some(i => i.read_at))

const filteredItems = computed(() =>
  filter.value === 'unread'
    ? localItems.value.filter(i => !i.read_at)
    : localItems.value
)

// ─── Actions ─────────────────────────────────────────────────────────────────
function csrf() {
  return document.querySelector('meta[name="csrf-token"]')?.content ?? ''
}

async function markRead(item) {
  await fetch(`/notifications/${item.id}/read`, {
    method: 'PATCH',
    headers: { 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
  })
  item.read_at = new Date().toISOString()
  router.reload({ only: ['unreadNotifications'] })
}

async function markAllRead() {
  markingAll.value = true
  await fetch('/notifications/read-all', {
    method: 'PATCH',
    headers: { 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
  })
  localItems.value.forEach(i => { i.read_at = new Date().toISOString() })
  router.reload({ only: ['unreadNotifications'] })
  markingAll.value = false
}

async function deleteItem(item) {
  await fetch(`/notifications/${item.id}`, {
    method: 'DELETE',
    headers: { 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
  })
  localItems.value = localItems.value.filter(i => i.id !== item.id)
  router.reload({ only: ['unreadNotifications'] })
}

async function clearRead() {
  clearing.value = true
  await fetch('/notifications', {
    method: 'DELETE',
    headers: { 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
  })
  localItems.value = localItems.value.filter(i => !i.read_at)
  clearing.value = false
}

function handleClick(item) {
  if (!item.read_at) markRead(item)
  if (item.url && item.url !== '/') router.visit(item.url)
}

function goPage(p) {
  router.get('/notifications', { page: p }, { preserveState: true })
}

function timeAgo(dateStr) {
  const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000)
  if (diff < 60)    return `${diff}d yang lalu`
  if (diff < 3600)  return `${Math.floor(diff / 60)}m yang lalu`
  if (diff < 86400) return `${Math.floor(diff / 3600)}j yang lalu`
  return `${Math.floor(diff / 86400)} hari yang lalu`
}
</script>

<style scoped>
.notif-page { padding: 2rem 1.25rem 7rem; max-width: 680px; margin: 0 auto; }

/* Header */
.page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1.25rem; gap: 1rem; flex-wrap: wrap; }
.page-eyebrow { font-size: 0.7rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: rgba(0,0,0,0.35); margin: 0 0 0.2rem; padding-left: 0.25rem; }
.page-title { font-size: 1.375rem; font-weight: 800; letter-spacing: -0.03em; color: rgba(0,0,0,0.85); margin: 0; line-height: 1; padding-left: 0.25rem; }

.header-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }

.btn-ghost {
  font-size: 0.75rem; font-weight: 600; padding: 0.45rem 0.9rem;
  border-radius: 0.75rem; border: 1px solid rgba(0,0,0,0.1);
  background: white; cursor: pointer; transition: all 0.2s;
  color: rgba(0,0,0,0.65);
}
.btn-ghost:hover { background: rgba(0,122,255,0.06); color: #007AFF; border-color: rgba(0,122,255,0.3); }
.btn-ghost--danger:hover { background: rgba(255,59,48,0.06); color: #FF3B30; border-color: rgba(255,59,48,0.3); }
.btn-ghost:disabled { opacity: 0.5; cursor: not-allowed; }

/* Filter tabs */
.filter-tabs { display: flex; gap: 0.5rem; margin-bottom: 1rem; }
.filter-tab {
  font-size: 0.78rem; font-weight: 600; padding: 0.4rem 0.9rem;
  border-radius: 999px; border: none; background: rgba(0,0,0,0.06);
  color: rgba(0,0,0,0.5); cursor: pointer; transition: all 0.2s;
  display: flex; align-items: center; gap: 0.4rem;
}
.filter-tab--active { background: #007AFF; color: white; }
.tab-count { font-size: 0.65rem; background: rgba(255,255,255,0.25); border-radius: 999px; padding: 0.05rem 0.4rem; }
.tab-count--blue { background: rgba(0,122,255,0.15); color: #007AFF; }
.filter-tab--active .tab-count { background: rgba(255,255,255,0.3); color: white; }

/* Notification card */
.notif-list { display: flex; flex-direction: column; gap: 0.5rem; }

.notif-card {
  display: flex;
  align-items: stretch;
  background: rgba(255,255,255,0.7);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border: 1px solid rgba(255,255,255,0.9);
  border-radius: 1.25rem;
  box-shadow: 0 4px 20px rgba(0,0,0,0.06);
  overflow: hidden;
  transition: transform 0.2s, box-shadow 0.2s;
}
.notif-card:hover { transform: translateY(-1px); box-shadow: 0 8px 28px rgba(0,0,0,0.1); }
.notif-card--unread { border-color: rgba(0,122,255,0.2); box-shadow: 0 4px 20px rgba(0,122,255,0.08); }

.notif-card__indicator { width: 4px; flex-shrink: 0; }
.notif-card__indicator--success { background: #34C759; }
.notif-card__indicator--error   { background: #FF3B30; }
.notif-card__indicator--warning { background: #FF9500; }
.notif-card__indicator--info    { background: #007AFF; }

.notif-card__content {
  display: flex; align-items: flex-start; gap: 0.875rem;
  padding: 1rem 0.75rem 1rem 1rem; flex: 1; cursor: pointer;
}
.notif-card__icon { font-size: 1.25rem; flex-shrink: 0; line-height: 1; margin-top: 0.1rem; }

.notif-card__body { flex: 1; min-width: 0; }
.notif-card__row { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.2rem; }
.notif-card__title { font-size: 0.85rem; font-weight: 700; color: rgba(0,0,0,0.8); margin: 0; }
.unread-dot { width: 7px; height: 7px; border-radius: 50%; background: #007AFF; flex-shrink: 0; }
.notif-card__desc { font-size: 0.78rem; color: rgba(0,0,0,0.5); margin: 0 0 0.35rem; line-height: 1.5; }
.notif-card__time { font-size: 0.68rem; color: rgba(0,0,0,0.3); margin: 0; }

.notif-card__actions {
  display: flex; flex-direction: column; justify-content: center;
  gap: 0.25rem; padding: 0.75rem; border-left: 1px solid rgba(0,0,0,0.05);
  background: rgba(0,0,0,0.01);
}
.action-btn {
  width: 1.75rem; height: 1.75rem; border-radius: 0.5rem; border: none;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all 0.15s;
}
.action-btn--read  { background: rgba(52,199,89,0.1); color: #34C759; }
.action-btn--read:hover { background: #34C759; color: white; }
.action-btn--del   { background: rgba(255,59,48,0.08); color: rgba(255,59,48,0.6); }
.action-btn--del:hover { background: #FF3B30; color: white; }

/* Empty state */
.notif-empty { text-align: center; padding: 4rem 2rem; }
.notif-empty__icon { font-size: 3rem; margin-bottom: 1rem; opacity: 0.3; }
.notif-empty__title { font-size: 1.1rem; font-weight: 700; color: rgba(0,0,0,0.5); margin: 0 0 0.5rem; }
.notif-empty__desc { font-size: 0.8rem; color: rgba(0,0,0,0.35); margin: 0; }

/* Pagination */
.pagination { display: flex; justify-content: center; gap: 0.35rem; margin-top: 1.5rem; flex-wrap: wrap; }
.page-btn { width: 2.25rem; height: 2.25rem; border-radius: 0.65rem; border: 1px solid rgba(0,0,0,0.1); background: white; font-size: 0.8rem; font-weight: 600; cursor: pointer; transition: all 0.15s; }
.page-btn:hover { background: rgba(0,122,255,0.08); }
.page-btn--active { background: #007AFF; color: white; border-color: #007AFF; }

/* Transition */
.notif-row-enter-active { transition: all 0.3s ease; }
.notif-row-leave-active { transition: all 0.25s ease; position: absolute; width: 100%; }
.notif-row-enter-from  { opacity: 0; transform: translateX(-12px); }
.notif-row-leave-to    { opacity: 0; transform: translateX(12px); }
</style>
