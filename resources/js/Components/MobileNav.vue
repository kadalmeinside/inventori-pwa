<template>
  <!-- ─── Floating Reveal Button (visible when nav is hidden) ─────────────── -->
  <Transition name="fab">
    <button
      v-if="isHidden && !showSheet"
      class="nav-fab"
      @click="revealNav"
      aria-label="Show navigation"
    >
      <!-- Compass / grid icon -->
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
           stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
        <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
        <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
      </svg>
    </button>
  </Transition>

  <!-- ─── Bottom Navigation Bar ────────────────────────────────────────────── -->
  <nav
    id="mobile-nav"
    class="mobile-nav"
    :class="{ 'mobile-nav--hidden': isHidden }"
    role="navigation"
    aria-label="Mobile Navigation"
  >
    <div class="mobile-nav__bar">

      <!-- Dashboard -->
      <Link :href="route('dashboard')" class="nav-tab" :class="{ 'nav-tab--active': cur('dashboard') }" aria-label="Dashboard">
        <div class="nav-tab__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
            <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
          </svg>
        </div>
        <span class="nav-tab__label">Dashboard</span>
      </Link>

      <!-- Stocks -->
      <Link :href="route('stocks.index')" class="nav-tab" :class="{ 'nav-tab--active': cur('stocks.index') }" aria-label="Stocks">
        <div class="nav-tab__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>
          </svg>
        </div>
        <span class="nav-tab__label">Stocks</span>
      </Link>

      <!-- Transfers -->
      <Link :href="route('transfers.index')" class="nav-tab" :class="{ 'nav-tab--active': cur('transfers.index') }" aria-label="Transfers">
        <div class="nav-tab__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m16 3 4 4-4 4"/><path d="M20 7H4"/><path d="m8 21-4-4 4-4"/><path d="M4 17h16"/>
          </svg>
        </div>
        <span class="nav-tab__label">Transfers</span>
      </Link>

      <!-- Stock Out -->
      <Link :href="route('stock-outs.index')" class="nav-tab" :class="{ 'nav-tab--active': cur('stock-outs.index') }" aria-label="Stock Out">
        <div class="nav-tab__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/><path d="M16 3h5"/>
          </svg>
        </div>
        <span class="nav-tab__label">Stock Out</span>
      </Link>

      <!-- More -->
      <button class="nav-tab" :class="{ 'nav-tab--active': showSheet }" @click="toggleSheet" aria-label="More" :aria-expanded="showSheet">
        <div class="nav-tab__icon">
          <div class="more-dots"><span /><span /><span /></div>
        </div>
        <span class="nav-tab__label">More</span>
      </button>
    </div>
  </nav>

  <!-- ─── Sheet Backdrop ────────────────────────────────────────────────────── -->
  <Transition name="backdrop">
    <div v-if="showSheet" class="sheet-backdrop" @click="showSheet = false" />
  </Transition>

  <!-- ─── More Bottom Sheet ─────────────────────────────────────────────────── -->
  <Transition name="sheet">
    <div v-if="showSheet" class="bottom-sheet" role="dialog" aria-modal="true" aria-label="More Navigation">
      <div class="sheet-handle" />

      <div class="sheet-title">
        <span>Navigation</span>
        <button class="sheet-close" @click="showSheet = false">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
            <path d="M18 6L6 18M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <div class="sheet-grid">
        <!-- Products (super admin only) -->
        <Link v-if="isSuperAdmin" :href="route('products.index')" class="sheet-item" :class="{ 'sheet-item--active': cur('products.index') }" @click="showSheet = false">
          <div class="sheet-item__icon sheet-item__icon--orange">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20">
              <path d="M20 7H4a2 2 0 00-2 2v9a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/>
              <path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/>
            </svg>
          </div>
          <span class="sheet-item__label">Products</span>
        </Link>

        <!-- Categories (super admin only) -->
        <a v-if="isSuperAdmin" href="#" class="sheet-item" :class="{ 'sheet-item--active': cur('categories.index') }" @click.prevent="goTo(route('categories.index'))">
          <div class="sheet-item__icon sheet-item__icon--teal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20">
              <path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/>
            </svg>
          </div>
          <span class="sheet-item__label">Categories</span>
        </a>

        <!-- Warehouses (super admin only) -->
        <a v-if="isSuperAdmin" href="#" class="sheet-item" :class="{ 'sheet-item--active': cur('warehouses.index') }" @click.prevent="goTo(route('warehouses.index'))">
          <div class="sheet-item__icon sheet-item__icon--teal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20">
              <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
              <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
          </div>
          <span class="sheet-item__label">Warehouses</span>
        </a>

        <!-- Users (super admin only) -->
        <a v-if="isSuperAdmin" href="#" class="sheet-item" :class="{ 'sheet-item--active': cur('users.index') }" @click.prevent="goTo(route('users.index'))">
          <div class="sheet-item__icon sheet-item__icon--purple">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20">
              <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
              <circle cx="9" cy="7" r="4"/>
              <path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/>
            </svg>
          </div>
          <span class="sheet-item__label">Users</span>
        </a>

        <!-- Transfer Requests (all roles) -->
        <a href="#" class="sheet-item" :class="{ 'sheet-item--active': cur('transfer-requests.index') }" @click.prevent="goTo(route('transfer-requests.index'))">
          <div class="sheet-item__icon sheet-item__icon--amber">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20">
              <path d="M16 3h5v5"/><path d="M8 3H3v5"/>
              <path d="M21 3l-7 7"/><path d="M3 3l7 7"/>
              <path d="M16 21h5v-5"/><path d="M21 21l-7-7"/>
              <path d="M3 21l7-7"/><path d="M8 21H3v-5"/>
            </svg>
          </div>
          <span class="sheet-item__label">Requests</span>
        </a>

        <!-- Reports (all roles) -->
        <a href="#" class="sheet-item" :class="{ 'sheet-item--active': cur('reports.index') }" @click.prevent="goTo(route('reports.index'))">
          <div class="sheet-item__icon sheet-item__icon--pink">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20">
              <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
              <line x1="3" y1="9" x2="21" y2="9"/>
              <line x1="9" y1="21" x2="9" y2="9"/>
            </svg>
          </div>
          <span class="sheet-item__label">Reports</span>
        </a>

        <!-- Profile -->
        <a href="#" class="sheet-item" :class="{ 'sheet-item--active': cur('profile.edit') }" @click.prevent="goTo(route('profile.edit'))">
          <div class="sheet-item__icon sheet-item__icon--blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20">
              <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
              <circle cx="12" cy="7" r="4"/>
            </svg>
          </div>
          <span class="sheet-item__label">Profile</span>
        </a>
      </div>

      <div class="sheet-divider" />

      <button class="sheet-logout" @click="emit('logout'); showSheet = false">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
          <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
          <polyline points="16 17 21 12 16 7"/>
          <line x1="21" y1="12" x2="9" y2="12"/>
        </svg>
        Sign Out
      </button>
    </div>
  </Transition>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const emit = defineEmits(['logout']);

