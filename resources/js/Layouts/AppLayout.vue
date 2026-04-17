<template>
  <div class="app-layout">
    <!-- Skip to main content (a11y) -->
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <!-- ─── Floating Desktop Sidebar ─────────────────────────────────────── -->
    <aside class="sidebar" role="navigation" aria-label="Desktop Navigation">
      <!-- Inner glass panel -->
      <div class="sidebar__glass">
        <!-- Brand -->
        <div class="sidebar__brand">
          <div class="sidebar__logo">
            <img src="/logo-inventory.png" alt="Inventori Logo" class="sidebar__logo-img">
          </div>
          <span class="sidebar__name">Inventori</span>
        </div>

        <!-- Main nav -->
        <nav class="sidebar__nav">
          <p class="sidebar__section-label">General</p>
          <Link
            v-for="item in navItems"
            :key="item.route"
            :href="route(item.route)"
            class="sidebar__link"
            :class="{ 'sidebar__link--active': isActive(item) }"
          >
            <span class="sidebar__link-icon" v-html="item.icon" />
            <span class="sidebar__link-text">{{ item.label }}</span>
            <span v-if="item.badge" class="sidebar__badge">{{ item.badge }}</span>
          </Link>
          <Link
            :href="route('stock-outs.index')"
            class="sidebar__link"
            :class="{ 'sidebar__link--active': route().current('stock-outs.*') }"
          >
            <span class="sidebar__link-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/><path d="M16 3h5"/></svg>
            </span>
            <span class="sidebar__link-text">Stock Out</span>
          </Link>
          <Link
            :href="route('transfer-requests.index')"
            class="sidebar__link"
            :class="{ 'sidebar__link--active': route().current('transfer-requests.*') }"
          >
            <span class="sidebar__link-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 3h5v5"/><path d="M8 3H3v5"/><path d="M21 3l-7.5 7.5"/><path d="M3 3l7.5 7.5"/><path d="M16 21h5v-5"/><path d="M21 21l-7.5-7.5"/><path d="M3 21l7.5-7.5"/><path d="M8 21H3v-5"/></svg>
            </span>
            <span class="sidebar__link-text">Requests</span>
          </Link>
          <Link
            :href="route('reports.index')"
            class="sidebar__link"
            :class="{ 'sidebar__link--active': route().current('reports.*') }"
          >
            <span class="sidebar__link-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                <line x1="3" y1="9" x2="21" y2="9"/>
                <line x1="9" y1="21" x2="9" y2="9"/>
              </svg>
            </span>
            <span class="sidebar__link-text">Reports</span>
          </Link>
        </nav>

        <!-- Management section (Super Admin) -->
        <div v-if="$page.props.auth.user.role === 'super_admin'" class="sidebar__section">
          <p class="sidebar__section-label">Management</p>
          <nav class="sidebar__nav" style="gap: 0.25rem;">
            <Link
              :href="route('products.index')"
              class="sidebar__link"
              :class="{ 'sidebar__link--active': route().current('products.*') }"
            >
              <span class="sidebar__link-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M20 7H4a2 2 0 00-2 2v9a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/>
                  <path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/>
                </svg>
              </span>
              <span class="sidebar__link-text">Products</span>
            </Link>
            <Link
              :href="route('categories.index')"
              class="sidebar__link"
              :class="{ 'sidebar__link--active': route().current('categories.*') }"
            >
              <span class="sidebar__link-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/>
                </svg>
              </span>
              <span class="sidebar__link-text">Categories</span>
            </Link>
            <Link
              :href="route('warehouses.index')"
              class="sidebar__link"
              :class="{ 'sidebar__link--active': route().current('warehouses.*') }"
            >
              <span class="sidebar__link-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                  <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
              </span>
              <span class="sidebar__link-text">Warehouses</span>
            </Link>
            <Link
              :href="route('users.index')"
              class="sidebar__link"
              :class="{ 'sidebar__link--active': route().current('users.*') }"
            >
              <span class="sidebar__link-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                  <circle cx="9" cy="7" r="4"/>
                  <path d="M23 21v-2a4 4 0 00-3-3.87"/>
                  <path d="M16 3.13a4 4 0 010 7.75"/>
                </svg>
              </span>
              <span class="sidebar__link-text">Users</span>
            </Link>
          </nav>
        </div>

        <!-- User footer -->
        <div class="sidebar__footer">
          <div class="sidebar__user">
            <div class="sidebar__avatar">{{ userInitials }}</div>
            <Link :href="route('profile.edit')" class="sidebar__user-info">
              <p class="sidebar__user-name">{{ $page.props.auth.user.name }}</p>
              <p class="sidebar__user-role">{{ userRole }}</p>
            </Link>
            <button @click="showLogoutConfirm = true" class="sidebar__logout" title="Logout">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
              </svg>
            </button>
          </div>
        </div>
      </div>
    </aside>

    <!-- ─── Main Content ──────────────────────────────────────────────────── -->
    <main id="main-content" class="layout-main">
      <Head :title="title" />
      <slot />
    </main>

    <!-- ─── Live Pusher Toast ─────────────────────────────────────────────── -->
    <Transition name="toast">
      <div v-if="liveToast" class="toast" :class="'toast--' + liveToast.type" role="alert">
        <span class="toast__icon">{{ liveToast.type === 'success' ? '✅' : (liveToast.type === 'error' ? '❌' : '🔔') }}</span>
        {{ liveToast.message }}
      </div>
    </Transition>

    <!-- ─── Flash Toast (from server redirect) ────────────────────────────── -->
    <Transition name="toast">
      <div v-if="!liveToast && flash.success" class="toast toast--success" role="alert">
        <span class="toast__icon">✅</span> {{ flash.success }}
      </div>
    </Transition>
    <Transition name="toast">
      <div v-if="!liveToast && flash.error" class="toast toast--error" role="alert">
        <span class="toast__icon">❌</span> {{ flash.error }}
      </div>
    </Transition>

    <!-- ─── Global Mobile Nav (always present on all pages) ──────────────── -->
    <!-- ─── Global Mobile Nav (always present on all pages) ──────────────── -->
    <MobileNav @logout="showLogoutConfirm = true" />

    <!-- ─── Logout Confirmation Modal ─────────────────────────────────────── -->
    <Modal :show="showLogoutConfirm" @close="showLogoutConfirm = false" maxWidth="sm">
      <div class="p-6">
        <div class="flex items-start gap-4 mb-6">
          <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-red-100 text-red-500 flex items-center justify-center border border-red-200">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
          </div>
          <div>
            <h2 class="text-lg font-bold text-gray-900 leading-tight">Sign Out Confirmation</h2>
            <p class="text-xs text-gray-500 mt-1">Are you sure you want to log out of your account?</p>
          </div>
        </div>
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
          <button type="button" @click="showLogoutConfirm = false" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 bg-gray-50 hover:bg-gray-100 border border-gray-200 transition-colors">
            Cancel
          </button>
          <button type="button" @click="confirmLogout" class="px-4 py-2 rounded-lg text-sm font-medium text-white bg-red-500 hover:bg-red-600 shadow-sm border border-red-600 transition-colors">
            Sign Out
          </button>
        </div>
      </div>
    </Modal>

    <!-- ─── PWA Install Prompt ────────────────────────────────────────────── -->
    <InstallPrompt />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head, Link, usePage, router } from '@inertiajs/vue3'
