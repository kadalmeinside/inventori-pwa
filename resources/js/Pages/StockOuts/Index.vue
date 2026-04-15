<template>
  <AppLayout title="Stock Out">
    <div class="page-wrap">

      <div class="page-header">
        <div>
          <p class="page-eyebrow">Warehouse Operations</p>
          <h1 class="page-title">Stock Out</h1>
        </div>
        <button class="btn-ios btn-ios-primary" @click="openModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M12 5v14M5 12h14"/></svg>
          Record Stock Out
        </button>
      </div>

      <div class="glass-card">
        <!-- Desktop -->
        <div class="table-wrap hidden md:block">
          <table class="ios-table">
            <thead>
              <tr>
                <th>Date / By</th>
                <th>Item & Location</th>
                <th>Category / Reason</th>
                <th class="text-right">Qty</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="req in stockOuts.data" :key="req.id" class="table-row">
                <td>
                  <div class="td-name" style="font-weight:500;">{{ formatDate(req.created_at) }}</div>
                  <div class="td-muted" style="font-size:0.75rem; margin-top:2px;">{{ req.requester?.name }}</div>
                </td>
                <td>
                  <div class="td-name">{{ req.product?.name }}</div>
                  <div class="td-muted" style="font-size:0.75rem; margin-top:2px;">{{ req.warehouse?.name }}</div>
                </td>
                <td style="max-width: 200px;">
                  <span v-if="req.category" class="cat-chip">{{ categoryLabels[req.category] || req.category }}</span>
                  <div class="td-muted reason-text">{{ req.reason || '—' }}</div>
                </td>
                <td class="text-right">
                  <span class="td-name font-mono">{{ req.quantity }}</span>
                  <span class="td-muted" style="font-size:0.75rem; margin-left:3px;">{{ req.product?.unit }}</span>
                </td>
              </tr>
              <tr v-if="stockOuts.data.length === 0"><td colspan="4" class="empty-cell">No stock out records found.</td></tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile Cards -->
        <div class="block md:hidden p-4">
          <div class="mobile-list">
            <div v-for="req in stockOuts.data" :key="'m-'+req.id" class="mobile-card">
              <div class="mobile-card__top">
                <div>
                  <div class="td-muted" style="font-size:0.72rem;">{{ formatDate(req.created_at) }} · {{ req.requester?.name }}</div>
                  <h3 class="mobile-card__name">{{ req.product?.name }}</h3>
                </div>
                <span v-if="req.category" class="cat-chip">{{ categoryLabels[req.category] }}</span>
              </div>

              <div class="mobile-card__meta">
                <span class="meta-chip">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="10" height="10"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/></svg>
                  {{ req.warehouse?.name }}
                </span>
              </div>

              <div class="detail-row">
                <div>
                  <div class="detail-label">Reason</div>
                  <div class="detail-val">{{ req.reason || '—' }}</div>
                </div>
                <div class="detail-qty">
                  <div class="detail-label">Qty</div>
                  <div class="detail-qty-val">{{ req.quantity }} <span style="font-size:0.65rem;">{{ req.product?.unit }}</span></div>
                </div>
              </div>
            </div>
            <div v-if="stockOuts.data.length === 0" class="empty-cell">No stock out records found.</div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="stockOuts.links.length > 3" class="pagination">
          <template v-for="(link, i) in stockOuts.links" :key="i">
            <span v-if="link.url === null" class="page-btn page-btn--disabled" v-html="link.label" />
            <Link v-else :href="link.url" class="page-btn" :class="link.active ? 'page-btn--active' : ''" v-html="link.label" />
          </template>
        </div>
      </div>
    </div>

    <!-- ─── Record Stock Out Modal ─────────────────────────────────────── -->
    <Modal :show="showModal" @close="closeModal" maxWidth="md">
      <div class="modal-body">
        <div class="modal-header">
          <div class="modal-header__icon" style="background:rgba(255,59,48,0.12);color:#FF3B30;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M5 12h14"/></svg>
          </div>
          <div>
            <h2 class="modal-header__title">Record Stock Out</h2>
            <p class="modal-header__sub">Deduct stock instantly from warehouse</p>
          </div>
          <button class="modal-close" @click="closeModal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M18 6L6 18M6 6l12 12"/></svg>
          </button>
        </div>
        <form @submit.prevent="submitStockOut" class="modal-form">
          <div class="form-grid">
            <!-- Warehouse -->
            <div class="field span-2">
              <InputLabel for="so_warehouse" value="Warehouse" />
              <div v-if="!isSuperAdmin" class="input-ios input-ios--locked">
                {{ myWarehouse?.name ?? 'Your Warehouse' }}
              </div>
              <select v-else id="so_warehouse" v-model="form.warehouse_id" class="input-ios" required>
                <option disabled value="">Select warehouse…</option>
                <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
              </select>
              <InputError :message="form.errors.warehouse_id" />
            </div>
            <!-- Product -->
            <div class="field span-2">
              <InputLabel for="so_product" value="Product" />
              <select id="so_product" v-model="form.product_id" class="input-ios" required>
                <option disabled value="">Select product…</option>
                <option v-for="p in products" :key="p.id" :value="p.id">{{ p.sku }} — {{ p.name }}</option>
              </select>
              <InputError :message="form.errors.product_id" />
            </div>
            <!-- Quantity -->
            <div class="field">
              <InputLabel for="so_qty" value="Quantity" />
              <TextInput id="so_qty" v-model="form.quantity" type="number" min="1" required />
              <InputError :message="form.errors.quantity" />
            </div>
            <!-- Category -->
            <div class="field">
              <InputLabel for="so_cat" value="Category" />
              <select id="so_cat" v-model="form.category" class="input-ios" required>
                <option value="sales">Penjualan</option>
                <option value="internal_use">Pemakaian Internal</option>
                <option value="damaged">Rusak</option>
                <option value="expired">Kedaluwarsa</option>
                <option value="adjustment">Stok Opname</option>
              </select>
              <InputError :message="form.errors.category" />
            </div>
            <!-- Reason -->
            <div class="field span-2">
              <InputLabel for="so_reason" value="Reason / Notes (optional)" />
              <TextInput id="so_reason" v-model="form.reason" type="text" placeholder="e.g. Sold to customer A" />
              <InputError :message="form.errors.reason" />
            </div>
          </div>
          <div class="modal-actions">
            <button type="button" @click="closeModal" class="btn-ios btn-ios-glass">Cancel</button>
            <PrimaryButton style="background:#FF3B30;box-shadow:0 4px 12px rgba(255,59,48,0.3);" :class="{ 'opacity-50': form.processing }" :disabled="form.processing">
              Record Stock Out
            </PrimaryButton>
          </div>
        </form>
      </div>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import AppLayout    from '@/Layouts/AppLayout.vue';
