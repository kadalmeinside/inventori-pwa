<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="#f2f4f8">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="Inventori">
        <meta name="robots" content="noindex, nofollow">

        <title inertia>{{ config('app.name', 'Inventori IMS') }}</title>

        <!-- PWA Manifest -->
        <link rel="manifest" href="/manifest.webmanifest">

        <!-- App Icons -->
        <link rel="icon" type="image/png" href="/logo-inventory.png">
        <link rel="apple-touch-icon" href="/logo-inventory.png">

        <!-- Apple Splash Screens (portrait) -->
        <meta name="apple-mobile-web-app-capable" content="yes">
        <link rel="apple-touch-startup-image"
              media="screen and (device-width: 430px) and (device-height: 932px) and (-webkit-device-pixel-ratio: 3)"
              href="/splash/splash-1290x2796.png">
        <link rel="apple-touch-startup-image"
              media="screen and (device-width: 393px) and (device-height: 852px) and (-webkit-device-pixel-ratio: 3)"
              href="/splash/splash-1179x2556.png">
        <link rel="apple-touch-startup-image"
              media="screen and (device-width: 390px) and (device-height: 844px) and (-webkit-device-pixel-ratio: 3)"
              href="/splash/splash-1170x2532.png">
        <link rel="apple-touch-startup-image"
              media="screen and (device-width: 375px) and (device-height: 812px) and (-webkit-device-pixel-ratio: 3)"
              href="/splash/splash-1125x2436.png">
        <link rel="apple-touch-startup-image"
              media="screen and (device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 2)"
              href="/splash/splash-828x1792.png">

        <!-- Fonts: Inter -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Ziggy Routes + Vite Assets -->
        @routes
        @vite(['resources/css/app.css', 'resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead

        <style>
            /* ── HTML Splash Screen (shows before Vue hydrates) ──────────────── */
            #app-splash {
                position: fixed;
                inset: 0;
                z-index: 9999;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                background: #f2f4f8;
                gap: 1.25rem;
                transition: opacity 0.5s ease, transform 0.5s ease;
            }
            #app-splash.splash-hidden {
                opacity: 0;
                transform: scale(1.04);
                pointer-events: none;
            }
            .splash-logo {
                width: 96px;
                height: 96px;
                border-radius: 22px;
                box-shadow:
                    0 16px 48px rgba(0, 80, 200, 0.18),
                    0 4px 12px rgba(0, 0, 0, 0.10),
                    inset 0 1.5px 0 rgba(255,255,255,0.9);
                animation: splash-pulse 1.6s ease-in-out infinite;
                object-fit: contain;
                background: white;
                padding: 10px;
            }
            .splash-name {
                font-family: 'Inter', system-ui, sans-serif;
                font-size: 1.375rem;
                font-weight: 800;
                letter-spacing: -0.04em;
                color: rgba(0,0,0,0.82);
                margin: 0;
            }
            .splash-tagline {
                font-family: 'Inter', system-ui, sans-serif;
                font-size: 0.8rem;
                font-weight: 500;
                color: rgba(0,0,0,0.38);
                margin: 0;
                letter-spacing: 0.05em;
                text-transform: uppercase;
            }
            .splash-loader {
                width: 32px;
                height: 3px;
                border-radius: 999px;
                background: rgba(0,0,0,0.08);
                overflow: hidden;
                margin-top: 0.5rem;
            }
            .splash-loader::after {
                content: '';
                display: block;
                height: 100%;
                width: 40%;
                border-radius: 999px;
                background: linear-gradient(90deg, #007AFF, #5AC8FA);
                animation: splash-bar 1.4s ease-in-out infinite;
            }
            @keyframes splash-pulse {
                0%, 100% { transform: scale(1);    box-shadow: 0 16px 48px rgba(0,80,200,0.18), 0 4px 12px rgba(0,0,0,0.10), inset 0 1.5px 0 rgba(255,255,255,0.9); }
                50%       { transform: scale(1.04); box-shadow: 0 24px 64px rgba(0,80,200,0.28), 0 8px 20px rgba(0,0,0,0.12), inset 0 1.5px 0 rgba(255,255,255,0.9); }
            }
            @keyframes splash-bar {
                0%   { transform: translateX(-100%); }
                50%  { transform: translateX(160%); }
                100% { transform: translateX(400%); }
            }
        </style>
    </head>
    <body style="margin:0; background:#f2f4f8; font-family:'Inter',system-ui,sans-serif;">

        <!-- ── Splash Screen ─────────────────────────────────────────────── -->
        <div id="app-splash" aria-hidden="true">
            <img src="/logo-inventory.png" alt="Inventori Logo" class="splash-logo">
            <p class="splash-name">Inventori IMS</p>
            <p class="splash-tagline">Multi-Warehouse Management</p>
            <div class="splash-loader"></div>
        </div>

        <!-- ── Vue App ───────────────────────────────────────────────────── -->
        @inertia

        <script>
            // Hide splash when Vue app is mounted (Inertia fires 'inertia:finish')
            document.addEventListener('inertia:finish', function () {
                var splash = document.getElementById('app-splash');
                if (splash) {
                    splash.classList.add('splash-hidden');
                    setTimeout(function () { splash.remove(); }, 600);
                }
            });
            // Fallback: force hide after 3s
            setTimeout(function () {
                var splash = document.getElementById('app-splash');
                if (splash) {
                    splash.classList.add('splash-hidden');
                    setTimeout(function () { if(splash.parentNode) splash.remove(); }, 600);
                }
            }, 3000);
        </script>
    </body>
</html>
