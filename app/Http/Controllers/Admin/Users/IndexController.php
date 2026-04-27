<?php

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class IndexController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $users = QueryBuilder::for(User::class)
            ->select(['id', 'name', 'email', 'role', 'is_active', 'created_at'])
            ->allowedFilters(
                AllowedFilter::partial('search', 'name'),
                'email',
                'role',
                AllowedFilter::exact('status', 'is_active')->ignore(null),
                AllowedFilter::callback('global', function ($query, $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('name', 'like', "%{$value}%")
                          ->orWhere('email', 'like', "%{$value}%");
                    });
                })
            )
            ->allowedSorts('id', 'name', 'created_at')
            ->defaultSort('-id')
            ->withCount('orders')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users'   => $users,
            'filters' => $request->only(['filter', 'sort']),
        ]);
    }
}
