<?php

namespace App\Presenters;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductIndexPagePresenter
{
    public function __construct(
        private readonly ProductPresenter $productPresenter
    ) {}

    public function present(
        LengthAwarePaginator $products,
        array $categories,
        array $filters,
        ?string $pageTitle = null
    ): array {
        $products->setCollection(
            $products->getCollection()->map(fn ($product) => $this->productPresenter->card($product))
        );

        return array_filter([
            'products' => $products,
            'categories' => $categories,
            'filters' => $filters,
            'pageTitle' => $pageTitle,
        ], fn ($value) => $value !== null);
    }
}
