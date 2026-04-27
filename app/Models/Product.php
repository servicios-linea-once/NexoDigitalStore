<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;
use Spatie\Tags\HasTags;

class Product extends Model implements Searchable
{
    use HasFactory, SoftDeletes, HasTags;

    /**
     * Always eager-load these relationships.
     * Prevents lazy-loading errors (Model::shouldBeStrict) and
     * avoids forgetting with() in any controller.
     */
    protected $with = ['coverImage', 'promotions'];

    protected $fillable = [
        'ulid', 'parent_id', 'variant_name', 'seller_id', 'category_id', 'name', 'slug',
        'description', 'short_description', 'cover_image', 'activation_guide',
        'platform', 'region', 'delivery_type', 'status',
        'price_usd', 'price_pen', 'cashback_percent', 'cashback_amount_nt', 'max_activations_per_key',
        'stock_count', 'is_featured', 'is_preorder', 'preorder_release_date',
        'total_sales', 'rating', 'rating_count', 'tags', 'meta',
    ];

    protected $casts = [
        'price_usd'               => 'decimal:2',
        'price_pen'               => 'decimal:2',
        'cashback_percent'        => 'decimal:2',
        'cashback_amount_nt'      => 'integer',
        'max_activations_per_key' => 'integer',
        'is_featured'             => 'boolean',
        'is_preorder'             => 'boolean',
        'preorder_release_date'   => 'date',
        'tags'                    => 'array',
        'meta'                    => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (Product $product) {
            $product->ulid = (string) Str::ulid();
        });
    }

    // ── Accessors ─────────────────────────────────────────────────────────
    public function getActivePromotionAttribute()
    {
        if (array_key_exists('active_promotion', $this->attributes)) {
            return $this->attributes['active_promotion'];
        }

        if ($this->relationLoaded('promotions')) {
            return $this->promotions
                ->first(fn (Promotion $promotion) => $this->isPromotionActive($promotion));
        }

        return $this->promotions()->activeNow()->first();
    }

    public function getDiscountedPriceUsdAttribute(): float
    {
        return $this->applyDiscount($this->price_usd, 'usd');
    }

    public function getDiscountedPricePenAttribute(): float
    {
        return $this->applyDiscount($this->price_pen, 'pen');
    }

    /**
     * Aplica descuentos por promoción según el tipo (porcentaje o cantidad fija).
     * Evita duplicación de lógica entre USD y PEN.
     *
     * @param float $basePrice Precio base a descontar
     * @param string $currency 'usd' o 'pen'
     * @return float Precio final después del descuento
     */
    private function applyDiscount(float $basePrice, string $currency): float
    {
        $promo = $this->active_promotion;
        if (!$promo) {
            return (float) $basePrice;
        }

        if ($promo->discount_type === 'percent') {
            return round($basePrice * (1 - $promo->discount_value / 100), 2);
        }

        $fixedKey = "discount_value_fixed_{$currency}";
        if ($promo->discount_type === "fixed_{$currency}") {
            return max(0, $basePrice - ($promo->$fixedKey ?? $promo->discount_value));
        }

        return (float) $basePrice;
    }

    public function getIsAvailableAttribute(): bool
    {
        return $this->status === 'active' && $this->stock_count > 0;
    }

    // ── Relationships ─────────────────────────────────────────────────────
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function parent()
    {
        return $this->belongsTo(Product::class, 'parent_id');
    }

    public function variants()
    {
        return $this->hasMany(Product::class, 'parent_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function promotions()
    {
        // Pivot table is promotion_product (alphabetical: promotion < product)
        return $this->belongsToMany(Promotion::class, 'promotion_product');
    }

    public function digitalKeys()
    {
        return $this->hasMany(DigitalKey::class);
    }

    public function availableKeys()
    {
        return $this->hasMany(DigitalKey::class)->where('status', 'available');
    }

    public function coverImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_cover', true);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class)->where('is_approved', true);
    }

    // sales_count alias for sort
    public function getSalesCountAttribute(): int
    {
        return (int) ($this->attributes['total_sales'] ?? 0);
    }

    // delivery_method alias
    public function getDeliveryMethodAttribute(): string
    {
        return $this->attributes['delivery_type'] ?? 'automatic';
    }

    // ── Price helpers ─────────────────────────────────────────────────────
    public function priceInCurrency(string $currencyCode): ?float
    {
        if ($currencyCode === 'USD') return $this->discounted_price_usd;
        if ($currencyCode === 'PEN') return $this->discounted_price_pen;
        return null;
    }

    // ── Scopes ───────────────────────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_count', '>', 0);
    }

    /**
     * Productos ordenados por rating descendente (más altos primero).
     */
    public function scopeByRating($query)
    {
        return $query->orderByDesc('rating');
    }

    /**
     * Productos ordenados por fecha creación descendente (más recientes primero).
     */
    public function scopeNewest($query)
    {
        return $query->latest();
    }

    /**
     * Productos ordenados por total de ventas descendente (bestsellers).
     */
    public function scopeBestSellers($query)
    {
        return $query->orderByDesc('total_sales');
    }

    /**
     * Productos activos, en stock y destacados con rating alto.
     */
    public function scopeFeaturedActive($query)
    {
        return $query->active()->featured()->inStock();
    }

    protected function isPromotionActive(Promotion $promotion): bool
    {
        if (! $promotion->is_active) {
            return false;
        }

        if ($promotion->start_date && $promotion->start_date->isFuture()) {
            return false;
        }

        if ($promotion->end_date && $promotion->end_date->isPast()) {
            return false;
        }

        return true;
    }

    public function scopeSearch($query, string $term)
    {
        return $query->whereRaw('MATCH(name, description) AGAINST(? IN BOOLEAN MODE)', [$term]);
    }

    public function getSearchResult(): SearchResult
    {
        $url = route('admin.store.products.edit', $this->ulid);

        return new SearchResult(
            $this,
            $this->name,
            $url
        );
    }
}
