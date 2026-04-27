<?php

namespace App\Http\Controllers\Subscriptions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subscriptions\RenewSubscriptionRequest;
use App\Jobs\RecordAuditLog;
use App\Models\UserSubscription;
use Illuminate\Http\RedirectResponse;

class RenewController extends Controller
{
    public function __invoke(RenewSubscriptionRequest $request, string $ulid): RedirectResponse
    {
        /** @var \App\Models\UserSubscription $sub */
        $sub = UserSubscription::where('ulid', $ulid)
            ->where('user_id', $request->user()->id)
            ->with('plan')
            ->firstOrFail();

        $plan = $sub->plan;

        if ($plan->isFree()) {
            return back()->with('error', 'El plan Free no necesita renovación.');
        }

        // Calculamos la nueva fecha base
        $base = ($sub->expires_at && $sub->expires_at->isFuture()) ? $sub->expires_at : now();

        // Clonamos la fecha (copy) para no mutar el objeto original en memoria
        $newExpiry = $base->copy()->addDays($plan->duration_days);

        // OPTIMIZACIÓN 1: Usamos fill() y save() en lugar de update() para que el
        // IDE no se confunda y deje de marcar el código de abajo como "inalcanzable".
        $sub->fill([
            'status'            => 'active',
            'expires_at'        => $newExpiry,
            'payment_gateway'   => $request->payment_gateway,
            'payment_reference' => $request->payment_reference,
            'cancelled_at'      => null,
        ])->save();

        // OPTIMIZACIÓN 2: Reutilizamos la variable $newExpiry en memoria.
        // Hemos eliminado las múltiples llamadas a $sub->fresh() que hacían
        // peticiones SQL basura a la base de datos.
        RecordAuditLog::dispatch('subscription_renewed', $request->user()->id, [
            'plan'       => $plan->name,
            'new_expiry' => $newExpiry->toDateString(),
        ]);

        return back()->with('success', "¡Suscripción renovada hasta {$newExpiry->format('d/m/Y')}!");
    }
}
