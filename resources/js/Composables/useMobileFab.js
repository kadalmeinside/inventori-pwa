/**
 * useMobileFab — Composable untuk FAB (Floating Action Button) mobile.
 *
 * PROBLEM: Race condition saat navigasi Inertia:
 *   1. Halaman B di-mount → setup() memanggil useMobileFab([actionB])
 *   2. Halaman A di-unmount → onUnmounted membersihkan _fabActions = []
 *   → FAB kosong!
 *
 * SOLUTION: Token mechanism.
 *   Setiap kali useMobileFab dipanggil, token baru dibuat.
 *   onBeforeUnmount hanya membersihkan jika token masih milik-nya
 *   (artinya belum ada halaman lain yang mendaftar actions baru).
 */
import { ref, onBeforeUnmount } from 'vue'

/** Global reactive state — shared across all component instances */
const _fabActions = ref([])
let _currentToken = 0

/**
 * Dipanggil oleh setiap halaman yang ingin mendaftarkan aksi FAB.
 * Actions di-clear secara otomatis ketika komponen di-unmount,
 * KECUALI komponen lain sudah mendaftarkan actions baru terlebih dahulu.
 *
 * @param {Array<{ label: string, icon: string, color: string, action: Function }>} actions
 */
export function useMobileFab(actions = []) {
    // Naikkan token → "kepemilikan" berpindah ke halaman ini
    const myToken = ++_currentToken
    _fabActions.value = actions

    onBeforeUnmount(() => {
        // Hanya bersihkan jika tidak ada halaman lain yang sudah mendaftar
        if (_currentToken === myToken) {
            _fabActions.value = []
        }
    })
}

/**
 * Digunakan oleh MobileFab.vue untuk membaca actions saat ini.
 */
export function useFabActions() {
    return _fabActions
}
