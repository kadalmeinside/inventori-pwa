/**
 * useMobileFab — Composable untuk FAB (Floating Action Button) mobile.
 *
 * TOKEN PATTERN menyelesaikan race condition navigasi Inertia:
 *   - Page B setup() → myToken = ++_currentToken → set actions
 *   - Page A onBeforeUnmount → myToken(lama) ≠ _currentToken → SKIP clear
 *   → Tidak ada actions yang ter-clear salah
 *
 * KENAPA TIDAK pakai router.on('before'):
 *   router.reload() juga memicu 'before', tapi TIDAK re-run setup().
 *   Akibatnya actions ter-clear dan tidak pernah kembali (bug!).
 *   Contoh: Stocks page reload via Pusher StockUpdated → FAB hilang.
 */
import { ref, onBeforeUnmount } from 'vue'

const _fabActions = ref([])
let _currentToken = 0

export function useMobileFab(actions = []) {
    const myToken = ++_currentToken
    _fabActions.value = actions

    onBeforeUnmount(() => {
        // Hanya clear jika belum ada halaman lain yang register
        if (_currentToken === myToken) {
            _fabActions.value = []
        }
    })
}

export function useFabActions() {
    return _fabActions
}
