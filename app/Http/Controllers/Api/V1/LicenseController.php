<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DigitalKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    /** List all digital keys purchased by the user */
    public function index(Request $request): JsonResponse
    {
        $keys = DigitalKey::with('product:id,ulid,name,slug,platform,region')
            ->where('buyer_id', $request->user()->id)
            ->where('status', 'sold')
            ->latest('sold_at')
            ->paginate(20);

        return response()->json([
            'data' => $keys->map(fn ($k) => [
                'ulid' => $k->ulid,
                'product' => $k->product,
                'key_value' => $k->key_value,
                'status' => $k->status,
                'sold_at' => $k->sold_at?->toIso8601String(),
            ]),
            'meta' => [
                'total' => $keys->total(),
                'current_page' => $keys->currentPage(),
                'last_page' => $keys->lastPage(),
            ],
        ]);
    }

    /** Show a single key detail */
    public function show(Request $request, string $ulid): JsonResponse
    {
        $key = DigitalKey::with('product:id,ulid,name,slug,platform,region,activation_guide')
            ->where('ulid', $ulid)
            ->where('buyer_id', $request->user()->id)
            ->firstOrFail();

        return response()->json([
            'ulid' => $key->ulid,
            'product' => $key->product,
            'key_value' => $key->key_value,
            'status' => $key->status,
            'sold_at' => $key->sold_at?->toIso8601String(),
            'activation_guide' => $key->product?->activation_guide,
        ]);
    }

    /** Activate a license on a machine */
    public function activate(Request $request, string $ulid): JsonResponse
    {
        $request->validate([
            'machine_id' => ['required', 'string', 'max:255'],
            'machine_name' => ['nullable', 'string', 'max:100'],
        ]);

        $key = DigitalKey::where('ulid', $ulid)
            ->where('buyer_id', $request->user()->id)
            ->firstOrFail();

        $activation = $key->activations()->create([
            'user_id' => $request->user()->id,
            'machine_id' => $request->machine_id,
            'machine_name' => $request->machine_name,
            'ip_address' => $request->ip(),
            'is_active' => true,
            'last_seen_at' => now(),
        ]);

        return response()->json([
            'message' => 'Licencia activada.',
            'activation' => $activation,
        ], 201);
    }

    /** Deactivate a license from a machine */
    public function deactivate(Request $request, string $ulid): JsonResponse
    {
        $request->validate(['machine_id' => ['required', 'string']]);

        $key = DigitalKey::where('ulid', $ulid)
            ->where('buyer_id', $request->user()->id)
            ->firstOrFail();

        $key->activations()
            ->where('machine_id', $request->machine_id)
            ->update(['is_active' => false]);

        return response()->json(['message' => 'Licencia desactivada en este dispositivo.']);
    }

    /** Heartbeat — update last_seen_at for license tracking */
    public function heartbeat(Request $request, string $ulid): JsonResponse
    {
        $request->validate(['machine_id' => ['required', 'string']]);

        $key = DigitalKey::where('ulid', $ulid)
            ->where('buyer_id', $request->user()->id)
            ->firstOrFail();

        $key->activations()
            ->where('machine_id', $request->machine_id)
            ->where('is_active', true)
            ->update(['last_seen_at' => now()]);

        return response()->json(['ok' => true, 'timestamp' => now()->toIso8601String()]);
    }
}
