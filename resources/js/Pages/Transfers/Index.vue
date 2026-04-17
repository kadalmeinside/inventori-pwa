<template>
  <AppLayout title="Stock Transfers">
    <div class="page-wrap">

      <!-- Header -->
      <div class="page-header">
        <div>
          <p class="page-eyebrow">Logistics</p>
          <h1 class="page-title">Stock Transfers</h1>
        </div>
        <button class="btn-ios btn-ios-primary" @click="openInitiateModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M12 5v14M5 12h14"/></svg>
          Initiate Transfer
        </button>
      </div>

      <!-- Table -->
      <div class="glass-card">
        <!-- Desktop -->
        <div class="table-wrap hidden md:block">
          <table class="ios-table">
            <thead>
              <tr>
                <th>Status</th>
                <th>Product</th>
                <th>Route</th>
                <th class="text-right">Qty</th>
                <th class="text-center">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="transfer in transfers.data" :key="transfer.id" class="table-row">
                <td>
                  <span class="status-badge" :class="statusClass(transfer.status)">
                    {{ transfer.status.replace('_', ' ').toUpperCase() }}
                  </span>
                </td>
                <td>
                  <div class="td-name">{{ transfer.product.name }}</div>
                  <div class="td-mono" style="font-size:0.68rem; color:rgba(0,0,0,0.35); margin-top:2px;">{{ transfer.product.sku }}</div>
                </td>
                <td>
                  <div style="font-size:0.78rem; color:rgba(0,0,0,0.55);">
                    <span style="color:rgba(0,0,0,0.35); font-size:0.65rem; text-transform:uppercase; letter-spacing:0.05em;">From</span>
                    {{ transfer.source_warehouse.name }}
                  </div>
                  <div style="font-size:0.78rem; color:var(--ios-green); margin-top:2px;">
                    <span style="color:rgba(52,199,89,0.6); font-size:0.65rem; text-transform:uppercase; letter-spacing:0.05em;">To</span>
                    {{ transfer.destination_warehouse.name }}
                  </div>
                </td>
                <td class="text-right">
                  <span class="td-name">{{ transfer.quantity }}</span>
                  <span class="td-muted" style="font-size:0.75rem; margin-left:3px;">{{ transfer.product.unit }}</span>
                </td>
                <td class="text-center">
                  <button v-if="transfer.status === 'in_transit' && canReceive(transfer)" class="action-btn action-btn--green" @click="receiveTransfer(transfer)">
                    Receive
                  </button>
                  <span v-else-if="transfer.status === 'in_transit'" class="waiting-label">Waiting</span>
                  <span v-else class="td-muted">—</span>
                </td>
              </tr>
              <tr v-if="transfers.data.length === 0"><td colspan="5" class="empty-cell">No active stock transfers found.</td></tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile Cards -->
        <div class="block md:hidden p-2">
          <div class="mobile-list">
            <div v-for="transfer in transfers.data" :key="'m-'+transfer.id" class="mobile-card">
              <div class="mobile-card__top">
                <span class="td-mono" style="font-size:0.68rem;">{{ transfer.product.sku }}</span>
                <span class="status-badge" :class="statusClass(transfer.status)">
                  {{ transfer.status.replace('_',' ').toUpperCase() }}
                </span>
              </div>
              <h3 class="mobile-card__name">{{ transfer.product.name }}</h3>

              <!-- Route visual -->
              <div class="route-card">
                <div class="route-card__track">
                  <div class="route-dot route-dot--from" />
                  <div class="route-line" />
                  <div class="route-dot route-dot--to" />
                </div>
                <div class="route-card__labels">
                  <div>
                    <div class="route-label">From</div>
                    <div class="route-name">{{ transfer.source_warehouse.name }}</div>
                  </div>
                  <div>
                    <div class="route-label route-label--to">To</div>
                    <div class="route-name route-name--to">{{ transfer.destination_warehouse.name }}</div>
                  </div>
                </div>
                <div class="route-qty">
                  <div class="route-label">Qty</div>
                  <div class="route-qty-val">{{ transfer.quantity }} <span style="font-size:0.65rem; font-family:sans-serif; font-weight:400; color:rgba(0,0,0,0.35);">{{ transfer.product.unit }}</span></div>
                </div>
              </div>

              <div v-if="transfer.status === 'in_transit' && canReceive(transfer)" class="mobile-card__actions">
                <button class="action-btn action-btn--green action-btn--full" @click="receiveTransfer(transfer)">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><polyline points="20 6 9 17 4 12"/></svg>
                  Confirm Receipt
                </button>
              </div>
              <div v-else-if="transfer.status === 'in_transit'" class="mobile-card__waiting">
                Waiting for destination to receive
              </div>
            </div>
            <div v-if="transfers.data.length === 0" class="empty-cell">No active stock transfers found.</div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="transfers.links.length > 3" class="pagination">
          <template v-for="(link, i) in transfers.links" :key="i">
            <span v-if="link.url === null" class="page-btn page-btn--disabled" v-html="link.label" />
            <Link v-else :href="link.url" class="page-btn" :class="link.active ? 'page-btn--active' : ''" v-html="link.label" />
          </template>
        </div>
      </div>
    </div>

    <!-- ─── Initiate Transfer Modal ───────────────────────────────────── -->
    <Modal :show="showModal" @close="closeModal" maxWidth="md">
      <div class="modal-body">
        <div class="modal-header">
          <div class="modal-header__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg>
          </div>
          <div>
            <h2 class="modal-header__title">Initiate Transfer</h2>
            <p class="modal-header__sub">Move stock between warehouses</p>
          </div>
          <button class="modal-close" @click="closeModal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M18 6L6 18M6 6l12 12"/></svg>
          </button>
        </div>
        <form @submit.prevent="submitTransfer" class="modal-form">
          <div class="form-grid">
            <div class="field">
              <InputLabel for="source_warehouse" value="Source Warehouse" />
              <!-- Branch Admin: auto-locked to own warehouse -->
              <div v-if="!isSuperAdmin" class="input-ios input-ios--locked">
                {{ myWarehouse?.name ?? 'Your Warehouse' }}
              </div>
              <select v-else id="source_warehouse" v-model="form.source_warehouse_id" class="input-ios" required>
                <option disabled value="">Select source…</option>
                <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
              </select>
              <InputError :message="form.errors.source_warehouse_id" />
            </div>
            <div class="field">
              <InputLabel for="dest_warehouse" value="Destination Warehouse" />
              <select id="dest_warehouse" v-model="form.destination_warehouse_id" class="input-ios" required>
                <option disabled value="">Select destination…</option>
                <option
                  v-for="wh in destinationWarehouses"
                  :key="wh.id"
                  :value="wh.id"
                >{{ wh.name }}</option>
              </select>
              <InputError :message="form.errors.destination_warehouse_id" />
            </div>
            <div class="field span-2">
              <InputLabel for="product" value="Product" />
              <select id="product" v-model="form.product_id" class="input-ios" required>
                <option disabled value="">Select product…</option>
                <option v-for="item in products" :key="item.id" :value="item.id">{{ item.sku }} — {{ item.name }}</option>
              </select>
              <InputError :message="form.errors.product_id" />
            </div>

            <!-- ── Stock Availability Alert ──────────────────────────── -->
            <div v-if="(isSuperAdmin ? form.source_warehouse_id : user.warehouse_id) && form.product_id" class="field span-2">
              <!-- No stock at all -->
              <div v-if="currentSourceStock === 0" class="stock-alert stock-alert--danger">
                <div class="stock-alert__icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </div>
                <div class="stock-alert__body">
                  <div class="stock-alert__title">Stok Tidak Tersedia di Cabang Asal</div>
                  <div class="stock-alert__msg">Cabang asal tidak memiliki stok untuk produk tersebut. Transfer tidak dapat dilakukan.</div>
                </div>
              </div>
              <!-- Insufficient stock -->
              <div v-else-if="form.quantity > currentSourceStock" class="stock-alert stock-alert--warning">
                <div class="stock-alert__icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </div>
                <div class="stock-alert__body">
                  <div class="stock-alert__title">Stok Tidak Mencukupi</div>
                  <div class="stock-alert__msg">Jumlah transfer melebihi stok yang tersedia. Stok saat ini: <strong>{{ currentSourceStock }}</strong> unit.</div>
                </div>
              </div>
              <!-- Stock OK -->
              <div v-else class="stock-alert stock-alert--ok">
                <div class="stock-alert__icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <div class="stock-alert__body">
                  <div class="stock-alert__title">Stok Tersedia</div>
                  <div class="stock-alert__msg">Stok tersedia di cabang asal: <strong>{{ currentSourceStock }}</strong> unit.</div>
                </div>
              </div>
            </div>

            <div class="field">
              <InputLabel for="qty" value="Transfer Quantity" />
              <TextInput id="qty" v-model="form.quantity" type="number" min="1" required />
              <InputError :message="form.errors.quantity" />
            </div>
            <div class="field">
              <InputLabel for="notes" value="Notes (Optional)" />
              <TextInput id="notes" v-model="form.notes" type="text" placeholder="e.g. Urgent restock" />
              <InputError :message="form.errors.notes" />
            </div>
          </div>
          <div class="modal-actions">
            <button type="button" @click="closeModal" class="btn-ios btn-ios-glass">Cancel</button>
            <PrimaryButton
              :class="{ 'opacity-50': form.processing || currentSourceStock === 0 }"
              :disabled="form.processing || currentSourceStock === 0"
            >Initiate</PrimaryButton>
          </div>
        </form>
      </div>
    </Modal>

    <!-- ─── Confirm Receipt Modal ─────────────────────────────────────── -->
    <Modal :show="showConfirmModal" @close="closeConfirmModal" maxWidth="sm">
      <div class="confirm-body">
        <div class="confirm-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <h3 class="confirm-title">Confirm Receipt</h3>
        <p class="confirm-sub">
          Are you sure you received<br>
          <strong>{{ activeTransfer?.quantity }} {{ activeTransfer?.product?.name }}</strong><br>
          from {{ activeTransfer?.source_warehouse?.name }}?
        </p>
        <p class="confirm-note">This will irrevocably add stock to {{ activeTransfer?.destination_warehouse?.name }}.</p>
        <div class="confirm-actions">
          <button @click="closeConfirmModal" class="btn-ios btn-ios-glass">Cancel</button>
          <button @click="submitReceive" class="btn-ios" style="background: var(--ios-green); color: white; box-shadow: 0 4px 16px rgba(52,199,89,0.35);">
            Confirm Received
          </button>
        </div>
      </div>
    </Modal>

  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, useForm, Link, usePage } from '@inertiajs/vue3';
