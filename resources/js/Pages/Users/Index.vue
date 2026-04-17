<template>
  <AppLayout title="User Management">
    <div class="page-wrap">

      <!-- Header -->
      <div class="page-header">
        <div>
          <p class="page-eyebrow">Administration</p>
          <h1 class="page-title">Users</h1>
        </div>
        <button class="btn-ios btn-ios-primary" @click="openModal()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M12 5v14M5 12h14"/></svg>
          Add User
        </button>
      </div>

      <!-- Table Card -->
      <div class="glass-card">

        <!-- Desktop -->
        <div class="table-wrap hidden md:block">
          <table class="ios-table">
            <thead>
              <tr>
                <th>User</th>
                <th>Role</th>
                <th>Warehouse</th>
                <th>Joined</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in users.data" :key="user.id" class="table-row">
                <td>
                  <div class="user-cell">
                    <div class="user-avatar">{{ initials(user.name) }}</div>
                    <div>
                      <div class="td-name">{{ user.name }}</div>
                      <div class="td-email">{{ user.email }}</div>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="role-badge" :class="roleClass(user.role)">
                    {{ roleLabel(user.role) }}
                  </span>
                </td>
                <td class="td-muted">{{ user.warehouse?.name ?? '—' }}</td>
                <td class="td-muted" style="font-size:0.78rem;">{{ formatDate(user.created_at) }}</td>
                <td class="text-center">
                  <div class="action-group">
                    <button class="action-btn action-btn--blue" @click="openModal(user)">Edit</button>
                    <button
                      v-if="user.id !== currentUserId"
                      class="action-btn action-btn--red"
                      @click="deleteUser(user)"
                    >Delete</button>
                  </div>
                </td>
              </tr>
              <tr v-if="users.data.length === 0">
                <td colspan="5" class="empty-cell">No users found.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile Cards -->
        <div class="block md:hidden p-2">
          <div class="mobile-list">
            <div v-for="user in users.data" :key="'m-'+user.id" class="mobile-card">
              <div class="mobile-card__top">
                <div class="user-avatar user-avatar--sm">{{ initials(user.name) }}</div>
                <div class="flex-1 min-w-0">
                  <div class="mobile-card__name">{{ user.name }}</div>
                  <div class="td-email" style="font-size:0.72rem;">{{ user.email }}</div>
                </div>
                <span class="role-badge" :class="roleClass(user.role)">{{ roleLabel(user.role) }}</span>
              </div>

              <div class="mobile-card__meta">
                <span class="meta-chip">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="10" height="10" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/></svg>
                  {{ user.warehouse?.name ?? 'No warehouse' }}
                </span>
                <span class="meta-chip">{{ formatDate(user.created_at) }}</span>
              </div>

              <div class="mobile-card__actions">
                <button class="action-btn action-btn--blue action-btn--full" @click="openModal(user)">Edit</button>
                <button
                  v-if="user.id !== currentUserId"
                  class="action-btn action-btn--red action-btn--full"
                  @click="deleteUser(user)"
                >Delete</button>
              </div>
            </div>
            <div v-if="users.data.length === 0" class="empty-cell">No users found.</div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="users.links.length > 3" class="pagination">
          <template v-for="(link, i) in users.links" :key="i">
            <span v-if="link.url === null" class="page-btn page-btn--disabled" v-html="link.label" />
            <Link v-else :href="link.url" class="page-btn" :class="link.active ? 'page-btn--active' : ''" v-html="link.label" />
          </template>
        </div>
      </div>
    </div>

    <!-- ─── User Modal ─────────────────────────────────────────────────────── -->
    <Modal :show="showModal" @close="closeModal" maxWidth="md">
      <div class="modal-body">
        <div class="modal-header">
          <div class="modal-header__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          </div>
          <div>
            <h2 class="modal-header__title">{{ isEditing ? 'Edit User' : 'New User' }}</h2>
            <p class="modal-header__sub">{{ isEditing ? 'Update user details and role' : 'Create a new user account' }}</p>
          </div>
          <button class="modal-close" @click="closeModal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M18 6L6 18M6 6l12 12"/></svg>
          </button>
        </div>

        <form @submit.prevent="submitForm" class="modal-form">
          <div class="form-grid">

            <!-- Name -->
            <div class="field span-2">
              <InputLabel for="u-name" value="Full Name" />
              <TextInput id="u-name" v-model="form.name" type="text" required placeholder="Full name" />
              <InputError :message="form.errors.name" />
            </div>

            <!-- Email -->
            <div class="field span-2">
              <InputLabel for="u-email" value="Email" />
              <TextInput id="u-email" v-model="form.email" type="email" required placeholder="email@example.com" />
              <InputError :message="form.errors.email" />
            </div>

            <!-- Password -->
            <div class="field span-2">
              <InputLabel for="u-pwd" :value="isEditing ? 'New Password (leave blank to keep)' : 'Password'" />
              <TextInput id="u-pwd" v-model="form.password" type="password" :required="!isEditing" placeholder="••••••••" />
              <InputError :message="form.errors.password" />
            </div>

            <!-- Password Confirmation -->
            <div class="field span-2" v-if="!isEditing || form.password">
              <InputLabel for="u-pwd-confirm" value="Confirm Password" />
              <TextInput id="u-pwd-confirm" v-model="form.password_confirmation" type="password" :required="!isEditing" placeholder="••••••••" />
              <InputError :message="form.errors.password_confirmation" />
            </div>

            <!-- Role -->
            <div class="field">
              <InputLabel for="u-role" value="Role" />
              <select id="u-role" v-model="form.role" class="input-ios" required>
                <option value="super_admin">Super Admin</option>
                <option value="branch_admin">Branch Admin</option>
              </select>
              <InputError :message="form.errors.role" />
            </div>

            <!-- Warehouse -->
            <div class="field">
              <InputLabel for="u-wh" value="Assigned Warehouse" />
              <select id="u-wh" v-model="form.warehouse_id" class="input-ios">
                <option :value="null">None (Super Admin)</option>
                <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
              </select>
              <InputError :message="form.errors.warehouse_id" />
            </div>

          </div>

          <!-- Role info strip -->
          <div class="role-info" :class="form.role === 'super_admin' ? 'role-info--admin' : 'role-info--branch'">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span v-if="form.role === 'super_admin'">Super Admin has full access to all warehouses, users, and settings.</span>
            <span v-else>Branch Admin can only access their assigned warehouse's stock.</span>
          </div>

          <div class="modal-actions">
            <button type="button" @click="closeModal" class="btn-ios btn-ios-glass">Cancel</button>
            <PrimaryButton :class="{ 'opacity-50': form.processing }" :disabled="form.processing">
              {{ isEditing ? 'Save Changes' : 'Create User' }}
            </PrimaryButton>
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
import TextInput     from '@/Components/TextInput.vue';
import InputLabel    from '@/Components/InputLabel.vue';
import InputError    from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

