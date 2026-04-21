/**
 * Inventori IMS — Custom Service Worker
 *
 * Menggabungkan:
 * 1. Workbox precache manifest (di-inject otomatis oleh vite-plugin-pwa)
 * 2. Runtime caching (Inertia, gambar, Google Fonts)
 * 3. Web Push Notification handler (push + notificationclick)
 */

import { clientsClaim }          from 'workbox-core'
import { precacheAndRoute }      from 'workbox-precaching'
import { registerRoute }         from 'workbox-routing'
import { NetworkFirst, CacheFirst, StaleWhileRevalidate } from 'workbox-strategies'
import { ExpirationPlugin }      from 'workbox-expiration'

// ─── Claim clients immediately on activation ────────────────────────────────
self.skipWaiting()
clientsClaim()

// ─── Precache (manifest di-inject oleh vite-plugin-pwa) ─────────────────────
precacheAndRoute(self.__WB_MANIFEST)

// ─── Runtime Caching ─────────────────────────────────────────────────────────

// 1. Inertia responses — NetworkFirst agar data selalu fresh
registerRoute(
  ({ request }) => request.headers.get('X-Inertia') === '1',
  new NetworkFirst({
    cacheName: 'inertia-responses',
    networkTimeoutSeconds: 5,
    plugins: [
      new ExpirationPlugin({ maxEntries: 50, maxAgeSeconds: 300 }),
    ],
  })
)

// 2. Gambar produk — CacheFirst (stable assets)
registerRoute(
  ({ request }) => request.destination === 'image',
  new CacheFirst({
    cacheName: 'images',
    plugins: [
      new ExpirationPlugin({
        maxEntries: 100,
        maxAgeSeconds: 30 * 24 * 60 * 60, // 30 hari
      }),
    ],
  })
)

// 3. Google Fonts — StaleWhileRevalidate
registerRoute(
  ({ url }) => url.origin === 'https://fonts.googleapis.com',
  new StaleWhileRevalidate({ cacheName: 'google-fonts-stylesheets' })
)

// ─── Web Push Notification Handler ──────────────────────────────────────────

/**
 * Event: push
 * Dipanggil saat server mengirim push message.
 * Bekerja saat aplikasi tertutup (background push).
 */
self.addEventListener('push', function (event) {
  // Jangan tampilkan jika izin notifikasi belum diberikan
  if (!self.Notification || self.Notification.permission !== 'granted') {
    return
  }

  let data = {
    title: 'Inventori IMS',
    body:  'Ada notifikasi baru.',
    icon:  '/icons/icon-192x192.png',
    badge: '/icons/icon-96x96.png',
    url:   '/',
    tag:   'inventori-notification',
  }

  if (event.data) {
    try {
      const parsed = event.data.json()
      data = { ...data, ...parsed }
    } catch (e) {
      data.body = event.data.text()
    }
  }

  const notificationOptions = {
    body:    data.body,
    icon:    data.icon,
    badge:   data.badge,
    tag:     data.tag,
    data:    { url: data.url },
    // Vibration pattern: pendek-panjang-pendek (Android)
    vibrate: [100, 50, 100],
    // Tampilkan di notification tray
    requireInteraction: false,
    actions: data.actions || [],
  }

  event.waitUntil(
    self.registration.showNotification(data.title, notificationOptions)
  )
})

/**
 * Event: notificationclick
 * Dipanggil saat pengguna mengetuk notifikasi.
 * Membuka atau memfokuskan window aplikasi ke URL yang dikirim.
 */
self.addEventListener('notificationclick', function (event) {
  event.notification.close()

  const targetUrl = event.notification.data?.url || '/'

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
      // Jika ada window yang sudah terbuka, fokus ke sana dan navigasi
      for (const client of clientList) {
        if ('focus' in client) {
          client.navigate(targetUrl)
          return client.focus()
        }
      }
      // Tidak ada window yang terbuka — buka yang baru
      if (clients.openWindow) {
        return clients.openWindow(targetUrl)
      }
    })
  )
})

/**
 * Event: pushsubscriptionchange
 * Dipanggil saat browser memperbarui subscription secara otomatis.
 * Re-subscribe dan kirim subscription baru ke server.
 */
self.addEventListener('pushsubscriptionchange', function (event) {
  event.waitUntil(
    self.registration.pushManager.subscribe(event.oldSubscription.options)
      .then(function (subscription) {
        // Kirim subscription baru ke backend
        return fetch('/push-subscriptions', {
          method:  'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: JSON.stringify(subscription),
        })
      })
  )
})
