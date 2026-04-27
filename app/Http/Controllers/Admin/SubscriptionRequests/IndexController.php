<?php

namespace App\Http\Controllers\Admin\SubscriptionRequests;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class IndexController extends Controller
{
    public function __invoke(Request $request): Response
    {
        // Optimización: Paginamos para que la base de datos no colapse si hay miles de solicitudes
        $requests = SubscriptionRequest::with(['seller:id,name,email', 'plan:id,name'])
            ->latest()
            ->paginate(20);

        $plans = Cache::remember('subscription_plans:active', 86400, function () {
            return SubscriptionPlan::active()->get();
        });

        return Inertia::render('Subscriptions/Requests', [
            'requests' => $requests,
            'plans'    => $plans,
        ]);
    }
}
