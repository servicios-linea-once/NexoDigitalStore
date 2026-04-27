<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * StoreSetting — Singleton key-value store for Nexo eStore global config.
 *
 * [PUNTO-2] Reemplaza SellerProfile en el modelo Single-Vendor.
 * La configuración de la tienda ya no está ligada a ningún usuario.
 *
 * Usage:
 *   StoreSetting::get('store_name')                    → 'Nexo eStore'
 *   StoreSetting::get('default_cashback_rate', 5.0)    → 5.0 (fallback)
 *   StoreSetting::set('support_email', 'x@nexo.store') → void
 *   StoreSetting::allSettings()                         → ['key' => 'value', ...]
 *   StoreSetting::public()                             → array of public settings
 */
class StoreSetting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'group', 'label', 'description', 'is_public'];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    /** Cache tag / TTL */
    private const CACHE_KEY = 'store_settings';
    private const CACHE_TTL = 3600; // 1 hour

    // ── Static read API ────────────────────────────────────────────────────

    /**
     * Get a single setting value, cast to its declared type.
     * Returns $default if the key doesn't exist.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $all = static::allCached();

        if (! array_key_exists($key, $all)) {
            return $default;
        }

        return $all[$key];
    }

    /**
     * Get all settings as a key-value array (cached).
     * Renamed from all() to avoid PHP fatal declaration incompatibility with Eloquent's Model::all().
     * Use StoreSetting::allSettings() or StoreSetting::get($key) in application code.
     */
    public static function allSettings(): array
    {
        return static::allCached();
    }

    /**
     * Get only public settings (safe to expose to frontend).
     */
    public static function public(): array
    {
        return array_filter(
            static::allCached(),
            fn ($key) => static::isPublicKey($key),
            ARRAY_FILTER_USE_KEY
        );
    }

    // ── Static write API ───────────────────────────────────────────────────

    /**
     * Set a setting value. Creates the record if it doesn't exist.
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => is_array($value) ? json_encode($value) : (string) $value]
        );

        static::flushCache();
    }

    /**
     * Set multiple settings at once.
     */
    public static function setMany(array $data): void
    {
        foreach ($data as $key => $value) {
            static::set($key, $value);
        }
    }

    // ── Cache helpers ──────────────────────────────────────────────────────

    public static function allCached(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return static::buildMap();
        });
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    // ── Internal ───────────────────────────────────────────────────────────

    private static function buildMap(): array
    {
        $map = [];
        foreach (parent::all(['key', 'value', 'type']) as $row) {
            $map[$row->key] = static::castValue($row->value, $row->type);
        }
        return $map;
    }

    private static function castValue(mixed $value, string $type): mixed
    {
        if ($value === null) return null;

        return match ($type) {
            'integer' => (int)    $value,
            'decimal' => (float)  $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json'    => json_decode($value, true),
            default   => (string) $value,
        };
    }

    private static function isPublicKey(string $key): bool
    {
        static $publicKeys = null;

        if ($publicKeys === null) {
            $publicKeys = parent::where('is_public', true)->pluck('key')->toArray();
        }

        return in_array($key, $publicKeys);
    }

    // ── Eloquent observer: flush cache on any change ───────────────────────
    protected static function booted(): void
    {
        static::saved(fn ()   => static::flushCache());
        static::deleted(fn () => static::flushCache());
    }
}
