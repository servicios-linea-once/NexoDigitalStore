<?php

namespace App\Presenters;

use App\Models\Product;
use Illuminate\Support\Collection;

class ProductShowPagePresenter
{
    public function __construct(
        private readonly ProductPresenter $productPresenter
    ) {}

    public function present(Product $product, $related): array
    {
        $relatedItems = collect($related);

        return [
            'product' => $this->productPresenter->detail($product, auth()->id()),
            'related' => $relatedItems
                ->filter(fn($item) => $item instanceof Product)
                ->map(fn ($item) => $this->productPresenter->card($item))
                ->values()
                ->all(),
        ];
    }
}
