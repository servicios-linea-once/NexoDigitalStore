<?php

namespace App\Presenters;

use App\Models\Product;

class ProductPresenter
{
    public function __construct(
        private readonly StorePresenter $storePresenter
    ) {}

    private function getFinalDiscount(Product $product): float
    {
        $user = auth()->user();
        $subDiscount = 0;

        if ($user && ($activeSub = $user->subscriptions()->where('status', 'active')->with('plan')->first())) {
            $subDiscount = (float) $activeSub->plan->discount_percent;
        }

        $promoDiscount = 0;
        if ($promo = $product->active_promotion) {
            if ($promo->discount_type === 'percent') {
                $promoDiscount = (float) $promo->discount_value;
            }
        }

        return max($promoDiscount, $subDiscount);
    }

    public function card(Product $product): array
    {
        $finalDiscount = $this->getFinalDiscount($product);
        
        $priceUsd = (float) $product->price_usd;
        $pricePen = (float) $product->price_pen;

        $discountedUsd = round($priceUsd * (1 - $finalDiscount / 100), 2);
        $discountedPen = round($pricePen * (1 - $finalDiscount / 100), 2);

        return [
            'id' => $product->id,
            'ulid' => $product->ulid,
            'slug' => $product->slug,
            'name' => $product->name,
            'price_usd' => $priceUsd,
            'price_pen' => $pricePen,
            'discounted_price_usd' => $discountedUsd,
            'discounted_price_pen' => $discountedPen,
            'discount_percent' => $finalDiscount,
            'has_discount' => $finalDiscount > 0,
            'is_subscription_discount' => $finalDiscount > 0 && ($product->active_promotion?->discount_value ?? 0) < $finalDiscount,
            'active_promotion' => $product->active_promotion ? [
                'name' => $product->active_promotion->name,
                'discount_type' => $product->active_promotion->discount_type,
                'discount_value' => (float) $product->active_promotion->discount_value,
            ] : null,
            'cashback_percent' => (float) ($product->cashback_percent ?? 0),
            'cashback_amount_nt' => (int) ($product->cashback_amount_nt ?? 0),
            'stock_count' => (int) $product->stock_count,
            'rating' => round((float) $product->rating, 1),
            'rating_count' => (int) $product->rating_count,
            'is_featured' => (bool) $product->is_featured,
            'is_preorder' => (bool) $product->is_preorder,
            'platform' => $product->platform,
            'region' => $product->region,
            'cover_image' => $product->coverImage?->url,
        ];
    }

    public function detail(Product $product, ?int $userId = null): array
    {
        $finalDiscount = $this->getFinalDiscount($product);
        
        return array_merge($this->card($product), [
            'description' => $product->description,
            'short_description' => $product->short_description,
            'delivery_method' => $product->delivery_method,
            'delivery_type' => $product->delivery_type,
            'activation_guide' => $product->activation_guide,
            'tags' => $product->tags ?? [],
            'in_wishlist' => $userId
                ? \App\Models\Wishlist::where('user_id', $userId)->where('product_id', $product->id)->exists()
                : false,
            'category' => [
                'id' => $product->category?->id,
                'name' => $product->category?->name,
                'slug' => $product->category?->slug,
                'parent' => $product->category?->parent ? [
                    'name' => $product->category->parent->name,
                    'slug' => $product->category->parent->slug,
                ] : null,
            ],
            'images' => $product->images->map(fn ($image) => [
                'id' => $image->id,
                'url' => $image->url,
                'is_cover' => (bool) $image->is_cover,
            ])->values()->all(),
            'prices' => [
                [
                    'currency' => 'USD',
                    'price' => round((float) $product->price_usd * (1 - $finalDiscount / 100), 2),
                    'compare_price' => $finalDiscount > 0 ? (float) $product->price_usd : null,
                ],
                [
                    'currency' => 'PEN',
                    'price' => round((float) $product->price_pen * (1 - $finalDiscount / 100), 2),
                    'compare_price' => $finalDiscount > 0 ? (float) $product->price_pen : null,
                ],
            ],
            'reviews' => $product->reviews->map(fn ($review) => [
                'id' => $review->id,
                'rating' => (int) $review->rating,
                'comment' => $review->body,
                'created_at' => $review->created_at->diffForHumans(),
                'user' => [
                    'name' => $review->user?->name ?? 'Anónimo',
                    'avatar' => $review->user?->avatar_url,
                ],
            ])->values()->all(),
            'variants' => $product->variants->where('status', 'active')->map(function ($variant) use ($finalDiscount) {
                return [
                    'id' => $variant->id,
                    'ulid' => $variant->ulid,
                    'name' => $variant->name,
                    'variant_name' => $variant->variant_name,
                    'price_usd' => (float) $variant->price_usd,
                    'price_pen' => (float) $variant->price_pen,
                    'discounted_price_usd' => round((float) $variant->price_usd * (1 - $finalDiscount / 100), 2),
                    'discounted_price_pen' => round((float) $variant->price_pen * (1 - $finalDiscount / 100), 2),
                    'stock_count' => (int) $variant->stock_count,
                ];
            })->values()->all(),
        ]);
    }
}
