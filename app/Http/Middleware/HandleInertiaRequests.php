<?php

namespace App\Http\Middleware;

use App\Enums\Permission;
use App\Settings\GeneralSettings;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();
        $generalSettings = app(GeneralSettings::class);

        // Build can[] map using Spatie hasPermissionTo
        $can = [];
        if ($user) {
            foreach (Permission::cases() as $perm) {
                $can[$perm->value] = $user->hasPermissionTo($perm->value);
            }
        }

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id'          => $user->id,
                    'name'        => $user->name,
                    'email'       => $user->email,
                    'role'        => $user->role,              // legacy field (still used in middleware)
                    'roles'       => $user->getRoleNames(),    // Spatie roles
                    'avatar'      => $user->avatar_url,
                    'is_active'   => (bool) ($user->is_active ?? true),
                ] : null,
            ],
            'can'  => $can,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
            ],
            'ui' => fn () => $user?->ui_preferences ?? ['theme' => 'nexo', 'mode' => 'dark', 'locale' => 'es'],
            'ziggy' => fn () => [...(new Ziggy)->toArray(), 'location' => $request->url()],
            // Wishlist count — injected globally for navbar badge
            'wishlistCount' => fn () => $user
                ? \App\Models\Wishlist::where('user_id', $user->id)->count()
                : 0,
            // Settings Globales (Spatie)
            'globalSettings' => $generalSettings->toArray(),
            // [PUNTO-2] Configuración pública de la tienda — expuesta al frontend
            'store' => fn () => \App\Models\StoreSetting::public(),
            // Categorías para el Navbar (Global)
            'navCategories' => fn () => \Illuminate\Support\Facades\Cache::remember('nav_categories', 3600, function () {
                return \App\Models\Category::active()
                    ->root()
                    ->whereNotNull('slug')
                    ->orderBy('sort_order')
                    ->get(['id', 'name', 'slug', 'icon', 'color']);
            }),
        ]);
    }
}
