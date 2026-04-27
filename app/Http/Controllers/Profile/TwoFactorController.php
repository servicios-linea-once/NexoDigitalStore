<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\TwoFactorAuth;
use App\Services\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TwoFactorController extends Controller
{
    public function __construct(private readonly TwoFactorService $service) {}

    /**
     * Genera un nuevo secret y QR. Aún no habilita 2FA: requiere confirm().
     */
    public function enable(Request $request): JsonResponse
    {
        $user   = $request->user();
        $record = $this->service->generateSecret($user);
        $qrSvg  = $this->service->qrCodeSvg($user, $record->secret);

        return response()->json([
            'qr_code'        => $qrSvg,
            'secret'         => $record->secret,
            'recovery_codes' => $record->recovery_codes,
        ]);
    }

    /**
     * Confirma activación validando el primer OTP.
     */
    public function confirm(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        if (! $this->service->confirm($request->user(), $request->input('code'))) {
            return back()->withErrors(['code' => 'Código OTP inválido o expirado.']);
        }

        AuditLog::record('2fa_enabled', $request->user()->id);

        return back()->with('success', '2FA activado correctamente.');
    }

    /**
     * Desactiva 2FA borrando el registro.
     */
    public function disable(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $this->service->disable($request->user());

        AuditLog::record('2fa_disabled', $request->user()->id);

        return back()->with('success', '2FA desactivado.');
    }
}
