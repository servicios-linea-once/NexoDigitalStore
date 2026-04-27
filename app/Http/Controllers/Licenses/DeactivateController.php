<?php

namespace App\Http\Controllers\Licenses;

use App\Http\Controllers\Controller;
use App\Models\LicenseActivation;
use App\Services\LicenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeactivateController extends Controller
{
    public function __invoke(Request $request, string $activationUlid, LicenseService $licenses): JsonResponse
    {
        // Usamos firstOrFail para asegurar que el usuario solo desactive sus propias máquinas
        $activation = LicenseActivation::where('ulid', $activationUlid)
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->firstOrFail();

        $licenses->deactivate($activation);

        return response()->json(['message' => 'Dispositivo desactivado. Puedes activar otro ahora.']);
    }
}
