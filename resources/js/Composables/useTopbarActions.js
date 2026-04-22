/**
 * useTopbarActions — pages dapat mendaftarkan ikon tombol
 * yang akan muncul DI SEBELAH KIRI bell di topbar.
 *
 * TOKEN PATTERN menyelesaikan race condition navigasi Inertia:
 *   - Page B setup() → myToken = ++_token → set actions
 *   - Page A onBeforeUnmount → myToken(lama) ≠ _token → SKIP clear
 *
 * KENAPA TIDAK pakai router.on('before'):
 *   router.reload() (misal dari Pusher) memicu 'before' tapi tidak
 *   re-run setup(), sehingga actions ter-clear permanen.
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
