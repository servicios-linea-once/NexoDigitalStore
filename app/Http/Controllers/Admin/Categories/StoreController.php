<?php

namespace App\Http\Controllers\Admin\Categories;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Models\AuditLog;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StoreController extends Controller
{
    public function __invoke(StoreCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug']      = $data['slug'] ?? Str::slug($data['name']);
        $data['is_active'] = true;

        // Optimización: Usamos una transacción para garantizar que ambas inserciones (Categoría y AuditLog)
        // se ejecuten juntas de forma atómica y segura.
        $category = DB::transaction(function () use ($data) {
            $cat = Category::create($data);
            AuditLog::record('admin_category_created', Auth::id(), ['category_id' => $cat->id]);

            return $cat;
        });

        cache()->forget('nav_categories');

        return back()->with('success', "Categoría \"{$category->name}\" creada.");
    }
}
