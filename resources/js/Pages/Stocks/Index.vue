<template>
  <AppLayout title="Stock Inventory">
    <div class="page-wrap">

      <!-- ─── Header ──────────────────────────────────────────────────── -->
      <div class="page-header">
        <div class="header-titles">
          <p class="page-eyebrow">Live Overview</p>
          <h1 class="page-title">Stock Inventory</h1>
        </div>
        <div class="header-actions">
          <div class="search-bar-group">
            <div class="search-wrap">
              <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
              <input
                v-model="search"
                type="text"
                placeholder="Search products or SKU…"
                class="search-input"
                @keyup.enter="performSearch"
              />
            </div>
            <template v-if="page.props.auth.user.role === 'super_admin'">
              <div class="search-wrap" v-if="viewMode !== 'global'">
                <select v-model="warehouseId" @change="performSearch" class="search-input" style="padding-left: 1rem;">
                  <option value="">All Branches</option>
                  <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
                </select>
              </div>
            </template>
          </div>
          <template v-if="page.props.auth.user.role === 'super_admin'">
            <button class="btn-ios btn-ios-glass" @click="toggleGlobalView" :class="{ 'btn-active': viewMode === 'global' }">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg>
              <span class="btn-text">Global Summary</span>
            </button>
          </template>

          <button v-if="page.props.auth.user.role !== 'super_admin'" class="btn-ios btn-ios-primary btn-receive" @click="openStockInModal">
            <svg class="btn-receive-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M12 5v14M5 12h14"/></svg>
            <span class="btn-text">Receive Stock</span>
          </button>
        </div>
      </div>

      <!-- ─── Table / Global View ──────────────────────────────────────── -->
      <div class="glass-card" v-if="viewMode !== 'global'">
        <!-- Desktop Table -->
        <div class="table-wrap hidden md:block">
          <table class="ios-table">
            <thead>
              <tr>
                <th>Product</th>
                <th>SKU</th>
                <th>Warehouse</th>
                <th class="text-right">Quantity</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="entry in stocks.data" :key="entry.id" class="table-row">
                <td class="td-name">{{ entry.product.name }}</td>
                <td class="td-mono">{{ entry.product.sku }}</td>
                <td class="td-muted">{{ entry.warehouse.name }}</td>
                <td class="text-right">
                  <span class="qty-badge" :class="entry.quantity <= entry.product.min_stock ? 'qty-badge--low' : 'qty-badge--ok'">
                    <svg v-if="entry.quantity <= entry.product.min_stock" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="12" height="12"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    {{ entry.quantity }} <span class="qty-unit">{{ entry.product.unit }}</span>
                  </span>
                </td>
                <td class="text-center">
                  <button class="action-btn action-btn--blue" @click="openStockOutModal(entry)">Stock Out</button>
                </td>
              </tr>
              <tr v-if="stocks.data.length === 0"><td colspan="5" class="empty-cell">No stock data found.</td></tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile Cards -->
        <div class="block md:hidden p-4">
          <div class="mobile-list">
            <div v-for="entry in stocks.data" :key="'m-'+entry.id" class="mobile-card" :class="entry.quantity <= entry.product.min_stock ? 'mobile-card--alert' : ''">
              <div class="mobile-card__top">
                <span class="td-mono" style="font-size: 0.68rem;">{{ entry.product.sku }}</span>
                <span class="qty-badge" :class="entry.quantity <= entry.product.min_stock ? 'qty-badge--low' : 'qty-badge--ok'">
                  {{ entry.quantity }} {{ entry.product.unit }}
                </span>
              </div>
              <h3 class="mobile-card__name">{{ entry.product.name }}</h3>
              <div class="mobile-card__meta">
                <span class="meta-chip">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="10" height="10"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/></svg>
                  {{ entry.warehouse.name }}
                </span>
                <span v-if="entry.quantity <= entry.product.min_stock" class="meta-chip meta-chip--alert">⚠ Low Stock</span>
              </div>
              <div class="mobile-card__actions">
                <button class="action-btn action-btn--blue action-btn--full" @click="openStockOutModal(entry)">Stock Out</button>
              </div>
            </div>
            <div v-if="stocks.data.length === 0" class="empty-cell">No stock data found.</div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="stocks.links.length > 3" class="pagination">
          <template v-for="(link, i) in stocks.links" :key="i">
            <span v-if="link.url === null" class="page-btn page-btn--disabled" v-html="link.label" />
            <Link v-else :href="link.url" class="page-btn" :class="link.active ? 'page-btn--active' : ''" v-html="link.label" />
          </template>
        </div>
      </div>

      <!-- ─── Global Summary View ──────────────────────────────────────── -->
      <div class="global-grid" v-else>
        <div v-for="entry in stocks.data" :key="entry.product_id" class="glass-card global-card">
          <div class="global-card__header">
            <div class="global-card__icon" :class="entry.quantity <= entry.product.min_stock ? 'icon-alert' : 'icon-ok'">
               <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
            </div>
            <div class="global-card__badge" :class="entry.quantity <= entry.product.min_stock ? 'badge-alert' : 'badge-ok'">
              {{ entry.quantity }} {{ entry.product.unit }}
            </div>
          </div>
          <div class="global-card__body">
            <div class="gc-sku">{{ entry.product.sku }}</div>
            <h3 class="gc-name">{{ entry.product.name }}</h3>
            <div class="gc-category">{{ entry.product.category?.name || 'Uncategorized' }}</div>
          </div>
        </div>
        <div v-if="stocks.data.length === 0" class="empty-cell" style="grid-column: 1 / -1">No stock data found.</div>
      </div>

      <!-- Pagination for Global View -->
      <div v-if="viewMode === 'global' && stocks.links.length > 3" class="pagination" style="margin-top: 2rem;">
        <template v-for="(link, i) in stocks.links" :key="i">
          <span v-if="link.url === null" class="page-btn page-btn--disabled" v-html="link.label" />
          <Link v-else :href="link.url" class="page-btn" :class="link.active ? 'page-btn--active' : ''" v-html="link.label" />
        </template>
      </div>

    </div>

    <!-- ─── Stock Out Modal ────────────────────────────────────────────── -->
    <Modal :show="showModal" @close="closeModal" maxWidth="md">
      <div class="modal-body">
        <div class="modal-header">
          <div class="modal-header__icon" style="background: rgba(255,59,48,0.10); border-color: rgba(255,59,48,0.20); color: #FF3B30;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg>
          </div>
          <div>
            <h2 class="modal-header__title">Request Stock Out</h2>
            <p class="modal-header__sub">
              <strong>{{ selectedEntry?.product?.name }}</strong> — {{ selectedEntry?.warehouse?.name }}
            </p>
          </div>
          <button class="modal-close" @click="closeModal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M18 6L6 18M6 6l12 12"/></svg>
          </button>
        </div>
        <form @submit.prevent="submitStockOut" class="modal-form">
          <div class="field">
            <InputLabel for="qty" value="Quantity to Remove" />
            <TextInput id="qty" v-model="form.quantity" type="number" min="1" :max="selectedEntry?.quantity" required />
            <InputError :message="form.errors.quantity" />
          </div>
          <div class="field">
            <InputLabel for="category" value="Category" />
            <select id="category" v-model="form.category" class="input-ios" required>
              <option disabled value="">Select category…</option>
              <option value="sales">Penjualan (Sales)</option>
              <option value="internal_use">Pemakaian Internal</option>
              <option value="damaged">Barang Rusak (Damaged)</option>
              <option value="expired">Kedaluwarsa (Expired)</option>
              <option value="adjustment">Penyesuaian (Adjustment)</option>
            </select>
            <InputError :message="form.errors.category" />
          </div>
          <div class="field">
            <InputLabel for="reason" value="Reason (Optional)" />
            <textarea id="reason" v-model="form.reason" class="input-ios" rows="3" placeholder="e.g. Sent to display, damaged in transit…" />
            <InputError :message="form.errors.reason" />
          </div>
          <div class="modal-actions">
            <button type="button" @click="closeModal" class="btn-ios btn-ios-glass">Cancel</button>
            <PrimaryButton :class="{ 'opacity-50': form.processing }" :disabled="form.processing">Submit Request</PrimaryButton>
          </div>
        </form>
      </div>
    </Modal>

    <!-- ─── Stock In Modal ─────────────────────────────────────────────── -->
    <Modal :show="showInModal" @close="closeInModal" maxWidth="md">
      <div class="modal-body">
        <div class="modal-header">
          <div class="modal-header__icon" style="background: rgba(52,199,89,0.10); border-color: rgba(52,199,89,0.20); color: #34C759;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M12 5v14M5 12h14"/></svg>
          </div>
          <div>
            <h2 class="modal-header__title">Receive New Stock</h2>
            <p class="modal-header__sub">Add incoming inventory to a warehouse</p>
          </div>
          <button class="modal-close" @click="closeInModal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M18 6L6 18M6 6l12 12"/></svg>
          </button>
        </div>
        <form @submit.prevent="submitStockIn" class="modal-form">
          <div class="field">
            <InputLabel for="in_warehouse" value="Destination Warehouse" />
            <div v-if="page.props.auth.user.role !== 'super_admin'" class="input-ios input-ios--locked">
              {{ warehouses.find(w => w.id === page.props.auth.user.warehouse_id)?.name ?? 'Your Warehouse' }}
            </div>
            <select v-else id="in_warehouse" v-model="inForm.warehouse_id" class="input-ios" required>
              <option disabled value="">Select warehouse…</option>
              <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
            </select>
            <InputError :message="inForm.errors.warehouse_id" />
          </div>
          <div class="field">
            <InputLabel for="in_product" value="Product" />
            <select id="in_product" v-model="inForm.product_id" class="input-ios" required>
              <option disabled value="">Select product…</option>
              <option v-for="prod in products" :key="prod.id" :value="prod.id">{{ prod.sku }} — {{ prod.name }}</option>
            </select>
            <InputError :message="inForm.errors.product_id" />
          </div>
          <div class="field">
            <InputLabel for="in_qty" value="Quantity to Add" />
            <TextInput id="in_qty" v-model="inForm.quantity" type="number" min="1" required />
            <InputError :message="inForm.errors.quantity" />
          </div>
          <div class="field">
            <InputLabel for="in_notes" value="Reference / Notes (Optional)" />
            <textarea id="in_notes" v-model="inForm.notes" class="input-ios" rows="2" placeholder="e.g. PO-12345, restocked from supplier…" />
            <InputError :message="inForm.errors.notes" />
          </div>
          <div class="modal-actions">
            <button type="button" @click="closeInModal" class="btn-ios btn-ios-glass">Cancel</button>
            <button type="submit" :disabled="inForm.processing" class="btn-ios btn-ios-primary" style="background: var(--ios-green); box-shadow: 0 4px 16px rgba(52,199,89,0.35);">
              Receive Stock
            </button>
          </div>
        </form>
      </div>
    </Modal>

  </AppLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { router, useForm, Link, usePage } from '@inertiajs/vue3';
