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
      <div class="so-modal">

        <!-- ── Decorative accent strip ── -->
        <div class="so-modal__accent" />

        <!-- ── Header ── -->
        <div class="so-modal__header">
          <div class="so-modal__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
              <path d="M5 12h14"/><path d="M15 7l5 5-5 5"/>
            </svg>
          </div>
          <div class="so-modal__heading">
            <h2 class="so-modal__title">Record Stock Out</h2>
            <p class="so-modal__sub">Deduct stock instantly from warehouse</p>
          </div>
          <button class="so-modal__close" @click="closeModal" aria-label="Close">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><path d="M18 6L6 18M6 6l12 12"/></svg>
          </button>
        </div>

        <!-- ── Form ── -->
        <form @submit.prevent="submitStockOut" class="so-modal__form">

          <!-- Section: Location & Product -->
          <div class="so-section">
            <div class="so-section__label">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="11" height="11"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/></svg>
              Location &amp; Product
            </div>
            <div class="so-fields">
              <!-- Warehouse -->
              <div class="so-field">
                <label class="so-field__label" for="so_warehouse">Warehouse</label>
                <div v-if="!isSuperAdmin" class="input-ios input-ios--locked">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13" style="display:inline;margin-right:6px;opacity:.4"><path d="M12 22s-8-4.5-8-11.8A8 8 0 0112 2a8 8 0 018 8.2c0 7.3-8 11.8-8 11.8z"/><circle cx="12" cy="10" r="3"/></svg>
                  {{ myWarehouse?.name ?? 'Your Warehouse' }}
                </div>
                <select v-else id="so_warehouse" v-model="form.warehouse_id" class="input-ios" required>
                  <option disabled value="">Select warehouse…</option>
                  <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
                </select>
                <InputError :message="form.errors.warehouse_id" />
              </div>
              <!-- Product -->
              <div class="so-field">
                <label class="so-field__label" for="so_product">Product</label>
                <select id="so_product" v-model="form.product_id" class="input-ios" required>
                  <option disabled value="">Select product…</option>
                  <option v-for="p in products" :key="p.id" :value="p.id">{{ p.sku }} — {{ p.name }}</option>
                </select>
                <InputError :message="form.errors.product_id" />
              </div>
            </div>
          </div>

          <!-- ── Stock Status Banner ── -->
          <Transition name="stock-banner">
            <div v-if="form.warehouse_id && form.product_id" class="so-stock" :class="stockBannerClass">
              <!-- Left: icon -->
              <div class="so-stock__icon">
                <!-- Danger -->
                <svg v-if="currentStock === 0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <!-- Warning -->
                <svg v-else-if="form.quantity > currentStock" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <!-- OK -->
                <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><polyline points="20 6 9 17 4 12"/></svg>
              </div>
              <!-- Middle: text -->
              <div class="so-stock__body">
                <span class="so-stock__title">
                  <template v-if="currentStock === 0">Stok Tidak Tersedia</template>
                  <template v-else-if="form.quantity > currentStock">Stok Tidak Mencukupi</template>
                  <template v-else>Stok Tersedia</template>
                </span>
                <span class="so-stock__msg">
                  <template v-if="currentStock === 0">Produk ini tidak memiliki stok di cabang ini.</template>
                  <template v-else>Sisa stok saat ini di cabang ini.</template>
                </span>
              </div>
              <!-- Right: qty pill -->
              <div class="so-stock__pill">
                <span class="so-stock__qty">{{ currentStock ?? 0 }}</span>
                <span class="so-stock__unit">unit</span>
              </div>
            </div>
          </Transition>

          <!-- Section: Deduction Details -->
          <div class="so-section">
            <div class="so-section__label">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="11" height="11"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
              Deduction Details
            </div>
            <div class="so-fields so-fields--2col">
              <!-- Quantity -->
              <div class="so-field">
                <label class="so-field__label" for="so_qty">Quantity</label>
                <TextInput id="so_qty" v-model="form.quantity" type="number" min="1" required />
                <InputError :message="form.errors.quantity" />
              </div>
              <!-- Category -->
              <div class="so-field">
                <label class="so-field__label" for="so_cat">Category</label>
                <select id="so_cat" v-model="form.category" class="input-ios" required>
                  <option value="sales">Penjualan</option>
                  <option value="internal_use">Pemakaian Internal</option>
                  <option value="damaged">Rusak / Cacat</option>
                  <option value="expired">Kedaluwarsa</option>
                  <option value="adjustment">Stok Opname</option>
                </select>
                <InputError :message="form.errors.category" />
              </div>
              <!-- Reason -->
              <div class="so-field so-field--full">
                <label class="so-field__label" for="so_reason">Reason / Notes <span class="so-field__opt">(optional)</span></label>
                <TextInput id="so_reason" v-model="form.reason" type="text" placeholder="e.g. Sold to customer A" />
                <InputError :message="form.errors.reason" />
              </div>
            </div>
          </div>

          <!-- ── Actions ── -->
          <div class="so-modal__actions">
            <button type="button" @click="closeModal" class="btn-ios btn-ios-glass so-btn-cancel">Cancel</button>
            <button
              type="submit"
              class="btn-ios so-btn-submit"
              :class="{ 'so-btn-submit--disabled': form.processing || currentStock === 0 }"
              :disabled="form.processing || currentStock === 0"
            >
              <svg v-if="form.processing" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14" class="so-spin"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg>
              <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M5 12h14"/><path d="M15 7l5 5-5 5"/></svg>
              {{ form.processing ? 'Processing…' : 'Record Stock Out' }}
            </button>
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

