<?php

namespace App\Http\Controllers\Subscriptions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subscriptions\PurchaseSubscriptionRequest;
use App\Jobs\RecordAuditLog;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function __invoke(PurchaseSubscriptionRequest $request): RedirectResponse
    {
        $plan = SubscriptionPlan::where('slug', $request->plan_slug)
            ->where('is_active', true)
            ->firstOrFail();

        if ($plan->isFree()) {
            return back()->with('error', 'El plan Free es gratuito y se asigna automáticamente.');
        }

        $user = $request->user();

        // OPTIMIZACIÓN: Ejecutamos el intercambio en una sola transacción segura
        $sub = DB::transaction(function () use ($user, $plan, $request) {
            // Cancelamos planes de pago activos anteriores
            UserSubscription::where('user_id', $user->id)
                ->where('status', 'active')
                ->whereHas('plan', fn ($q) => $q->where('price_usd', '>', 0))
                ->update(['status' => 'cancelled', 'cancelled_at' => now()]);

            return UserSubscription::create([
                'user_id'           => $user->id,
                'plan_id'           => $plan->id,
                'status'            => 'active',
                'payment_gateway'   => $request->payment_gateway,
                'payment_reference' => $request->payment_reference,
                'amount_paid'       => $plan->price_usd,
                'currency'          => 'USD',
                'starts_at'         => now(),
                'expires_at'        => now()->addDays($plan->duration_days),
                'auto_renew'        => false,
            ]);
        });

        // OPTIMIZACIÓN: Enviamos el log de auditoría a la cola (background)
        RecordAuditLog::dispatch('subscription_purchased', $user->id, [
            'plan'       => $plan->name,
            'expires_at' => $sub->expires_at?->toDateString(),
        ]);

        return redirect()->route('profile.index')
            ->with('success', "¡Suscripción {$plan->name} activada hasta {$sub->expires_at?->format('d/m/Y')}!");
    }
}