defineProps({ users: Object, warehouses: Array });

const page          = usePage();
const currentUserId = computed(() => page.props.auth.user.id);

const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const form = useForm({ name: '', email: '', password: '', password_confirmation: '', role: 'branch_admin', warehouse_id: null });

const initials   = (name) => name?.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase() ?? '?';
const formatDate = (d)    => new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
const roleLabel  = (r)    => r === 'super_admin' ? 'Super Admin' : 'Branch Admin';
const roleClass  = (r)    => r === 'super_admin' ? 'role-badge--purple' : 'role-badge--blue';

const openModal = (user = null) => {
  form.clearErrors();
  if (user) {
    isEditing.value     = true;
    editingId.value     = user.id;
    form.name                 = user.name;
    form.email                = user.email;
    form.password             = '';
    form.password_confirmation = '';
    form.role                 = user.role;
    form.warehouse_id         = user.warehouse_id ?? null;
  } else {
    isEditing.value = false;
    editingId.value     = null;
    form.reset();
  }
  showModal.value = true;
};

const closeModal = () => { showModal.value = false; setTimeout(() => form.reset(), 300); };

const submitForm = () => {
  const opts = { preserveScroll: true, onSuccess: closeModal };
  isEditing.value
    ? form.put(route('users.update', editingId.value), opts)
    : form.post(route('users.store'), opts);
};

