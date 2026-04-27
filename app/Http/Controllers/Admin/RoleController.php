<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    /**
     * Lista de roles con estadísticas de usuarios y permisos.
     */
    public function index(): Response
    {
        $roles = Role::with('permissions')->get()->map(function ($role) {
            $stats = User::role($role->name)->selectRaw('COUNT(*) as total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active')->first();
            
            return [
                'id'          => $role->id,
                'name'        => $role->name,
                'total'       => (int) $stats->total,
                'active'      => (int) $stats->active,
                'permissions' => $role->permissions->pluck('name'),
                'color'       => $this->getRoleColor($role->name),
            ];
        });

        return Inertia::render('Admin/Roles/Index', [
            'roles'        => $roles,
            'allPermissions' => Permission::all()->pluck('name'),
            'recentChanges' => AuditLog::where('event', 'admin_role_changed')
                ->with('user:id,name,email')
                ->latest()->limit(10)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:50', 'unique:roles,name'],
            'permissions' => ['nullable', 'array'],
        ]);

        DB::transaction(function () use ($validated) {
            $role = Role::create(['name' => $validated['name'], 'guard_name' => 'web']);
            if (!empty($validated['permissions'])) {
                $role->syncPermissions($validated['permissions']);
            }
        });

        return back()->with('success', "Rol '{$validated['name']}' creado correctamente.");
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $role = Role::findOrFail($id);
        
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:50', Rule::unique('roles', 'name')->ignore($id)],
            'permissions' => ['nullable', 'array'],
        ]);

        if ($role->name === 'admin' && $validated['name'] !== 'admin') {
            return back()->with('error', 'No puedes renombrar el rol de administrador base.');
        }

        DB::transaction(function () use ($role, $validated) {
            $role->update(['name' => $validated['name']]);
            if (isset($validated['permissions'])) {
                $role->syncPermissions($validated['permissions']);
            }
        });

        return back()->with('success', "Rol actualizado correctamente.");
    }

    public function destroy(int $id): RedirectResponse
    {
        $role = Role::findOrFail($id);

        if (in_array($role->name, ['admin', 'buyer', 'seller'])) {
            return back()->with('error', 'Los roles base del sistema no pueden ser eliminados.');
        }

        if (User::role($role->name)->exists()) {
            return back()->with('error', 'No puedes eliminar un rol que tiene usuarios asignados.');
        }

        $role->delete();

        return back()->with('success', "Rol eliminado.");
    }

    public function assign(Request $request): RedirectResponse
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'role'    => ['required', 'exists:roles,name'],
        ]);

        $user = User::findOrFail($request->user_id);
        
        if ($user->id === Auth::id()) {
            return back()->with('error', 'No puedes cambiar tu propio rol.');
        }

        $oldRole = $user->role; // asumiendo que mantienes el campo legacy
        $user->syncRoles([$request->role]);
        $user->update(['role' => $request->role]);

        AuditLog::record('admin_role_changed', Auth::id(), [
            'target_user_id' => $user->id,
            'old_role'       => $oldRole,
            'new_role'       => $request->role,
        ]);

        return back()->with('success', "Rol de {$user->name} actualizado a {$request->role}.");
    }

    public function bulkAssign(Request $request): RedirectResponse
    {
        $request->validate([
            'user_ids'   => ['required', 'array', 'min:1'],
            'user_ids.*' => ['exists:users,id'],
            'role'       => ['required', 'exists:roles,name'],
        ]);

        $users = User::whereIn('id', $request->user_ids)->where('id', '!=', Auth::id())->get();

        foreach ($users as $user) {
            $user->syncRoles([$request->role]);
            $user->update(['role' => $request->role]);
        }

        return back()->with('success', "{$users->count()} usuarios actualizados.");
    }

    private function getRoleColor(string $name): string
    {
        return match ($name) {
            'admin'  => 'danger',
            'seller' => 'success',
            'buyer'  => 'info',
            default  => 'primary',
        };
    }
}
