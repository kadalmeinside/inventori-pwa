/**
 * useTopbarActions — pages dapat mendaftarkan ikon tombol
 * yang akan muncul DI SEBELAH KIRI bell di topbar.
 *
 * LIFECYCLE FLOW dengan Inertia:
 *   1. router 'before'  → clear actions (halaman lama)
 *   2. New page mounts  → useMobileFab / useTopbarActions dipanggil di setup()
 *   3. Old page unmounts → onBeforeUnmount (token check, cleanup fallback)
 *
 * Dengan 'before' event, actions di-clear SEBELUM navigasi dimulai,
 * sehingga halaman baru bisa register actions baru saat mount.
 */
import { ref, onBeforeUnmount } from 'vue'
import { router } from '@inertiajs/vue3'

const _actions = ref([])
let _token = 0

// Global: clear actions saat navigasi dimulai
// (router.on mengembalikan fungsi untuk remove listener)
router.on('before', () => {
    _actions.value = []
})

export function useTopbarActions(actions = []) {
    const myToken = ++_token
    _actions.value = actions

    onBeforeUnmount(() => {
        if (_token === myToken) _actions.value = []
    })
}

export function useTopbarActionsState() {
    return _actions
}