const deleteUser = (user) => {
  if (confirm(`Delete user "${user.name}"? This cannot be undone.`))
    router.delete(route('users.destroy', user.id), { preserveScroll: true });
};
</script>

<style scoped>
.page-wrap { padding: 2rem 1.25rem 5rem; max-width: 1100px; margin: 0 auto; }
.page-header { display:flex; align-items:flex-end; justify-content:space-between; margin-bottom:1.5rem; gap:1rem; flex-wrap:wrap; }
.page-eyebrow { font-size:0.7rem; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; color:rgba(0,0,0,0.35); margin:0 0 0.2rem; }
.page-title { font-size:1.75rem; font-weight:800; letter-spacing:-0.04em; color:rgba(0,0,0,0.85); margin:0; line-height:1; }

.glass-card { background:rgba(255,255,255,0.62); backdrop-filter:blur(28px) saturate(180%); -webkit-backdrop-filter:blur(28px) saturate(180%); border:1px solid rgba(255,255,255,0.82); border-radius:1.5rem; box-shadow:0 8px 40px rgba(0,80,200,0.10), inset 0 1px 0 rgba(255,255,255,0.95); overflow:hidden; }

.table-wrap { overflow-x:auto; }
.ios-table { width:100%; border-collapse:collapse; font-size:0.875rem; }
.ios-table thead tr { background:rgba(0,0,0,0.03); border-bottom:1px solid rgba(0,0,0,0.06); }
.ios-table th { padding:0.875rem 1.25rem; font-size:0.68rem; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:rgba(0,0,0,0.38); text-align:left; white-space:nowrap; }
.ios-table td { padding:0.875rem 1.25rem; color:rgba(0,0,0,0.60); border-bottom:1px solid rgba(0,0,0,0.04); vertical-align:middle; }
.table-row { transition:background 0.15s; }
.table-row:hover { background:rgba(0,122,255,0.03); }
.table-row:last-child td { border-bottom:none; }
.td-name { font-weight:600; color:rgba(0,0,0,0.80); }
.td-email { font-size:0.75rem; color:rgba(0,0,0,0.38); margin-top:1px; }
.td-muted { color:rgba(0,0,0,0.40); }