const page         = usePage();
const showSheet    = ref(false);
const isHidden     = ref(false);
const isSuperAdmin = computed(() => page.props.auth?.user?.role === 'super_admin');

const toggleSheet = () => { showSheet.value = !showSheet.value; };
const closeSheet  = () => { showSheet.value = false; };
const cur = (routeName) => { try { return route().current(routeName); } catch { return false; } };



const goTo = (url) => {
  showSheet.value = false;
  setTimeout(() => router.get(url), 150);
};

// ─── Scroll-hide logic ────────────────────────────────────────────────────────
let lastScrollY  = 0;
let ticking      = false;
let pinTimer     = null;

const PIN_MS = 2500; // ms to keep nav visible after reveal

const revealNav = () => {
  isHidden.value = false;
  resetPinTimer();
};

const resetPinTimer = () => {
  clearTimeout(pinTimer);
  pinTimer = setTimeout(() => { /* allow auto-hide again */ }, PIN_MS);
};

const handleScroll = () => {
  if (ticking) return;
  ticking = true;
  requestAnimationFrame(() => {
    const y   = window.scrollY;
    const dy  = y - lastScrollY;

    // Never hide when sheet is open
    if (!showSheet.value) {
      if (dy > 8 && y > 120) {
        // Scrolling down more than threshold → hide
        isHidden.value = true;
      } else if (dy < -8) {
        // Scrolling back up → reveal
        isHidden.value = false;
      }
    }

    lastScrollY = y;
    ticking = false;
  });
};

onMounted(() => {
  lastScrollY = window.scrollY;
  window.addEventListener('scroll', handleScroll, { passive: true });
});

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll);
  clearTimeout(pinTimer);
});
</script>

<style scoped>
/* ─── Only on mobile ─────────────────────────────────────────────────────── */
@media (min-width: 768px) {
  .mobile-nav, .nav-fab, .sheet-backdrop, .bottom-sheet { display: none !important; }
}

/* ─── Nav Bar ────────────────────────────────────────────────────────────── */
.mobile-nav {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 100;
  padding: 0 0.75rem calc(env(safe-area-inset-bottom, 0px) + 0.5rem) 0.75rem;
  transition: transform 0.4s cubic-bezier(0.34, 1.1, 0.64, 1);
  pointer-events: none;
}

.mobile-nav--hidden {
  transform: translateY(calc(100% + 16px));
}

