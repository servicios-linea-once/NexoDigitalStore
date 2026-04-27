<?php

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use App\Jobs\RecordAuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ToggleStatusController extends Controller
{
    public function __invoke(string $id): RedirectResponse
    {
        $this->requirePermission('users.edit');

        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return back()->with('error', 'No puedes suspender tu propia cuenta.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        // Opt: Cola en segundo plano. ->afterCommit() asegura que el Job
        // solo se dispare si la base de datos se actualizó correctamente.
        RecordAuditLog::dispatch(
            $user->is_active ? 'admin_user_activated' : 'admin_user_suspended',
            Auth::id(),
            ['target_user_id' => $user->id],
            $user->id
        )->afterCommit();

        return back()->with('success', $user->is_active ? 'Usuario activado.' : 'Usuario suspendido.');
    }
}
