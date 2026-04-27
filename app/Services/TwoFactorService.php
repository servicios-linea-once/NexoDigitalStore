<?php

namespace App\Services;

use App\Models\TwoFactorAuth;
use App\Models\User;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * TwoFactorService — encapsula la lógica de 2FA TOTP (RFC 6238).
 *
 * Flujo:
 *   1. generateSecret(user) crea un registro no habilitado con secret + recovery codes
 *   2. se muestra QR al usuario
 *   3. confirm(user, otp) valida el OTP y activa is_enabled=true
 *   4. verify(user, otp) se usa en el login para validar el segundo factor
 *   5. disable(user) desactiva y borra el registro
 */
class TwoFactorService
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    public function generateSecret(User $user): TwoFactorAuth
    {
        $secret         = $this->google2fa->generateSecretKey(32);
        $recoveryCodes  = collect(range(1, 8))->map(fn () => strtoupper(Str::random(10)))->toArray();

        return TwoFactorAuth::updateOrCreate(
            ['user_id' => $user->id],
            [
                'secret'         => $secret,
                'recovery_codes' => $recoveryCodes,
                'is_enabled'     => false,
                'enabled_at'     => null,
            ],
        );
    }

    public function qrCodeSvg(User $user, string $secret): string
    {
        $company = config('app.name', 'Nexo');
        $url     = $this->google2fa->getQRCodeUrl($company, $user->email, $secret);

        $renderer = new ImageRenderer(
            new RendererStyle(220, 1),
            new SvgImageBackEnd(),
        );

        return (new Writer($renderer))->writeString($url);
    }

    public function confirm(User $user, string $otp): bool
    {
        $record = TwoFactorAuth::where('user_id', $user->id)->first();

        if (! $record) {
            return false;
        }

        $valid = $this->google2fa->verifyKey($record->secret, $otp);

        if ($valid) {
            $record->update([
                'is_enabled' => true,
                'enabled_at' => now(),
            ]);
        }

        return $valid;
    }

    public function verify(User $user, string $otp): bool
    {
        $record = TwoFactorAuth::where('user_id', $user->id)->where('is_enabled', true)->first();

        if (! $record) {
            return false;
        }

        return $this->google2fa->verifyKey($record->secret, $otp);
    }

    public function disable(User $user): void
    {
        TwoFactorAuth::where('user_id', $user->id)->delete();
    }

    /**
     * Get current OTP for testing purposes.
     * Note: In production, the secret should already be encrypted.
     */
    public function getCurrentOtp(string $secret): string
    {
        return $this->google2fa->getCurrentOtp($secret);
    }
}
