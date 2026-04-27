<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /** All active plans (public) */
    public function plans(): JsonResponse
    {
        $plans = SubscriptionPlan::active()->get([
            'id', 'name', 'slug', 'description', 'features',
            'price_usd', 'price_pen', 'duration_days',
            'discount_percent', 'is_active', 'is_visible',
        ]);

        return response()->json(['data' => $plans]);
    }

    /** Current user's active subscription */
    public function current(Request $request): JsonResponse
    {
        $sub = $request->user()
            ->activeSubscription()
            ->with('plan')
            ->first();

        if (! $sub) {
            return response()->json(['subscription' => null]);
        }

        return response()->json([
            'subscription' => [
                'ulid' => $sub->ulid,
                'plan' => $sub->plan->name,
                'plan_slug' => $sub->plan->slug,
                'discount_percent' => $sub->discountPercent(),
                'status' => $sub->status,
                'starts_at' => $sub->starts_at?->toIso8601String(),
                'expires_at' => $sub->expires_at?->toIso8601String(),
                'days_remaining' => $sub->daysRemaining(),
                'is_lifetime' => $sub->expires_at === null,
            ],
        ]);
    }
}
