<?php

namespace App\Http\Controllers\Admin\Categories;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DestroyController extends Controller
{
    public function __invoke(string $id): RedirectResponse
    {
        $category = Category::findOrFail($id);

        // GRAN OPTIMIZACIÓN: En lugar de withCount('products'), usamos exists().
        // La BD detiene la búsqueda tan pronto encuentra 1 producto, ahorrando un 'table scan'
        if ($category->products()->exists()) {
            // Solo hacemos el count si vamos a armar el mensaje de error.
            $count = $category->products()->count();
            return back()->with('error', "No se puede eliminar: tiene {$count} productos.");
        }

        DB::transaction(function () use ($category) {
            AuditLog::record('admin_category_deleted', Auth::id(), ['category_name' => $category->name]);
            $category->delete();
        });

        cache()->forget('nav_categories');

        return back()->with('success', 'Categoría eliminada.');
    }
}
