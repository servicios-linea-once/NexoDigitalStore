<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::query()
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")
            )
            ->when($request->role, fn ($q, $r) => $q->where('role', $r))
            ->when($request->status !== null, fn ($q) => $q->where('is_active', $request->status === 'active')
            )
            ->withCount('orders')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'role', 'status']),
        ]);
    }

    public function show(string $id): Response
    {
        $user = User::with([
            'wallet',
            'orders' => fn ($q) => $q->latest()->limit(10),
            'sellerProfile',
        ])->findOrFail($id);

        $auditLogs = AuditLog::where('user_id', $user->id)
            ->latest()
            ->limit(20)
            ->get();

        return Inertia::render('Admin/Users/Show', [
            'user' => $user,
            'auditLogs' => $auditLogs,
        ]);
    }

    public function toggleStatus(string $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        // Prevent disabling own account
        if ($user->id === Auth::id()) {
            return back()->with('error', 'No puedes desactivar tu propia cuenta.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        AuditLog::record(
            $user->is_active ? 'admin_user_activated' : 'admin_user_suspended',
            Auth::id(),
            ['target_user_id' => $user->id]
        );

        return back()->with('success', $user->is_active ? 'Usuario activado.' : 'Usuario suspendido.');
    }

    public function updateRole(UpdateUserRoleRequest $request, string $id): RedirectResponse
    {

        $user = User::findOrFail($id);
        $oldRole = $user->role;
        $user->update(['role' => $request->role]);

        AuditLog::record('admin_role_changed', Auth::id(), [
            'target_user_id' => $user->id,
            'old_role' => $oldRole,
            'new_role' => $request->role,
        ]);

        return back()->with('success', "Rol actualizado a {$request->role}.");
    }

    public function destroy(string $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        AuditLog::record('admin_user_deleted', Auth::id(), ['target_email' => $user->email]);

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Usuario eliminado.');
    }
}
