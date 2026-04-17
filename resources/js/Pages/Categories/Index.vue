<template>
  <AppLayout title="Category Management">
    <div class="page-wrap">

      <div class="page-header">
        <div>
          <p class="page-eyebrow">Management</p>
          <h1 class="page-title">Categories</h1>
        </div>
        <button class="btn-ios btn-ios-primary" @click="openModal()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M12 5v14M5 12h14"/></svg>
          Add Category
        </button>
      </div>

      <div class="glass-card">
        <!-- Desktop -->
        <div class="table-wrap hidden md:block">
          <table class="ios-table">
            <thead>
              <tr>
                <th>Status</th>
                <th>Name</th>
                <th>Icon</th>
                <th>Description</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="cat in categories.data" :key="cat.id" class="table-row">
                <td>
                  <span class="badge" :class="cat.is_active ? 'badge--green' : 'badge--gray'">
                    {{ cat.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td class="td-name">{{ cat.name }}</td>
                <td class="td-mono">{{ cat.icon || '—' }}</td>
                <td class="td-muted">{{ cat.description || '—' }}</td>
                <td class="text-center">
                  <div class="action-group">
                    <button class="action-btn action-btn--blue" @click="openModal(cat)">Edit</button>
                    <button class="action-btn action-btn--red" @click="deleteCategory(cat)">Delete</button>
                  </div>
                </td>
              </tr>
              <tr v-if="categories.data.length === 0"><td colspan="5" class="empty-cell">No categories found.</td></tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile -->
        <div class="block md:hidden p-2">
          <div class="mobile-list">
            <div v-for="cat in categories.data" :key="'m-'+cat.id" class="mobile-card">
              <div class="mobile-card__top">
                <span class="td-mono" style="font-size:0.68rem;">{{ cat.icon }}</span>
                <span class="badge" :class="cat.is_active ? 'badge--green' : 'badge--gray'">
                  {{ cat.is_active ? 'Active' : 'Inactive' }}
                </span>
              </div>
              <h3 class="mobile-card__name">{{ cat.name }}</h3>
              <div v-if="cat.description" class="mobile-card__description">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="11" height="11"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                {{ cat.description }}
              </div>
              <div class="mobile-card__actions">
                <button class="action-btn action-btn--blue action-btn--full" @click="openModal(cat)">Edit</button>
                <button class="action-btn action-btn--red action-btn--full" @click="deleteCategory(cat)">Delete</button>
              </div>
            </div>
            <div v-if="categories.data.length === 0" class="empty-cell">No categories found.</div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="categories.links.length > 3" class="pagination">
          <template v-for="(link, i) in categories.links" :key="i">
            <span v-if="link.url === null" class="page-btn page-btn--disabled" v-html="link.label" />
            <Link v-else :href="link.url" class="page-btn" :class="link.active ? 'page-btn--active' : ''" v-html="link.label" />
          </template>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <Modal :show="showModal" @close="closeModal" maxWidth="md">
      <div class="modal-body">
        <div class="modal-header">
          <div class="modal-header__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
          </div>
          <div>
            <h2 class="modal-header__title">{{ isEditing ? 'Edit Category' : 'New Category' }}</h2>
            <p class="modal-header__sub">{{ isEditing ? 'Update category details' : 'Add a new location to the network' }}</p>
          </div>
          <button class="modal-close" @click="closeModal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M18 6L6 18M6 6l12 12"/></svg>
          </button>
        </div>
        <form @submit.prevent="submitForm" class="modal-form">
          <div class="field">
            <InputLabel for="name" value="Category Name" />
            <TextInput id="name" v-model="form.name" type="text" required placeholder="e.g. Main Category Jakarta" />
            <InputError :message="form.errors.name" />
          </div>
          <div class="field">
            <InputLabel for="icon" value="Category Code" />
            <TextInput id="icon" v-model="form.icon" type="text" required placeholder="e.g. JKT-01" style="font-family: var(--font-mono); text-transform: uppercase;" />
            <InputError :message="form.errors.icon" />
          </div>
          <div class="field">
            <InputLabel for="description" value="Description" />
            <textarea id="description" v-model="form.description" class="input-ios" rows="3" placeholder="Category description…" />
            <InputError :message="form.errors.description" />
          </div>
          <div class="field">
            <label class="toggle-row">
              <input id="is_active" v-model="form.is_active" type="checkbox" class="toggle-check" />
              <span class="toggle-track"><span class="toggle-thumb" /></span>
              <span class="toggle-label">Set as Active</span>
            </label>
            <InputError :message="form.errors.is_active" />
          </div>
          <div class="modal-actions">
            <button type="button" @click="closeModal" class="btn-ios btn-ios-glass">Cancel</button>
            <PrimaryButton :class="{ 'opacity-50': form.processing }" :disabled="form.processing">
              {{ isEditing ? 'Save Changes' : 'Create Category' }}
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
import AppLayout     from '@/Layouts/AppLayout.vue';
import Modal         from '@/Components/Modal.vue';
import TextInput     from '@/Components/TextInput.vue';
import InputLabel    from '@/Components/InputLabel.vue';
import InputError    from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

defineProps({ categories: Object });
const showModal = ref(false), isEditing = ref(false), editingId = ref(null);
const form = useForm({ name: '', icon: '', description: '', is_active: true });

const openModal = (cat = null) => {
  form.clearErrors();
  if (cat) { isEditing.value = true; editingId.value = cat.id; form.name = cat.name; form.icon = cat.icon || ''; form.description = cat.description || ''; form.is_active = !!cat.is_active; }
  else { isEditing.value = false; editingId.value = null; form.reset(); }
  showModal.value = true;
};
const closeModal = () => { showModal.value = false; setTimeout(() => form.reset(), 300); };
const submitForm = () => {
  const opts = { preserveScroll: true, onSuccess: closeModal };
  isEditing.value ? form.put(route('categories.update', editingId.value), opts) : form.post(route('categories.store'), opts);
};
const deleteCategory = (cat) => {
  if (confirm(`Delete category: ${cat.name}?`))
    router.delete(route('categories.destroy', cat.id), { preserveScroll: true });
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
.td-mono { font-family: var(--font-mono); font-size: 0.78rem; letter-spacing: 0.04em; color: rgba(0,0,0,0.50); }
.td-muted { color: rgba(0,0,0,0.40); font-size: 0.875rem; }
.badge { display: inline-flex; align-items: center; padding: 0.2rem 0.6rem; border-radius: 999px; font-size: 0.65rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; border: 1px solid; }
.badge--green { background: rgba(52,199,89,0.12); color: #1a7a34; border-color: rgba(52,199,89,0.25); }
.badge--gray  { background: rgba(0,0,0,0.06); color: rgba(0,0,0,0.40); border-color: rgba(0,0,0,0.10); }
.action-group { display: flex; gap: 0.4rem; justify-content: center; }
.action-btn { padding: 0.35rem 0.875rem; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 600; cursor: pointer; border: 1px solid; transition: all 0.15s; -webkit-tap-highlight-color: transparent; }
.action-btn:active { transform: scale(0.96); }
.action-btn--blue { background: rgba(0,122,255,0.09); border-color: rgba(0,122,255,0.20); color: #007AFF; }
.action-btn--blue:hover { background: rgba(0,122,255,0.16); }
.action-btn--red { background: rgba(255,59,48,0.08); border-color: rgba(255,59,48,0.20); color: #FF3B30; }
.action-btn--red:hover { background: rgba(255,59,48,0.15); }
.action-btn--full { flex: 1; text-align: center; }
.empty-cell { text-align: center; padding: 3rem 1rem; color: rgba(0,0,0,0.30); font-size: 0.875rem; }
.pagination { display: flex; justify-content: center; gap: 0.375rem; padding: 1rem 1.25rem; border-top: 1px solid rgba(0,0,0,0.05); flex-wrap: wrap; }
.page-btn { padding: 0.4rem 0.75rem; border-radius: 0.5rem; font-size: 0.8125rem; font-weight: 500; background: rgba(255,255,255,0.7); border: 1px solid rgba(0,0,0,0.08); color: rgba(0,0,0,0.60); text-decoration: none; transition: all 0.15s; }
.page-btn--active { background: #007AFF; border-color: #007AFF; color: white; font-weight: 700; }
.page-btn--disabled { color: rgba(0,0,0,0.25); background: transparent; border-color: transparent; }
.mobile-list { display: flex; flex-direction: column; gap: 0.75rem; }
.mobile-card { background: rgba(255,255,255,0.7); border: 1px solid rgba(255,255,255,0.85); border-radius: 1rem; padding: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.06), inset 0 1px 0 rgba(255,255,255,0.9); display: flex; flex-direction: column; gap: 0.5rem; }
.mobile-card__top { display: flex; align-items: center; justify-content: space-between; }
.mobile-card__name { font-size: 1rem; font-weight: 700; color: rgba(0,0,0,0.82); letter-spacing: -0.02em; margin: 0; }
.mobile-card__description { display: flex; align-items: flex-start; gap: 0.3rem; font-size: 0.78rem; color: rgba(0,0,0,0.40); }
.mobile-card__actions { display: flex; gap: 0.5rem; padding-top: 0.375rem; border-top: 1px solid rgba(0,0,0,0.05); margin-top: 0.25rem; }
.modal-body { padding: 1.5rem; }
.modal-header { display: flex; align-items: flex-start; gap: 0.875rem; margin-bottom: 1.5rem; padding-bottom: 1.25rem; border-bottom: 1px solid rgba(0,0,0,0.06); }
.modal-header__icon { width: 2.5rem; height: 2.5rem; background: rgba(0,122,255,0.10); border: 1px solid rgba(0,122,255,0.18); border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; color: #007AFF; flex-shrink: 0; }
.modal-header__title { font-size: 1.0625rem; font-weight: 700; color: rgba(0,0,0,0.82); letter-spacing: -0.02em; margin: 0 0 0.15rem; }
.modal-header__sub { font-size: 0.78rem; color: rgba(0,0,0,0.38); margin: 0; }
.modal-close { margin-left: auto; flex-shrink: 0; width: 1.875rem; height: 1.875rem; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.06); border: none; border-radius: 50%; color: rgba(0,0,0,0.45); cursor: pointer; }
.modal-form { display: flex; flex-direction: column; gap: 0.875rem; }
.field { display: flex; flex-direction: column; gap: 0.3rem; }
.modal-actions { display: flex; justify-content: flex-end; gap: 0.625rem; padding-top: 1rem; border-top: 1px solid rgba(0,0,0,0.05); }
/* Toggle */
.toggle-row { display: flex; align-items: center; gap: 0.625rem; cursor: pointer; user-select: none; }
.toggle-check { display: none; }
.toggle-track { position: relative; width: 2.375rem; height: 1.375rem; background: rgba(0,0,0,0.15); border-radius: 999px; transition: background 0.25s; flex-shrink: 0; }
.toggle-check:checked + .toggle-track { background: var(--ios-green); }
.toggle-thumb { position: absolute; top: 2px; left: 2px; width: 1.0625rem; height: 1.0625rem; background: white; border-radius: 50%; transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1); box-shadow: 0 1px 4px rgba(0,0,0,0.20); }
.toggle-check:checked + .toggle-track .toggle-thumb { transform: translateX(1rem); }
.toggle-label { font-size: 0.8125rem; font-weight: 500; color: rgba(0,0,0,0.60); }
</style>
