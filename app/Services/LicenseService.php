<?php

namespace App\Services;

use App\Models\DigitalKey;
use App\Models\LicenseActivation;
use App\Models\OrderItem;
use App\Models\User;
use App\Presenters\StorePresenter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * LicenseService — encapsulates license shaping, activation, and deactivation.
 *
 * Extracted from LicenseController where shapeLicense() was a private method
 * also needed by the API controller (duplication vector).
 */
class LicenseService
{
    public function __construct(
        private readonly StorePresenter $storePresenter
    ) {}

    // ── Shape a license item for frontend ─────────────────────────────────

    /**
     * Builds the frontend-safe representation of a purchased license.
     * Used by both web LicenseController and Api/V1/LicenseController.
     */
    public function shape(OrderItem $item, int $userId, bool $decryptKey = false): array
    {
        $key = $item->digitalKey;

        $keyValue = null;
        if ($key && $decryptKey) {
            try {
                $keyValue = decrypt($key->key_value);
            } catch (\Throwable) {
                $keyValue = $key->key_value; // fallback if stored unencrypted
            }
        }

        return [
            'id'              => $item->id,
            'ulid'            => $item->ulid,
            'product_name'    => $item->product_name,
            'platform'        => $item->product?->platform,
            'region'          => $item->product?->region,
            'cover_image'     => $item->product_cover ?? $item->product?->coverImage?->url,
            'delivery_status' => $item->delivery_status,
            'delivery_type'   => $item->delivery_type,
            'key_value'       => $keyValue,
            'order_ulid'      => $item->order?->ulid,
            'order_ref'       => strtoupper(substr($item->order?->ulid ?? '', -8)),
            'order_item_id'   => $item->id,
            'unit_price'      => (float) $item->unit_price,
            'cashback_amount' => (float) $item->cashback_amount,
            'payment_gateway' => $item->order?->payment_method,
            'seller'          => $this->storePresenter->fromSeller($item->product?->seller),
            'purchased_at'    => $item->created_at?->format('d/m/Y'),
        ];
    }

    // ── Activate a device ─────────────────────────────────────────────────

    /**
     * Registers a new device activation for a digital key.
     *
     * @throws \RuntimeException if the activation limit is reached
     */
    public function activate(
        DigitalKey $key,
        User       $user,
        OrderItem  $item,
        array      $data,
        string     $ip
    ): LicenseActivation {
        // Use max_activations vs activation_count (our tracking column from migration 060002)
        // Falls back to current_activations (column from migration 220855) if activation_count missing
        $used = $key->activation_count ?? $key->current_activations ?? 0;
        $max  = $key->max_activations ?? 1;

        if ($used >= $max) {
            throw new \RuntimeException(
                "Límite de activaciones alcanzado ({$used}/{$max}). Desactiva un dispositivo primero."
            );
        }

        return DB::transaction(function () use ($key, $user, $item, $data, $ip) {
            $activation = LicenseActivation::create([
                'ulid'             => (string) Str::ulid(),
                'digital_key_id'   => $key->id,
                'user_id'          => $user->id,
                'order_item_id'    => $item->id,
                'machine_id'       => $data['machine_id'] ?? null,
                'machine_name'     => $data['machine_name'] ?? null,
                'os'               => $data['os'] ?? null,
                'device_type'      => $data['device_type'] ?? 'other',
                'ip_address'       => $ip,
                'status'           => 'active',
                'activated_at'     => now(),
                'activation_token' => encrypt(Str::random(64)),
            ]);

            $key->increment('activation_count');
            // Also keep current_activations in sync (legacy column from migration 220855)
            $key->increment('current_activations');

            return $activation;
        });
    }

    // ── Deactivate a device ───────────────────────────────────────────────

    public function deactivate(LicenseActivation $activation): void
    {
        DB::transaction(function () use ($activation) {
            $activation->update([
                'status'              => 'deactivated',
                'deactivated_at'      => now(),
                'deactivation_reason' => 'user_request',
            ]);

            DigitalKey::where('id', $activation->digital_key_id)
                ->where('activation_count', '>', 0)
                ->decrement('activation_count');
        });
    }
}
