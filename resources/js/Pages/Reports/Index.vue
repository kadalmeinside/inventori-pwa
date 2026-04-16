<template>
  <AppLayout title="Reports">
    <div class="page-wrap">
      <div class="page-header">
        <div>
          <p class="page-eyebrow">Analytics &amp; Logs</p>
          <h1 class="page-title">Reports</h1>
        </div>
      </div>

      <!-- ── Filter Bar ── -->
      <div class="filter-card glass-card">
        <form @submit.prevent="applyFilters" class="filter-grid">
          <div class="filter-group">
            <label class="filter-label">Period</label>
            <select v-model="form.period" class="input-ios filter-input" @change="onPeriodChange">
              <option value="7d">Last 7 Days</option>
              <option value="30d">Last 30 Days</option>
              <optgroup label="Monthly">
                <option v-for="m in availableMonths" :key="m" :value="m">{{ formatMonth(m) }}</option>
              </optgroup>
              <option value="custom">Custom Range</option>
            </select>
          </div>

          <template v-if="form.period === 'custom'">
            <div class="filter-group">
              <label class="filter-label">From</label>
              <input type="date" v-model="form.from" class="input-ios filter-input" required />
            </div>
            <div class="filter-group">
              <label class="filter-label">To</label>
              <input type="date" v-model="form.to" class="input-ios filter-input" required />
            </div>
          </template>

          <div class="filter-group" v-if="isSuperAdmin">
            <label class="filter-label">Warehouse</label>
            <select v-model="form.warehouse_id" class="input-ios filter-input">
              <option value="">All Warehouses</option>
              <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
            </select>
          </div>

          <div class="filter-group">
            <label class="filter-label">Type</label>
            <select v-model="form.type" class="input-ios filter-input">
              <option value="">All Types</option>
              <option value="stock_in">Stock In</option>
              <option value="stock_out">Stock Out</option>
              <option value="transfer_in">Transfer In</option>
              <option value="transfer_out">Transfer Out</option>
            </select>
          </div>

          <div class="filter-group">
            <label class="filter-label">Product</label>
            <select v-model="form.product_id" class="input-ios filter-input">
              <option value="">All Products</option>
              <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} - {{ p.sku }}</option>
            </select>
          </div>

          <div class="filter-actions">
            <button type="submit" class="btn-ios btn-ios-primary" :disabled="isLoading" style="white-space: nowrap; padding: 0.65rem 1rem;">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
              Apply Filter
            </button>
            <button type="button" class="btn-ios btn-ios-glass" @click="resetFilters" :disabled="isLoading" style="white-space: nowrap; padding: 0.65rem 1rem;">
              Reset
            </button>
          </div>
        </form>
      </div>

      <!-- ── Summary Cards ── -->
      <div class="summary-grid">
        <div class="summary-card glass-card">
          <div class="summary-icon bg-green-lighttext-green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><path d="M12 5v14M5 12l7 7 7-7"/></svg>
          </div>
          <div class="summary-details">
            <div class="summary-title">Total In</div>
            <div class="summary-value text-green">{{ metrics.inTotal }} <span class="summary-unit">units</span></div>
          </div>
        </div>
        <div class="summary-card glass-card">
          <div class="summary-icon bg-red-lighttext-red">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
          </div>
          <div class="summary-details">
            <div class="summary-title">Total Out</div>
            <div class="summary-value text-red">{{ metrics.outTotal }} <span class="summary-unit">units</span></div>
          </div>
        </div>
        <div class="summary-card glass-card">
          <div class="summary-icon bg-blue-lighttext-blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
          </div>
          <div class="summary-details">
            <div class="summary-title">Net Movement</div>
            <div class="summary-value" :class="metrics.net >= 0 ? 'text-green' : 'text-red'">
              {{ metrics.net > 0 ? '+' : ''}}{{ metrics.net }} <span class="summary-unit">units</span>
            </div>
          </div>
        </div>
        <div class="summary-card glass-card">
          <div class="summary-icon bg-orange-lighttext-orange">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
          </div>
          <div class="summary-details">
            <div class="summary-title">Low Stock Items</div>
            <div class="summary-value text-orange">{{ metrics.lowStockCount }} <span class="summary-unit">items</span></div>
          </div>
        </div>
      </div>

      <!-- ── Stock Out Breakdown (if any) ── -->
      <div v-if="metrics.stockOutBreakdown && metrics.stockOutBreakdown.length > 0" class="glass-card section-card">
        <h3 class="section-title">Stock Out Breakdown</h3>
        <div class="breakdown-bars">
          <div v-for="cat in metrics.stockOutBreakdown" :key="cat.category" class="brk-item">
            <div class="brk-label">
              <span class="cat-chip">{{ getCategoryLabel(cat.category) }}</span>
              <span class="brk-val">{{ cat.total_quantity }} u</span>
            </div>
            <div class="brk-bar-bg">
              <div class="brk-bar-fg" :class="getCategoryColorClass(cat.category)" :style="{ width: getPercentage(cat.total_quantity) + '%' }"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Inventory Logs Table ── -->
      <div class="glass-card table-section">
        <div class="table-header">
          <h3 class="section-title" style="margin:0">Movement Logs</h3>
        </div>
        <div class="table-wrap hidden md:block">
          <table class="ios-table">
            <thead>
              <tr>
                <th>Date / By</th>
                <th>Movement Type</th>
                <th>Product &amp; Location</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Balance</th>
                <th>Notes</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="log in logs.data" :key="log.id" class="table-row">
                <td>
                  <div class="td-name">{{ formatDate(log.created_at) }}</div>
                  <div class="td-muted text-xs mt-1">{{ log.creator?.name }}</div>
                </td>
                <td>
                  <span class="type-badge" :class="getTypeClass(log.movement_type)">
                    {{ formatType(log.movement_type) }}
                  </span>
                </td>
                <td>
                  <div class="td-name">{{ log.product?.name }}</div>
                  <div class="td-muted text-xs mt-1">{{ log.warehouse?.name }}</div>
                </td>
                <td class="text-right">
                  <span class="td-name font-mono" :class="isPositive(log.movement_type) ? 'text-green' : 'text-red'">
                    {{ isPositive(log.movement_type) ? '+' : '-' }}{{ log.quantity }}
                  </span>
                </td>
                <td class="text-right font-mono text-sm">
                  <span style="color:rgba(0,0,0,0.4)">{{ log.balance_before }} &rarr; </span>
                  <strong>{{ log.balance_after }}</strong>
                </td>
                <td class="td-muted text-xs italic" style="max-width: 200px">
                  {{ log.notes || '—' }}
                </td>
              </tr>
              <tr v-if="logs.data.length === 0"><td colspan="6" class="empty-cell">No logs found for selected period.</td></tr>
            </tbody>
          </table>
        </div>
        
        <!-- Mobile View (Cards) -->
        <div class="mobile-list md:hidden">
          <div v-for="log in logs.data" :key="log.id" class="mobile-card">
            <div class="mobile-card__top">
              <div>
                <span class="type-badge" :class="getTypeClass(log.movement_type)">
                  {{ formatType(log.movement_type) }}
                </span>
                <p class="mobile-card__name">{{ log.product?.name }}</p>
                <p class="td-muted text-xs">{{ log.warehouse?.name }}</p>
              </div>
              <div class="text-right">
                <span class="td-name font-mono text-lg" :class="isPositive(log.movement_type) ? 'text-green' : 'text-red'">
                  {{ isPositive(log.movement_type) ? '+' : '-' }}{{ log.quantity }}
                </span>
              </div>
            </div>
            <div class="mobile-card__actions">
              <span class="td-muted text-xs">{{ formatDate(log.created_at) }} &middot; {{ log.creator?.name }}</span>
              <span class="font-mono text-xs"><span style="color:rgba(0,0,0,0.4)">{{ log.balance_before }} &rarr; </span><strong>{{ log.balance_after }}</strong></span>
            </div>
            <p v-if="log.notes" class="td-muted text-xs italic mt-1">{{ log.notes }}</p>
          </div>
          <div v-if="logs.data.length === 0" class="empty-cell">No logs found for selected period.</div>
        </div>

        <!-- Pagination -->
        <div v-if="logs.links.length > 3" class="pagination">
          <template v-for="(link, i) in logs.links" :key="i">
            <span v-if="link.url === null" class="page-btn page-btn--disabled" v-html="link.label" />
            <Link v-else :href="link.url" class="page-btn" :class="link.active ? 'page-btn--active' : ''" v-html="link.label" />
          </template>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, Link, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  logs: Object,
  metrics: Object,
  filters: Object,
  availableMonths: Array,
  warehouses: Array,
  products: Array,
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const isSuperAdmin = computed(() => user.value?.role === 'super_admin');

