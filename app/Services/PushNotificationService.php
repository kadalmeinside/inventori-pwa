<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * PushNotificationService
 *
 * Implementasi Web Push Notification (RFC 8291 + RFC 8292) yang benar:
 * - VAPID JWT dengan ES256 (DER → IEEE P1363 R||S)
 * - Payload encryption: ECDH + HKDF + AES-128-GCM
 * - Menggunakan openssl_pkey_derive() (PHP 8.1+) untuk ECDH
 */
class PushNotificationService
{
    protected string $vapidPublicKey;
    protected string $vapidPrivateKey;
    protected string $subject;

    public function __construct()
    {
        $this->vapidPublicKey  = config('webpush.vapid.public_key',  '');
        $this->vapidPrivateKey = config('webpush.vapid.private_key', '');
        $this->subject         = config('webpush.vapid.subject', 'mailto:admin@inventori.app');
    }

    // ─── Public API ────────────────────────────────────────────────────────────

    public function sendToUser(User $user, array $payload): void
    {
        foreach ($user->pushSubscriptions()->get() as $sub) {
            $this->sendToSubscription($sub, $payload);
        }
    }

    public function sendToSuperAdmins(array $payload): void
    {
        User::where('role', 'super_admin')
            ->with('pushSubscriptions')
            ->get()
            ->each(fn ($u) => $this->sendToUser($u, $payload));
    }

    public function sendToWarehouse(int $warehouseId, array $payload): void
    {
        User::where('warehouse_id', $warehouseId)
            ->with('pushSubscriptions')
            ->get()
            ->each(fn ($u) => $this->sendToUser($u, $payload));
    }

