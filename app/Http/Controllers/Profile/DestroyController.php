<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\DeleteAccountRequest;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DestroyController extends Controller
{
    public function __invoke(DeleteAccountRequest $request): RedirectResponse
    {
        $user = $request->user();

        // DB Transaction para mantener consistencia durante la eliminación
        DB::transaction(function () use ($user) {
            AuditLog::record('account_deleted', $user->id);
            $user->delete(); // Llama a SoftDeletes (si lo tiene) o eliminación en cascada
        });

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Tu cuenta ha sido eliminada.');
    }
}
