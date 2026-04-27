<?php

namespace App\Http\Controllers\Admin\Categories;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Models\AuditLog;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UpdateController extends Controller
{
    public function __invoke(StoreCategoryRequest $request, string $id): RedirectResponse
    {
        $category = Category::findOrFail($id);
        $data = $request->validated();

        DB::transaction(function () use ($category, $data) {
            $category->update($data);
            AuditLog::record('admin_category_updated', Auth::id(), ['category_id' => $category->id]);
        });

        cache()->forget('nav_categories');

        return back()->with('success', 'Categoría actualizada.');
    }
}