const props = defineProps({ stockOuts: Object, warehouses: Array, products: Array, stocks: Array });
const page  = usePage();
const user  = computed(() => page.props.auth.user);
const isSuperAdmin = computed(() => user.value.role === 'super_admin');
const myWarehouse  = computed(() => props.warehouses?.find(w => w.id === user.value.warehouse_id));

const categoryLabels = { sales: 'Penjualan', internal_use: 'Pemakaian', damaged: 'Rusak', expired: 'Kedaluwarsa', adjustment: 'Stok Opname' };
const formatDate = (d) => new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });

// ─── Record Stock Out Modal ───────────────────────────────────────────────────
const showModal = ref(false);
const form = useForm({ warehouse_id: '', product_id: '', quantity: 1, category: 'sales', reason: '' });

// Current available stock for the selected warehouse + product combination
const currentStock = computed(() => {
  if (!form.warehouse_id || !form.product_id) return null;
  const entry = props.stocks?.find(
    s => s.warehouse_id === form.warehouse_id && s.product_id === form.product_id
  );
  return entry ? entry.quantity : 0;
});

const stockBannerClass = computed(() => {
  if (currentStock.value === 0)                       return 'so-stock--danger';
  if (form.quantity > currentStock.value)             return 'so-stock--warning';
  return 'so-stock--ok';
});

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

/* ────────────────────────────────────────────────────────────
   MODAL — Record Stock Out
──────────────────────────────────────────────────────────── */
.so-modal {
  position: relative;
  overflow: hidden;
}

/* Red gradient accent strip at top */
.so-modal__accent {
  height: 4px;
  background: linear-gradient(90deg, #FF3B30 0%, #FF6B6B 50%, #FF9500 100%);
  width: 100%;
}

/* Header */
.so-modal__header {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  padding: 1.25rem 1.5rem 1rem;
  border-bottom: 1px solid rgba(0,0,0,0.055);
}

.so-modal__icon {
  flex-shrink: 0;
  width: 2.625rem;
  height: 2.625rem;
  background: linear-gradient(135deg, rgba(255,59,48,0.14) 0%, rgba(255,100,70,0.08) 100%);
  border: 1px solid rgba(255,59,48,0.20);
  border-radius: 0.875rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #FF3B30;
  box-shadow: 0 2px 8px rgba(255,59,48,0.12);
}

.so-modal__heading { flex: 1; min-width: 0; }
.so-modal__title {
  font-size: 1rem;
  font-weight: 700;
  color: rgba(0,0,0,0.84);
  letter-spacing: -0.025em;
  margin: 0 0 0.1rem;
}
.so-modal__sub {
  font-size: 0.72rem;
  color: rgba(0,0,0,0.38);
  margin: 0;
}

.so-modal__close {
  flex-shrink: 0;
  width: 1.875rem;
  height: 1.875rem;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0,0,0,0.055);
  border: none;
  border-radius: 50%;
  color: rgba(0,0,0,0.40);
  cursor: pointer;
  transition: background 0.15s, color 0.15s;
}
.so-modal__close:hover { background: rgba(0,0,0,0.10); color: rgba(0,0,0,0.65); }

/* Form */
.so-modal__form {
  display: flex;
  flex-direction: column;
  gap: 0;
  padding: 0 0 1.5rem;
}

/* Section */
.so-section {
  padding: 1.125rem 1.5rem 0.5rem;
}

.so-section__label {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.62rem;
  font-weight: 800;
  letter-spacing: 0.085em;
  text-transform: uppercase;
  color: rgba(0,0,0,0.30);
  margin-bottom: 0.75rem;
}

/* Field grid */
.so-fields {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
}

.so-fields--2col { grid-template-columns: 1fr 1fr; }

.so-field { display: flex; flex-direction: column; gap: 0.3rem; }
.so-field--full { grid-column: 1 / -1; }

.so-field__label {
  font-size: 0.7rem;
  font-weight: 600;
  color: rgba(0,0,0,0.45);
  letter-spacing: 0.03em;
}
.so-field__opt { font-weight: 400; color: rgba(0,0,0,0.28); }

/* Locked input */
.input-ios--locked {
  background: rgba(0,0,0,0.03);
  border: 1px solid rgba(0,0,0,0.07);
  color: rgba(0,0,0,0.55);
  font-size: 0.875rem;
  cursor: default;
}

