<?php

namespace App\Http\Controllers\Subscriptions;

use App\Http\Controllers\Controller;
use App\Jobs\RecordAuditLog;
use App\Models\UserSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CancelController extends Controller
{
    public function __invoke(Request $request, string $ulid): RedirectResponse
    {
        $sub = UserSubscription::where('ulid', $ulid)
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->with('plan') // Optimizando carga
            ->firstOrFail();

        if ($sub->plan->isFree()) {
            return back()->with('error', 'No puedes cancelar el plan Free.');
        }

        $sub->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        // Job a la cola
        RecordAuditLog::dispatch('subscription_cancelled', $request->user()->id, [
            'plan' => $sub->plan->name,
        ]);

        return back()->with('success', 'Suscripción cancelada. Tu plan Free sigue activo.');
    }
}
