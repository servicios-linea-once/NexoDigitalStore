<?php

namespace App\Http\Controllers\SubscriptionRequests;

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
        // Optimización: La consulta es más rápida al no tener que filtrar el rol de admin
        $requests = SubscriptionRequest::with('plan:id,name,slug')
            ->where('seller_id', $request->user()->id)
            ->latest()
            ->get();

        // Optimización: Reutilizamos la caché de 24 horas creada en refactorizaciones previas
        $plans = Cache::remember('subscription_plans:active', 86400, function () {
            return SubscriptionPlan::active()->get();
        });

        return Inertia::render('Subscriptions/Requests', [
            'requests' => $requests,
            'plans'    => $plans,
        ]);
    }
}