const form = ref({
  period: props.filters.period || '30d',
  from: props.filters.from || '',
  to: props.filters.to || '',
  warehouse_id: props.filters.warehouse_id || '',
  type: props.filters.type || '',
  product_id: props.filters.product_id || '',
});

const isLoading = ref(false);

const applyFilters = () => {
  isLoading.value = true;
  router.get(route('reports.index'), form.value, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => isLoading.value = false,
  });
};

const resetFilters = () => {
  form.value = {
    period: '30d',
    from: '',
    to: '',
    warehouse_id: '',
    type: '',
    product_id: '',
  };
  applyFilters();
};

const onPeriodChange = () => {
  // Clear custom dates if period is not custom
  if (form.value.period !== 'custom') {
    form.value.from = '';
    form.value.to = '';
  }
};

const formatMonth = (val) => {
  if (!val) return '';
  const [yy, mm] = val.split('-');
  const date = new Date(yy, mm - 1);
  return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
};

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('id-ID', {
    day: 'numeric', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  });
};

const formatType = (type) => {
  return type.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
};

const getTypeClass = (type) => {
  if (type === 'stock_in' || type === 'transfer_in') return 'type-badge--in';
  return 'type-badge--out';
};

const isPositive = (type) => {
  return type === 'stock_in' || type === 'transfer_in';
};