import AppLayout     from '@/Layouts/AppLayout.vue';
import Modal         from '@/Components/Modal.vue';
import TextInput     from '@/Components/TextInput.vue';
import InputLabel    from '@/Components/InputLabel.vue';
import InputError    from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({ stocks: Object, filters: Object, warehouses: Array, products: Array });
const page  = usePage();
const search = ref(props.filters.search || '');
const warehouseId = ref(props.filters.warehouse_id || '');
const viewMode = ref(props.filters.view_mode || 'default');

const performSearch = () => {
    router.get(route('stocks.index'), {
        search: search.value,
        warehouse_id: warehouseId.value,
        view_mode: viewMode.value,
    }, { preserveState: true, replace: true });
};

const toggleGlobalView = () => {
    viewMode.value = viewMode.value === 'global' ? 'default' : 'global';
    performSearch();
};

const showModal = ref(false), showInModal = ref(false);
const selectedEntry = ref(null);

const form = useForm({ warehouse_id: '', product_id: '', quantity: 1, category: '', reason: '' });
const inForm = useForm({
  warehouse_id: page.props.auth.user.role === 'branch_admin' ? page.props.auth.user.warehouse_id : '',
  product_id: '', quantity: 1, notes: '',
});

const openStockOutModal = (entry) => {
  selectedEntry.value = entry;
  form.warehouse_id = entry.warehouse_id;
  form.product_id = entry.product_id;
  form.quantity = 1; form.category = ''; form.reason = '';
  form.clearErrors(); showModal.value = true;
};
const closeModal = () => { showModal.value = false; selectedEntry.value = null; form.reset(); };
const submitStockOut = () => form.post(route('stock-outs.store'), { preserveScroll: true, onSuccess: closeModal });

