<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class ProductData extends Data
{
    public function __construct(
        public string $ulid,
        public string $name,
        public string $slug,
        public ?string $description,
        public float $price_usd,
        public float $price_pen,
        public int $stock_count,
        public string $status,
        public ?string $category_name,
        public ?string $cover_image_url,
        /** @var DataCollection<int, ProductVariantData>|null */
        public ?DataCollection $variants,
    ) {}

    public static function fromModel(\App\Models\Product $product): self
    {
        return new self(
            ulid: $product->ulid,
            name: $product->name,
            slug: $product->slug,
            description: $product->description,
            price_usd: (float) $product->price_usd,
            price_pen: (float) $product->price_pen,
            stock_count: (int) $product->stock_count,
            status: $product->status,
            category_name: $product->category?->name,
            cover_image_url: $product->coverImage?->url,
            variants: ProductVariantData::collection($product->variants),
        );
    }
}

class ProductVariantData extends Data
{
    public function __construct(
        public string $ulid,
        public string $variant_name,
        public float $price_usd,
        public float $price_pen,
        public int $stock_count,
        public string $status,
    ) {}

    public static function fromModel(\App\Models\Product $variant): self
    {
        return new self(
            ulid: $variant->ulid,
            variant_name: $variant->variant_name,
            price_usd: (float) $variant->price_usd,
            price_pen: (float) $variant->price_pen,
            stock_count: (int) $variant->stock_count,
            status: $variant->status,
        );
    }
}
