<?php

namespace App\Http\Controllers\Admin\Reviews;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $query = Review::query()->with([
            'user:id,name,email',
            // OPTIMIZACIÓN CRÍTICA: Desactivamos 'coverImage' y 'promotions'
            // que vienen por defecto en el modelo Product.
            // Esto ahorra múltiples consultas SQL innecesarias por cada reseña.
            'product' => fn ($q) => $q->select('id', 'name', 'slug')->without(['coverImage', 'promotions'])
        ]);

        // OPTIMIZACIÓN: Reemplazamos los múltiples `when()` por un `match`
        // mucho más rápido, semántico y fácil de leer.
        match ($request->status) {
            'pending'  => $query->where('is_approved', false)->where('is_flagged', false),
            'flagged'  => $query->where('is_flagged', true),
            'approved' => $query->where('is_approved', true),
            default    => $query, // Si no hay filtro, aplica la consulta general
        };

        $reviews = $query->latest()
            ->paginate(25)
            ->through(fn ($r) => [
                'id'            => $r->id,
                'rating'        => $r->rating,
                'title'         => $r->title,
                'body'          => $r->body,
                'is_approved'   => $r->is_approved,
                'is_flagged'    => $r->is_flagged,
                'flag_reason'   => $r->flag_reason,
                'helpful_count' => $r->helpful_count,
                'seller_reply'  => $r->seller_reply,
                'user'          => ['name' => $r->user?->name, 'email' => $r->user?->email],
                'product'       => ['name' => $r->product?->name, 'slug' => $r->product?->slug],
                'created_at'    => $r->created_at->format('d/m/Y H:i'),
            ]);

        return Inertia::render('Admin/Reviews/Index', [
            'reviews' => $reviews,
            'filters' => $request->only('status'),
        ]);
    }
}
