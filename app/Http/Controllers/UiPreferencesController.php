<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Saves UI preferences (theme, mode, locale) for authenticated users.
 * Guests rely on localStorage only (handled client-side).
 */
class UiPreferencesController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'theme'  => ['sometimes', 'string', 'in:nexo,midnight,ocean,ember'],
            'mode'   => ['sometimes', 'string', 'in:dark,light'],
            'locale' => ['sometimes', 'string', 'in:es,en'],
        ]);

        if (empty($validated)) {
            return response()->json(['ok' => true]);
        }

        // OPTIMIZACIÓN: Mapeo dinámico y limpio.
        // Convierte ['theme' => 'dark'] en ['ui_theme' => 'dark'] automáticamente,
        // eliminando la necesidad de escribir múltiples "if (isset(...))".
        $mappedData = collect($validated)
            ->mapWithKeys(fn ($value, $key) => ["ui_{$key}" => $value])
            ->toArray();

        // forceFill() aplica los datos de forma segura sin importar si están
        // en el array $fillable del modelo User, y save() ejecuta 1 solo query.
        $request->user()->forceFill($mappedData)->save();

        return response()->json(['ok' => true]);
    }
}