import MobileNav     from '@/Components/MobileNav.vue'
import InstallPrompt from '@/Components/InstallPrompt.vue'
import Modal         from '@/Components/Modal.vue'

defineProps({
  title: { type: String, default: 'Inventori IMS' },
})

const page  = usePage()
const flash = computed(() => page.props.flash ?? {})

const showLogoutConfirm = ref(false)
const liveToast = ref(null)
let echoChannel;

onMounted(() => {
    if (window.Echo && page.props.auth?.user) {
        const role = page.props.auth.user.role;
        const channelName = role === 'super_admin' ? 'superadmin' : `warehouse.${page.props.auth.user.warehouse_id}`;
        
        echoChannel = window.Echo.private(channelName)
            .listen('.SystemNotification', (e) => {
                liveToast.value = { message: e.message, type: e.type };
                setTimeout(() => { liveToast.value = null; }, 5000);
            });
    }
});

onUnmounted(() => {
    if (echoChannel && window.Echo) {
        window.Echo.leave(echoChannel.name);
    }
});

const confirmLogout = () => {
  router.delete(route('logout'))
}

const navItems = [
  {
    route: 'dashboard',
    label: 'Dashboard',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>`,
    badge: null,
  },
  {
    route: 'stocks.index',
    label: 'Stock',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>`,
    badge: null,
  },
  {
    route: 'transfers.index',
    label: 'Transfers',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16 3 4 4-4 4"/><path d="M20 7H4"/><path d="m8 21-4-4 4-4"/><path d="M4 17h16"/></svg>`,
    badge: null,
  },
]

