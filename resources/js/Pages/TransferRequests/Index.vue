<template>
  <AppLayout title="Transfer Requests">
    <div class="page-wrap">

      <div class="page-header">
        <div>
          <p class="page-eyebrow">{{ isSuperAdmin ? 'Approval Queue' : 'My Requests' }}</p>
          <h1 class="page-title">Transfer Requests</h1>
        </div>
        <!-- Branch Admin can create new requests -->
        <button v-if="!isSuperAdmin" class="btn-ios btn-ios-primary" @click="openModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M12 5v14M5 12h14"/></svg>
          Request Stock
        </button>
      </div>

      <!-- Info banner for Branch Admin -->
      <div v-if="!isSuperAdmin" class="info-banner">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
        <span>Request kiriman stok ke gudang Anda dari Pusat atau cabang lain. Super Admin akan memprosesnya.</span>
      </div>

      <div class="glass-card">
        <!-- Desktop Table -->
        <div class="table-wrap hidden md:block">
          <table class="ios-table">
            <thead>
              <tr>
                <th>Status</th>
                <th>Date / By</th>
                <th v-if="isSuperAdmin">Destination</th>
                <th>Product</th>
                <th class="text-right">Qty</th>
                <th>Notes</th>
                <th v-if="isSuperAdmin" class="text-center">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="req in requests.data" :key="req.id" class="table-row">
                <td>
                  <span class="status-badge" :class="statusClass(req.status)">
                    {{ req.status.toUpperCase() }}
                  </span>
                </td>
                <td>
                  <div class="td-name" style="font-weight:500;">{{ formatDate(req.created_at) }}</div>
                  <div class="td-muted" style="font-size:0.75rem; margin-top:2px;">{{ req.requester?.name }}</div>
                </td>
                <td v-if="isSuperAdmin">
                  <div class="td-name">{{ req.to_warehouse?.name }}</div>
                  <div class="td-muted" style="font-size:0.72rem;">
                    From: {{ req.from_warehouse?.name ?? '(not set)' }}
                  </div>
                </td>
                <td>
                  <div class="td-name">{{ req.product?.name }}</div>
                  <div class="td-muted" style="font-size:0.72rem;">{{ req.product?.sku }}</div>
                </td>
                <td class="text-right">
                  <span class="td-name font-mono">{{ req.quantity }}</span>
                  <span class="td-muted" style="font-size:0.75rem; margin-left:3px;">{{ req.product?.unit }}</span>
                </td>
                <td style="max-width:180px;">
                  <span class="td-muted reason-text">{{ req.notes || '—' }}</span>
                </td>
                <td v-if="isSuperAdmin" class="text-center">
                  <div v-if="req.status === 'pending'" class="action-group">
                    <button class="action-btn action-btn--green" @click="openApproveModal(req)">Approve</button>
                    <button class="action-btn action-btn--red" @click="rejectReq(req)">Reject</button>
                  </div>
                  <span v-else class="by-label">
                    {{ req.reviewer ? `By ${req.reviewer.name}` : '—' }}
                  </span>
                </td>
              </tr>
              <tr v-if="requests.data.length === 0">
                <td :colspan="isSuperAdmin ? 7 : 5" class="empty-cell">No transfer requests found.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile Cards -->
        <div class="block md:hidden p-2">
          <div class="mobile-list">
            <div v-for="req in requests.data" :key="'m-'+req.id" class="mobile-card">
              <div class="mobile-card__top">
                <div>
                  <div class="td-muted" style="font-size:0.72rem;">{{ formatDate(req.created_at) }} · {{ req.requester?.name }}</div>
                  <h3 class="mobile-card__name">{{ req.product?.name }}</h3>
                </div>
                <span class="status-badge" :class="statusClass(req.status)">{{ req.status.toUpperCase() }}</span>
              </div>

              <div class="route-row" v-if="isSuperAdmin">
                <span class="route-chip route-chip--from">{{ req.from_warehouse?.name ?? 'Pending source' }}</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                <span class="route-chip route-chip--to">{{ req.to_warehouse?.name }}</span>
              </div>

              <div class="detail-row">
                <div>
                  <div class="detail-label">Notes</div>
                  <div class="detail-val">{{ req.notes || '—' }}</div>
                </div>
                <div class="detail-qty">
                  <div class="detail-label">Qty</div>
                  <div class="detail-qty-val">{{ req.quantity }} <span style="font-size:0.65rem;">{{ req.product?.unit }}</span></div>
                </div>
              </div>

              <div v-if="isSuperAdmin && req.status === 'pending'" class="mobile-card__actions">
                <button class="action-btn action-btn--green action-btn--full" @click="openApproveModal(req)">Approve</button>
                <button class="action-btn action-btn--red action-btn--full" @click="rejectReq(req)">Reject</button>
              </div>
            </div>
            <div v-if="requests.data.length === 0" class="empty-cell">No transfer requests found.</div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="requests.links.length > 3" class="pagination">
          <template v-for="(link, i) in requests.links" :key="i">
            <span v-if="link.url === null" class="page-btn page-btn--disabled" v-html="link.label" />
            <Link v-else :href="link.url" class="page-btn" :class="link.active ? 'page-btn--active' : ''" v-html="link.label" />
          </template>
        </div>
      </div>
    </div>

    <!-- ─── New Request Modal (Branch Admin) ─────────────────────────────── -->
    <Modal :show="showRequestModal" @close="closeRequestModal" maxWidth="md">
      <div class="modal-body">
        <div class="modal-header">
          <div class="modal-header__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </div>
          <div>
            <h2 class="modal-header__title">Request Stock</h2>
            <p class="modal-header__sub">Minta kiriman stok ke gudang Anda</p>
          </div>
          <button class="modal-close" @click="closeRequestModal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M18 6L6 18M6 6l12 12"/></svg>
          </button>
        </div>
        <form @submit.prevent="submitRequest" class="modal-form">
          <div class="form-grid">
            <div class="field span-2">
              <InputLabel for="req_product" value="Product" />
              <select id="req_product" v-model="requestForm.product_id" class="input-ios" required>
                <option disabled value="">Select product…</option>
                <option v-for="p in products" :key="p.id" :value="p.id">{{ p.sku }} — {{ p.name }}</option>
              </select>
              <InputError :message="requestForm.errors.product_id" />
            </div>
            <div class="field">
              <InputLabel for="req_qty" value="Quantity Requested" />
              <TextInput id="req_qty" v-model="requestForm.quantity" type="number" min="1" required />
              <InputError :message="requestForm.errors.quantity" />
            </div>
            <div class="field">
              <InputLabel for="req_notes" value="Notes (Optional)" />
              <TextInput id="req_notes" v-model="requestForm.notes" type="text" placeholder="e.g. Urgent restock" />
              <InputError :message="requestForm.errors.notes" />
            </div>
          </div>
          <div class="modal-actions">
            <button type="button" @click="closeRequestModal" class="btn-ios btn-ios-glass">Cancel</button>
            <PrimaryButton :class="{ 'opacity-50': requestForm.processing }" :disabled="requestForm.processing">
              Submit Request
            </PrimaryButton>
          </div>
        </form>
      </div>
    </Modal>

    <!-- ─── Approve Modal (Super Admin picks source warehouse) ───────────── -->
    <Modal :show="showApproveModal" @close="closeApproveModal" maxWidth="sm">
      <div class="modal-body">
        <div class="modal-header">
          <div class="modal-header__icon" style="background:rgba(52,199,89,0.12);color:#34C759;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <div>
            <h2 class="modal-header__title">Approve Request</h2>
            <p class="modal-header__sub">Select source warehouse to transfer from</p>
          </div>
          <button class="modal-close" @click="closeApproveModal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M18 6L6 18M6 6l12 12"/></svg>
          </button>
        </div>
        <div class="approve-info" v-if="activeRequest">
          <span class="cat-chip">{{ activeRequest.product?.name }}</span>
          <span class="cat-chip">{{ activeRequest.quantity }} {{ activeRequest.product?.unit }}</span>
          <span class="cat-chip">→ {{ activeRequest.to_warehouse?.name }}</span>
        </div>
        <form @submit.prevent="submitApprove" class="modal-form" style="margin-top:0.75rem;">
          <div class="form-grid">
            <div class="field span-2">
              <InputLabel for="src_warehouse" value="Source Warehouse (kirim dari)" />
              <select id="src_warehouse" v-model="approveForm.from_warehouse_id" class="input-ios" required>
                <option disabled value="">Select source…</option>
                <option v-for="wh in sourceWarehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
              </select>
              <InputError :message="approveForm.errors.from_warehouse_id" />
            </div>

            <!-- ── Stock Alert (muncul setelah gudang sumber dipilih) ── -->
            <Transition name="stock-banner">
              <div v-if="approveForm.from_warehouse_id && activeRequest" class="field span-2">
                <div class="approve-stock" :class="stockApproveClass">
                  <!-- Icon -->
                  <div class="approve-stock__icon">
                    <svg v-if="approveSourceStock === 0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="17" height="17"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <svg v-else-if="activeRequest.quantity > approveSourceStock" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="17" height="17"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="17" height="17"><polyline points="20 6 9 17 4 12"/></svg>
                  </div>
                  <!-- Body -->
                  <div class="approve-stock__body">
                    <span class="approve-stock__title">
                      <template v-if="approveSourceStock === 0">Stok Tidak Tersedia</template>
                      <template v-else-if="activeRequest.quantity > approveSourceStock">Stok Tidak Mencukupi</template>
                      <template v-else>Stok Cukup &mdash; Siap Transfer</template>
                    </span>
                    <span class="approve-stock__msg">
                      <template v-if="approveSourceStock === 0">Gudang ini tidak memiliki stok produk tersebut.</template>
                      <template v-else-if="activeRequest.quantity > approveSourceStock">
                        Diminta <strong>{{ activeRequest.quantity }}</strong> unit, tersedia <strong>{{ approveSourceStock }}</strong> unit.
                        Kurang <strong>{{ activeRequest.quantity - approveSourceStock }}</strong> unit.
                      </template>
                      <template v-else>
                        Stok tersedia: <strong>{{ approveSourceStock }}</strong> unit.
                        Sisa setelah transfer: <strong>{{ approveSourceStock - activeRequest.quantity }}</strong> unit.
                      </template>
                    </span>
                  </div>
                  <!-- Qty Pill -->
                  <div class="approve-stock__pill">
                    <span class="approve-stock__qty">{{ approveSourceStock ?? 0 }}</span>
                    <span class="approve-stock__unit">unit</span>
                  </div>
                </div>
              </div>
            </Transition>

          </div>
          <div class="modal-actions">
            <button type="button" @click="closeApproveModal" class="btn-ios btn-ios-glass">Cancel</button>
            <button
              type="submit"
              class="btn-ios approve-submit-btn"
              :class="{ 'approve-submit-btn--disabled': approveForm.processing || approveSourceStock === 0 || (activeRequest && activeRequest.quantity > approveSourceStock) }"
              :disabled="approveForm.processing || approveSourceStock === 0 || (activeRequest && activeRequest.quantity > approveSourceStock)"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><polyline points="20 6 9 17 4 12"/></svg>
              Approve &amp; Transfer
            </button>
          </div>
        </form>
      </div>
    </Modal>

  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, useForm, Link, usePage } from '@inertiajs/vue3';
