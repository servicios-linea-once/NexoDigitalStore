<?php

namespace App\Http\Controllers\Admin\SubscriptionRequests;

use App\Http\Controllers\Controller;
use App\Jobs\RecordAuditLog;
use App\Models\SubscriptionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RejectController extends Controller
{
    public function __invoke(Request $request, SubscriptionRequest $subscriptionRequest): RedirectResponse
    {
        if ($subscriptionRequest->status !== 'pending') {
            return back()->with('error', 'Esta solicitud ya fue procesada anteriormente.');
        }

        $subscriptionRequest->update([
            'status'      => 'rejected',
            'admin_id'    => $request->user()->id,
            'admin_notes' => $request->notes, // Permite guardar la razón del rechazo
        ]);

        RecordAuditLog::dispatch('subscription_request_rejected', $request->user()->id, [
            'request_id' => $subscriptionRequest->id,
            'notes'      => $request->notes,
        ]);

        return back()->with('success', 'Solicitud rechazada.');
    }
}