const isActive = (item) => {
  const url = page.url
  const map  = { dashboard: '/', 'stocks.index': '/stocks', 'transfers.index': '/transfers' }
  const p    = map[item.route]
  return p === '/' ? url === '/' : url.startsWith(p)
}

const userInitials = computed(() => {
  const name = page.props.auth?.user?.name ?? 'U'
  return name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase()
})

const userRole = computed(() => {
  const role = page.props.auth?.user?.role ?? 'branch_admin'
  return role === 'super_admin' ? 'Super Admin' : 'Branch Admin'
})
</script>

<style scoped>
/* ─── App layout shell ───────────────────────────────────────────────────── */
.app-layout {
  display: flex;
  min-height: 100vh;
  position: relative;
  background: #f2f4f8;
}

/* ─── Skip link ──────────────────────────────────────────────────────────── */
.skip-link {
  position: absolute;
  top: -100px;
  left: 1rem;
  background: var(--ios-blue);
  color: #fff;
  padding: 0.5rem 1rem;
  border-radius: 0 0 0.5rem 0.5rem;
  z-index: 9999;
  transition: top 0.2s;
  font-size: 0.875rem;
}
.skip-link:focus { top: 0; }

/* ─── Sidebar wrapper ────────────────────────────────────────────────────── */
.sidebar {
  width: 260px;
  flex-shrink: 0;
  display: none;
  flex-direction: column;
  position: sticky;
  top: 0;
  height: 100vh;
  padding: 1rem;
  overflow-y: auto;
  z-index: 50;
}

@media (min-width: 768px) {
  .sidebar { display: flex; }
}

/* Glass inner panel */
.sidebar__glass {
  flex: 1;
  display: flex;
  flex-direction: column;
  background: rgba(255, 255, 255, 0.60);
  backdrop-filter: blur(32px) saturate(200%);
  -webkit-backdrop-filter: blur(32px) saturate(200%);
  border: 1px solid rgba(255, 255, 255, 0.80);
  border-radius: 1.5rem;
  box-shadow:
    0 8px 40px rgba(0, 80, 200, 0.10),
    0 2px 8px rgba(0, 0, 0, 0.05),
    inset 0 1px 0 rgba(255, 255, 255, 0.95),
    inset 0 -1px 0 rgba(0, 0, 0, 0.03);
  padding: 1.25rem 1rem 1rem;
  overflow: hidden;
}

/* ─── Brand ──────────────────────────────────────────────────────────────── */
.sidebar__brand {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0 0.5rem;
  margin-bottom: 1.75rem;
}

.sidebar__logo {
  width: 2.5rem;
  height: 2.5rem;
  background: white;
  border-radius: 0.875rem;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 4px 12px rgba(0, 122, 255, 0.20), inset 0 1px 0 rgba(255,255,255,0.9);
  overflow: hidden;
  padding: 4px;
}

.sidebar__logo-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  border-radius: 0.5rem;
}

