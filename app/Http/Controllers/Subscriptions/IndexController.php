<?php

namespace App\Http\Controllers\Subscriptions;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class IndexController extends Controller
{
    public function __invoke(Request $request): Response
    {
        // OPTIMIZACIÓN: Cacheamos los planes activos por 24 horas
        $plans = Cache::remember('subscription_plans:active', 86400, function () {
            return SubscriptionPlan::active()->get();
        });

        $activeSub = null;
        if (Auth::check()) {
            $activeSub = $request->user()
                ->activeSubscription()
                ->with('plan')
                ->first();
        }

        return Inertia::render('Subscriptions/Index', [
            'plans'     => $plans,
            'activeSub' => $activeSub,
        ]);
    }
}
