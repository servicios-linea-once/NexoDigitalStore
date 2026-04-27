<?php

namespace App\Http\Controllers\Admin\SubscriptionRequests;

use App\Http\Controllers\Controller;
use App\Jobs\RecordAuditLog;
use App\Models\SubscriptionRequest;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApproveController extends Controller
{
    public function __invoke(Request $request, SubscriptionRequest $subscriptionRequest): RedirectResponse
    {
        // Prevención de errores: Evitar procesar solicitudes duplicadas
        if ($subscriptionRequest->status !== 'pending') {
            return back()->with('error', 'Esta solicitud ya fue procesada anteriormente.');
        }

        $customer = User::where('email', $subscriptionRequest->customer_email)->first();

        if (! $customer) {
            return back()->with('error', 'El cliente con ese correo no está registrado en el sistema.');
        }

        // Optimización: Carga ansiosa para evitar N+1 en la transacción
        $subscriptionRequest->load('plan');

        DB::transaction(function () use ($subscriptionRequest, $customer, $request) {
            $plan = $subscriptionRequest->plan;

            // Optimización: Cancelamos primero cualquier plan de pago activo anterior para evitar conflictos
            UserSubscription::where('user_id', $customer->id)
                ->where('status', 'active')
                ->whereHas('plan', fn ($q) => $q->where('price_usd', '>', 0))
                ->update(['status' => 'cancelled', 'cancelled_at' => now()]);

            UserSubscription::create([
                'user_id'           => $customer->id,
                'plan_id'           => $plan->id,
                'status'            => 'active',
                'payment_gateway'   => 'manual',
                'payment_reference' => 'req-approved:' . $subscriptionRequest->id,
                'amount_paid'       => 0,
                'currency'          => 'USD',
                'starts_at'         => now(),
                'expires_at'        => $plan->isFree() ? null : now()->addDays($plan->duration_days),
                'auto_renew'        => false,
            ]);

            $subscriptionRequest->update([
                'status'      => 'approved',
                'admin_id'    => $request->user()->id,
                'approved_at' => now(),
            ]);
        });

        RecordAuditLog::dispatch('subscription_request_approved', $request->user()->id, [
            'request_id' => $subscriptionRequest->id,
            'customer'   => $customer->email,
        ]);

        return back()->with('success', 'Suscripción activada y solicitud aprobada.');
    }
}
