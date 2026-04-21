<?php

namespace App\Console\Commands;

use App\Models\PushSubscription;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Console\Command;

class TestPushNotification extends Command
{
    protected $signature   = 'webpush:test {--user= : ID user yang akan menerima notifikasi (default: semua)}';
    protected $description = 'Kirim test push notification untuk memverifikasi konfigurasi VAPID';

    public function handle(PushNotificationService $push): int
    {
        $this->info('🔔 Mengirim test push notification...');

        // Cek VAPID keys
        $publicKey  = config('webpush.vapid.public_key');
        $privateKey = config('webpush.vapid.private_key');

        if (empty($publicKey) || empty($privateKey)) {
            $this->error('VAPID keys belum ada di .env. Jalankan: php artisan webpush:vapid');
            return self::FAILURE;
        }

        $this->line('✅ VAPID Public Key: ' . substr($publicKey, 0, 20) . '...');

        // Cek extension
        foreach (['openssl', 'gmp'] as $ext) {
            if (!extension_loaded($ext)) {
                $this->error("Extension PHP '{$ext}' tidak ditemukan. Push tidak akan berfungsi.");
                return self::FAILURE;
            }
        }
        $this->line('✅ PHP extensions: openssl, gmp tersedia.');

        // Ambil subscription
        $userId = $this->option('user');

        if ($userId) {
            $user = User::find($userId);
            if (!$user) {
                $this->error("User ID {$userId} tidak ditemukan.");
                return self::FAILURE;
            }
            $subscriptions = $user->pushSubscriptions()->get();
        } else {
            $subscriptions = PushSubscription::all();
        }

        if ($subscriptions->isEmpty()) {
            $this->warn('⚠️  Tidak ada subscription yang ditemukan di database.');
            $this->warn('   Pastikan user sudah mengaktifkan push notification di browser.');
            return self::FAILURE;
        }

        $this->line("📋 Ditemukan {$subscriptions->count()} subscription(s).");

        $payload = [
            'title' => '🔔 Test Push Notification',
            'body'  => 'Jika Anda melihat ini, berarti Web Push Notification berhasil! Waktu: ' . now()->format('H:i:s'),
            'icon'  => '/icons/icon-192x192.png',
            'url'   => '/',
            'tag'   => 'webpush-test',
        ];

        foreach ($subscriptions as $subscription) {
            $this->line("  → Mengirim ke endpoint: " . substr($subscription->endpoint, 0, 60) . '...');
            try {
                $push->sendToSubscription($subscription, $payload);
                $this->info("  ✅ Berhasil dikirim ke user ID {$subscription->user_id}");
            } catch (\Exception $e) {
                $this->error("  ❌ Gagal: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info('Selesai. Periksa Laravel log di storage/logs/laravel.log untuk detail.');
        return self::SUCCESS;
    }
}
