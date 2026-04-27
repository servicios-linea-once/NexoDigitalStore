<?php

namespace App\Http\Controllers\Admin\Categories;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class IndexController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $categories = QueryBuilder::for(Category::class)
            ->root()
            ->allowedFilters([
                AllowedFilter::partial('global', 'name'),
                'slug',
                'is_active',
            ])
            ->allowedSorts(['id', 'name', 'sort_order'])
            ->defaultSort('sort_order')
            ->with([
                'children' => fn ($query) => $query->select(['id', 'parent_id', 'name', 'slug', 'is_active', 'sort_order', 'icon', 'color'])
            ])
            ->withCount('products')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Categories/Index', [
            'categories' => $categories,
            'filters'    => $request->only(['filter', 'sort']),
        ]);
    }
}