import AppLayout     from '@/Layouts/AppLayout.vue';
import Modal         from '@/Components/Modal.vue';
import TextInput     from '@/Components/TextInput.vue';
import InputLabel    from '@/Components/InputLabel.vue';
import InputError    from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({ transfers: Object, warehouses: Array, products: Array, stocks: Array });
const page  = usePage();
const user  = computed(() => page.props.auth.user);
const isSuperAdmin = computed(() => user.value.role === 'super_admin');
const myWarehouse  = computed(() => props.warehouses?.find(w => w.id === user.value.warehouse_id));

// Destinations exclude the user's own warehouse for Branch Admin
const destinationWarehouses = computed(() => {
  if (isSuperAdmin.value) return props.warehouses;
  return props.warehouses.filter(w => w.id !== user.value.warehouse_id);
});

const canReceive = (t) => {
  return user.value.role === 'super_admin' || user.value.warehouse_id === t.destination_warehouse_id;
};

const statusClass = (s) => ({
  pending:    'status-badge--blue',
  in_transit: 'status-badge--amber',
  received:   'status-badge--green',
  rejected:   'status-badge--red',
}[s] || '');

const showModal = ref(false), showConfirmModal = ref(false);
const activeTransfer = ref(null);

const form = useForm({ source_warehouse_id: '', destination_warehouse_id: '', product_id: '', quantity: 1, notes: '' });

