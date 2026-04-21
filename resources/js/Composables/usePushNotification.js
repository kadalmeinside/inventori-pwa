/**
 * usePushNotification.js
 *
 * Composable untuk mengelola Web Push Notification lifecycle:
 * - Deteksi dukungan browser (termasuk iOS Add-to-Home-Screen check)
 * - Request permission
 * - Subscribe / Unsubscribe ke push manager
 * - Sinkronisasi state dengan backend Laravel
 */

import { ref, computed, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'

// ─── Helpers ──────────────────────────────────────────────────────────────────

/**
 * Konversi VAPID public key dari base64url ke Uint8Array
 * yang dibutuhkan oleh pushManager.subscribe()
 */
function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4)
  const base64  = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/')
  const raw     = window.atob(base64)
  const output  = new Uint8Array(raw.length)
  for (let i = 0; i < raw.length; ++i) {
    output[i] = raw.charCodeAt(i)
  }
  return output
}

/**
 * Deteksi apakah pengguna berada di iOS Safari (standalone atau tidak)
 */
function detectIOS() {
  const ua = navigator.userAgent
  return /iP(hone|ad|od)/.test(ua) && /Safari/.test(ua)
}

/**
 * Apakah PWA sudah di-install (standalone mode)?
 * Di iOS ini WAJIB agar push notification bekerja.
 */
function isStandalone() {
  return (
    window.matchMedia('(display-mode: standalone)').matches ||
    window.navigator.standalone === true
  )
}

// ─── Composable ───────────────────────────────────────────────────────────────

export function usePushNotification() {
  const page        = usePage()
  const vapidKey    = computed(() => page.props.vapidPublicKey ?? '')

  // ── State ──────────────────────────────────────────────────────────────────
  const isSupported   = ref(false)  // Browser mendukung Push API?
  const isIOS         = ref(false)  // Pengguna di iOS?
  const isInstalled   = ref(false)  // PWA sudah di-install (standalone)?
  const permission    = ref('default') // 'default' | 'granted' | 'denied'
  const isSubscribed  = ref(false)  // Sudah berlangganan push?
  const isLoading     = ref(false)  // Sedang proses subscribe/unsubscribe?
  const error         = ref(null)   // Pesan error

  // ── Computed ───────────────────────────────────────────────────────────────

  /**
   * Apakah kita BISA menawarkan push notification?
   * - Browser harus support Push API & Notifications API
   * - Di iOS: harus dalam mode standalone (sudah di-install)
   */
  const canSubscribe = computed(() => {
    if (!isSupported.value) return false
    if (isIOS.value && !isInstalled.value) return false
    if (permission.value === 'denied') return false
    return true
  })

  /**
   * Perlu tampilkan banner "Install ke Home Screen" untuk iOS?
   */
  const needsInstallForPush = computed(() => {
    return isIOS.value && !isInstalled.value && isSupported.value
  })

  // ── Methods ────────────────────────────────────────────────────────────────

  /**
   * Inisialisasi: cek dukungan, permission, dan state subscription saat ini
   */
  async function init() {
    isIOS.value       = detectIOS()
    isInstalled.value = isStandalone()

    // Cek dukungan API
    if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
      isSupported.value = false
      return
    }
    isSupported.value = true
    permission.value  = Notification.permission

    // Cek apakah sudah ada subscription aktif
    try {
      const registration  = await navigator.serviceWorker.ready
      const subscription  = await registration.pushManager.getSubscription()

      if (subscription) {
        isSubscribed.value = true
        // Pastikan subscription juga ada di DB (sinkronisasi)
        await syncWithServer(subscription)
      }
    } catch (e) {
      console.warn('[Push] Gagal cek subscription:', e)
    }
  }

  /**
   * Minta izin notifikasi & daftarkan ke push server
   */
  async function subscribe() {
    if (!canSubscribe.value || !vapidKey.value) {
      error.value = 'VAPID key belum dikonfigurasi di server.'
      return false
    }

    isLoading.value = true
    error.value     = null

    try {
      // 1. Minta izin
      const result = await Notification.requestPermission()
      permission.value = result

      if (result !== 'granted') {
        error.value = 'Izin notifikasi ditolak.'
        return false
      }

      // 2. Dapatkan service worker registration
      const registration = await navigator.serviceWorker.ready

      // 3. Subscribe ke Push Manager
      const subscription = await registration.pushManager.subscribe({
        userVisibleOnly:      true,
        applicationServerKey: urlBase64ToUint8Array(vapidKey.value),
      })

      // 4. Kirim subscription ke backend Laravel
      await axios.post('/push-subscriptions', subscription.toJSON(), {
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      })

      isSubscribed.value = true
      return true

    } catch (e) {
      console.error('[Push] Gagal subscribe:', e)
      error.value = 'Gagal mengaktifkan notifikasi: ' + e.message
      return false
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Batalkan langganan push notification
   */
  async function unsubscribe() {
    isLoading.value = true
    error.value     = null

    try {
      const registration = await navigator.serviceWorker.ready
      const subscription = await registration.pushManager.getSubscription()

      if (subscription) {
        // Hapus dari server dulu
        await axios.delete('/push-subscriptions', {
          data: { endpoint: subscription.endpoint },
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })

        // Unsubscribe dari browser
        await subscription.unsubscribe()
      }

      isSubscribed.value = false
      return true

    } catch (e) {
      console.error('[Push] Gagal unsubscribe:', e)
      error.value = 'Gagal menonaktifkan notifikasi: ' + e.message
      return false
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Sinkronisasi subscription yang ada di browser dengan backend
   */
  async function syncWithServer(subscription) {
    try {
      const { data } = await axios.get('/push-subscriptions/check', {
        params: { endpoint: subscription.endpoint },
      })

      if (!data.subscribed) {
        // Subscription ada di browser tapi tidak di DB — kirim ulang
        await axios.post('/push-subscriptions', subscription.toJSON(), {
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
      }
    } catch (e) {
      console.warn('[Push] Gagal sinkronisasi dengan server:', e)
    }
  }

  // ── Init on mount ──────────────────────────────────────────────────────────
  onMounted(() => { init() })

  return {
    // State
    isSupported,
    isIOS,
    isInstalled,
    permission,
    isSubscribed,
    isLoading,
    error,
    // Computed
    canSubscribe,
    needsInstallForPush,
    // Methods
    subscribe,
    unsubscribe,
  }
}
