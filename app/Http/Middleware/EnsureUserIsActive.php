<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->is_active) {
            auth()->logout();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Tu cuenta ha sido desactivada. Contacta soporte.',
                ], 403);
            }

            return redirect()->route('login')
                ->withErrors(['email' => 'Tu cuenta ha sido desactivada. Contacta soporte.']);
        }

        return $next($request);
    }
}