import AppLayout     from '@/Layouts/AppLayout.vue';
import Modal         from '@/Components/Modal.vue';
import InputLabel    from '@/Components/InputLabel.vue';
import InputError    from '@/Components/InputError.vue';
import TextInput     from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({ requests: Object, warehouses: Array, products: Array, stocks: Array });
const page  = usePage();
const user  = computed(() => page.props.auth.user);
const isSuperAdmin = computed(() => user.value.role === 'super_admin');

// Exclude destination warehouse from source options
const sourceWarehouses = computed(() => {
  if (!activeRequest.value) return props.warehouses;
  return props.warehouses.filter(w => w.id !== activeRequest.value.to_warehouse_id);
});

const formatDate = (d) => new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
const statusClass = (s) => ({
  pending:  'status-badge--amber',
  approved: 'status-badge--green',
  rejected: 'status-badge--red',
}[s] || '');

// ─── Branch Admin: New Request Modal ─────────────────────────────────────────
const showRequestModal = ref(false);
const requestForm = useForm({ product_id: '', quantity: 1, notes: '' });

const openModal         = () => { requestForm.reset(); requestForm.clearErrors(); showRequestModal.value = true; };
const closeRequestModal = () => { showRequestModal.value = false; requestForm.reset(); };
const submitRequest     = () => requestForm.post(route('transfer-requests.store'), { preserveScroll: true, onSuccess: closeRequestModal });

