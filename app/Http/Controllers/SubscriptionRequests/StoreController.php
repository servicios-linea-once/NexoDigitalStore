<?php

namespace App\Http\Controllers\SubscriptionRequests;

use App\Http\Controllers\Controller;
use App\Jobs\RecordAuditLog;
use App\Models\SubscriptionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_email' => ['required', 'email'],
            'plan_id'        => ['required', 'exists:subscription_plans,id'],
        ]);

        $subReq = SubscriptionRequest::create([
            'seller_id'      => $request->user()->id,
            'customer_email' => $validated['customer_email'],
            'plan_id'        => $validated['plan_id'],
            'status'         => 'pending',
        ]);

        // Optimización: Registro de auditoría asíncrono (Job)
        RecordAuditLog::dispatch('subscription_request_created', $request->user()->id, [
            'request_id'     => $subReq->id,
            'customer_email' => $validated['customer_email'],
        ]);

        return back()->with('success', 'Solicitud enviada al administrador correctamente.');
    }
}
