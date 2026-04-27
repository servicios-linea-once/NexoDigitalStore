<?php

namespace App\Http\Controllers\Admin\AuditLogs;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $query = AuditLog::with(['user:id,name,email'])
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('event', 'like', "%{$s}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$s}%"));
            }))
            ->when($request->action, fn ($q, $a) => $q->where('event', $a))
            ->when($request->date,   fn ($q, $d) => $q->whereDate('created_at', $d))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Admin/AuditLogs', [
            'logs'    => $query,
            'filters' => $request->only('search', 'action', 'date'),
        ]);
    }
}