// ─── Super Admin: Approve Modal ───────────────────────────────────────────────
const showApproveModal = ref(false);
const activeRequest    = ref(null);
const approveForm      = useForm({ from_warehouse_id: '' });

// Stock available at the selected source warehouse for the requested product
const approveSourceStock = computed(() => {
  if (!approveForm.from_warehouse_id || !activeRequest.value) return null;
  const entry = props.stocks?.find(
    s => s.warehouse_id === approveForm.from_warehouse_id
      && s.product_id  === activeRequest.value.product_id
  );
  return entry ? entry.quantity : 0;
});

const stockApproveClass = computed(() => {
  if (approveSourceStock.value === null)                                         return '';
  if (approveSourceStock.value === 0)                                            return 'approve-stock--danger';
  if (activeRequest.value && activeRequest.value.quantity > approveSourceStock.value) return 'approve-stock--warning';
  return 'approve-stock--ok';
});

const openApproveModal  = (req) => { activeRequest.value = req; approveForm.reset(); approveForm.clearErrors(); showApproveModal.value = true; };
const closeApproveModal = () => { showApproveModal.value = false; setTimeout(() => activeRequest.value = null, 300); };
const submitApprove     = () => {
  if (activeRequest.value) {
    approveForm.patch(route('transfer-requests.approve', activeRequest.value.id), { preserveScroll: true, onSuccess: closeApproveModal });
  }
};

