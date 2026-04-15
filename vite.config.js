import { defineConfig } from 'vite'
import laravel          from 'laravel-vite-plugin'
import vue              from '@vitejs/plugin-vue'
import { VitePWA }      from 'vite-plugin-pwa'

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.js'],
      refresh: true,
    }),

    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false,
        },
      },
    }),

    VitePWA({
      // Register the service worker automatically
      registerType: 'autoUpdate',

      // Use Workbox for caching strategies
      workbox: {
        // Cache all static assets
        globPatterns: ['**/*.{js,css,html,ico,png,svg,woff,woff2}'],

        // Runtime caching rules
        runtimeCaching: [
          {
            // Cache Inertia API responses (JSON) with NetworkFirst
            urlPattern: ({ request }) => request.headers.get('X-Inertia') === '1',
            handler: 'NetworkFirst',
            options: {
              cacheName:        'inertia-responses',
              networkTimeoutSeconds: 5,
              expiration: {
                maxEntries:       50,
                maxAgeSeconds:    300, // 5 minutes
              },
            },
          },
          {
            // Cache product images
            urlPattern: /\.(?:png|jpg|jpeg|webp|svg|gif)$/,
            handler: 'CacheFirst',
            options: {
              cacheName: 'images',
              expiration: {
                maxEntries:       100,
                maxAgeSeconds: 30 * 24 * 60 * 60, // 30 days
              },
            },
          },
          {
            // Cache Google Fonts
            urlPattern: /^https:\/\/fonts\.googleapis\.com/,
            handler: 'StaleWhileRevalidate',
            options: { cacheName: 'google-fonts-stylesheets' },
          },
        ],
      },

      // Web App Manifest
      manifest: {
        name:             'Inventori IMS',
        short_name:       'Inventori',
        description:      'Multi-warehouse Inventory Management System',
        theme_color:      '#f2f4f8',
        background_color: '#f2f4f8',
        display:          'standalone',
        orientation:      'portrait-primary',
        scope:            '/',
        start_url:        '/',
        lang:             'id',

        icons: [
          { src: '/icons/icon-72x72.png',   sizes: '72x72',   type: 'image/png', purpose: 'any' },
          { src: '/icons/icon-96x96.png',   sizes: '96x96',   type: 'image/png', purpose: 'any' },
          { src: '/icons/icon-128x128.png', sizes: '128x128', type: 'image/png', purpose: 'any' },
          { src: '/icons/icon-144x144.png', sizes: '144x144', type: 'image/png', purpose: 'any' },
          { src: '/icons/icon-152x152.png', sizes: '152x152', type: 'image/png', purpose: 'any' },
          { src: '/icons/icon-192x192.png', sizes: '192x192', type: 'image/png', purpose: 'any maskable' },
          { src: '/icons/icon-384x384.png', sizes: '384x384', type: 'image/png', purpose: 'any maskable' },
          { src: '/icons/icon-512x512.png', sizes: '512x512', type: 'image/png', purpose: 'any maskable' },
        ],

        shortcuts: [
          {
            name:       'Dashboard',
            short_name: 'Home',
            url:        '/',
            icons: [{ src: '/icons/icon-96x96.png', sizes: '96x96' }],
          },
          {
            name:       'Stock',
            short_name: 'Stock',
            url:        '/stocks',
            icons: [{ src: '/icons/icon-96x96.png', sizes: '96x96' }],
          },
          {
            name:       'Transfers',
            short_name: 'Transfer',
            url:        '/transfers',
            icons: [{ src: '/icons/icon-96x96.png', sizes: '96x96' }],
          },
        ],
      },

      // Dev options: enable PWA in development for testing
      devOptions: {
        enabled:    true,
        type:       'module',
      },
    }),
  ],

  resolve: {
    alias: {
      '@': '/resources/js',
    },
  },
})