.mobile-nav__bar {
  display: flex;
  align-items: center;
  justify-content: space-around;
  padding: 0.625rem 0.5rem;
  background: rgba(255, 255, 255, 0.88);
  backdrop-filter: blur(32px) saturate(200%);
  -webkit-backdrop-filter: blur(32px) saturate(200%);
  border: 1px solid rgba(255, 255, 255, 0.95);
  border-radius: 1.5rem;
  box-shadow:
    0 8px 32px rgba(0, 80, 200, 0.14),
    0 2px 8px rgba(0, 0, 0, 0.06),
    inset 0 1.5px 0 rgba(255, 255, 255, 1),
    inset 0 -1px 0 rgba(0, 0, 0, 0.03);
  pointer-events: all;
}

/* ─── Tab ────────────────────────────────────────────────────────────────── */
.nav-tab {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.25rem;
  padding: 0.375rem 0.625rem;
  border-radius: 1rem;
  text-decoration: none;
  color: rgba(0, 0, 0, 0.38);
  min-width: 3.25rem;
  cursor: pointer;
  border: none;
  background: transparent;
  font-family: inherit;
  transition:
    color 0.2s,
    background 0.2s,
    transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1),
    box-shadow 0.2s;
  -webkit-tap-highlight-color: transparent;
}

.nav-tab:active { transform: scale(0.90); }

.nav-tab--active {
  color: var(--ios-blue);
  background: rgba(0, 122, 255, 0.10);
  box-shadow: inset 0 1px 0 rgba(255,255,255,0.8), 0 2px 8px rgba(0,122,255,0.10);
}

.nav-tab__icon {
  width: 1.5rem;
  height: 1.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.nav-tab__icon svg { width: 100%; height: 100%; }
.nav-tab--active .nav-tab__icon { transform: scale(1.1); }

.nav-tab__label { font-size: 0.62rem; font-weight: 600; letter-spacing: 0.01em; line-height: 1; }

.more-dots { display: flex; align-items: center; gap: 3px; }
.more-dots span { width: 4px; height: 4px; border-radius: 50%; background: currentColor; display: block; }

/* ─── Floating Reveal Button ─────────────────────────────────────────────── */
.nav-fab {
  position: fixed;
  bottom: calc(env(safe-area-inset-bottom, 0px) + 1rem);
  left: 50%;
  transform: translateX(-50%);
  z-index: 99;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 3rem;
  height: 3rem;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.88);
  backdrop-filter: blur(20px) saturate(200%);
  -webkit-backdrop-filter: blur(20px) saturate(200%);
  border: 1px solid rgba(255, 255, 255, 0.95);
  box-shadow:
    0 8px 24px rgba(0, 80, 200, 0.20),
    0 2px 6px rgba(0, 0, 0, 0.10),
    inset 0 1.5px 0 rgba(255, 255, 255, 1);
  color: var(--ios-blue);
  cursor: pointer;
  -webkit-tap-highlight-color: transparent;
  transition:
    transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1),
    box-shadow 0.2s,
    background 0.15s;
}

.nav-fab:hover {
  background: rgba(255, 255, 255, 0.98);
  box-shadow:
    0 12px 32px rgba(0, 80, 200, 0.28),
    0 4px 10px rgba(0, 0, 0, 0.12),
    inset 0 1.5px 0 rgba(255, 255, 255, 1),
    inset 1px 0 0 rgba(255,255,255,0.7),
    inset -1px 0 0 rgba(255,255,255,0.7);
  transform: translateX(-50%) scale(1.1) translateY(-2px);
}

.nav-fab:active {
  transform: translateX(-50%) scale(0.93);
}

/* FAB transition */
.fab-enter-active { transition: opacity 0.25s ease, transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1); }
.fab-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.fab-enter-from  { opacity: 0; transform: translateX(-50%) scale(0.7) translateY(12px); }
.fab-leave-to    { opacity: 0; transform: translateX(-50%) scale(0.8) translateY(8px); }

/* ─── Backdrop ───────────────────────────────────────────────────────────── */
.sheet-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.20);
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
  z-index: 199;
}

.backdrop-enter-active, .backdrop-leave-active { transition: opacity 0.28s ease; }
.backdrop-enter-from, .backdrop-leave-to { opacity: 0; }

/* ─── Bottom Sheet ───────────────────────────────────────────────────────── */
.bottom-sheet {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 200;
  padding: 0.75rem 1.25rem calc(env(safe-area-inset-bottom, 0px) + 6rem) 1.25rem;
  background: rgba(248, 250, 252, 0.96);
  backdrop-filter: blur(40px) saturate(200%);
  -webkit-backdrop-filter: blur(40px) saturate(200%);
  border-top: 1px solid rgba(255, 255, 255, 0.95);
  border-radius: 1.75rem 1.75rem 0 0;
  box-shadow:
    0 -8px 40px rgba(0, 80, 200, 0.12),
    0 -2px 8px rgba(0, 0, 0, 0.06),
    inset 0 1.5px 0 rgba(255, 255, 255, 1);
}

