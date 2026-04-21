<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PushSubscriptionController extends Controller
{
    /**
     * Simpan atau perbarui push subscription milik user yang sedang login.
     * Dipanggil oleh frontend setelah user menyetujui notifikasi.
     *
     * POST /push-subscriptions
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint'                => 'required|string|max:500',
            'keys.p256dh'             => 'required|string',
            'keys.auth'               => 'required|string',
            'expirationTime'          => 'nullable',
        ]);

        $user = $request->user();

        // Upsert: satu endpoint = satu subscription
        PushSubscription::updateOrCreate(
            [
                'user_id'  => $user->id,
                'endpoint' => $validated['endpoint'],
            ],
            [
                'public_key'       => $validated['keys']['p256dh'],
                'auth_token'       => $validated['keys']['auth'],
                'content_encoding' => 'aes128gcm',
            ]
        );

        return response()->json(['message' => 'Subscription berhasil disimpan.'], 201);
    }

    /**
     * Hapus push subscription (user mencabut izin notifikasi).
     *
     * DELETE /push-subscriptions
     */
    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => 'required|string|max:500',
        ]);

        $request->user()
                ->pushSubscriptions()
                ->where('endpoint', $validated['endpoint'])
                ->delete();

        return response()->json(['message' => 'Subscription berhasil dihapus.']);
    }

    /**
     * Cek apakah user memiliki subscription aktif (untuk sinkronisasi state UI).
     *
     * GET /push-subscriptions/check
     */
    public function check(Request $request): JsonResponse
    {
        $endpoint = $request->query('endpoint');

        if (!$endpoint) {
            return response()->json(['subscribed' => false]);
        }

        $subscribed = $request->user()
                              ->pushSubscriptions()
                              ->where('endpoint', $endpoint)
                              ->exists();

        return response()->json(['subscribed' => $subscribed]);
    }
}