const openStockInModal = () => {
  inForm.clearErrors(); inForm.reset();
  if (page.props.auth.user.role === 'branch_admin') inForm.warehouse_id = page.props.auth.user.warehouse_id;
  showInModal.value = true;
};
const closeInModal = () => { showInModal.value = false; inForm.reset(); };
const submitStockIn = () => inForm.post(route('stocks.in'), { preserveScroll: true, onSuccess: closeInModal });

let echoChannel;
onMounted(() => {
    if (window.Echo) {
        const role = page.props.auth.user.role;
        const channelName = role === 'super_admin' ? 'superadmin' : `warehouse.${page.props.auth.user.warehouse_id}`;

        echoChannel = window.Echo.private(channelName)
            .listen('.StockUpdated', (e) => {
                // Hard reload the stocks data from server
                router.reload({
                    only: ['stocks'],
                    preserveScroll: true,
                    preserveState: false,
                });
            });
    }
});

onUnmounted(() => {
    if (echoChannel && window.Echo) {
        window.Echo.leave(echoChannel.name);
    }
});
</script>

<style scoped>
.page-wrap { padding: 2rem 1.25rem 5rem; max-width: 1100px; margin: 0 auto; }

.btn-active { background: #007AFF !important; border-color: #007AFF !important; color: white !important; box-shadow: 0 4px 12px rgba(0,122,255,0.4) !important; }
.btn-active .btn-text { max-width: 120px !important; opacity: 1 !important; font-size: 0.875rem !important; }

/* Global Grid */
.global-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem; margin-top: 1rem; }
.global-card { padding: 1.25rem; display: flex; flex-direction: column; gap: 1rem; }
.global-card__header { display: flex; align-items: flex-start; justify-content: space-between; }
.global-card__icon { width: 3rem; height: 3rem; border-radius: 1rem; display: flex; align-items: center; justify-content: center; }
.icon-ok { background: rgba(52,199,89,0.12); color: #28a745; border: 1px solid rgba(52,199,89,0.25); }
.icon-alert { background: rgba(255,59,48,0.12); color: #FF3B30; border: 1px solid rgba(255,59,48,0.25); }
.global-card__badge { padding: 0.35rem 0.75rem; border-radius: 999px; font-weight: 700; font-family: var(--font-mono); font-size: 0.9rem; border: 1px solid; }
.badge-ok { background: rgba(52,199,89,0.10); color: #1a7a34; border-color: rgba(52,199,89,0.25); }
.badge-alert { background: rgba(255,59,48,0.10); color: #FF3B30; border-color: rgba(255,59,48,0.25); }
.global-card__body { display: flex; flex-direction: column; gap: 0.25rem; }
.gc-sku { font-family: var(--font-mono); font-size: 0.75rem; color: rgba(0,0,0,0.5); letter-spacing: 0.04em; }
.gc-name { font-size: 1.125rem; font-weight: 700; color: rgba(0,0,0,0.85); margin: 0; line-height: 1.2; }
.gc-category { font-size: 0.75rem; color: rgba(0,0,0,0.45); text-transform: uppercase; letter-spacing: 0.04em; margin-top: 0.25rem; }


/* Mobile-first layout for Header */
.page-header { display: grid; grid-template-columns: 1fr auto; grid-template-areas: 'titles button' 'search search'; gap: 1rem; margin-bottom: 1.5rem; }
.header-titles { grid-area: titles; display: flex; flex-direction: column; justify-content: flex-end; }
.header-actions { display: contents; } /* Children placed by grid areas on mobile */

.page-eyebrow { font-size: 0.7rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: rgba(0,0,0,0.35); margin: 0 0 0.2rem; }
.page-title { font-size: 1.75rem; font-weight: 800; letter-spacing: -0.04em; color: rgba(0,0,0,0.85); margin: 0; line-height: 1; }

/* Receive Button (Perfect circle on mobile, expands on hover) */
.btn-receive { grid-area: button; padding: 0; width: 2.375rem; height: 2.375rem; border-radius: 999px; overflow: hidden; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,122,255,0.4); transition: all 0.3s var(--spring); align-self: flex-end; }
.btn-receive-icon { flex-shrink: 0; }
.btn-text { white-space: nowrap; max-width: 0; opacity: 0; overflow: hidden; font-size: 0; transition: max-width 0.3s var(--spring), opacity 0.2s ease, font-size 0.2s; }

@media (max-width: 767px) {
  .btn-receive { gap: 0; } /* Remove inherited gap to center icon */
  .btn-receive:hover, .btn-receive:active { width: auto; padding: 0 1.125rem; border-radius: 999px; gap: 0.3rem; }
  .btn-receive:hover .btn-text, .btn-receive:active .btn-text { max-width: 120px; opacity: 1; font-size: 0.875rem; }
}

/* Original flex layout for Desktop */
@media (min-width: 768px) {
  .page-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
  .header-actions { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; }
  .btn-receive { width: auto; height: auto; padding: 0.625rem 1.25rem; border-radius: var(--radius-full); align-self: auto; box-shadow: 0 4px 16px rgba(0, 122, 255, 0.35); }
  .btn-text { max-width: 120px; opacity: 1; font-size: 0.875rem; margin-left: 0.4rem; }
}

/* Search */
.search-bar-group { grid-area: search; display: flex; flex-direction: column; gap: 0.75rem; width: 100%; }
.search-wrap { position: relative; width: 100%; }
.search-icon { position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); width: 1rem; height: 1rem; color: rgba(0,0,0,0.30); pointer-events: none; }
.search-input { padding: 0.625rem 1rem 0.625rem 2.25rem; background: rgba(255,255,255,0.7); border: 1px solid rgba(255,255,255,0.85); border-radius: 0.75rem; font-family: var(--font-sans); font-size: 0.875rem; color: var(--text-primary); outline: none; transition: border-color 0.2s, box-shadow 0.2s; box-shadow: inset 0 1px 3px rgba(0,0,0,0.05); width: 100%; }
.search-input::placeholder { color: rgba(0,0,0,0.30); }
.search-input:focus { background: rgba(255,255,255,0.9); border-color: rgba(0,122,255,0.4); box-shadow: 0 0 0 3px rgba(0,122,255,0.12); }
@media (min-width: 768px) {
  .search-bar-group { flex-direction: row; align-items: center; width: auto; }
  .search-wrap { width: auto; }
  .search-input { width: 220px; }
}

/* Glass card */
.glass-card { background: rgba(255,255,255,0.62); backdrop-filter: blur(28px) saturate(180%); -webkit-backdrop-filter: blur(28px) saturate(180%); border: 1px solid rgba(255,255,255,0.82); border-radius: 1.5rem; box-shadow: 0 8px 40px rgba(0,80,200,0.10), 0 2px 8px rgba(0,0,0,0.05), inset 0 1px 0 rgba(255,255,255,0.95); overflow: hidden; }
.table-wrap { overflow-x: auto; }
.ios-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
.ios-table thead tr { background: rgba(0,0,0,0.03); border-bottom: 1px solid rgba(0,0,0,0.06); }
.ios-table th { padding: 0.875rem 1.25rem; font-size: 0.68rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: rgba(0,0,0,0.38); text-align: left; white-space: nowrap; }
.ios-table td { padding: 0.875rem 1.25rem; color: rgba(0,0,0,0.60); border-bottom: 1px solid rgba(0,0,0,0.04); vertical-align: middle; }
.table-row { transition: background 0.15s; }
.table-row:hover { background: rgba(0,122,255,0.03); }
.table-row:last-child td { border-bottom: none; }
.td-name { font-weight: 600; color: rgba(0,0,0,0.80); }
.td-mono { font-family: var(--font-mono); font-size: 0.78rem; letter-spacing: 0.04em; }
.td-muted { color: rgba(0,0,0,0.40); }

/* Qty badge */
.qty-badge { display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.25rem 0.65rem; border-radius: 999px; font-size: 0.8rem; font-weight: 700; font-family: var(--font-mono); border: 1px solid; }
.qty-badge--ok  { background: rgba(52,199,89,0.10); color: #1a7a34; border-color: rgba(52,199,89,0.25); }
.qty-badge--low { background: rgba(255,59,48,0.10); color: #FF3B30; border-color: rgba(255,59,48,0.25); }
.qty-unit { font-size: 0.65rem; font-weight: 400; font-family: var(--font-sans); }

/* Actions */
.action-btn { padding: 0.35rem 0.875rem; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 600; cursor: pointer; border: 1px solid; transition: all 0.15s; -webkit-tap-highlight-color: transparent; }
.action-btn:active { transform: scale(0.96); }
.action-btn--blue { background: rgba(0,122,255,0.09); border-color: rgba(0,122,255,0.20); color: #007AFF; }
.action-btn--blue:hover { background: rgba(0,122,255,0.16); }
.action-btn--full { flex: 1; text-align: center; }

/* Empty */
.empty-cell { text-align: center; padding: 3rem 1rem; color: rgba(0,0,0,0.30); font-size: 0.875rem; }

/* Pagination */
.pagination { display: flex; justify-content: center; gap: 0.375rem; padding: 1rem 1.25rem; border-top: 1px solid rgba(0,0,0,0.05); flex-wrap: wrap; }
.page-btn { padding: 0.4rem 0.75rem; border-radius: 0.5rem; font-size: 0.8125rem; font-weight: 500; background: rgba(255,255,255,0.7); border: 1px solid rgba(0,0,0,0.08); color: rgba(0,0,0,0.60); text-decoration: none; transition: all 0.15s; }
.page-btn--active { background: #007AFF; border-color: #007AFF; color: white; font-weight: 700; box-shadow: 0 2px 8px rgba(0,122,255,0.30); }
.page-btn--disabled { color: rgba(0,0,0,0.25); background: transparent; border-color: transparent; }

/* Mobile list */
.mobile-list { display: flex; flex-direction: column; gap: 0.75rem; }
.mobile-card { background: rgba(255,255,255,0.7); border: 1px solid rgba(255,255,255,0.85); border-radius: 1rem; padding: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.06), inset 0 1px 0 rgba(255,255,255,0.9); display: flex; flex-direction: column; gap: 0.5rem; }
.mobile-card--alert { border-color: rgba(255,59,48,0.25); background: rgba(255,59,48,0.04); }
.mobile-card__top { display: flex; align-items: center; justify-content: space-between; }
.mobile-card__name { font-size: 1rem; font-weight: 700; color: rgba(0,0,0,0.82); letter-spacing: -0.02em; margin: 0; }
.mobile-card__meta { display: flex; gap: 0.5rem; flex-wrap: wrap; }
.meta-chip { display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.72rem; color: rgba(0,0,0,0.45); background: rgba(0,0,0,0.05); padding: 0.2rem 0.5rem; border-radius: 999px; }
.meta-chip--alert { background: rgba(255,59,48,0.10); color: var(--ios-red); }
.mobile-card__actions { display: flex; gap: 0.5rem; padding-top: 0.375rem; border-top: 1px solid rgba(0,0,0,0.05); margin-top: 0.25rem; }

/* Modal */
.modal-body { padding: 1.5rem; }
.modal-header { display: flex; align-items: flex-start; gap: 0.875rem; margin-bottom: 1.5rem; padding-bottom: 1.25rem; border-bottom: 1px solid rgba(0,0,0,0.06); }
.modal-header__icon { width: 2.5rem; height: 2.5rem; border: 1px solid; border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.modal-header__title { font-size: 1.0625rem; font-weight: 700; color: rgba(0,0,0,0.82); letter-spacing: -0.02em; margin: 0 0 0.15rem; }
.modal-header__sub { font-size: 0.78rem; color: rgba(0,0,0,0.38); margin: 0; }
.modal-close { margin-left: auto; flex-shrink: 0; width: 1.875rem; height: 1.875rem; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.06); border: none; border-radius: 50%; color: rgba(0,0,0,0.45); cursor: pointer; transition: background 0.15s; }
.modal-close:hover { background: rgba(0,0,0,0.10); }
.modal-form { display: flex; flex-direction: column; gap: 0.875rem; }
.field { display: flex; flex-direction: column; gap: 0.3rem; }
.modal-actions { display: flex; justify-content: flex-end; gap: 0.625rem; margin-top: 0.5rem; padding-top: 1rem; border-top: 1px solid rgba(0,0,0,0.05); }
</style>
