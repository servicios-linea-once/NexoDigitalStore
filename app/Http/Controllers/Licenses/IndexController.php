<?php

namespace App\Http\Controllers\Licenses;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Services\LicenseService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexController extends Controller
{
    public function __invoke(Request $request, LicenseService $licenses): Response
    {
        $user = $request->user();

        $items = OrderItem::whereHas('order', fn ($q) => $q->where('buyer_id', $user->id))
            ->where('delivery_status', 'delivered')
            ->with(['digitalKey', 'order:id,ulid,payment_method', 'product:id,name,platform,region,cover_image'])
            ->latest('delivered_at')
            ->get()
            ->map(fn ($item) => $licenses->shape($item, $user->id));

        return Inertia::render('Licenses/Index', ['licenses' => $items]);
    }
}
