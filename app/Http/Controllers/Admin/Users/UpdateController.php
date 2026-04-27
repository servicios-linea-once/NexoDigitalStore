<?php

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use App\Jobs\RecordAuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UpdateController extends Controller
{
    public function __invoke(Request $request, string $id): RedirectResponse
    {
        $this->requirePermission('users.edit');

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', "unique:users,email,{$id}"],
        ]);

        $user = User::findOrFail($id);

        // Asignamos los datos sin guardarlos todavía
        $user->fill($validated);

        // OPTIMIZACIÓN 1: Si no hubo cambios reales, evitamos golpear la BD y la Cola de Redis
        if (! $user->isDirty()) {
            return back()->with('info', 'No se detectaron cambios.');
        }

        // Extraemos solo lo que cambió para que el Log de Auditoría sea más ligero
        $changes = $user->getDirty();

        // Guardamos los cambios
        $user->save();

        // OPTIMIZACIÓN 2: Enviamos la auditoría a la cola en segundo plano
        // e invalidamos la caché del historial pasándole $user->id como 4to parámetro.
        RecordAuditLog::dispatch(
            'admin_user_updated',
            Auth::id(),
            [
                'target_user_id' => $user->id,
                'changes'        => $changes,
            ],
            $user->id
        )->afterCommit();

        return back()->with('success', 'Usuario actualizado correctamente.');
    }
}