// ─── Super Admin: Instant Reject ─────────────────────────────────────────────
const rejectReq = (req) => {
  if (confirm(`Reject request for ${req.quantity} ${req.product?.name}?`)) {
    router.patch(route('transfer-requests.reject', req.id), {}, { preserveScroll: true });
  }
};
</script>

<style scoped>
.page-wrap { padding: 2rem 1.25rem 5rem; max-width: 1100px; margin: 0 auto; }
.page-header { display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 1rem; gap: 1rem; flex-wrap: wrap; }
.page-eyebrow { font-size: 0.7rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: rgba(0,0,0,0.35); margin: 0 0 0.2rem; }
.page-title { font-size: 1.75rem; font-weight: 800; letter-spacing: -0.04em; color: rgba(0,0,0,0.85); margin: 0; line-height: 1; }

.info-banner { display: flex; align-items: flex-start; gap: 0.625rem; background: rgba(0,122,255,0.08); border: 1px solid rgba(0,122,255,0.18); border-radius: 0.875rem; padding: 0.875rem 1rem; margin-bottom: 1.25rem; font-size: 0.8125rem; color: rgba(0,70,200,0.85); line-height: 1.5; }
.info-banner svg { flex-shrink: 0; margin-top: 1px; }

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
.reason-text { font-size: 0.78rem; font-style: italic; }

