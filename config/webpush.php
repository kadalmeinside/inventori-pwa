<?php

return [
    /*
    |--------------------------------------------------------------------------
    | VAPID Keys for Web Push Notifications
    |--------------------------------------------------------------------------
    |
    | VAPID (Voluntary Application Server Identification) keys digunakan untuk
    | mengautentikasi server Anda ke layanan push browser (Google, Apple, dll).
    |
    | Generate keys dengan command:
    |   php artisan webpush:vapid
    |
    | Atau gunakan library web-push-libs/web-push untuk PHP.
    | Setelah generate, masukkan nilai ke .env Anda.
    |
    */

    'vapid' => [
        'subject'     => env('VAPID_SUBJECT', 'mailto:admin@inventori.app'),
        'public_key'  => env('VAPID_PUBLIC_KEY', ''),
        'private_key' => env('VAPID_PRIVATE_KEY', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default TTL (Time to Live)
    |--------------------------------------------------------------------------
    |
    | Berapa lama (dalam detik) push service menyimpan notifikasi jika
    | perangkat sedang offline. Default: 4 hari.
    |
    */

    'default_ttl' => env('WEBPUSH_TTL', 345600),
];
