<template>
  <AppLayout title="Dashboard">
    <div class="dashboard">
      <!-- ─── Header ──────────────────────────────────────────────────── -->
      <header class="dashboard__header">
        <div>
          <p class="dashboard__greeting">{{ greeting }}, {{ firstName }} 👋</p>
          <h1 class="dashboard__title">Inventory Dashboard</h1>
          <p class="dashboard__subtitle" v-if="warehouseName">
            <span class="warehouse-chip">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="10" height="10"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
              {{ warehouseName }}
            </span>
          </p>
        </div>
        <button class="icon-btn" title="Refresh" @click="$inertia.reload()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M23 4v6h-6"/>
            <path d="M1 20v-6h6"/>
            <path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/>
          </svg>
        </button>
      </header>

      <!-- ─── KPI Cards ─────────────────────────────────────────────── -->
      <section class="kpi-grid" aria-label="Key Performance Indicators">

        <div class="kpi-card kpi-card--blue">
          <div class="kpi-card__icon-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 7H4a2 2 0 00-2 2v9a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>
          </div>
          <span class="kpi-card__value">{{ stats.totalProducts }}</span>
          <span class="kpi-card__label">Total Products</span>
        </div>

        <div class="kpi-card kpi-card--amber">
          <div class="kpi-card__icon-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
          </div>
          <span class="kpi-card__value">{{ alertCount }}</span>
          <span class="kpi-card__label">Low Stock Alerts</span>
        </div>

        <div class="kpi-card kpi-card--purple">
          <div class="kpi-card__icon-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg>
          </div>
          <span class="kpi-card__value">{{ stats.inTransit }}</span>
          <span class="kpi-card__label">In Transit</span>
        </div>

        <div class="kpi-card kpi-card--green">
          <div class="kpi-card__icon-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <span class="kpi-card__value">{{ stats.pendingApprovals }}</span>
          <span class="kpi-card__label">Pending Approvals</span>
        </div>

      </section>

      <!-- ─── Stock Alerts ──────────────────────────────────────────── -->
      <section v-if="alertCount > 0" class="stock-alerts" aria-label="Stock Alerts">
        <div class="section-header">
          <h2 class="section-title">
            <span class="alert-dot" />
            Stock Alerts
          </h2>
          <Link :href="route('stocks.index')" class="view-all">
            View All
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="12" height="12"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg>
          </Link>
        </div>

        <div v-if="criticalAlerts.length" class="alert-group">
          <p class="alert-group__label alert-group__label--critical">
            <span class="alert-group__dot alert-group__dot--critical" />
            Out of Stock ({{ criticalAlerts.length }})
          </p>
          <div class="alert-list">
            <StockAlertCard v-for="entry in criticalAlerts" :key="entry.id" :entry="entry" severity="critical" />
          </div>
        </div>

        <div v-if="warningAlerts.length" class="alert-group">
          <p class="alert-group__label alert-group__label--warning">
            <span class="alert-group__dot alert-group__dot--warning" />
            Low Stock ({{ warningAlerts.length }})
          </p>
          <div class="alert-list">
            <StockAlertCard v-for="entry in warningAlerts" :key="entry.id" :entry="entry" severity="warning" />
          </div>
        </div>
      </section>

      <!-- ─── All Good State ────────────────────────────────────────── -->
      <div v-else class="stock-ok">
        <div class="stock-ok__icon-wrap">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <p class="stock-ok__title">All stock levels healthy</p>
        <p class="stock-ok__sub">No alerts at this time</p>
      </div>
    </div>

    <!-- PWA Install Prompt -->
    <!-- Mobile Bottom Nav -->
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import AppLayout      from '@/Layouts/AppLayout.vue'
import StockAlertCard from '@/Components/StockAlertCard.vue'
import { useStockAlerts } from '@/Composables/useStockAlerts'

const page = usePage()
const { criticalAlerts, warningAlerts, alertCount } = useStockAlerts()

const props = defineProps({
  stats: {
    type: Object,
    default: () => ({ totalProducts: 0, inTransit: 0, pendingApprovals: 0 }),
  },
})

const firstName      = computed(() => page.props.auth.user.name.split(' ')[0])
const warehouseName  = computed(() => page.props.auth.user.warehouse?.name ?? null)

const greeting = computed(() => {
  const h = new Date().getHours()
  if (h < 12) return 'Good morning'
  if (h < 17) return 'Good afternoon'
  return 'Good evening'
})
</script>

<style scoped>
/* ─── Page wrapper ───────────────────────────────────────────────────────── */
.dashboard {
  padding: 2rem 1.25rem 7rem;
  max-width: 800px;
  margin: 0 auto;
}

/* ─── Header ─────────────────────────────────────────────────────────────── */
.dashboard__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.75rem;
}

.dashboard__greeting {
  font-size: 0.875rem;
  color: rgba(0, 0, 0, 0.45);
  margin-bottom: 0.2rem;
  font-weight: 500;
}

.dashboard__title {
  font-size: 1.75rem;
  font-weight: 800;
  letter-spacing: -0.04em;
  color: rgba(0, 0, 0, 0.85);
  margin: 0;
  line-height: 1.1;
}

.dashboard__subtitle { margin: 0.5rem 0 0; }

.warehouse-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  padding: 0.25rem 0.75rem;
  background: rgba(88, 86, 214, 0.10);
  border: 1px solid rgba(88, 86, 214, 0.20);
  border-radius: 999px;
  font-size: 0.72rem;
  color: #5856D6;
  font-weight: 600;
}

