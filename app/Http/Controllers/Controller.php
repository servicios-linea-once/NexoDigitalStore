<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Check a Spatie permission and abort 403 if denied.
     * Usage: $this->requirePermission('users.edit');
     */
    protected function requirePermission(string $permission): void
    {
        $user = Auth::user();

        if (! $user || ! $user->hasPermissionTo($permission)) {
            throw new AccessDeniedHttpException(
                "No tienes permiso para realizar esta acción: [{$permission}]"
            );
        }
    }

    /**
     * Check if authenticated user has a Spatie permission (no abort).
     */
    protected function userCan(string $permission): bool
    {
        return Auth::user()?->hasPermissionTo($permission) ?? false;
    }
}
