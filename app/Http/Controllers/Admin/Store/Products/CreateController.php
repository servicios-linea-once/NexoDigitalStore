<?php

namespace App\Http\Controllers\Admin\Store\Products;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * GET /admin/store/products/create
 */
class CreateController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Admin/Store/Products/Create', [
            'categories' => Category::where('is_active', true)
                ->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
                ->whereNull('parent_id')
                ->orderBy('sort_order')
                ->get(['id', 'name', 'slug', 'icon']),
            'platforms' => config('nexo.catalog.platforms', [
                'Steam', 'Epic Games', 'GOG', 'Battle.net', 'PSN', 'Xbox',
                'Nintendo', 'Netflix', 'Spotify', 'Disney+', 'Microsoft', 'Rockstar',
            ]),
            'regions' => config('nexo.catalog.regions', ['Global', 'PE', 'US', 'EU', 'MX', 'CO', 'AR']),
        ]);
    }
}