import Modal        from '@/Components/Modal.vue';
import InputLabel   from '@/Components/InputLabel.vue';
import InputError   from '@/Components/InputError.vue';
import TextInput    from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({ stockOuts: Object, warehouses: Array, products: Array });
const page  = usePage();
const user  = computed(() => page.props.auth.user);
const isSuperAdmin = computed(() => user.value.role === 'super_admin');
const myWarehouse  = computed(() => props.warehouses?.find(w => w.id === user.value.warehouse_id));

const categoryLabels = { sales: 'Penjualan', internal_use: 'Pemakaian', damaged: 'Rusak', expired: 'Kedaluwarsa', adjustment: 'Stok Opname' };
const formatDate = (d) => new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });

// ─── Record Stock Out Modal ───────────────────────────────────────────────────
const showModal = ref(false);
const form = useForm({ warehouse_id: '', product_id: '', quantity: 1, category: 'sales', reason: '' });

const openModal = () => {
  form.reset();
  form.clearErrors();
  // Auto-fill warehouse for Branch Admin
  if (!isSuperAdmin.value) form.warehouse_id = user.value.warehouse_id;
  showModal.value = true;
};

const closeModal = () => { showModal.value = false; form.reset(); };
const submitStockOut = () => form.post(route('stock-outs.store'), { preserveScroll: true, onSuccess: closeModal });
</script>

<style scoped>
.page-wrap { padding: 2rem 1.25rem 5rem; max-width: 1100px; margin: 0 auto; }
.page-header { display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 1.5rem; gap: 1rem; }
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
.reason-text { font-size: 0.78rem; font-style: italic; margin-top: 2px; }