.status-badge { display: inline-flex; align-items: center; padding: 0.2rem 0.6rem; border-radius: 999px; font-size: 0.62rem; font-weight: 700; letter-spacing: 0.05em; border: 1px solid; }
.status-badge--amber { background: rgba(255,149,0,0.10); color: #FF9500; border-color: rgba(255,149,0,0.22); }
.status-badge--green { background: rgba(52,199,89,0.10); color: #1a7a34; border-color: rgba(52,199,89,0.22); }
.status-badge--red   { background: rgba(255,59,48,0.10); color: #FF3B30; border-color: rgba(255,59,48,0.22); }

.cat-chip { display: inline-flex; align-items: center; padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.65rem; font-weight: 600; background: rgba(0,122,255,0.08); color: #007AFF; border: 1px solid rgba(0,122,255,0.15); }
.approve-info { display: flex; flex-wrap: wrap; gap: 0.375rem; padding: 0 1.5rem; }

.action-group { display: flex; gap: 0.4rem; justify-content: center; }
.action-btn { padding: 0.35rem 0.875rem; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 600; cursor: pointer; border: 1px solid; transition: all 0.15s; display: inline-flex; align-items: center; gap: 0.3rem; -webkit-tap-highlight-color: transparent; }
.action-btn:active { transform: scale(0.96); }
.action-btn--green { background: rgba(52,199,89,0.10); border-color: rgba(52,199,89,0.20); color: var(--ios-green); }
.action-btn--green:hover { background: rgba(52,199,89,0.18); }
.action-btn--red { background: rgba(255,59,48,0.10); border-color: rgba(255,59,48,0.20); color: #FF3B30; }
.action-btn--red:hover { background: rgba(255,59,48,0.18); }
.action-btn--full { flex: 1; justify-content: center; }
.by-label { font-size: 0.72rem; color: rgba(0,0,0,0.35); }

.route-row { display: flex; align-items: center; gap: 0.4rem; }
.route-chip { padding: 0.1rem 0.45rem; border-radius: 999px; font-size: 0.65rem; font-weight: 600; }
.route-chip--from { background: rgba(0,0,0,0.06); color: rgba(0,0,0,0.55); }
.route-chip--to { background: rgba(52,199,89,0.12); color: #1a7a34; }

.empty-cell { text-align: center; padding: 3rem 1rem; color: rgba(0,0,0,0.30); font-size: 0.875rem; }
.pagination { display: flex; justify-content: center; gap: 0.375rem; padding: 1rem 1.25rem; border-top: 1px solid rgba(0,0,0,0.05); flex-wrap: wrap; }
.page-btn { padding: 0.4rem 0.75rem; border-radius: 0.5rem; font-size: 0.8125rem; font-weight: 500; background: rgba(255,255,255,0.7); border: 1px solid rgba(0,0,0,0.08); color: rgba(0,0,0,0.60); text-decoration: none; transition: all 0.15s; }
.page-btn--active { background: #007AFF; border-color: #007AFF; color: white; font-weight: 700; }
.page-btn--disabled { color: rgba(0,0,0,0.25); background: transparent; border-color: transparent; }

.mobile-list { display: flex; flex-direction: column; gap: 0.75rem; }
.mobile-card { background: rgba(255,255,255,0.7); border: 1px solid rgba(255,255,255,0.85); border-radius: 1rem; padding: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.06), inset 0 1px 0 rgba(255,255,255,0.9); display: flex; flex-direction: column; gap: 0.5rem; }
.mobile-card__top { display: flex; align-items: flex-start; justify-content: space-between; gap: 0.5rem; }
.mobile-card__name { font-size: 1rem; font-weight: 700; color: rgba(0,0,0,0.82); letter-spacing: -0.02em; margin: 0.2rem 0 0; }
.detail-row { display: flex; align-items: center; justify-content: space-between; background: rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.05); border-radius: 0.625rem; padding: 0.625rem 0.75rem; }
.detail-label { font-size: 0.62rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(0,0,0,0.30); margin-bottom: 2px; }
.detail-val { font-size: 0.8125rem; font-style: italic; color: rgba(0,0,0,0.55); }
.detail-qty { text-align: right; }
.detail-qty-val { font-size: 1.25rem; font-weight: 800; font-family: var(--font-mono); letter-spacing: -0.03em; color: rgba(0,0,0,0.75); }
.mobile-card__actions { display: flex; gap: 0.5rem; padding-top: 0.375rem; border-top: 1px solid rgba(0,0,0,0.05); margin-top: 0.25rem; }

/* Modal shared styles */
.modal-body { padding: 1.5rem; }
.modal-header { display: flex; align-items: flex-start; gap: 0.875rem; margin-bottom: 1.5rem; }
.modal-header__icon { width: 36px; height: 36px; border-radius: 10px; background: rgba(0,122,255,0.12); color: #007AFF; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.modal-header__title { font-size: 1rem; font-weight: 700; color: rgba(0,0,0,0.85); margin: 0 0 0.2rem; }
.modal-header__sub { font-size: 0.78rem; color: rgba(0,0,0,0.45); margin: 0; }
.modal-close { margin-left: auto; background: rgba(0,0,0,0.06); border: none; border-radius: 8px; width: 28px; height: 28px; cursor: pointer; display: flex; align-items: center; justify-content: center; color: rgba(0,0,0,0.45); flex-shrink: 0; }
.modal-form { display: flex; flex-direction: column; gap: 1rem; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.field { display: flex; flex-direction: column; gap: 0.35rem; }
.span-2 { grid-column: span 2; }
.modal-actions { display: flex; justify-content: flex-end; gap: 0.75rem; padding-top: 0.5rem; border-top: 1px solid rgba(0,0,0,0.06); }

.input-ios { width: 100%; padding: 0.65rem 0.875rem; border-radius: 0.75rem; border: 1px solid rgba(0,0,0,0.12); background: rgba(255,255,255,0.8); font-size: 0.875rem; color: rgba(0,0,0,0.82); outline: none; transition: border-color 0.15s, box-shadow 0.15s; appearance: none; }
.input-ios:focus { border-color: #007AFF; box-shadow: 0 0 0 3px rgba(0,122,255,0.12); }
.input-ios--locked { background: rgba(0,0,0,0.04); color: rgba(0,0,0,0.55); border-color: rgba(0,0,0,0.08); cursor: not-allowed; font-weight: 600; }

@media (max-width: 640px) {
  .form-grid { grid-template-columns: 1fr; }
  .span-2 { grid-column: span 1; }
  .modal-body { padding: 1.25rem; }
}

/* ── Approve Stock Banner ─────────────────────────────────── */
.approve-stock {
  display: flex;
  align-items: center;
  gap: 0.7rem;
  padding: 0.8rem 0.9rem;
  border-radius: 0.875rem;
  border: 1px solid;
  transition: all 0.2s ease;
}

/* Danger */
.approve-stock--danger {
  background: linear-gradient(135deg, rgba(255,59,48,0.08) 0%, rgba(255,90,70,0.04) 100%);
  border-color: rgba(255,59,48,0.22);
}
.approve-stock--danger .approve-stock__icon { background: rgba(255,59,48,0.12); color: #FF3B30; }
.approve-stock--danger .approve-stock__title { color: #c0291f; }
.approve-stock--danger .approve-stock__msg { color: rgba(180,40,30,0.65); }
.approve-stock--danger .approve-stock__pill { background: rgba(255,59,48,0.10); border-color: rgba(255,59,48,0.20); }
.approve-stock--danger .approve-stock__qty  { color: #FF3B30; }

/* Warning */
.approve-stock--warning {
  background: linear-gradient(135deg, rgba(255,149,0,0.08) 0%, rgba(255,180,0,0.04) 100%);
  border-color: rgba(255,149,0,0.22);
}
.approve-stock--warning .approve-stock__icon { background: rgba(255,149,0,0.12); color: #FF9500; }
.approve-stock--warning .approve-stock__title { color: #9a5000; }
.approve-stock--warning .approve-stock__msg { color: rgba(140,80,0,0.65); }
.approve-stock--warning .approve-stock__pill { background: rgba(255,149,0,0.10); border-color: rgba(255,149,0,0.20); }
.approve-stock--warning .approve-stock__qty  { color: #FF9500; }

/* OK */
.approve-stock--ok {
  background: linear-gradient(135deg, rgba(52,199,89,0.07) 0%, rgba(48,176,100,0.03) 100%);
  border-color: rgba(52,199,89,0.20);
}
.approve-stock--ok .approve-stock__icon { background: rgba(52,199,89,0.12); color: #34C759; }
.approve-stock--ok .approve-stock__title { color: #176b2f; }
.approve-stock--ok .approve-stock__msg { color: rgba(20,90,40,0.60); }
.approve-stock--ok .approve-stock__pill { background: rgba(52,199,89,0.10); border-color: rgba(52,199,89,0.18); }
.approve-stock--ok .approve-stock__qty  { color: #28a745; }

/* Sub-elements */
.approve-stock__icon {
  flex-shrink: 0;
  width: 2.125rem;
  height: 2.125rem;
  border-radius: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
}
.approve-stock__body {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
  min-width: 0;
}
.approve-stock__title {
  font-size: 0.76rem;
  font-weight: 700;
  letter-spacing: -0.01em;
}
.approve-stock__msg {
  font-size: 0.68rem;
  line-height: 1.4;
}
.approve-stock__pill {
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-width: 3rem;
  padding: 0.35rem 0.5rem;
  border-radius: 0.5rem;
  border: 1px solid;
  text-align: center;
}
.approve-stock__qty {
  font-size: 1.125rem;
  font-weight: 800;
  font-family: var(--font-mono);
  letter-spacing: -0.04em;
  line-height: 1;
}
.approve-stock__unit {
  font-size: 0.5rem;
  font-weight: 600;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: rgba(0,0,0,0.30);
  margin-top: 2px;
}

/* Transition */
.stock-banner-enter-active { transition: all 0.28s cubic-bezier(0.34,1.56,0.64,1); }
.stock-banner-leave-active { transition: all 0.18s ease; }
.stock-banner-enter-from   { opacity: 0; transform: scaleY(0.8) translateY(-6px); }
.stock-banner-leave-to     { opacity: 0; transform: scaleY(0.9) translateY(-4px); }

/* Approve submit button */
.approve-submit-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.6rem 1.125rem;
  font-size: 0.8125rem;
  background: linear-gradient(180deg, #45d468 0%, #34C759 50%, #28a748 100%);
  color: white;
  box-shadow: 0 4px 14px rgba(52,199,89,0.38), inset 0 1px 0 rgba(255,255,255,0.28), inset 0 -1px 0 rgba(0,0,0,0.12);
  border: 1px solid rgba(30,160,60,0.4);
  transition: all 0.2s ease;
}
.approve-submit-btn:hover:not(:disabled) {
  background: linear-gradient(180deg, #52db72 0%, #3bd464 50%, #2eb850 100%);
  box-shadow: 0 6px 20px rgba(52,199,89,0.50), inset 0 1.5px 0 rgba(255,255,255,0.38);
}
.approve-submit-btn--disabled {
  opacity: 0.42;
  cursor: not-allowed;
}
</style>

