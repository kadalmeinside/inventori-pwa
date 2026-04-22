/**
 * useMobileFab — Composable untuk FAB (Floating Action Button) mobile.
 *
 * LIFECYCLE dengan Inertia:
 *   router 'before' → clear actions (sebelum navigasi dimulai)
 *   New page setup() → useMobileFab([...]) register actions baru
 *   Old page onBeforeUnmount → token check (fallback cleanup)
 */
import { ref, onBeforeUnmount } from 'vue'
import { router } from '@inertiajs/vue3'

const _fabActions = ref([])
let _currentToken = 0

// Global: clear saat navigasi dimulai
router.on('before', () => {
    _fabActions.value = []
})

export function useMobileFab(actions = []) {
    const myToken = ++_currentToken
    _fabActions.value = actions

    onBeforeUnmount(() => {
        if (_currentToken === myToken) {
            _fabActions.value = []
        }
    })
}

export function useFabActions() {
    return _fabActions
}