// Current stock at source warehouse for selected product
const currentSourceStock = computed(() => {
  const srcId = isSuperAdmin.value ? form.source_warehouse_id : user.value.warehouse_id;
  if (!srcId || !form.product_id) return null;
  const entry = props.stocks?.find(
    s => s.warehouse_id === srcId && s.product_id === form.product_id
  );
  return entry ? entry.quantity : 0;
});

const openInitiateModal = () => {
  form.clearErrors();
  form.reset();
  // Auto-set source for Branch Admin
  if (!isSuperAdmin.value) {
    form.source_warehouse_id = user.value.warehouse_id;
  }
  showModal.value = true;
};
const closeModal  = () => { showModal.value = false; form.reset(); };
const submitTransfer = () => form.post(route('transfers.store'), { preserveScroll: true, onSuccess: closeModal });

const receiveTransfer   = (t) => { activeTransfer.value = t; showConfirmModal.value = true; };
const closeConfirmModal = () => { showConfirmModal.value = false; setTimeout(() => activeTransfer.value = null, 300); };
const submitReceive = () => {
  if (activeTransfer.value) {
    router.patch(route('transfers.receive', activeTransfer.value.id), {}, { preserveScroll: true, onSuccess: closeConfirmModal });
  }
};
</script>

<style scoped>
.page-wrap { padding: 2rem 1.25rem 5rem; max-width: 1100px; margin: 0 auto; }
.page-header { display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 1.5rem; gap: 1rem; flex-wrap: wrap; }
.page-eyebrow { font-size: 0.7rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: rgba(0,0,0,0.35); margin: 0 0 0.2rem; }
.page-title { font-size: 1.75rem; font-weight: 800; letter-spacing: -0.04em; color: rgba(0,0,0,0.85); margin: 0; line-height: 1; }
.glass-card { background: rgba(255,255,255,0.62); backdrop-filter: blur(28px) saturate(180%); -webkit-backdrop-filter: blur(28px) saturate(180%); border: 1px solid rgba(255,255,255,0.82); border-radius: 1.5rem; box-shadow: 0 8px 40px rgba(0,80,200,0.10), inset 0 1px 0 rgba(255,255,255,0.95); overflow: hidden; }
.table-wrap { overflow-x: auto; }
.ios-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
.ios-table thead tr { background: rgba(0,0,0,0.03); border-bottom: 1px solid rgba(0,0,0,0.06); }
.ios-table th { padding: 0.875rem 1.25rem; font-size: 0.68rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: rgba(0,0,0,0.38); text-align: left; white-space: nowrap; }
.ios-table td { padding: 0.875rem 1.25rem; color: rgba(0,0,0,0.60); border-bottom: 1px solid rgba(0,0,0,0.04); vertical-align: middle; }
.table-row { transition: background 0.15s; }
.table-row:hover { background: rgba(0,122,255,0.03); }
.table-row:last-child td { border-bottom: none; }
.td-name { font-weight: 600; color: rgba(0,0,0,0.80); }
.td-muted { color: rgba(0,0,0,0.40); }
.td-mono { font-family: var(--font-mono); font-size: 0.78rem; letter-spacing: 0.04em; }