.sidebar__name {
  font-size: 1.125rem;
  font-weight: 800;
  letter-spacing: -0.03em;
  background: linear-gradient(135deg, #007AFF, #5856D6);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

/* ─── Section label ──────────────────────────────────────────────────────── */
.sidebar__section-label {
  font-size: 0.65rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(0, 0, 0, 0.30);
  padding: 0 0.625rem;
  margin-bottom: 0.375rem;
  margin-top: 0.25rem;
}

.sidebar__section {
  margin-top: 0.75rem;
}

/* ─── Nav ────────────────────────────────────────────────────────────────── */
.sidebar__nav {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  flex: 1;
}

.sidebar__link {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.625rem 0.75rem;
  border-radius: 0.875rem;
  text-decoration: none;
  color: rgba(0, 0, 0, 0.50);
  font-size: 0.875rem;
  font-weight: 500;
  transition:
    background 0.2s var(--ease-ios),
    color 0.2s var(--ease-ios),
    box-shadow 0.2s;
  position: relative;
  -webkit-tap-highlight-color: transparent;
}

.sidebar__link:hover {
  background: rgba(255, 255, 255, 0.6);
  color: rgba(0, 0, 0, 0.80);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06), inset 0 1px 0 rgba(255,255,255,0.9);
}

.sidebar__link--active {
  background: rgba(0, 122, 255, 0.12);
  color: #007AFF;
  box-shadow: 0 2px 8px rgba(0, 122, 255, 0.15), inset 0 1px 0 rgba(255,255,255,0.8);
}

.sidebar__link-icon {
  width: 1.125rem;
  height: 1.125rem;
  flex-shrink: 0;
  display: flex;
  align-items: center;
}

.sidebar__link-icon :deep(svg) { width: 100%; height: 100%; }

.sidebar__link-text { flex: 1; }

.sidebar__badge {
  margin-left: auto;
  background: var(--ios-red);
  color: white;
  font-size: 0.625rem;
  font-weight: 700;
  padding: 0.1rem 0.4rem;
  border-radius: 999px;
  min-width: 1.1rem;
  text-align: center;
}

/* ─── User footer ────────────────────────────────────────────────────────── */
.sidebar__footer {
  margin-top: auto;
  padding-top: 0.75rem;
}

.sidebar__user {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  padding: 0.625rem 0.5rem;
  border-top: 1px solid rgba(0, 0, 0, 0.06);
}

.sidebar__avatar {
  width: 2.25rem;
  height: 2.25rem;
  background: linear-gradient(145deg, #007AFF, #5856D6);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.7rem;
  font-weight: 700;
  color: white;
  flex-shrink: 0;
  box-shadow: 0 2px 8px rgba(0, 122, 255, 0.3);
}

.sidebar__user-info {
  flex: 1;
  min-width: 0;
  text-decoration: none;
  transition: opacity 0.15s;
}
.sidebar__user-info:hover { opacity: 0.75; }

.sidebar__user-name {
  font-size: 0.8rem;
  font-weight: 600;
  color: rgba(0, 0, 0, 0.80);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin: 0;
}

.sidebar__user-role {
  font-size: 0.68rem;
  color: rgba(0, 0, 0, 0.35);
  margin: 0;
}

.sidebar__logout {
  background: transparent;
  border: none;
  color: rgba(0, 0, 0, 0.30);
  cursor: pointer;
  padding: 0.4rem;
  display: flex;
  align-items: center;
  border-radius: 0.5rem;
  transition: color 0.15s, background 0.15s;
  flex-shrink: 0;
}
.sidebar__logout:hover {
  color: var(--ios-red);
  background: rgba(255, 59, 48, 0.08);
}

/* ─── Main content ───────────────────────────────────────────────────────── */
.layout-main {
  flex: 1;
  min-width: 0;
}

/* ─── Toast ──────────────────────────────────────────────────────────────── */
.toast {
  position: fixed;
  top: max(1.25rem, env(safe-area-inset-top, 1.25rem));
  left: 1.25rem;
  right: 1.25rem;
  margin: 0 auto;
  width: fit-content;
  max-width: calc(100vw - 2.5rem);
  padding: 0.875rem 1.25rem;
  border-radius: 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  z-index: 99999;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(28px);
  -webkit-backdrop-filter: blur(28px);
  border: 1px solid rgba(255, 255, 255, 0.9);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12), inset 0 1px 0 rgba(255,255,255,1);
  color: rgba(0, 0, 0, 0.80);
  word-break: break-word; /* Ensure long words wrap */
}

@media (min-width: 400px) {
  .toast {
    max-width: 360px;
  }
}

@media (min-width: 640px) {
  .toast {
    left: auto;
    right: 1.25rem;
  }
}

.toast--success { border-left: 3px solid var(--ios-green); }
.toast--error   { border-left: 3px solid var(--ios-red); }
.toast--info    { border-left: 3px solid var(--ios-blue); }

.toast-enter-active, .toast-leave-active {
  transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.toast-enter-from {
  opacity: 0;
  transform: translateY(-0.75rem) scale(0.95);
}
.toast-leave-to {
  opacity: 0;
  transform: translateY(-0.5rem) scale(0.97);
}
</style>
