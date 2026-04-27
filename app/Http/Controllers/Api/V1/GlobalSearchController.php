<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Searchable\Search;

class GlobalSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $q = $request->get('q');

        if (! $q || strlen($q) < 3) {
            return response()->json([]);
        }

        $results = (new Search())
            ->registerModel(Product::class, 'name', 'slug')
            ->registerModel(User::class, 'name', 'email')
            ->registerModel(Category::class, 'name')
            ->search($q);

        return response()->json($results);
    }
}
