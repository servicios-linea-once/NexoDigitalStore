<?php

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class ShowController extends Controller
{
    public function __invoke(string $id): Response
    {
        $this->requirePermission('users.view');

        // El usuario base con las ordenes
        $user = User::with([
            'wallet',
            'sellerProfile',
            'orders' => fn ($q) => $q->latest('id')->limit(10)->with('items.product:id,name'),
        ])->withCount('orders')->findOrFail($id);

        // Opt: CACHÉ DEL HISTORIAL. Evita golpear la BD en cada refresh.
        // Se invalida automáticamente gracias al Job de auditoría que creamos.
        $auditLogs = Cache::remember("user.{$user->id}.audit_logs", now()->addHours(2), function () use ($user) {
            return AuditLog::where('target_user_id', $user->id)
                ->orWhere('user_id', $user->id)
                ->latest('id')
                ->limit(20)
                ->get();
        });

        return Inertia::render('Admin/Users/Show', [
            'user'      => $user,
            'auditLogs' => $auditLogs,
        ]);
    }
}
