<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * PushNotificationService
 *
 * Mengirimkan Web Push Notification menggunakan VAPID authentication.
 * Ini adalah implementasi mandiri yang tidak bergantung pada package pihak ketiga,
 * namun membutuhkan `ext-openssl` dan `guzzlehttp/guzzle` (sudah ada di Laravel).
 *
 * Setelah package laravel-notification-channels/webpush tersedia, kelas ini
 * dapat digantikan dengan WebPushChannel bawaan package tersebut.
 */
class PushNotificationService
{
    protected string $publicKey;
    protected string $privateKey;
    protected string $subject;

    public function __construct()
    {
        $this->publicKey  = config('webpush.vapid.public_key',  '');
        $this->privateKey = config('webpush.vapid.private_key', '');
        $this->subject    = config('webpush.vapid.subject', 'mailto:admin@inventori.app');
    }

    /**
     * Kirim notifikasi ke semua subscription milik seorang user.
     */
    public function sendToUser(User $user, array $payload): void
    {
        $subscriptions = $user->pushSubscriptions()->get();

        foreach ($subscriptions as $subscription) {
            $this->sendToSubscription($subscription, $payload);
        }
    }

    /**
     * Kirim notifikasi ke semua super_admin.
     */
    public function sendToSuperAdmins(array $payload): void
    {
        $admins = User::where('role', 'super_admin')->with('pushSubscriptions')->get();

        foreach ($admins as $admin) {
            $this->sendToUser($admin, $payload);
        }
    }

    /**
     * Kirim notifikasi ke semua user di warehouse tertentu.
     */
    public function sendToWarehouse(int $warehouseId, array $payload): void
    {
        $users = User::where('warehouse_id', $warehouseId)->with('pushSubscriptions')->get();

        foreach ($users as $user) {
            $this->sendToUser($user, $payload);
        }
    }

    /**
     * Kirim notifikasi ke sebuah subscription spesifik.
     * Jika endpoint return 410 Gone, subscription dihapus otomatis.
     */
    public function sendToSubscription(PushSubscription $subscription, array $payload): void
    {
        if (empty($this->publicKey) || empty($this->privateKey)) {
            Log::warning('[WebPush] VAPID keys belum dikonfigurasi. Lewati pengiriman.');
            return;
        }

        $payloadJson = json_encode(array_merge([
            'title' => 'Inventori IMS',
            'body'  => '',
            'icon'  => '/icons/icon-192x192.png',
            'badge' => '/icons/icon-96x96.png',
            'url'   => '/',
        ], $payload));

        try {
            $http     = new \GuzzleHttp\Client(['timeout' => 10]);
            $vapid    = $this->buildVapidHeaders($subscription->endpoint, $payloadJson, $subscription);

            $response = $http->post($subscription->endpoint, [
                'headers' => $vapid['headers'],
                'body'    => $vapid['body'],
            ]);

            if ($response->getStatusCode() === 410) {
                // Browser mencabut subscription — hapus dari DB
                $subscription->delete();
                Log::info('[WebPush] Subscription dihapus (410 Gone): ' . $subscription->endpoint);
            }
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            if ($e->getResponse() && $e->getResponse()->getStatusCode() === 410) {
                $subscription->delete();
            }
            Log::error('[WebPush] Gagal kirim ke ' . $subscription->endpoint . ': ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('[WebPush] Error: ' . $e->getMessage());
        }
    }

    /**
     * Build VAPID-authenticated headers untuk Web Push request.
     * Implementasi sederhana menggunakan JWT manual.
     */
    protected function buildVapidHeaders(string $endpoint, string $payload, PushSubscription $subscription): array
    {
        // Ambil origin dari endpoint
        $parsed = parse_url($endpoint);
        $audience = $parsed['scheme'] . '://' . $parsed['host'];

        $expiration = time() + 43200; // 12 jam

        // JWT Header
        $jwtHeader = base64url_encode(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));

        // JWT Payload
        $jwtPayload = base64url_encode(json_encode([
            'aud' => $audience,
            'exp' => $expiration,
            'sub' => $this->subject,
        ]));

        $signingInput = $jwtHeader . '.' . $jwtPayload;

        // Sign dengan private key ECDSA P-256
        $privateKeyPem = $this->vapidPrivateKeyToPem($this->privateKey);
        $privateKeyRes = openssl_pkey_get_private($privateKeyPem);

        openssl_sign($signingInput, $signature, $privateKeyRes, OPENSSL_ALGO_SHA256);

        $jwt = $signingInput . '.' . base64url_encode($signature);

        $headers = [
            'Authorization'  => 'vapid t=' . $jwt . ', k=' . $this->publicKey,
            'Content-Type'   => 'application/json',
            'Content-Length' => strlen($payload),
            'TTL'            => '86400',
        ];

        return [
            'headers' => $headers,
            'body'    => $payload,
        ];
    }

    /**
     * Konversi VAPID private key (base64url) ke format PEM ECDSA P-256.
     */
    protected function vapidPrivateKeyToPem(string $privateKeyBase64Url): string
    {
        $privateKeyBin = base64url_decode($privateKeyBase64Url);

        // DER encoding untuk EC private key P-256
        $der = "\x30\x77"           // SEQUENCE
             . "\x02\x01\x01"       // version = 1
             . "\x04\x20" . $privateKeyBin  // privateKey OCTET STRING
             . "\xa0\x0a\x06\x08"   // parameters OID
             . "\x2a\x86\x48\xce\x3d\x03\x01\x07";  // P-256 OID

        return "-----BEGIN EC PRIVATE KEY-----\n"
             . chunk_split(base64_encode($der), 64, "\n")
             . "-----END EC PRIVATE KEY-----";
    }
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

if (!function_exists('base64url_encode')) {
    function base64url_encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

if (!function_exists('base64url_decode')) {
    function base64url_decode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
