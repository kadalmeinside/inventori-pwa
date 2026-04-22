/**
 * useMobileSheet — shared state untuk mengetahui apakah
 * More sheet di MobileNav sedang terbuka.
 * Digunakan oleh MobileFab untuk menyembunyikan dirinya
 * ketika sheet aktif, agar tidak mengganggu sheet UI.
 */
import { ref } from 'vue'

export const isSheetOpen = ref(false)