const categoryLabels = {
  sales: 'Penjualan', internal_use: 'Pemakaian', damaged: 'Rusak',
  expired: 'Kedaluwarsa', adjustment: 'Stok Opname'
};

const getCategoryLabel = (cat) => categoryLabels[cat] || cat;

const maxBreakdownValue = computed(() => {
  if (!props.metrics.stockOutBreakdown || props.metrics.stockOutBreakdown.length === 0) return 1;
  return Math.max(...props.metrics.stockOutBreakdown.map(c => c.total_quantity));
});

const getPercentage = (val) => {
  return Math.max(5, Math.round((val / maxBreakdownValue.value) * 100));
};

const getCategoryColorClass = (cat) => {
  const map = {
    sales: 'bg-blue',
    internal_use: 'bg-purple',
    damaged: 'bg-red',
    expired: 'bg-orange',
    adjustment: 'bg-teal',
  };
  return map[cat] || 'bg-gray';
};
</script>

<style scoped>
.page-wrap { padding: 2rem 1.25rem 5rem; max-width: 1200px; margin: 0 auto; }
.page-header { margin-bottom: 1.5rem; }
.page-eyebrow { font-size: 0.7rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: rgba(0,0,0,0.35); margin: 0 0 0.2rem; }
.page-title { font-size: 1.75rem; font-weight: 800; letter-spacing: -0.04em; color: rgba(0,0,0,0.85); margin: 0; line-height: 1; }

.glass-card {
  background: rgba(255,255,255,0.62);
  backdrop-filter: blur(28px) saturate(180%);
  -webkit-backdrop-filter: blur(28px) saturate(180%);
  border: 1px solid rgba(255,255,255,0.82);
  border-radius: 1.5rem;
  box-shadow: 0 8px 30px rgba(0,80,200,0.06), inset 0 1px 0 rgba(255,255,255,0.95);
  overflow: hidden;
}

/* Filter Card */
.filter-card { padding: 1.25rem; margin-bottom: 1.5rem; }
.filter-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 1rem;
  align-items: flex-end;
}
.filter-group { display: flex; flex-direction: column; gap: 0.35rem; }
.filter-label { font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(0,0,0,0.4); }
.filter-actions { display: flex; gap: 0.5rem; grid-column: 1 / -1; margin-top: 0.5rem; }
@media (min-width: 768px) {
  .filter-actions { grid-column: auto; flex-direction: row; align-items: flex-end; }
}

