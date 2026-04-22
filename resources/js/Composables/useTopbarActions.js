/**
 * useTopbarActions — pages dapat mendaftarkan ikon tombol
 * yang akan muncul DI SEBELAH KIRI bell di topbar.
 * Menggunakan token pattern (sama seperti useMobileFab) untuk
 * menghindari race condition saat navigasi Inertia.
 */
import { ref, onBeforeUnmount } from 'vue'

const _actions = ref([])
let _token = 0

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
