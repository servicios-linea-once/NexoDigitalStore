<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GrantSubscriptionRequest;
use App\Http\Requests\Admin\UpdatePlanRequest;
use App\Models\AuditLog;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    // ── List all subscriptions ─────────────────────────────────────────────
    public function index(Request $request): Response
    {
        $subs = UserSubscription::with(['user:id,name,email,role', 'plan'])
            ->when($request->search, function ($q, $s) {
                $q->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                );
            })
            ->when($request->plan, fn ($q, $p) => $q->whereHas('plan', fn ($pl) => $pl->where('slug', $p))
            )
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $plans = SubscriptionPlan::withCount('subscriptions')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'price_usd', 'price_pen', 'discount_percent', 'is_active', 'duration_days']);

        // Stats
        $stats = [
            'total_active' => UserSubscription::where('status', 'active')->count(),
            'total_paid' => UserSubscription::where('status', 'active')
                ->whereHas('plan', fn ($q) => $q->where('price_usd', '>', 0))
                ->count(),
            'expiring_soon' => UserSubscription::where('status', 'active')
                ->whereNotNull('expires_at')
                ->whereBetween('expires_at', [now(), now()->addDays(7)])
                ->count(),
            'monthly_revenue' => UserSubscription::where('status', 'active')
                ->where('created_at', '>=', now()->startOfMonth())
                ->sum('amount_paid'),

            // Counts per plan slug
            'free_count'     => UserSubscription::where('status', 'active')->whereHas('plan', fn($q) => $q->where('slug', 'free'))->count(),
            'pro_count'      => UserSubscription::where('status', 'active')->whereHas('plan', fn($q) => $q->where('slug', 'pro'))->count(),
            'business_count' => UserSubscription::where('status', 'active')->whereHas('plan', fn($q) => $q->where('slug', 'business'))->count(),
            'ultimate_count' => UserSubscription::where('status', 'active')->whereHas('plan', fn($q) => $q->where('slug', 'ultimate'))->count(),
        ];

        return Inertia::render('Admin/Subscriptions/Index', [
            'subscriptions' => $subs,
            'plans' => $plans,
            'stats' => $stats,
            'filters' => $request->only(['search', 'plan', 'status']),
        ]);
    }

    // ── Admin assigns a plan to a user (manual activation) ────────────────
    public function assign(GrantSubscriptionRequest $request): RedirectResponse
    {

        // Frontend sends user_search (email) — resolve to user_id
        $user = User::where('email', $request->user_search)
            ->orWhere('name', 'like', "%{$request->user_search}%")
            ->firstOrFail();
        $plan = SubscriptionPlan::findOrFail($request->plan_id);

        // Cancel all existing active paid subscriptions
        UserSubscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->whereHas('plan', fn ($q) => $q->where('price_usd', '>', 0))
            ->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        $days = $request->days ?? $plan->duration_days;
        $expiresAt = $plan->isFree() ? null : now()->addDays($days);

        $sub = UserSubscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'payment_gateway' => 'manual',
            'payment_reference' => 'admin-assigned:'.$request->user()->id,
            'amount_paid' => 0,   // admin gift
            'currency' => 'USD',
            'starts_at' => now(),
            'expires_at' => $expiresAt,
            'auto_renew' => false,
        ]);

        AuditLog::record('admin_subscription_assigned', $request->user()->id, null, [
            'target_user' => $user->email,
            'plan' => $plan->name,
            'expires_at' => $expiresAt?->toDateString(),
            'note' => $request->note,
        ]);

        return back()->with('success', "Plan {$plan->name} asignado a {$user->name}.");
    }

    // ── Admin revokes a subscription (sets to expired immediately) ────────
    public function revoke(Request $request, int $id): RedirectResponse
    {
        $sub = UserSubscription::with('user', 'plan')->findOrFail($id);

        if ($sub->plan->isFree()) {
            return back()->with('error', 'No se puede revocar el plan Free.');
        }

        $sub->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'expires_at' => now(), // effective immediately
        ]);

        AuditLog::record('admin_subscription_revoked', $request->user()->id, null, [
            'target_user' => $sub->user->email,
            'plan' => $sub->plan->name,
        ]);

        return back()->with('success', "Suscripción revocada para {$sub->user->name}.");
    }

    // ── Manage plans (create/update) ──────────────────────────────────────
    public function updatePlan(Request $request, int $id): RedirectResponse
    {
        $plan = SubscriptionPlan::findOrFail($id);

        $validated = $request->validate([
            'price_usd'        => ['required', 'numeric', 'min:0'],
            'price_pen'        => ['nullable', 'numeric', 'min:0'],
            'discount_percent' => ['required', 'numeric', 'min:0', 'max:50'],
            'is_active'        => ['boolean'],
            'is_visible'       => ['boolean'],
            'duration_days'    => ['required', 'integer', 'min:0'],
        ]);

        $plan->update($validated);

        AuditLog::record('admin_plan_updated', $request->user()->id, null, [

            'plan' => $plan->name, 'changes' => $validated,
        ]);

        return back()->with('success', "Plan {$plan->name} actualizado.");
    }
}