    public function sendToSubscription(PushSubscription $subscription, array $payload): void
    {
        if (empty($this->vapidPublicKey) || empty($this->vapidPrivateKey)) {
            Log::warning('[WebPush] VAPID keys tidak dikonfigurasi.');
            return;
        }

        $body = json_encode(array_merge([
            'title' => 'Inventori IMS',
            'body'  => '',
            'icon'  => '/icons/icon-192x192.png',
            'badge' => '/icons/icon-96x96.png',
            'url'   => '/',
        ], $payload));

        try {
            // 1. Enkripsi payload (RFC 8291)
            $encrypted = $this->encryptPayload(
                $body,
                $subscription->public_key,
                $subscription->auth_token
            );

            // 2. Build VAPID headers (RFC 8292)
            $headers = $this->buildVapidHeaders($subscription->endpoint, strlen($encrypted));

            // 3. Kirim ke push service
            $http = new \GuzzleHttp\Client(['timeout' => 15]);
            $response = $http->post($subscription->endpoint, [
                'headers' => $headers,
                'body'    => $encrypted,
            ]);

            $status = $response->getStatusCode();
            Log::info("[WebPush] Sent → HTTP {$status}");

            if ($status === 410) {
                $subscription->delete();
            }

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $status = $e->getResponse()?->getStatusCode();
            $body   = $e->getResponse()?->getBody()?->getContents();
            if ($status === 410) {
                $subscription->delete();
            }
            Log::error("[WebPush] HTTP {$status}: {$body}");
        } catch (\Throwable $e) {
            Log::error('[WebPush] Error: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
        }
    }

    // ─── RFC 8292: VAPID JWT ───────────────────────────────────────────────────

    protected function buildVapidHeaders(string $endpoint, int $contentLength): array
    {
        $parsed   = parse_url($endpoint);
        $audience = $parsed['scheme'] . '://' . $parsed['host'];

        $header  = $this->b64u(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
        $claims  = $this->b64u(json_encode([
            'aud' => $audience,
            'exp' => time() + 43200,
            'sub' => $this->subject,
        ]));

        $signingInput = $header . '.' . $claims;

        // Load VAPID private key
        $pem        = $this->privateKeyToPem($this->vapidPrivateKey);
        $privateKey = openssl_pkey_get_private($pem);

        if (!$privateKey) {
            throw new \RuntimeException('VAPID private key load failed: ' . openssl_error_string());
        }

        // Sign → DER format, convert to R||S (IEEE P1363) required by JWT ES256
        openssl_sign($signingInput, $derSig, $privateKey, OPENSSL_ALGO_SHA256);
        $jwt = $signingInput . '.' . $this->b64u($this->derToRawP1363($derSig));

        return [
            'Authorization'    => "vapid t={$jwt}, k={$this->vapidPublicKey}",
            'Content-Type'     => 'application/octet-stream',
            'Content-Encoding' => 'aes128gcm',
            'Content-Length'   => (string) $contentLength,
            'TTL'              => '86400',
        ];
    }

    /**
     * DER ECDSA signature → raw R||S (64 bytes) required for JWT ES256.
     * DER: 30 [len] 02 [rlen] [R] 02 [slen] [S]
     */
    protected function derToRawP1363(string $der): string
    {
        $offset = 2; // skip SEQUENCE tag + length

        $offset++;   // skip INTEGER tag 0x02
        $rLen    = ord($der[$offset++]);
        $r       = substr($der, $offset, $rLen);
        $offset += $rLen;

        $offset++;   // skip INTEGER tag 0x02
        $sLen    = ord($der[$offset++]);
        $s       = substr($der, $offset, $sLen);

        // Remove DER leading zero padding, pad to 32 bytes
        return str_pad(ltrim($r, "\x00"), 32, "\x00", STR_PAD_LEFT)
             . str_pad(ltrim($s, "\x00"), 32, "\x00", STR_PAD_LEFT);
    }

    // ─── RFC 8291: Payload Encryption ─────────────────────────────────────────

    /**
     * Encrypt payload using ECDH + HKDF + AES-128-GCM (RFC 8291 aes128gcm).
     */
    protected function encryptPayload(string $plaintext, string $p256dhB64, string $authB64): string
    {
        // Decode browser subscription keys
        $browserPubKeyBin = $this->b64uDecode($p256dhB64); // 65 bytes: 0x04 + X(32) + Y(32)
        $authSecret       = $this->b64uDecode($authB64);   // 16 bytes

        // Generate ephemeral EC P-256 key pair
        $ephemeralKey = openssl_pkey_new([
            'curve_name'       => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ]);

        if (!$ephemeralKey) {
            throw new \RuntimeException('openssl_pkey_new failed: ' . openssl_error_string());
        }

        $ephemeralDetails = openssl_pkey_get_details($ephemeralKey);
        $serverPubKeyBin  = "\x04"
            . str_pad($ephemeralDetails['ec']['x'], 32, "\x00", STR_PAD_LEFT)
            . str_pad($ephemeralDetails['ec']['y'], 32, "\x00", STR_PAD_LEFT);

        // ECDH: compute shared secret using openssl_pkey_derive (PHP 8.1+)
        $browserPubKeyPem = $this->publicKeyToPem($browserPubKeyBin);
        $browserPubKeyRes = openssl_pkey_get_public($browserPubKeyPem);

        if (!$browserPubKeyRes) {
            throw new \RuntimeException('Browser public key invalid: ' . openssl_error_string());
        }

        $sharedSecret = openssl_pkey_derive($browserPubKeyRes, $ephemeralKey);

        if ($sharedSecret === false) {
            throw new \RuntimeException('ECDH key derivation failed: ' . openssl_error_string());
        }

        // Random 16-byte salt
        $salt = random_bytes(16);

        // HKDF key derivation (RFC 8291 §3.3 + §3.4)
        [$cek, $nonce] = $this->hkdfDeriveKeys(
            $sharedSecret,
            $salt,
            $authSecret,
            $browserPubKeyBin,
            $serverPubKeyBin
        );

        // AES-128-GCM encryption
        // RFC 8291 §4: append padding delimiter byte 0x02
        $tag        = '';
        $ciphertext = openssl_encrypt(
            $plaintext . "\x02",
            'aes-128-gcm',
            $cek,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            '',
            16
        );

        if ($ciphertext === false) {
            throw new \RuntimeException('AES-128-GCM encryption failed: ' . openssl_error_string());
        }

        // RFC 8291 §2.1: content coding header
        // salt(16) + record_size(4, big-endian) + key_id_len(1) + server_pub_key(65) + ciphertext + tag
        return $salt
            . pack('N', 4096)           // record size (rs)
            . pack('C', 65)             // keyid length (server public key = 65 bytes)
            . $serverPubKeyBin          // ephemeral server public key
            . $ciphertext               // ciphertext
            . $tag;                     // GCM auth tag (16 bytes)
    }

    /**
     * Derive CEK and Nonce via HKDF (RFC 8291 §3.3 + §3.4).
     */
    protected function hkdfDeriveKeys(
        string $sharedSecret,
        string $salt,
        string $authSecret,
        string $browserPubKey,
        string $serverPubKey
    ): array {
        // Step 1: Extract IKM
        // PRK = HKDF-Extract(auth_secret, shared_secret)
        $prk = hash_hmac('sha256', $sharedSecret, $authSecret, true);

        // IKM = HKDF-Expand(PRK, "WebPush: info\x00" || ua_pub || as_pub, 32)
        $ikmInfo = "WebPush: info\x00" . $browserPubKey . $serverPubKey;
        $ikm     = substr(hash_hmac('sha256', $ikmInfo . "\x01", $prk, true), 0, 32);

        // Step 2: Extract PRK from salt + IKM
        $prkSalt = hash_hmac('sha256', $ikm, $salt, true);

        // Step 3: Expand CEK (16 bytes) and Nonce (12 bytes)
        $cek   = substr(hash_hmac('sha256', "Content-Encoding: aes128gcm\x00\x01", $prkSalt, true), 0, 16);
        $nonce = substr(hash_hmac('sha256', "Content-Encoding: nonce\x00\x01", $prkSalt, true), 0, 12);

        return [$cek, $nonce];
    }

    // ─── Key Format Helpers ────────────────────────────────────────────────────

    /**
     * Build EC private key PEM from raw 32-byte scalar (base64url encoded).
     * ECPrivateKey DER format per RFC 5915, curve P-256.
     */
    protected function privateKeyToPem(string $base64url): string
    {
        $d = str_pad($this->b64uDecode($base64url), 32, "\x00", STR_PAD_LEFT);

        // ECPrivateKey DER (RFC 5915):
        // Content = version(3) + privateKey(34) + parameters[0](12) = 49 bytes = 0x31
        //
        // 30 31       SEQUENCE, length 49
        //   02 01 01  INTEGER version=1
        //   04 20 [d] OCTET STRING, 32-byte private scalar
        //   a0 0a     [0] EXPLICIT, length 10
        //     06 08   OID, length 8
        //     2a 86 48 ce 3d 03 01 07  P-256 OID (1.2.840.10045.3.1.7)

        $der = "\x30\x31"                             // SEQUENCE, length=49 (0x31)
             . "\x02\x01\x01"                        // version = 1
             . "\x04\x20" . $d                       // privateKey (32 bytes)
             . "\xa0\x0a"                            // [0] EXPLICIT, length=10
             . "\x06\x08"                            // OID tag + length=8
             . "\x2a\x86\x48\xce\x3d\x03\x01\x07"; // P-256 OID

        return "-----BEGIN EC PRIVATE KEY-----\n"
             . chunk_split(base64_encode($der), 64, "\n")
             . "-----END EC PRIVATE KEY-----";
    }

    /**
     * Build EC public key PEM from uncompressed point binary (0x04 + X + Y, 65 bytes).
     * SubjectPublicKeyInfo DER format per RFC 5480.
     */
    protected function publicKeyToPem(string $publicKeyBin): string
    {
        // AlgorithmIdentifier: ecPublicKey + P-256 OID
        $oid = "\x30\x13"
             . "\x06\x07\x2a\x86\x48\xce\x3d\x02\x01"     // OID ecPublicKey
             . "\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07"; // OID P-256

        // BIT STRING wrapper (0x00 = no unused bits)
        $bitStr = "\x03" . chr(strlen($publicKeyBin) + 1) . "\x00" . $publicKeyBin;

        $der = "\x30" . chr(strlen($oid) + strlen($bitStr)) . $oid . $bitStr;

        return "-----BEGIN PUBLIC KEY-----\n"
             . chunk_split(base64_encode($der), 64, "\n")
             . "-----END PUBLIC KEY-----";
    }

    // ─── Base64url Helpers ─────────────────────────────────────────────────────

    /** Base64url encode */
    protected function b64u(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /** Base64url decode */
    protected function b64uDecode(string $data): string
    {
        $pad  = 4 - (strlen($data) % 4);
        $data = $pad < 4 ? $data . str_repeat('=', $pad) : $data;
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