.icon-btn {
  width: 2.75rem;
  height: 2.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.85);
  border-radius: 0.875rem;
  color: rgba(0, 0, 0, 0.45);
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
  box-shadow: 0 2px 8px rgba(0,0,0,0.08), inset 0 1px 0 rgba(255,255,255,0.9);
}
.icon-btn svg { width: 1.125rem; height: 1.125rem; }
.icon-btn:hover {
  background: rgba(255, 255, 255, 0.9);
  color: rgba(0, 0, 0, 0.70);
  transform: scale(1.05);
}
.icon-btn:active { transform: scale(0.95); }

/* ─── KPI Grid ───────────────────────────────────────────────────────────── */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.875rem;
  margin-bottom: 2rem;
}

/* ─── KPI Card — Liquid Glass ────────────────────────────────────────────── */
.kpi-card {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0.25rem;
  padding: 1.25rem 1.125rem 1rem;
  border-radius: 1.25rem;
  background: rgba(255, 255, 255, 0.60);
  backdrop-filter: blur(24px) saturate(180%);
  -webkit-backdrop-filter: blur(24px) saturate(180%);
  border: 1px solid rgba(255, 255, 255, 0.80);
  box-shadow:
    0 4px 20px rgba(0, 0, 0, 0.07),
    0 1px 4px rgba(0, 0, 0, 0.04),
    inset 0 1px 0 rgba(255, 255, 255, 0.95);
  transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.2s;
  position: relative;
  overflow: hidden;
}

.kpi-card::after {
  content: '';
  position: absolute;
  inset: 0;
  border-radius: inherit;
  pointer-events: none;
  opacity: 0;
  transition: opacity 0.2s;
}

.kpi-card:active { transform: scale(0.97); }

/* Icon container */
.kpi-card__icon-wrap {
  width: 2.25rem;
  height: 2.25rem;
  border-radius: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 0.625rem;
}

.kpi-card__icon-wrap svg { width: 1.125rem; height: 1.125rem; }

/* Value */
.kpi-card__value {
  font-size: 2rem;
  font-weight: 800;
  letter-spacing: -0.04em;
  line-height: 1;
  display: block;
}

/* Label */
.kpi-card__label {
  font-size: 0.72rem;
  font-weight: 500;
  color: rgba(0, 0, 0, 0.40);
  text-transform: uppercase;
  letter-spacing: 0.04em;
  display: block;
}

/* Color variants */
.kpi-card--blue  { background: rgba(0, 122, 255, 0.10); border-color: rgba(0, 122, 255, 0.20); }
.kpi-card--blue  .kpi-card__icon-wrap { background: rgba(0, 122, 255, 0.15); color: #007AFF; }
.kpi-card--blue  .kpi-card__value     { color: #007AFF; }

.kpi-card--amber { background: rgba(255, 149, 0, 0.10); border-color: rgba(255, 149, 0, 0.20); }
.kpi-card--amber .kpi-card__icon-wrap { background: rgba(255, 149, 0, 0.15); color: #FF9500; }
.kpi-card--amber .kpi-card__value     { color: #FF9500; }

.kpi-card--purple { background: rgba(88, 86, 214, 0.10); border-color: rgba(88, 86, 214, 0.20); }
.kpi-card--purple .kpi-card__icon-wrap { background: rgba(88, 86, 214, 0.15); color: #5856D6; }
.kpi-card--purple .kpi-card__value     { color: #5856D6; }

.kpi-card--green { background: rgba(52, 199, 89, 0.10); border-color: rgba(52, 199, 89, 0.20); }
.kpi-card--green .kpi-card__icon-wrap { background: rgba(52, 199, 89, 0.15); color: #34C759; }
.kpi-card--green .kpi-card__value     { color: #34C759; }

/* ─── Stock Alerts Section ───────────────────────────────────────────────── */
.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.875rem;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1rem;
  font-weight: 700;
  color: rgba(0, 0, 0, 0.80);
  letter-spacing: -0.02em;
  margin: 0;
}

.alert-dot {
  width: 0.5rem;
  height: 0.5rem;
  background: var(--ios-red);
  border-radius: 50%;
  animation: pulse 1.4s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50%       { opacity: 0.5; transform: scale(1.4); }
}

.view-all {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.8125rem;
  color: var(--ios-blue);
  text-decoration: none;
  font-weight: 600;
  transition: opacity 0.15s;
}
.view-all:hover { opacity: 0.75; }

.alert-group { margin-bottom: 1rem; }

.alert-group__label {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.75rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
  padding-left: 0.125rem;
}

.alert-group__dot {
  width: 0.4rem;
  height: 0.4rem;
  border-radius: 50%;
  flex-shrink: 0;
}

.alert-group__label--critical { color: var(--ios-red); }
.alert-group__dot--critical   { background: var(--ios-red); }
.alert-group__label--warning  { color: var(--ios-orange); }
.alert-group__dot--warning    { background: var(--ios-orange); }

.alert-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

/* ─── Stock OK ───────────────────────────────────────────────────────────── */
.stock-ok {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 3rem 1rem;
  gap: 0.5rem;
}

.stock-ok__icon-wrap {
  width: 4rem;
  height: 4rem;
  background: rgba(52, 199, 89, 0.12);
  border-radius: 1.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--ios-green);
  margin-bottom: 0.5rem;
  border: 1px solid rgba(52, 199, 89, 0.2);
}

.stock-ok__title {
  font-size: 1rem;
  font-weight: 700;
  color: rgba(0, 0, 0, 0.70);
}

.stock-ok__sub {
  font-size: 0.8125rem;
  color: rgba(0, 0, 0, 0.35);
}
</style>
