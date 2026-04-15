<template>
  <AppLayout title="Product Management">
    <div class="page-wrap">

      <!-- ─── Page Header ─────────────────────────────────────────────── -->
      <div class="page-header">
        <div>
          <p class="page-header__eyebrow">Management</p>
          <h1 class="page-header__title">Products</h1>
        </div>
        <button class="btn-ios btn-ios-primary" @click="openModal()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="15" height="15">
            <path d="M12 5v14M5 12h14"/>
          </svg>
          Add Product
        </button>
      </div>

      <!-- ─── Glass Table Card ───────────────────────────────────────── -->
      <div class="glass-card">

        <!-- Desktop Table -->
        <div class="table-wrap hidden md:block">
          <table class="ios-table">
            <thead>
              <tr>
                <th>Status</th>
                <th>Product Name</th>
                <th>SKU</th>
                <th>Category</th>
                <th>Min. Stock</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="product in products.data" :key="product.id" class="table-row">
                <td>
                  <span class="badge" :class="product.is_active ? 'badge--green' : 'badge--gray'">
                    {{ product.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td class="td-name">{{ product.name }}</td>
                <td class="td-mono">{{ product.sku }}</td>
                <td class="td-muted">{{ product.category?.name || '—' }}</td>
                <td>
                  <span class="td-mono td-white">{{ product.min_stock }}</span>
                  <span class="td-unit"> {{ product.unit }}</span>
                </td>
                <td class="text-center">
                  <div class="action-group">
                    <button class="action-btn action-btn--edit" @click="openModal(product)">Edit</button>
                    <button class="action-btn action-btn--danger" @click="deleteProduct(product)">Disable</button>
                  </div>
                </td>
              </tr>
              <tr v-if="products.data.length === 0">
                <td colspan="6" class="empty-cell">No products found.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile Card List -->
        <div class="block md:hidden p-4">
          <div class="mobile-list">
            <div
              v-for="product in products.data"
              :key="'m-' + product.id"
              class="mobile-card"
            >
              <!-- Status + SKU -->
              <div class="mobile-card__top">
                <span class="td-mono" style="font-size:0.68rem; color:rgba(0,0,0,0.35);">{{ product.sku }}</span>
                <span class="badge" :class="product.is_active ? 'badge--green' : 'badge--gray'">
                  {{ product.is_active ? 'Active' : 'Inactive' }}
                </span>
              </div>

              <!-- Name -->
              <h3 class="mobile-card__name">{{ product.name }}</h3>

              <!-- Meta row -->
              <div class="mobile-card__meta">
                <span class="meta-chip">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="10" height="10"><path d="M20 7H4a2 2 0 00-2 2v9a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/></svg>
                  {{ product.category?.name || '—' }}
                </span>
                <span class="meta-chip">
                  Min: <strong>{{ product.min_stock }}</strong> {{ product.unit }}
                </span>
              </div>

              <!-- Actions -->
              <div class="mobile-card__actions">
                <button class="action-btn action-btn--edit action-btn--full" @click="openModal(product)">Edit</button>
                <button class="action-btn action-btn--danger action-btn--full" @click="deleteProduct(product)">Disable</button>
              </div>
            </div>

            <div v-if="products.data.length === 0" class="empty-cell">No products found.</div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="products.links.length > 3" class="pagination">
          <template v-for="(link, i) in products.links" :key="i">
            <span v-if="link.url === null" class="page-btn page-btn--disabled" v-html="link.label" />
            <Link
              v-else
              :href="link.url"
              class="page-btn"
              :class="link.active ? 'page-btn--active' : ''"
              v-html="link.label"
            />
          </template>
        </div>

      </div>
    </div>

    <!-- ─── Product Modal ──────────────────────────────────────────────── -->
    <Modal :show="showModal" @close="closeModal" maxWidth="md">
      <div class="modal-body">
        <!-- Modal header -->
        <div class="modal-header">
          <div class="modal-header__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
              <path d="M20 7H4a2 2 0 00-2 2v9a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/>
              <path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/>
            </svg>
          </div>
          <div>
            <h2 class="modal-header__title">{{ isEditing ? 'Edit Product' : 'New Product' }}</h2>
            <p class="modal-header__sub">{{ isEditing ? 'Update product details' : 'Add a new product to the catalog' }}</p>
          </div>
          <button class="modal-close" type="button" @click="closeModal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M18 6L6 18M6 6l12 12"/></svg>
          </button>
        </div>

        <!-- Form -->
        <form @submit.prevent="submitForm" class="modal-form">
          <div class="form-grid">

            <!-- Name -->
            <div class="field span-2">
              <InputLabel for="name" value="Product Name" />
              <TextInput id="name" v-model="form.name" type="text" required placeholder="e.g. Laptop Stand Pro" />
              <InputError :message="form.errors.name" />
            </div>

            <!-- SKU -->
            <div class="field">
              <InputLabel for="sku" value="SKU" />
              <TextInput id="sku" v-model="form.sku" type="text" required placeholder="e.g. LAP-001" style="font-family: var(--font-mono); text-transform: uppercase;" />
              <InputError :message="form.errors.sku" />
            </div>

            <!-- Category -->
            <div class="field">
              <InputLabel for="category_id" value="Category" />
              <select id="category_id" v-model="form.category_id" required class="input-ios">
                <option value="" disabled>Select a Category...</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
              </select>
              <InputError :message="form.errors.category_id" />
            </div>

            <!-- Unit -->
            <div class="field">
              <InputLabel for="unit" value="Unit" />
              <select id="unit" v-model="form.unit" required class="input-ios">
                <option value="pcs">Pieces (pcs)</option>
                <option value="box">Box (box)</option>
                <option value="kg">Kilograms (kg)</option>
                <option value="liter">Liter (L)</option>
              </select>
              <InputError :message="form.errors.unit" />
            </div>

            <!-- Min stock -->
            <div class="field">
              <InputLabel for="min_stock" value="Min. Stock Alert" />
              <TextInput id="min_stock" v-model="form.min_stock" type="number" min="0" required placeholder="0" />
              <InputError :message="form.errors.min_stock" />
            </div>

            <!-- Description -->
            <div class="field span-2">
              <InputLabel for="description" value="Description / Notes" />
              <textarea
                id="description"
                v-model="form.description"
                class="input-ios"
                rows="3"
                placeholder="Optional notes about this product…"
              />
              <InputError :message="form.errors.description" />
            </div>

            <!-- Active toggle -->
            <div class="field span-2">
              <label class="toggle-row">
                <input id="is_active" v-model="form.is_active" type="checkbox" class="toggle-check" />
                <span class="toggle-track">
                  <span class="toggle-thumb" />
                </span>
                <span class="toggle-label">Active — visible to stock transactions</span>
              </label>
              <InputError :message="form.errors.is_active" />
            </div>

          </div>

          <!-- Footer actions -->
          <div class="modal-actions">
            <button type="button" @click="closeModal" class="btn-ios btn-ios-glass">Cancel</button>
            <PrimaryButton :class="{ 'opacity-50': form.processing }" :disabled="form.processing">
              {{ isEditing ? 'Save Changes' : 'Create Product' }}
            </PrimaryButton>
          </div>
        </form>
      </div>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, useForm, Link } from '@inertiajs/vue3';
import AppLayout      from '@/Layouts/AppLayout.vue';
import Modal          from '@/Components/Modal.vue';
import TextInput      from '@/Components/TextInput.vue';
import InputLabel     from '@/Components/InputLabel.vue';
import InputError     from '@/Components/InputError.vue';
import PrimaryButton  from '@/Components/PrimaryButton.vue';

defineProps({ products: Object, categories: Array });

const showModal  = ref(false);
const isEditing  = ref(false);
const editingId  = ref(null);

const form = useForm({
  name: '', sku: '', category_id: '', unit: 'pcs',
  min_stock: 0, description: '', is_active: true,
});

const openModal = (product = null) => {
  form.clearErrors();
  if (product) {
    isEditing.value = true;
    editingId.value  = product.id;
    form.name        = product.name;
    form.sku         = product.sku;
    form.category_id = product.category_id || '';
    form.unit        = product.unit;
    form.min_stock   = product.min_stock;
    form.description = product.description || '';
    form.is_active   = !!product.is_active;
  } else {
    isEditing.value = false;
    editingId.value  = null;
    form.reset();
  }
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  setTimeout(() => form.reset(), 300);
};

const submitForm = () => {
  const opts = { preserveScroll: true, onSuccess: closeModal };
  isEditing.value
    ? form.put(route('products.update', editingId.value), opts)
    : form.post(route('products.store'), opts);
};

const deleteProduct = (product) => {
  if (confirm(`Deactivate "${product.name}" (${product.sku})? It won't be available for stock movements.`)) {
    router.delete(route('products.destroy', product.id), { preserveScroll: true });
  }
};
</script>

<style scoped>
/* ─── Page ───────────────────────────────────────────────────────────────── */
.page-wrap {
  padding: 2rem 1.25rem 5rem;
  max-width: 1100px;
  margin: 0 auto;
}

/* ─── Page Header ────────────────────────────────────────────────────────── */
.page-header {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  margin-bottom: 1.5rem;
  gap: 1rem;
  flex-wrap: wrap;
}

.page-header__eyebrow {
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(0, 0, 0, 0.35);
  margin: 0 0 0.2rem;
}

.page-header__title {
  font-size: 1.75rem;
  font-weight: 800;
  letter-spacing: -0.04em;
  color: rgba(0, 0, 0, 0.85);
  margin: 0;
  line-height: 1;
}

/* ─── Glass card wrapper ─────────────────────────────────────────────────── */
.glass-card {
  background: rgba(255, 255, 255, 0.62);
  backdrop-filter: blur(28px) saturate(180%);
  -webkit-backdrop-filter: blur(28px) saturate(180%);
  border: 1px solid rgba(255, 255, 255, 0.82);
  border-radius: 1.5rem;
  box-shadow:
    0 8px 40px rgba(0, 80, 200, 0.10),
    0 2px 8px rgba(0, 0, 0, 0.05),
    inset 0 1px 0 rgba(255, 255, 255, 0.95);
  overflow: hidden;
}

/* ─── Table ──────────────────────────────────────────────────────────────── */
.table-wrap { overflow-x: auto; }

.ios-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.ios-table thead tr {
  background: rgba(0, 0, 0, 0.03);
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}

.ios-table th {
  padding: 0.875rem 1.25rem;
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: rgba(0, 0, 0, 0.38);
  text-align: left;
  white-space: nowrap;
}

.ios-table td {
  padding: 0.875rem 1.25rem;
  color: rgba(0, 0, 0, 0.60);
  border-bottom: 1px solid rgba(0, 0, 0, 0.04);
  vertical-align: middle;
}

.table-row { transition: background 0.15s; }
.table-row:hover { background: rgba(0, 122, 255, 0.03); }
.table-row:last-child td { border-bottom: none; }

/* ─── Cell styles ────────────────────────────────────────────────────────── */
.td-name  { font-weight: 600; color: rgba(0,0,0,0.80); }
.td-mono  { font-family: var(--font-mono); font-size: 0.78rem; letter-spacing: 0.04em; }
.td-muted { color: rgba(0,0,0,0.40); }
.td-white { font-weight: 700; color: rgba(0,0,0,0.75); font-family: var(--font-mono); }
.td-unit  { font-size: 0.75rem; color: rgba(0,0,0,0.35); }

/* ─── Badge ──────────────────────────────────────────────────────────────── */
.badge {
  display: inline-flex;
  align-items: center;
  padding: 0.2rem 0.6rem;
  border-radius: 999px;
  font-size: 0.65rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  border: 1px solid;
}

.badge--green {
  background: rgba(52, 199, 89, 0.12);
  color: #1a7a34;
  border-color: rgba(52, 199, 89, 0.25);
}
.badge--gray {
  background: rgba(0, 0, 0, 0.06);
  color: rgba(0, 0, 0, 0.40);
  border-color: rgba(0, 0, 0, 0.10);
}

/* ─── Action buttons ─────────────────────────────────────────────────────── */
.action-group {
  display: flex;
  gap: 0.5rem;
  justify-content: center;
}

.action-btn {
  padding: 0.35rem 0.875rem;
  border-radius: 0.5rem;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  border: 1px solid;
  transition: all 0.15s;
  -webkit-tap-highlight-color: transparent;
}

.action-btn:active { transform: scale(0.96); }

.action-btn--edit {
  background: rgba(0, 122, 255, 0.09);
  border-color: rgba(0, 122, 255, 0.20);
  color: #007AFF;
}
.action-btn--edit:hover { background: rgba(0, 122, 255, 0.16); }

.action-btn--danger {
  background: rgba(255, 59, 48, 0.08);
  border-color: rgba(255, 59, 48, 0.20);
  color: #FF3B30;
}
.action-btn--danger:hover { background: rgba(255, 59, 48, 0.15); }

.action-btn--full { flex: 1; text-align: center; }

/* ─── Empty cell ─────────────────────────────────────────────────────────── */
.empty-cell {
  text-align: center;
  padding: 3rem 1rem;
  color: rgba(0, 0, 0, 0.30);
  font-size: 0.875rem;
}

/* ─── Pagination ─────────────────────────────────────────────────────────── */
.pagination {
  display: flex;
  justify-content: center;
  gap: 0.375rem;
  padding: 1rem 1.25rem;
  border-top: 1px solid rgba(0, 0, 0, 0.05);
  flex-wrap: wrap;
}

.page-btn {
  padding: 0.4rem 0.75rem;
  border-radius: 0.5rem;
  font-size: 0.8125rem;
  font-weight: 500;
  background: rgba(255, 255, 255, 0.7);
  border: 1px solid rgba(0, 0, 0, 0.08);
  color: rgba(0, 0, 0, 0.60);
  text-decoration: none;
  transition: all 0.15s;
}
.page-btn:hover { background: rgba(255, 255, 255, 0.9); }
.page-btn--active {
  background: #007AFF;
  border-color: #007AFF;
  color: white;
  font-weight: 700;
  box-shadow: 0 2px 8px rgba(0, 122, 255, 0.30);
}
.page-btn--disabled {
  color: rgba(0, 0, 0, 0.25);
  background: transparent;
  border-color: transparent;
}

/* ─── Mobile list ────────────────────────────────────────────────────────── */
.mobile-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.mobile-card {
  background: rgba(255, 255, 255, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.85);
  border-radius: 1rem;
  padding: 1rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06), inset 0 1px 0 rgba(255,255,255,0.9);
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.mobile-card__top {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.mobile-card__name {
  font-size: 1rem;
  font-weight: 700;
  color: rgba(0, 0, 0, 0.82);
  letter-spacing: -0.02em;
  margin: 0;
}

.mobile-card__meta {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.meta-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.72rem;
  color: rgba(0, 0, 0, 0.45);
  background: rgba(0, 0, 0, 0.05);
  padding: 0.2rem 0.5rem;
  border-radius: 999px;
}

.mobile-card__actions {
  display: flex;
  gap: 0.5rem;
  padding-top: 0.375rem;
  border-top: 1px solid rgba(0, 0, 0, 0.05);
  margin-top: 0.25rem;
}

/* ─── Modal body ─────────────────────────────────────────────────────────── */
.modal-body { padding: 1.5rem; }

.modal-header {
  display: flex;
  align-items: flex-start;
  gap: 0.875rem;
  margin-bottom: 1.5rem;
  padding-bottom: 1.25rem;
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}

.modal-header__icon {
  width: 2.5rem;
  height: 2.5rem;
  background: rgba(0, 122, 255, 0.10);
  border: 1px solid rgba(0, 122, 255, 0.18);
  border-radius: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #007AFF;
  flex-shrink: 0;
}

.modal-header__title {
  font-size: 1.0625rem;
  font-weight: 700;
  color: rgba(0, 0, 0, 0.82);
  letter-spacing: -0.02em;
  margin: 0 0 0.15rem;
}

.modal-header__sub {
  font-size: 0.78rem;
  color: rgba(0, 0, 0, 0.38);
  margin: 0;
}

.modal-close {
  margin-left: auto;
  flex-shrink: 0;
  width: 1.875rem;
  height: 1.875rem;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.06);
  border: none;
  border-radius: 50%;
  color: rgba(0, 0, 0, 0.45);
  cursor: pointer;
  transition: background 0.15s, color 0.15s;
}
.modal-close:hover { background: rgba(0,0,0,0.10); color: rgba(0,0,0,0.70); }

/* ─── Form ───────────────────────────────────────────────────────────────── */
.modal-form { display: flex; flex-direction: column; gap: 0.875rem; }

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.875rem;
}

