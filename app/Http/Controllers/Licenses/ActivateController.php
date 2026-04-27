<?php

namespace App\Http\Controllers\Licenses;

use App\Http\Controllers\Controller;
use App\Http\Requests\Licenses\ActivateLicenseRequest;
use App\Models\OrderItem;
use App\Services\LicenseService;
use Illuminate\Http\JsonResponse;

class ActivateController extends Controller
{
    public function __invoke(ActivateLicenseRequest $request, string $ulid, LicenseService $licenses): JsonResponse
    {
        $user = $request->user();

        $item = OrderItem::where('ulid', $ulid)
            ->whereHas('order', fn ($q) => $q->where('buyer_id', $user->id))
            ->where('delivery_status', 'delivered')
            ->with('digitalKey')
            ->firstOrFail();

        $key = $item->digitalKey;

        if (! $key) {
            return response()->json(['error' => 'No hay clave digital asociada.'], 422);
        }

        try {
            $activation = $licenses->activate($key, $user, $item, $request->validated(), $request->ip());
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json([
            'message'          => 'Dispositivo activado correctamente.',
            'activation_id'    => $activation->ulid,
            'activations_used' => $key->fresh()->activation_count,
            'max_activations'  => $key->max_activations,
        ]);
    }
}
