<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ulid' => $this->ulid,
            'name' => $this->name,
            'slug' => $this->slug,
            'platform' => $this->platform,
            'region' => $this->region,
            'price_usd' => (float) $this->price_usd,
            'price_pen' => (float) $this->price_pen,
            'discounted_price_usd' => (float) $this->discounted_price_usd,
            'discounted_price_pen' => (float) $this->discounted_price_pen,
            'cashback_amount_nt' => (int) $this->cashback_amount_nt,
            'stock_count' => (int) $this->stock_count,
            'is_featured' => (bool) $this->is_featured,
            'rating' => round((float) $this->rating, 1),
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null,
            'cover_image' => $this->coverImage?->url,
            'active_promotion' => $this->active_promotion ? [
                'name' => $this->active_promotion->name,
                'discount_type' => $this->active_promotion->discount_type,
                'discount_value' => (float) $this->active_promotion->discount_value,
            ] : null,
        ];
    }
}