/* User cell with avatar */
.user-cell { display:flex; align-items:center; gap:0.75rem; }
.user-avatar { width:2.25rem; height:2.25rem; background:linear-gradient(145deg, #007AFF, #5856D6); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:0.7rem; font-weight:700; color:white; flex-shrink:0; box-shadow:0 2px 8px rgba(0,122,255,0.25); }
.user-avatar--sm { width:2rem; height:2rem; font-size:0.65rem; }

/* Role badges */
.role-badge { display:inline-flex; align-items:center; padding:0.2rem 0.65rem; border-radius:999px; font-size:0.62rem; font-weight:700; letter-spacing:0.05em; border:1px solid; }
.role-badge--purple { background:rgba(88,86,214,0.10); color:#5856D6; border-color:rgba(88,86,214,0.22); }
.role-badge--blue { background:rgba(0,122,255,0.10); color:#007AFF; border-color:rgba(0,122,255,0.22); }

/* Actions */
.action-group { display:flex; gap:0.4rem; justify-content:center; }
.action-btn { padding:0.35rem 0.875rem; border-radius:0.5rem; font-size:0.75rem; font-weight:600; cursor:pointer; border:1px solid; transition:all 0.15s; -webkit-tap-highlight-color:transparent; }
.action-btn:active { transform:scale(0.96); }
.action-btn--blue { background:rgba(0,122,255,0.09); border-color:rgba(0,122,255,0.20); color:#007AFF; }
.action-btn--blue:hover { background:rgba(0,122,255,0.16); }
.action-btn--red { background:rgba(255,59,48,0.08); border-color:rgba(255,59,48,0.20); color:#FF3B30; }
.action-btn--red:hover { background:rgba(255,59,48,0.15); }
.action-btn--full { flex:1; text-align:center; }

.empty-cell { text-align:center; padding:3rem 1rem; color:rgba(0,0,0,0.30); font-size:0.875rem; }

.pagination { display:flex; justify-content:center; gap:0.375rem; padding:1rem 1.25rem; border-top:1px solid rgba(0,0,0,0.05); flex-wrap:wrap; }
.page-btn { padding:0.4rem 0.75rem; border-radius:0.5rem; font-size:0.8125rem; font-weight:500; background:rgba(255,255,255,0.7); border:1px solid rgba(0,0,0,0.08); color:rgba(0,0,0,0.60); text-decoration:none; transition:all 0.15s; }
.page-btn--active { background:#007AFF; border-color:#007AFF; color:white; font-weight:700; }
.page-btn--disabled { color:rgba(0,0,0,0.25); background:transparent; border-color:transparent; }

/* Mobile */
.mobile-list { display:flex; flex-direction:column; gap:0.75rem; }
.mobile-card { background:rgba(255,255,255,0.7); border:1px solid rgba(255,255,255,0.85); border-radius:1rem; padding:1rem; box-shadow:0 2px 8px rgba(0,0,0,0.06), inset 0 1px 0 rgba(255,255,255,0.9); display:flex; flex-direction:column; gap:0.5rem; }
.mobile-card__top { display:flex; align-items:center; gap:0.75rem; }
.mobile-card__name { font-size:0.9375rem; font-weight:700; color:rgba(0,0,0,0.82); letter-spacing:-0.02em; }
.mobile-card__meta { display:flex; gap:0.5rem; flex-wrap:wrap; }
.meta-chip { display:inline-flex; align-items:center; gap:0.3rem; font-size:0.72rem; color:rgba(0,0,0,0.45); background:rgba(0,0,0,0.05); padding:0.2rem 0.5rem; border-radius:999px; }
.mobile-card__actions { display:flex; gap:0.5rem; padding-top:0.375rem; border-top:1px solid rgba(0,0,0,0.05); margin-top:0.25rem; }

/* Modal */
.modal-body { padding:1.5rem; }
.modal-header { display:flex; align-items:flex-start; gap:0.875rem; margin-bottom:1.5rem; padding-bottom:1.25rem; border-bottom:1px solid rgba(0,0,0,0.06); }
.modal-header__icon { width:2.5rem; height:2.5rem; background:rgba(88,86,214,0.10); border:1px solid rgba(88,86,214,0.18); border-radius:0.75rem; display:flex; align-items:center; justify-content:center; color:#5856D6; flex-shrink:0; }
.modal-header__title { font-size:1.0625rem; font-weight:700; color:rgba(0,0,0,0.82); letter-spacing:-0.02em; margin:0 0 0.15rem; }
.modal-header__sub { font-size:0.78rem; color:rgba(0,0,0,0.38); margin:0; }
.modal-close { margin-left:auto; flex-shrink:0; width:1.875rem; height:1.875rem; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0.06); border:none; border-radius:50%; color:rgba(0,0,0,0.45); cursor:pointer; }
.modal-close:hover { background:rgba(0,0,0,0.10); }
.modal-form { display:flex; flex-direction:column; gap:0.875rem; }
.form-grid { display:grid; grid-template-columns:1fr 1fr; gap:0.875rem; }
.field { display:flex; flex-direction:column; gap:0.3rem; }
.span-2 { grid-column:1/-1; }
.modal-actions { display:flex; justify-content:flex-end; gap:0.625rem; padding-top:1rem; border-top:1px solid rgba(0,0,0,0.05); }

/* Role info strip */
.role-info { display:flex; align-items:flex-start; gap:0.5rem; font-size:0.75rem; line-height:1.5; padding:0.625rem 0.875rem; border-radius:0.625rem; border:1px solid; }
.role-info--admin { background:rgba(88,86,214,0.07); border-color:rgba(88,86,214,0.18); color:#5856D6; }
.role-info--branch { background:rgba(0,122,255,0.07); border-color:rgba(0,122,255,0.18); color:#007AFF; }
</style>