.status-badge { display: inline-flex; align-items: center; padding: 0.2rem 0.6rem; border-radius: 999px; font-size: 0.62rem; font-weight: 700; letter-spacing: 0.05em; border: 1px solid; }
.status-badge--blue  { background: rgba(0,122,255,0.10); color: #007AFF; border-color: rgba(0,122,255,0.22); }
.status-badge--green { background: rgba(52,199,89,0.10); color: #1a7a34; border-color: rgba(52,199,89,0.22); }
.status-badge--red   { background: rgba(255,59,48,0.10); color: #FF3B30; border-color: rgba(255,59,48,0.22); }

.cat-chip { display: inline-flex; align-items: center; padding: 0.1rem 0.45rem; border-radius: 999px; font-size: 0.6rem; font-weight: 700; letter-spacing: 0.04em; background: rgba(0,122,255,0.08); color: #007AFF; border: 1px solid rgba(0,122,255,0.15); margin-bottom: 3px; }

.action-group { display: flex; gap: 0.4rem; justify-content: center; }
.action-btn { padding: 0.35rem 0.875rem; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 600; cursor: pointer; border: 1px solid; transition: all 0.15s; display: inline-flex; align-items: center; gap: 0.3rem; -webkit-tap-highlight-color: transparent; }
.action-btn:active { transform: scale(0.96); }
.action-btn--green { background: rgba(52,199,89,0.10); border-color: rgba(52,199,89,0.20); color: var(--ios-green); }
.action-btn--green:hover { background: rgba(52,199,89,0.18); }
.action-btn--red { background: rgba(255,59,48,0.10); border-color: rgba(255,59,48,0.20); color: #FF3B30; }
.action-btn--red:hover { background: rgba(255,59,48,0.18); }
.action-btn--full { flex: 1; justify-content: center; }
.by-label { font-size: 0.72rem; color: rgba(0,0,0,0.35); }

.empty-cell { text-align: center; padding: 3rem 1rem; color: rgba(0,0,0,0.30); font-size: 0.875rem; }
.pagination { display: flex; justify-content: center; gap: 0.375rem; padding: 1rem 1.25rem; border-top: 1px solid rgba(0,0,0,0.05); flex-wrap: wrap; }
.page-btn { padding: 0.4rem 0.75rem; border-radius: 0.5rem; font-size: 0.8125rem; font-weight: 500; background: rgba(255,255,255,0.7); border: 1px solid rgba(0,0,0,0.08); color: rgba(0,0,0,0.60); text-decoration: none; transition: all 0.15s; }
.page-btn--active { background: #007AFF; border-color: #007AFF; color: white; font-weight: 700; }
.page-btn--disabled { color: rgba(0,0,0,0.25); background: transparent; border-color: transparent; }

.mobile-list { display: flex; flex-direction: column; gap: 0.75rem; }
.mobile-card { background: rgba(255,255,255,0.7); border: 1px solid rgba(255,255,255,0.85); border-radius: 1rem; padding: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.06), inset 0 1px 0 rgba(255,255,255,0.9); display: flex; flex-direction: column; gap: 0.5rem; }
.mobile-card__top { display: flex; align-items: flex-start; justify-content: space-between; gap: 0.5rem; }
.mobile-card__name { font-size: 1rem; font-weight: 700; color: rgba(0,0,0,0.82); letter-spacing: -0.02em; margin: 0.2rem 0 0; }
.mobile-card__meta { display: flex; gap: 0.4rem; flex-wrap: wrap; }
.meta-chip { display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.72rem; color: rgba(0,0,0,0.45); background: rgba(0,0,0,0.05); padding: 0.2rem 0.5rem; border-radius: 999px; }
.detail-row { display: flex; align-items: center; justify-content: space-between; background: rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.05); border-radius: 0.625rem; padding: 0.625rem 0.75rem; }
.detail-label { font-size: 0.62rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(0,0,0,0.30); margin-bottom: 2px; }
.detail-val { font-size: 0.8125rem; font-style: italic; color: rgba(0,0,0,0.55); }
.detail-qty { text-align: right; }
.detail-qty-val { font-size: 1.25rem; font-weight: 800; font-family: var(--font-mono); letter-spacing: -0.03em; color: rgba(0,0,0,0.75); }
.mobile-card__actions { display: flex; gap: 0.5rem; padding-top: 0.375rem; border-top: 1px solid rgba(0,0,0,0.05); margin-top: 0.25rem; }
</style>