.sheet-enter-active { transition: transform 0.42s cubic-bezier(0.34, 1.2, 0.64, 1); }
.sheet-leave-active { transition: transform 0.28s cubic-bezier(0.4, 0, 1, 1); }
.sheet-enter-from, .sheet-leave-to { transform: translateY(100%); }

/* Handle */
.sheet-handle {
  width: 2.5rem;
  height: 0.25rem;
  background: rgba(0, 0, 0, 0.14);
  border-radius: 999px;
  margin: 0 auto 1.25rem;
}

/* Sheet header */
.sheet-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.25rem;
}

.sheet-title span {
  font-size: 1.125rem;
  font-weight: 800;
  letter-spacing: -0.03em;
  color: rgba(0, 0, 0, 0.82);
}

.sheet-close {
  width: 1.875rem;
  height: 1.875rem;
  background: rgba(0, 0, 0, 0.07);
  border: none;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: rgba(0, 0, 0, 0.45);
  cursor: pointer;
  -webkit-tap-highlight-color: transparent;
}

/* Sheet grid */
.sheet-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.sheet-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  padding: 0.875rem 0.5rem;
  border-radius: 1.125rem;
  text-decoration: none;
  background: rgba(255, 255, 255, 0.72);
  border: 1px solid rgba(255, 255, 255, 0.88);
  box-shadow: 0 2px 8px rgba(0,0,0,0.05), inset 0 1px 0 rgba(255,255,255,0.95);
  transition:
    transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1),
    box-shadow 0.2s,
    background 0.15s;
  cursor: pointer;
  -webkit-tap-highlight-color: transparent;
}

.sheet-item:active { transform: scale(0.92); }

.sheet-item:hover {
  background: rgba(255, 255, 255, 0.95);
  transform: scale(1.04) translateY(-2px);
  box-shadow:
    0 8px 24px rgba(0, 80, 200, 0.14),
    inset 0 1.5px 0 rgba(255, 255, 255, 1),
    inset 1px 0 0 rgba(255,255,255,0.7),
    inset -1px 0 0 rgba(255,255,255,0.7);
}

.sheet-item--active {
  background: rgba(0, 122, 255, 0.09);
  border-color: rgba(0, 122, 255, 0.20);
}

.sheet-item__icon {
  width: 2.75rem;
  height: 2.75rem;
  border-radius: 0.875rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.sheet-item:active .sheet-item__icon { transform: scale(0.88); }
.sheet-item:hover  .sheet-item__icon { transform: scale(1.08); }

.sheet-item__icon--orange { background: linear-gradient(145deg, #FF9500, #E07800); color: white; box-shadow: 0 4px 12px rgba(255,149,0,0.35); }
.sheet-item__icon--teal   { background: linear-gradient(145deg, #30B0C7, #1A8FA8); color: white; box-shadow: 0 4px 12px rgba(48,176,199,0.35); }
.sheet-item__icon--purple { background: linear-gradient(145deg, #BF5AF2, #9B40D4); color: white; box-shadow: 0 4px 12px rgba(175,82,222,0.35); }
.sheet-item__icon--blue   { background: linear-gradient(145deg, #007AFF, #0055D4); color: white; box-shadow: 0 4px 12px rgba(0,122,255,0.35); }
.sheet-item__icon--amber  { background: linear-gradient(145deg, #FFB340, #F59300); color: white; box-shadow: 0 4px 12px rgba(245,147,0,0.35); }
.sheet-item__icon--pink   { background: linear-gradient(145deg, #FF2D55, #D11035); color: white; box-shadow: 0 4px 12px rgba(255,45,85,0.35); }

.sheet-item__label {
  font-size: 0.72rem;
  font-weight: 600;
  color: rgba(0, 0, 0, 0.65);
  text-align: center;
}

.sheet-divider { height: 1px; background: rgba(0,0,0,0.06); margin: 0.5rem 0; }

.sheet-logout {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  width: 100%;
  padding: 0.75rem;
  border-radius: 1rem;
  background: rgba(255, 59, 48, 0.08);
  border: 1px solid rgba(255, 59, 48, 0.18);
  color: var(--ios-red);
  font-size: 0.875rem;
  font-weight: 600;
  font-family: inherit;
  cursor: pointer;
  text-decoration: none;
  -webkit-tap-highlight-color: transparent;
  transition: background 0.15s, transform 0.15s;
}

.sheet-logout:hover  { background: rgba(255, 59, 48, 0.14); }
.sheet-logout:active { transform: scale(0.97); }
</style>