/* Summary Grid */
.summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
.summary-card { display: flex; align-items: center; gap: 1.25rem; padding: 1.25rem; }
.summary-icon {
  width: 3.5rem; height: 3.5rem; border-radius: 1rem;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.bg-green-lighttext-green { background: rgba(52,199,89,0.12); color: #34C759; }
.bg-red-lighttext-red { background: rgba(255,59,48,0.12); color: #FF3B30; }
.bg-blue-lighttext-blue { background: rgba(0,122,255,0.12); color: #007AFF; }
.bg-orange-lighttext-orange { background: rgba(255,149,0,0.12); color: #FF9500; }

.text-green { color: #28a745; }
.text-red { color: #FF3B30; }
.text-orange { color: #FF9500; }

.summary-details { display: flex; flex-direction: column; }
.summary-title { font-size: 0.75rem; font-weight: 600; color: rgba(0,0,0,0.45); margin-bottom: 0.15rem; }
.summary-value { font-size: 1.75rem; font-weight: 800; font-family: var(--font-mono); letter-spacing: -0.03em; line-height: 1; color: rgba(0,0,0,0.85); }
.summary-unit { font-size: 0.7rem; font-weight: 600; letter-spacing: 0em; color: rgba(0,0,0,0.3); font-family: var(--font-sans); }

/* Breakdown */
.section-card { padding: 1.5rem; margin-bottom: 1.5rem; }
.section-title { font-size: 1.125rem; font-weight: 700; letter-spacing: -0.02em; color: rgba(0,0,0,0.8); margin: 0 0 1rem; }
.breakdown-bars { display: flex; flex-direction: column; gap: 0.8rem; }
.brk-item { display: flex; flex-direction: column; gap: 0.3rem; }
.brk-label { display: flex; justify-content: space-between; align-items: center; }
.brk-val { font-size: 0.75rem; font-family: var(--font-mono); font-weight: 600; color: rgba(0,0,0,0.6); }
.brk-bar-bg { width: 100%; height: 6px; background: rgba(0,0,0,0.04); border-radius: 999px; overflow: hidden; }
.brk-bar-fg { height: 100%; border-radius: 999px; transition: width 1s ease; }
.bg-blue { background: #007AFF; }
.bg-purple { background: #AF52DE; }
.bg-red { background: #FF3B30; }
.bg-orange { background: #FF9500; }
.bg-teal { background: #30B0C7; }
.bg-gray { background: #8E8E93; }

.cat-chip { display: inline-flex; align-items: center; padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.65rem; font-weight: 600; background: rgba(0,0,0,0.04); color: rgba(0,0,0,0.7); }

/* Table Section */
.table-section { overflow: hidden; }
.table-header { padding: 1.25rem 1.5rem 1rem; border-bottom: 1px solid rgba(0,0,0,0.05); }

.ios-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
.ios-table thead tr { background: rgba(0,0,0,0.02); border-bottom: 1px solid rgba(0,0,0,0.06); }
.ios-table th { padding: 0.875rem 1.5rem; font-size: 0.65rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; color: rgba(0,0,0,0.35); text-align: left; }
.ios-table td { padding: 1rem 1.5rem; color: rgba(0,0,0,0.70); border-bottom: 1px solid rgba(0,0,0,0.04); vertical-align: middle; }
.table-row:last-child td { border-bottom: none; }
.table-row:hover { background: rgba(0,122,255,0.02); }

.td-name { font-weight: 600; color: rgba(0,0,0,0.85); }
.td-muted { color: rgba(0,0,0,0.45); }

.type-badge { display: inline-flex; align-items: center; padding: 0.25rem 0.6rem; border-radius: 999px; font-size: 0.65rem; font-weight: 700; letter-spacing: 0.04em; }
.type-badge--in { background: rgba(52,199,89,0.1); color: #28a745; border: 1px solid rgba(52,199,89,0.2); }
.type-badge--out { background: rgba(255,59,48,0.1); color: #FF3B30; border: 1px solid rgba(255,59,48,0.2); }

.empty-cell { text-align: center; padding: 3rem 1rem; color: rgba(0,0,0,0.40); }

/* Pagination */
.pagination { display: flex; justify-content: center; gap: 0.375rem; padding: 1rem 1.5rem; border-top: 1px solid rgba(0,0,0,0.05); flex-wrap: wrap; }
.page-btn { padding: 0.4rem 0.75rem; border-radius: 0.5rem; font-size: 0.8125rem; font-weight: 500; background: rgba(255,255,255,0.7); border: 1px solid rgba(0,0,0,0.08); color: rgba(0,0,0,0.60); text-decoration: none; transition: all 0.15s; }
.page-btn--active { background: #007AFF; border-color: #007AFF; color: white; font-weight: 700; }
.page-btn--disabled { color: rgba(0,0,0,0.25); background: transparent; border-color: transparent; }

/* Mobile Cards */
.mobile-list { display: flex; flex-direction: column; gap: 0.75rem; padding: 1rem; }
.mobile-card { background: rgba(255,255,255,0.7); border: 1px solid rgba(255,255,255,0.85); border-radius: 1rem; padding: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.06), inset 0 1px 0 rgba(255,255,255,0.9); display: flex; flex-direction: column; gap: 0.5rem; }
.mobile-card__top { display: flex; align-items: flex-start; justify-content: space-between; gap: 0.5rem; }
.mobile-card__name { font-size: 1rem; font-weight: 700; color: rgba(0,0,0,0.82); letter-spacing: -0.02em; margin: 0.2rem 0 0; }
.mobile-card__actions { display: flex; justify-content: space-between; align-items: center; padding-top: 0.375rem; border-top: 1px solid rgba(0,0,0,0.05); margin-top: 0.25rem; }
</style>
