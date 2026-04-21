<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateVapidKeys extends Command
{
    protected $signature   = 'webpush:vapid';
    protected $description = 'Generate VAPID keys untuk Web Push Notification (EC P-256)';

    public function handle(): int
    {
        if (!extension_loaded('openssl')) {
            $this->error('Extension OpenSSL tidak ditemukan. Pastikan OpenSSL tersedia.');
            return self::FAILURE;
        }

        // Generate EC P-256 key pair
        $key = openssl_pkey_new([
            'curve_name'       => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ]);

        if (!$key) {
            $this->error('Gagal membuat EC key pair: ' . openssl_error_string());
            return self::FAILURE;
        }

        $details    = openssl_pkey_get_details($key);

        if (!isset($details['ec'])) {
            $this->error('Gagal mendapatkan detail EC key.');
            return self::FAILURE;
        }

        $ecDetails = $details['ec'];

        // Pad ke 32 bytes (P-256 requirement)
        $xBin = str_pad($ecDetails['x'], 32, "\x00", STR_PAD_LEFT);
        $yBin = str_pad($ecDetails['y'], 32, "\x00", STR_PAD_LEFT);
        $dBin = str_pad($ecDetails['d'], 32, "\x00", STR_PAD_LEFT);

        // Uncompressed point format: 0x04 + X + Y (65 bytes total)
        $publicKeyBin = "\x04" . $xBin . $yBin;

        $vapidPublicKey  = rtrim(strtr(base64_encode($publicKeyBin), '+/', '-_'), '=');
        $vapidPrivateKey = rtrim(strtr(base64_encode($dBin), '+/', '-_'), '=');

        $this->info('✅ VAPID Keys berhasil dibuat!');
        $this->newLine();
        $this->line('Tambahkan baris berikut ke file <comment>.env</comment> Anda:');
        $this->newLine();
        $this->line("VAPID_PUBLIC_KEY={$vapidPublicKey}");
        $this->line("VAPID_PRIVATE_KEY={$vapidPrivateKey}");
        $this->newLine();
        $this->line('Dan tambahkan ke <comment>.env.example</comment> (tanpa nilai):');
        $this->newLine();
        $this->line('VAPID_PUBLIC_KEY=');
        $this->line('VAPID_PRIVATE_KEY=');
        $this->newLine();
        $this->line('Tambahkan juga ke <comment>VITE</comment> environment agar bisa diakses frontend:');
        $this->newLine();
        $this->line("VITE_VAPID_PUBLIC_KEY={$vapidPublicKey}");
        $this->newLine();

        // Tawarkan untuk langsung menulis ke .env
        if ($this->confirm('Tulis langsung ke file .env sekarang?', true)) {
            $envPath    = base_path('.env');
            $envContent = file_get_contents($envPath);

            $keysToAdd = [
                'VAPID_PUBLIC_KEY'      => $vapidPublicKey,
                'VAPID_PRIVATE_KEY'     => $vapidPrivateKey,
                'VITE_VAPID_PUBLIC_KEY' => $vapidPublicKey,
            ];

            foreach ($keysToAdd as $key => $value) {
                if (str_contains($envContent, $key . '=')) {
                    // Update existing
                    $envContent = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $envContent);
                } else {
                    // Append
                    $envContent .= "\n{$key}={$value}";
                }
            }

            file_put_contents($envPath, $envContent);
            $this->info('✅ Keys berhasil ditulis ke .env');
        }

        return self::SUCCESS;
    }
}