.field { display: flex; flex-direction: column; gap: 0.3rem; }
.span-2 { grid-column: 1 / -1; }

/* ─── Toggle ─────────────────────────────────────────────────────────────── */
.toggle-row {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  cursor: pointer;
  user-select: none;
}

.toggle-check { display: none; }

.toggle-track {
  position: relative;
  width: 2.375rem;
  height: 1.375rem;
  background: rgba(0, 0, 0, 0.15);
  border-radius: 999px;
  transition: background 0.25s;
  flex-shrink: 0;
}

.toggle-check:checked + .toggle-track {
  background: var(--ios-green);
}

.toggle-thumb {
  position: absolute;
  top: 2px;
  left: 2px;
  width: 1.0625rem;
  height: 1.0625rem;
  background: white;
  border-radius: 50%;
  transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
  box-shadow: 0 1px 4px rgba(0,0,0,0.20);
}

.toggle-check:checked ~ .toggle-track .toggle-thumb,
.toggle-check:checked + .toggle-track .toggle-thumb {
  transform: translateX(1rem);
}

.toggle-label {
  font-size: 0.8125rem;
  font-weight: 500;
  color: rgba(0, 0, 0, 0.60);
}

/* ─── Modal actions ──────────────────────────────────────────────────────── */
.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.625rem;
  margin-top: 0.5rem;
  padding-top: 1rem;
  border-top: 1px solid rgba(0, 0, 0, 0.05);
}
</style>
