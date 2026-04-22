/**
 * useMobileSheet — shared state untuk mengetahui apakah
 * More sheet di MobileNav sedang terbuka.
 * Digunakan oleh MobileFab untuk menyembunyikan dirinya
 * ketika sheet aktif, agar tidak mengganggu sheet UI.
 */
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

export const isSheetOpen = ref(false)

// Safety net: reset saat navigasi Inertia selesai.
// Memastikan FAB selalu muncul di halaman baru meskipun
// sheet tidak ditutup dengan benar sebelum navigasi.
router.on('navigate', () => {
    isSheetOpen.value = false
})
