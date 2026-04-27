<?php

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use App\Jobs\RecordAuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DestroyController extends Controller
{
    public function __invoke(string $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        DB::transaction(function () use ($user) {
            $user->delete();
        });

        // Opt: Cola en background. No enviamos targetUserId porque el usuario ya no existe.
        RecordAuditLog::dispatch(
            'admin_user_deleted',
            Auth::id(),
            ['target_email' => $user->email]
        )->afterCommit();

        // Opt: Limpiar limpieza de caché residual
        Cache::forget("user.{$user->id}.audit_logs");

        return redirect()->route('admin.users.index')->with('success', 'Usuario eliminado.');
    }
}
