<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StoreSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * StoreSettingController — Admin panel for Nexo eStore global config.
 *
 * [PUNTO-2] Permite a los administradores editar la configuración de la tienda
 * que antes vivía en SellerProfile (ligada a un usuario).
 *
 * Ruta sugerida: GET/POST /admin/settings
 * Nombre: admin.settings
 */
class StoreSettingController extends Controller
{
    public function index(): Response
    {
        // Group settings by their 'group' field for the admin UI
        $settings = StoreSetting::query()
            ->orderBy('group')
            ->orderBy('key')
            ->get(['id', 'key', 'value', 'type', 'group', 'label', 'description', 'is_public'])
            ->groupBy('group')
            ->map(fn ($group) => $group->map(fn ($s) => [
                'id'          => $s->id,
                'key'         => $s->key,
                'value'       => $s->value,
                'type'        => $s->type,
                'label'       => $s->label ?? $s->key,
                'description' => $s->description,
                'is_public'   => $s->is_public,
            ]));

        return Inertia::render('Admin/StoreSettings', [
            'settings' => $settings,
            'groups'   => ['general', 'commerce', 'notifications', 'legal'],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'settings'       => ['required', 'array'],
            'settings.*.key' => ['required', 'string', 'max:100'],
            'settings.*.value' => ['nullable', 'string'],
        ]);

        foreach ($request->settings as $item) {
            StoreSetting::set($item['key'], $item['value'] ?? null);
        }

        return back()->with('success', '✅ Configuración de la tienda actualizada.');
    }

    /**
     * Quick helper: update a single key via PATCH /admin/settings/{key}
     */
    public function updateOne(Request $request, string $key): RedirectResponse
    {
        $request->validate(['value' => ['nullable', 'string', 'max:1000']]);

        StoreSetting::set($key, $request->value);

        return back()->with('success', "Configuración '{$key}' actualizada.");
    }
}
