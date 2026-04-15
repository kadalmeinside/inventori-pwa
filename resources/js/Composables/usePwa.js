/**
 * usePwa — PWA Install Prompt Composable
 *
 * Captures the `beforeinstallprompt` event so we can show a custom
 * "Add to Home Screen" UI instead of the default browser banner.
 *
 * Usage:
 *   const { canInstall, installApp, isInstalled } = usePwa()
 */

import { ref, onMounted, onUnmounted } from 'vue'

export function usePwa() {
  /** Whether the install prompt is available (deferred event captured) */
  const canInstall  = ref(false)
  /** Whether the app was recently installed via our prompt */
  const isInstalled = ref(false)
  /** The deferred BeforeInstallPromptEvent */
  let deferredPrompt = null

  const onBeforeInstallPrompt = (e) => {
    // Prevent Chrome 67+ from automatically showing the mini-infobar
    e.preventDefault()
    deferredPrompt = e
    canInstall.value = true
  }

  const onAppInstalled = () => {
    canInstall.value  = false
    isInstalled.value = true
    deferredPrompt    = null
    console.info('[PWA] App installed successfully.')
  }

  onMounted(() => {
    window.addEventListener('beforeinstallprompt', onBeforeInstallPrompt)
    window.addEventListener('appinstalled',        onAppInstalled)

    // If already running as installed PWA (standalone mode)
    if (window.matchMedia('(display-mode: standalone)').matches) {
      isInstalled.value = true
      canInstall.value  = false
    }
  })

  onUnmounted(() => {
    window.removeEventListener('beforeinstallprompt', onBeforeInstallPrompt)
    window.removeEventListener('appinstalled',        onAppInstalled)
  })

  /**
   * Trigger the native install prompt.
   * Resolves with 'accepted' or 'dismissed'.
   */
  const installApp = async () => {
    if (!deferredPrompt) return

    deferredPrompt.prompt()
    const { outcome } = await deferredPrompt.userChoice

    if (outcome === 'accepted') {
      isInstalled.value = true
    }

    canInstall.value  = false
    deferredPrompt    = null

    return outcome
  }

  return { canInstall, isInstalled, installApp }
}
