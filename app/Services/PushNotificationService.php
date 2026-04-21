<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * PushNotificationService
 *
 * Implementasi Web Push Notification dengan VAPID authentication yang benar:
 * - JWT signing menggunakan ES256 (ECDSA P-256 + SHA-256)
 * - Signature dikonversi dari DER ke IEEE P1363 format (R||S) — wajib untuk JWT
 * - Payload dienkripsi menggunakan Content Encryption Key (ECDH + AES-128-GCM)
 * - Sesuai RFC 8291 (Message Encryption) dan RFC 8292 (VAPID)
 *
 * Tidak membutuhkan package eksternal, hanya ext-openssl dan ext-mbstring.
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

    // ─── Public Send Methods ───────────────────────────────────────────────────

    public function sendToUser(User $user, array $payload): void
    {
        foreach ($user->pushSubscriptions()->get() as $subscription) {
            $this->sendToSubscription($subscription, $payload);
        }
    }

    public function sendToSuperAdmins(array $payload): void
    {
        $admins = User::where('role', 'super_admin')->with('pushSubscriptions')->get();
        foreach ($admins as $admin) {
            $this->sendToUser($admin, $payload);
        }
    }

    public function sendToWarehouse(int $warehouseId, array $payload): void
    {
        $users = User::where('warehouse_id', $warehouseId)->with('pushSubscriptions')->get();
        foreach ($users as $user) {
            $this->sendToUser($user, $payload);
        }
    }

    public function sendToSubscription(PushSubscription $subscription, array $payload): void
    {
        if (empty($this->vapidPublicKey) || empty($this->vapidPrivateKey)) {
            Log::warning('[WebPush] VAPID keys tidak dikonfigurasi.');
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
            // 1. Enkripsi payload sesuai RFC 8291
            $encrypted = $this->encryptPayload(
                $payloadJson,
                $subscription->public_key,
                $subscription->auth_token
            );

            // 2. Build VAPID JWT headers sesuai RFC 8292
            $headers = $this->buildVapidHeaders($subscription->endpoint, $encrypted);

            // 3. Kirim ke Push Service endpoint
            $http = new \GuzzleHttp\Client(['timeout' => 15, 'verify' => true]);
            $response = $http->post($subscription->endpoint, [
                'headers' => $headers,
                'body'    => $encrypted['ciphertext'],
            ]);

            Log::info('[WebPush] Terkirim ke ' . substr($subscription->endpoint, 0, 60) . '... Status: ' . $response->getStatusCode());

            if ($response->getStatusCode() === 410) {
                $subscription->delete();
            }

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $statusCode = $e->getResponse()?->getStatusCode();
            if ($statusCode === 410) {
                $subscription->delete();
                Log::info('[WebPush] Subscription expired (410), dihapus.');
            } else {
                $body = $e->getResponse()?->getBody()?->getContents();
                Log::error("[WebPush] HTTP {$statusCode} ke endpoint: " . $body);
            }
        } catch (\Exception $e) {
            Log::error('[WebPush] Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
        }
    }

    // ─── RFC 8292: VAPID JWT ───────────────────────────────────────────────────

    /**
     * Build VAPID Authorization header dan headers push request yang lengkap.
     */
    protected function buildVapidHeaders(string $endpoint, array $encrypted): array
    {
        $parsed   = parse_url($endpoint);
        $audience = $parsed['scheme'] . '://' . $parsed['host'];

        // JWT Header + Payload
        $jwtHeader  = $this->base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
        $jwtPayload = $this->base64UrlEncode(json_encode([
            'aud' => $audience,
            'exp' => time() + 43200,
            'sub' => $this->subject,
        ]));

        $signingInput = $jwtHeader . '.' . $jwtPayload;

        // Sign dengan private key — hasil DER harus dikonversi ke R||S (IEEE P1363)
        $privateKeyPem = $this->buildPrivateKeyPem($this->vapidPrivateKey);
        $privateKeyRes = openssl_pkey_get_private($privateKeyPem);

        if (!$privateKeyRes) {
            throw new \RuntimeException('Gagal load VAPID private key: ' . openssl_error_string());
        }

        openssl_sign($signingInput, $derSignature, $privateKeyRes, OPENSSL_ALGO_SHA256);

        // Konversi DER signature → raw R||S (64 bytes) — WAJIB untuk ES256 JWT
        $rawSignature = $this->derToRaw($derSignature);

        $jwt = $signingInput . '.' . $this->base64UrlEncode($rawSignature);

        return [
            'Authorization'    => 'vapid t=' . $jwt . ', k=' . $this->vapidPublicKey,
            'Content-Type'     => 'application/octet-stream',
            'Content-Encoding' => 'aes128gcm',
            'Content-Length'   => strlen($encrypted['ciphertext']),
            'TTL'              => '86400',
        ];
    }

    /**
     * Konversi DER-encoded ECDSA signature ke raw R||S format (IEEE P1363).
     *
     * DER format:  30 len 02 rLen [r bytes] 02 sLen [s bytes]
     * P1363 format: r (32 bytes) || s (32 bytes) = 64 bytes total
     */
    protected function derToRaw(string $der): string
    {
        // Skip: SEQUENCE tag (0x30) + length
        $offset = 2;

        // R component
        $offset++;          // skip INTEGER tag (0x02)
        $rLen    = ord($der[$offset++]);
        $r       = substr($der, $offset, $rLen);
        $offset += $rLen;

        // S component
        $offset++;          // skip INTEGER tag (0x02)
        $sLen    = ord($der[$offset++]);
        $s       = substr($der, $offset, $sLen);

        // Pad/trim ke 32 bytes masing-masing (leading zero dari DER harus dibuang)
        $r = ltrim($r, "\x00");
        $s = ltrim($s, "\x00");

        return str_pad($r, 32, "\x00", STR_PAD_LEFT)
             . str_pad($s, 32, "\x00", STR_PAD_LEFT);
    }

    // ─── RFC 8291: Payload Encryption (aes128gcm) ─────────────────────────────

    /**
     * Enkripsi payload dengan ECDH-ES + AES-128-GCM sesuai RFC 8291.
     *
     * @param string $payload    Plaintext JSON payload
     * @param string $p256dhB64  Browser public key (base64url, uncompressed point)
     * @param string $authB64    Browser auth secret (base64url)
     * @return array{ciphertext: string, salt: string, serverPublicKey: string}
     */
    protected function encryptPayload(string $payload, string $p256dhB64, string $authB64): array
    {
        // Decode browser keys
        $browserPublicKeyBin = $this->base64UrlDecode($p256dhB64);
        $authSecret          = $this->base64UrlDecode($authB64);

        // Generate ephemeral EC key pair untuk enkripsi ini
        $ephemeralKey     = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        $ephemeralDetails = openssl_pkey_get_details($ephemeralKey);

        // Ephemeral public key dalam uncompressed point format (0x04 + X + Y)
        $xBin = str_pad($ephemeralDetails['ec']['x'], 32, "\x00", STR_PAD_LEFT);
        $yBin = str_pad($ephemeralDetails['ec']['y'], 32, "\x00", STR_PAD_LEFT);
        $serverPublicKeyBin = "\x04" . $xBin . $yBin;

        // ECDH: shared secret dengan browser's public key
        $browserPubKeyPem = $this->buildPublicKeyPem($browserPublicKeyBin);
        $browserPubKeyRes = openssl_pkey_get_public($browserPubKeyPem);
        openssl_pkey_export($ephemeralKey, $ephemeralPrivatePem);
        $ephemeralPrivRes = openssl_pkey_get_private($ephemeralPrivatePem);

        // Compute ECDH shared secret
        $sharedSecret = $this->ecdhSharedSecret($ephemeralPrivRes, $browserPubKeyBin);

        // Random 16-byte salt
        $salt = random_bytes(16);

        // RFC 8291 §3.4: HKDF key derivation
        [$contentEncryptionKey, $nonce] = $this->deriveKeys($sharedSecret, $salt, $authSecret, $browserPublicKeyBin, $serverPublicKeyBin);

        // Padding: tambah 0x02 byte di akhir payload (RFC 8291 padding)
        $paddedPayload = $payload . "\x02";

        // AES-128-GCM encryption
        $tag        = '';
        $ciphertext = openssl_encrypt($paddedPayload, 'aes-128-gcm', $contentEncryptionKey, OPENSSL_RAW_DATA, $nonce, $tag);

        // RFC 8291 §2.1: content coding header
        // salt (16) + rs (4, = 4096) + keyidlen (1) + keyid (65)
        $rs        = pack('N', 4096);
        $keyIdLen  = pack('C', strlen($serverPublicKeyBin));
        $header    = $salt . $rs . $keyIdLen . $serverPublicKeyBin;

        return [
            'ciphertext'      => $header . $ciphertext . $tag,
            'salt'            => $salt,
            'serverPublicKey' => $serverPublicKeyBin,
        ];
    }

    /**
     * HKDF key derivation sesuai RFC 8291 §3.3 dan §3.4.
     */
    protected function deriveKeys(string $sharedSecret, string $salt, string $authSecret, string $browserPublicKey, string $serverPublicKey): array
    {
        // PRK — ikm = HKDF-Extract(auth_secret, shared_secret)
        $prk = hash_hmac('sha256', $sharedSecret, $authSecret, true);

        // info = "WebPush: info\x00" + receiver_pub + sender_pub
        $keyInfoPlain = "WebPush: info\x00" . $browserPublicKey . $serverPublicKey;

        // IKM = HKDF-Expand(prk, key_info, 32)
        $ikm = hash_hmac('sha256', $keyInfoPlain . "\x01", $prk, true);

        // CEK (content encryption key) — 16 bytes
        $cekInfo = "Content-Encoding: aes128gcm\x00";
        $prkCek  = hash_hmac('sha256', $ikm, $salt, true);
        $cek     = substr(hash_hmac('sha256', $cekInfo . "\x01", $prkCek, true), 0, 16);

        // Nonce — 12 bytes
        $nonceInfo = "Content-Encoding: nonce\x00";
        $nonce     = substr(hash_hmac('sha256', $nonceInfo . "\x01", $prkCek, true), 0, 12);

        return [$cek, $nonce];
    }

    /**
     * Hitung ECDH shared secret menggunakan private key dan browser's public key.
     */
    protected function ecdhSharedSecret($privateKeyRes, string $peerPublicKeyBin): string
    {
        // Ambil koordinat X, Y dari uncompressed public key (0x04 + X + Y)
        $x = substr($peerPublicKeyBin, 1, 32);
        $y = substr($peerPublicKeyBin, 33, 32);

        // Build PEM untuk peer public key
        $peerPem = $this->buildPublicKeyPem($peerPublicKeyBin);
        $peerKey = openssl_pkey_get_public($peerPem);

        // openssl_dh_compute_key tidak tersedia untuk EC di semua versi PHP
        // Gunakan pendekatan: extract private scalar dan hitung secara manual
        $privDetails = openssl_pkey_get_details($privateKeyRes);
        $d = str_pad($privDetails['ec']['d'], 32, "\x00", STR_PAD_LEFT);

        // Gunakan PHP's openssl_pkey untuk ECDH via export dan re-import
        // Alternatif: gunakan metode P-256 manual
        return $this->p256EcdhCompute($privDetails['ec']['d'], $x, $y);
    }

    /**
     * Hitung ECDH shared secret pada kurva P-256.
     * Menggunakan PHP GMP untuk aritmatika titik elips.
     */
    protected function p256EcdhCompute(string $privateKeyBin, string $peerXBin, string $peerYBin): string
    {
        // Parameter kurva P-256
        $p = gmp_init('FFFFFFFF00000001000000000000000000000000FFFFFFFFFFFFFFFFFFFFFFFF', 16);
        $a = gmp_init('FFFFFFFF00000001000000000000000000000000FFFFFFFFFFFFFFFFFFFFFFFC', 16);

        $privateKey = gmp_import($privateKeyBin);
        $peerX      = gmp_import($peerXBin);
        $peerY      = gmp_import($peerYBin);

        // Point multiplication: shared = d * P_peer
        [$sharedX] = $this->pointMultiply($privateKey, [$peerX, $peerY], $p, $a);

        // Return X coordinate sebagai shared secret (32 bytes, big-endian)
        $hex = gmp_strval($sharedX, 16);
        return hex2bin(str_pad($hex, 64, '0', STR_PAD_LEFT));
    }

    /**
     * Elliptic curve point multiplication menggunakan double-and-add.
     * Operates on affine coordinates modulo p.
     */
    protected function pointMultiply(\GMP $k, array $point, \GMP $p, \GMP $a): array
    {
        $result = null;
        $addend = $point;

        $kBin = gmp_strval($k, 2);
        foreach (str_split($kBin) as $bit) {
            if ($result !== null) {
                $result = $this->pointDouble($result, $p, $a);
            }
            if ($bit === '1') {
                $result = ($result === null) ? $addend : $this->pointAdd($result, $addend, $p);
            }
        }

        return $result;
    }

    protected function pointAdd(array $P, array $Q, \GMP $p): array
    {
        [$x1, $y1] = $P;
        [$x2, $y2] = $Q;

        $lam = gmp_mod(
            gmp_mul(gmp_sub($y2, $y1), gmp_invert(gmp_sub($x2, $x1), $p)),
            $p
        );
        $x3 = gmp_mod(gmp_sub(gmp_sub(gmp_mul($lam, $lam), $x1), $x2), $p);
        $y3 = gmp_mod(gmp_sub(gmp_mul($lam, gmp_sub($x1, $x3)), $y1), $p);

        return [gmp_mod($x3, $p), gmp_mod($y3, $p)];
    }

    protected function pointDouble(array $P, \GMP $p, \GMP $a): array
    {
        [$x1, $y1] = $P;

        $lam = gmp_mod(
            gmp_mul(
                gmp_add(gmp_mul(gmp_init(3), gmp_mul($x1, $x1)), $a),
                gmp_invert(gmp_mul(gmp_init(2), $y1), $p)
            ),
            $p
        );
        $x3 = gmp_mod(gmp_sub(gmp_mul($lam, $lam), gmp_mul(gmp_init(2), $x1)), $p);
        $y3 = gmp_mod(gmp_sub(gmp_mul($lam, gmp_sub($x1, $x3)), $y1), $p);

        return [gmp_mod($x3, $p), gmp_mod($y3, $p)];
    }

    // ─── Key Format Helpers ────────────────────────────────────────────────────

    /**
     * Bangun EC private key PEM dari raw 32-byte scalar (base64url encoded).
     */
    protected function buildPrivateKeyPem(string $privateKeyBase64Url): string
    {
        $d = $this->base64UrlDecode($privateKeyBase64Url);

        // ECPrivateKey DER (RFC 5915) untuk P-256 — tanpa public key section
        $der = "\x30\x41"                  // SEQUENCE (65 bytes)
             . "\x02\x01\x01"             // version = 1
             . "\x04\x20" . str_pad($d, 32, "\x00", STR_PAD_LEFT)  // privateKey (32 bytes)
             . "\xa0\x0a\x06\x08"         // [0] OID tag
             . "\x2a\x86\x48\xce\x3d\x03\x01\x07"; // P-256 OID (1.2.840.10045.3.1.7)

        return "-----BEGIN EC PRIVATE KEY-----\n"
             . chunk_split(base64_encode($der), 64, "\n")
             . "-----END EC PRIVATE KEY-----";
    }

    /**
     * Bangun EC public key PEM dari uncompressed point binary (0x04 + X + Y).
     */
    protected function buildPublicKeyPem(string $publicKeyBin): string
    {
        // SubjectPublicKeyInfo DER untuk EC P-256
        $oid = "\x30\x13"                         // SEQUENCE
             . "\x06\x07\x2a\x86\x48\xce\x3d\x02\x01"  // OID ecPublicKey
             . "\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07"; // OID P-256

        $bitString = "\x03" . chr(strlen($publicKeyBin) + 1) . "\x00" . $publicKeyBin;
        $der = "\x30" . chr(strlen($oid) + strlen($bitString)) . $oid . $bitString;

        return "-----BEGIN PUBLIC KEY-----\n"
             . chunk_split(base64_encode($der), 64, "\n")
             . "-----END PUBLIC KEY-----";
    }

    // ─── Base64url Helpers ─────────────────────────────────────────────────────

    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    protected function base64UrlDecode(string $data): string
    {
        $padding = 4 - (strlen($data) % 4);
        if ($padding < 4) {
            $data .= str_repeat('=', $padding);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
