<?php

namespace App\Presenters;

use Illuminate\Support\Collection;

class HomePagePresenter
{
    public function __construct(
        private readonly ProductPresenter $productPresenter
    ) {}

    public function present(
        $featured,
        $newArrivals,
        $bestSellers,
        $categories
    ): array {
        $featured = collect($featured);
        $newArrivals = collect($newArrivals);
        $bestSellers = collect($bestSellers);
        $categories = collect($categories);

        return [
            'featured' => $featured->map(fn ($product) => $this->productPresenter->card($product))->values()->all(),
            'newArrivals' => $newArrivals->map(fn ($product) => $this->productPresenter->card($product))->values()->all(),
            'bestSellers' => $bestSellers->map(fn ($product) => $this->productPresenter->card($product))->values()->all(),
            'categories' => $categories->values()->all(),
        ];
    }
}
