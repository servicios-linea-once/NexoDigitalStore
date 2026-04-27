<?php

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use App\Jobs\RecordAuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UpdateRoleController extends Controller
{
    public function __invoke(Request $request, string $id): RedirectResponse
    {
        $this->requirePermission('users.role');

        $request->validate([
            'role' => ['required', Rule::in(['admin', 'seller', 'buyer'])],
        ]);

        $user    = User::findOrFail($id);
        $oldRole = $user->role;

        if ($user->id === Auth::id()) {
            return back()->with('error', 'No puedes cambiar tu propio rol.');
        }

        // Opt: Usamos transacción para garantizar integridad al dar permisos y crear el perfil
        DB::transaction(function () use ($user, $request) {
            $user->update(['role' => $request->role]);
            $user->syncRoles([$request->role]);

            if ($request->role === 'seller') {
                $user->sellerProfile()->firstOrCreate(
                    ['user_id' => $user->id],
                    ['store_name' => $user->name, 'rating' => 0]
                );
            }
        });

        // Opt: Registro a la cola
        RecordAuditLog::dispatch('admin_role_changed', Auth::id(), [
            'target_user_id' => $user->id,
            'old_role'       => $oldRole,
            'new_role'       => $request->role,
        ], $user->id)->afterCommit();

        return back()->with('success', "Rol de {$user->name} actualizado a {$request->role}.");
    }
}
