<?php

namespace App\Http\Controllers\Admin\Store;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PromotionController extends Controller
{
    public function index(Request $request): Response
    {
        $promotions = Promotion::withCount('products')->latest()->paginate(15);

        return Inertia::render('Admin/Store/Promotions/Index', [
            'promotions' => $promotions,
        ]);
    }

    public function create(Request $request): Response
    {
        $products = Product::active()->get(['id', 'name']);

        return Inertia::render('Admin/Store/Promotions/Create', [
            'products' => $products,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'discount_type'  => ['required', 'in:percent,fixed_usd,fixed_pen'],
            'discount_value' => ['required', 'numeric', 'min:0.01'],
            'start_date'     => ['nullable', 'date'],
            'end_date'       => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active'      => ['boolean'],
            'products'       => ['nullable', 'array'],
            'products.*'     => ['exists:products,id'],
        ]);

        $promotion = Promotion::create([
            'seller_id'      => $request->user()->id,
            'name'           => $validated['name'],
            'discount_type'  => $validated['discount_type'],
            'discount_value' => $validated['discount_value'],
            'start_date'     => $validated['start_date'] ?? null,
            'end_date'       => $validated['end_date'] ?? null,
            'is_active'      => $validated['is_active'] ?? true,
        ]);

        if (! empty($validated['products'])) {
            $promotion->products()->sync($validated['products']);
        }

        return redirect()->route('admin.store.promotions.index')
            ->with('success', 'Promoción creada correctamente.');
    }

    public function edit(Request $request, Promotion $promotion): Response
    {
        $products = Product::active()->get(['id', 'name']);
        $promotion->load('products:id');

        return Inertia::render('Admin/Store/Promotions/Edit', [
            'promotion' => [
                'id'               => $promotion->id,
                'name'             => $promotion->name,
                'discount_type'    => $promotion->discount_type,
                'discount_value'   => $promotion->discount_value,
                'start_date'       => $promotion->start_date ? $promotion->start_date->format('Y-m-d\TH:i') : null,
                'end_date'         => $promotion->end_date   ? $promotion->end_date->format('Y-m-d\TH:i')   : null,
                'is_active'        => $promotion->is_active,
                'selected_products'=> $promotion->products->pluck('id')->toArray(),
            ],
            'products' => $products,
        ]);
    }

    public function update(Request $request, Promotion $promotion): RedirectResponse
    {
        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'discount_type'  => ['required', 'in:percent,fixed_usd,fixed_pen'],
            'discount_value' => ['required', 'numeric', 'min:0.01'],
            'start_date'     => ['nullable', 'date'],
            'end_date'       => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active'      => ['boolean'],
            'products'       => ['nullable', 'array'],
            'products.*'     => ['exists:products,id'],
        ]);

        $promotion->update([
            'name'           => $validated['name'],
            'discount_type'  => $validated['discount_type'],
            'discount_value' => $validated['discount_value'],
            'start_date'     => $validated['start_date'] ?? null,
            'end_date'       => $validated['end_date'] ?? null,
            'is_active'      => $validated['is_active'] ?? true,
        ]);

        $promotion->products()->sync($validated['products'] ?? []);

        return redirect()->route('admin.store.promotions.index')
            ->with('success', 'Promoción actualizada correctamente.');
    }

    public function destroy(Request $request, Promotion $promotion): RedirectResponse
    {
        $promotion->delete();

        return redirect()->route('admin.store.promotions.index')
            ->with('success', 'Promoción eliminada.');
    }
}