/* ── Stock Status Banner ── */
.so-stock {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin: 0.5rem 1.5rem;
  padding: 0.875rem 1rem;
  border-radius: 0.875rem;
  border: 1px solid;
  transition: all 0.25s ease;
}

/* Danger */
.so-stock--danger {
  background: linear-gradient(135deg, rgba(255,59,48,0.08) 0%, rgba(255,90,70,0.04) 100%);
  border-color: rgba(255,59,48,0.22);
}
.so-stock--danger .so-stock__icon { background: rgba(255,59,48,0.12); color: #FF3B30; }
.so-stock--danger .so-stock__title { color: #c0291f; }
.so-stock--danger .so-stock__msg { color: rgba(180,40,30,0.65); }
.so-stock--danger .so-stock__pill { background: rgba(255,59,48,0.10); border-color: rgba(255,59,48,0.20); }
.so-stock--danger .so-stock__qty { color: #FF3B30; }

/* Warning */
.so-stock--warning {
  background: linear-gradient(135deg, rgba(255,149,0,0.08) 0%, rgba(255,180,0,0.04) 100%);
  border-color: rgba(255,149,0,0.22);
}
.so-stock--warning .so-stock__icon { background: rgba(255,149,0,0.12); color: #FF9500; }
.so-stock--warning .so-stock__title { color: #9a5000; }
.so-stock--warning .so-stock__msg { color: rgba(140,80,0,0.65); }
.so-stock--warning .so-stock__pill { background: rgba(255,149,0,0.10); border-color: rgba(255,149,0,0.20); }
.so-stock--warning .so-stock__qty { color: #FF9500; }

/* OK */
.so-stock--ok {
  background: linear-gradient(135deg, rgba(52,199,89,0.07) 0%, rgba(48,176,100,0.03) 100%);
  border-color: rgba(52,199,89,0.20);
}
.so-stock--ok .so-stock__icon { background: rgba(52,199,89,0.12); color: #34C759; }
.so-stock--ok .so-stock__title { color: #176b2f; }
.so-stock--ok .so-stock__msg { color: rgba(20,90,40,0.60); }
.so-stock--ok .so-stock__pill { background: rgba(52,199,89,0.10); border-color: rgba(52,199,89,0.18); }
.so-stock--ok .so-stock__qty { color: #28a745; }

/* Banner sub-elements */
.so-stock__icon {
  flex-shrink: 0;
  width: 2.25rem;
  height: 2.25rem;
  border-radius: 0.625rem;
  display: flex;
  align-items: center;
  justify-content: center;
}
.so-stock__body {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
  min-width: 0;
}
.so-stock__title {
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: -0.01em;
}
.so-stock__msg {
  font-size: 0.68rem;
  line-height: 1.35;
}
.so-stock__pill {
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-width: 3.25rem;
  padding: 0.4rem 0.6rem;
  border-radius: 0.625rem;
  border: 1px solid;
  text-align: center;
}
.so-stock__qty {
  font-size: 1.25rem;
  font-weight: 800;
  font-family: var(--font-mono);
  letter-spacing: -0.04em;
  line-height: 1;
}
.so-stock__unit {
  font-size: 0.55rem;
  font-weight: 600;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: rgba(0,0,0,0.30);
  margin-top: 2px;
}

/* Banner transition */
.stock-banner-enter-active { transition: all 0.28s cubic-bezier(0.34,1.56,0.64,1); }
.stock-banner-leave-active { transition: all 0.18s ease; }
.stock-banner-enter-from { opacity: 0; transform: scaleY(0.8) translateY(-6px); }
.stock-banner-leave-to  { opacity: 0; transform: scaleY(0.9) translateY(-4px); }

/* Actions */
.so-modal__actions {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 0.625rem;
  padding: 1rem 1.5rem 0;
  border-top: 1px solid rgba(0,0,0,0.055);
  margin-top: 0.875rem;
}

.so-btn-cancel { padding: 0.6rem 1.125rem; font-size: 0.8125rem; }

.so-btn-submit {
  padding: 0.6rem 1.25rem;
  font-size: 0.8125rem;
  background: linear-gradient(180deg, #ff5547 0%, #FF3B30 50%, #e02218 100%);
  color: white;
  box-shadow: 0 4px 16px rgba(255,59,48,0.38), inset 0 1px 0 rgba(255,255,255,0.30), inset 0 -1px 0 rgba(0,0,0,0.15);
  border: 1px solid rgba(200,30,20,0.35);
  transition: all 0.2s ease;
}
.so-btn-submit:hover:not(:disabled) {
  background: linear-gradient(180deg, #ff6357 0%, #ff4535 50%, #e82d1f 100%);
  box-shadow: 0 6px 22px rgba(255,59,48,0.50), inset 0 1.5px 0 rgba(255,255,255,0.40), inset 0 -1px 0 rgba(0,0,0,0.15);
}
.so-btn-submit--disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

/* Spinner animation */
@keyframes so-spin { to { transform: rotate(360deg); } }
.so-spin { animation: so-spin 0.8s linear infinite; }</style>