/* Status badge */
.status-badge { display: inline-flex; align-items: center; padding: 0.2rem 0.6rem; border-radius: 999px; font-size: 0.62rem; font-weight: 700; letter-spacing: 0.05em; border: 1px solid; }
.status-badge--blue  { background: rgba(0,122,255,0.10); color: #007AFF; border-color: rgba(0,122,255,0.22); }
.status-badge--amber { background: rgba(255,149,0,0.10); color: #FF9500; border-color: rgba(255,149,0,0.22); }
.status-badge--green { background: rgba(52,199,89,0.10); color: #1a7a34; border-color: rgba(52,199,89,0.22); }
.status-badge--red   { background: rgba(255,59,48,0.10); color: #FF3B30; border-color: rgba(255,59,48,0.22); }

/* Action btn */
.action-btn { padding: 0.35rem 0.875rem; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 600; cursor: pointer; border: 1px solid; transition: all 0.15s; display: inline-flex; align-items: center; gap: 0.3rem; -webkit-tap-highlight-color: transparent; }
.action-btn:active { transform: scale(0.96); }
.action-btn--green { background: rgba(52,199,89,0.10); border-color: rgba(52,199,89,0.22); color: var(--ios-green); }
.action-btn--green:hover { background: rgba(52,199,89,0.18); }
.action-btn--full { justify-content: center; width: 100%; }
.waiting-label { font-size: 0.68rem; color: rgba(0,0,0,0.30); font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em; }

/* Empty */
.empty-cell { text-align: center; padding: 3rem 1rem; color: rgba(0,0,0,0.30); font-size: 0.875rem; }

/* Pagination */
.pagination { display: flex; justify-content: center; gap: 0.375rem; padding: 1rem 1.25rem; border-top: 1px solid rgba(0,0,0,0.05); flex-wrap: wrap; }
.page-btn { padding: 0.4rem 0.75rem; border-radius: 0.5rem; font-size: 0.8125rem; font-weight: 500; background: rgba(255,255,255,0.7); border: 1px solid rgba(0,0,0,0.08); color: rgba(0,0,0,0.60); text-decoration: none; transition: all 0.15s; }
.page-btn--active { background: #007AFF; border-color: #007AFF; color: white; font-weight: 700; }
.page-btn--disabled { color: rgba(0,0,0,0.25); background: transparent; border-color: transparent; }

/* Mobile */
.mobile-list { display: flex; flex-direction: column; gap: 0.75rem; }
.mobile-card { background: rgba(255,255,255,0.7); border: 1px solid rgba(255,255,255,0.85); border-radius: 1rem; padding: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.06), inset 0 1px 0 rgba(255,255,255,0.9); display: flex; flex-direction: column; gap: 0.5rem; }
.mobile-card__top { display: flex; align-items: center; justify-content: space-between; }
.mobile-card__name { font-size: 1rem; font-weight: 700; color: rgba(0,0,0,0.82); letter-spacing: -0.02em; margin: 0; }
.mobile-card__actions { display: flex; gap: 0.5rem; padding-top: 0.375rem; border-top: 1px solid rgba(0,0,0,0.05); margin-top: 0.25rem; }
.mobile-card__waiting { font-size: 0.75rem; color: rgba(0,0,0,0.35); text-align: center; padding-top: 0.5rem; border-top: 1px solid rgba(0,0,0,0.05); margin-top: 0.25rem; }

/* Route visual */
.route-card { display: flex; align-items: center; gap: 0.75rem; background: rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.06); border-radius: 0.75rem; padding: 0.75rem; }
.route-card__track { display: flex; flex-direction: column; align-items: center; gap: 0; flex-shrink: 0; }
.route-dot { width: 8px; height: 8px; border-radius: 50%; }
.route-dot--from { background: rgba(0,0,0,0.30); }
.route-dot--to { background: var(--ios-green); box-shadow: 0 0 6px rgba(52,199,89,0.5); }
.route-line { width: 2px; height: 20px; background: rgba(0,0,0,0.10); margin: 2px 0; }
.route-card__labels { flex: 1; display: flex; flex-direction: column; gap: 0.375rem; }
.route-label { font-size: 0.6rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: rgba(0,0,0,0.30); }
.route-label--to { color: rgba(52,199,89,0.7); }
.route-name { font-size: 0.78rem; font-weight: 500; color: rgba(0,0,0,0.65); }
.route-name--to { color: var(--ios-green); }
.route-qty { text-align: right; flex-shrink: 0; }
.route-qty-val { font-size: 1.125rem; font-weight: 800; font-family: var(--font-mono); letter-spacing: -0.03em; color: rgba(0,0,0,0.75); }

/* Modal */
.modal-body { padding: 1.5rem; }
.modal-header { display: flex; align-items: flex-start; gap: 0.875rem; margin-bottom: 1.5rem; padding-bottom: 1.25rem; border-bottom: 1px solid rgba(0,0,0,0.06); }
.modal-header__icon { width: 2.5rem; height: 2.5rem; background: rgba(0,122,255,0.10); border: 1px solid rgba(0,122,255,0.18); border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; color: #007AFF; flex-shrink: 0; }
.modal-header__title { font-size: 1.0625rem; font-weight: 700; color: rgba(0,0,0,0.82); letter-spacing: -0.02em; margin: 0 0 0.15rem; }
.modal-header__sub { font-size: 0.78rem; color: rgba(0,0,0,0.38); margin: 0; }
.modal-close { margin-left: auto; flex-shrink: 0; width: 1.875rem; height: 1.875rem; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.06); border: none; border-radius: 50%; color: rgba(0,0,0,0.45); cursor: pointer; }
.modal-form { display: flex; flex-direction: column; gap: 0.875rem; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.875rem; }
.field { display: flex; flex-direction: column; gap: 0.3rem; }
.span-2 { grid-column: 1 / -1; }
.modal-actions { display: flex; justify-content: flex-end; gap: 0.625rem; padding-top: 1rem; border-top: 1px solid rgba(0,0,0,0.05); }

/* Confirm modal */
.confirm-body { padding: 2rem 1.5rem; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 0.75rem; }
.confirm-icon { width: 4rem; height: 4rem; background: rgba(52,199,89,0.12); border: 1px solid rgba(52,199,89,0.22); border-radius: 1.25rem; display: flex; align-items: center; justify-content: center; color: var(--ios-green); margin-bottom: 0.25rem; }
.confirm-title { font-size: 1.125rem; font-weight: 700; color: rgba(0,0,0,0.82); letter-spacing: -0.02em; margin: 0; }
.confirm-sub { font-size: 0.875rem; color: rgba(0,0,0,0.55); line-height: 1.6; margin: 0; }
.confirm-note { font-size: 0.75rem; color: rgba(0,0,0,0.35); margin: 0; }
.confirm-actions { display: flex; gap: 0.625rem; margin-top: 0.5rem; }

/* ── Stock Alert ──────────────────────────────────────────── */
.stock-alert { display: flex; align-items: flex-start; gap: 0.625rem; padding: 0.75rem 0.875rem; border-radius: 0.75rem; border: 1px solid; animation: alertFadeIn 0.25s ease; }
@keyframes alertFadeIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }
.stock-alert__icon { flex-shrink: 0; width: 1.75rem; height: 1.75rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-top: 1px; }
.stock-alert__body { display: flex; flex-direction: column; gap: 0.15rem; }
.stock-alert__title { font-size: 0.78rem; font-weight: 700; }
.stock-alert__msg { font-size: 0.72rem; line-height: 1.4; }

/* Danger – no stock */
.stock-alert--danger { background: rgba(255,59,48,0.07); border-color: rgba(255,59,48,0.25); }
.stock-alert--danger .stock-alert__icon { background: rgba(255,59,48,0.12); color: #FF3B30; }
.stock-alert--danger .stock-alert__title { color: #d32f2f; }
.stock-alert--danger .stock-alert__msg { color: rgba(180,30,20,0.75); }

/* Warning – insufficient */
.stock-alert--warning { background: rgba(255,149,0,0.07); border-color: rgba(255,149,0,0.25); }
.stock-alert--warning .stock-alert__icon { background: rgba(255,149,0,0.12); color: #FF9500; }
.stock-alert--warning .stock-alert__title { color: #b06000; }
.stock-alert--warning .stock-alert__msg { color: rgba(150,80,0,0.80); }

/* OK – sufficient */
.stock-alert--ok { background: rgba(52,199,89,0.07); border-color: rgba(52,199,89,0.22); }
.stock-alert--ok .stock-alert__icon { background: rgba(52,199,89,0.12); color: #34C759; }
.stock-alert--ok .stock-alert__title { color: #1a6d33; }
.stock-alert--ok .stock-alert__msg { color: rgba(20,100,50,0.75); }
</style>
