<?php

namespace App\Http\Controllers\Licenses;

use App\Http\Controllers\Controller;
use App\Models\LicenseActivation;
use App\Models\OrderItem;
use App\Services\LicenseService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShowController extends Controller
{
    public function __invoke(Request $request, string $ulid, LicenseService $licenses): Response
    {
        $user = $request->user();

        $item = OrderItem::where('ulid', $ulid)
            ->whereHas('order', fn ($q) => $q->where('buyer_id', $user->id))
            ->with([
                'digitalKey',
                'order:id,ulid,payment_method,buyer_id',
                'order.buyer:id,name',
                'product:id,name,platform,region,activation_guide',
                // FIX: Se eliminó 'product.seller.sellerProfile' porque somos Single-Vendor
                'review:id,order_item_id',
            ])
            ->firstOrFail();

        $key = $item->digitalKey;

        // Optimización: Solo buscamos activaciones si existe la clave digital
        $activations = $key
            ? LicenseActivation::where('digital_key_id', $key->id)
                ->where('user_id', $user->id)
                ->orderByDesc('activated_at')
                ->get()
                ->map(fn ($a) => [
                    'id'           => $a->id,
                    'ulid'         => $a->ulid,
                    'machine_name' => $a->machine_name ?? 'Dispositivo desconocido',
                    'os'           => $a->os,
                    'device_type'  => $a->device_type,
                    'ip_address'   => $a->ip_address,
                    'status'       => $a->status,
                    'activated_at' => $a->activated_at?->format('d/m/Y H:i'),
                    'last_seen_at' => $a->last_seen_at?->diffForHumans(),
                ])
            : collect();

        $license = $licenses->shape($item, $user->id, decryptKey: true);
        $license['activations']      = $activations;
        $license['max_activations']  = $key?->max_activations ?? 1;
        $license['activation_count'] = $key?->activation_count ?? 0;
        $license['activation_guide'] = $item->product?->activation_guide;
        $license['has_review']       = (bool) $item->review;
        $license['order_item_id']    = $item->id;

        return Inertia::render('Licenses/Show', ['license' => $license]);
    }
}
