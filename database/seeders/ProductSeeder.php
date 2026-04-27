<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\DigitalKey;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * ProductSeeder — synced with the REAL products & digital_keys schema.
 *
 * products table real columns:
 *   price_usd, price_pen, cashback_amount_nt, stock_count,
 *   delivery_type (enum: auto/manual/api), status (enum: draft/active/paused/sold_out)
 *   is_featured, is_preorder, platform, region, name, slug, description,
 *   short_description, max_activations_per_key, ulid (auto via model)
 *
 * digital_keys real columns:
 *   product_id, seller_id, key_value, status (available/reserved/sold/refunded)
 *   is_license, max_activations, ulid (auto via model)
 */
class ProductSeeder extends Seeder
{
    /** USD → PEN exchange rate for seeding (static demo value) */
    private const PEN_RATE = 3.75;

    public function run(): void
    {
        $seller = User::where('email', 'seller@nexodigital.com')->first();

        if (! $seller) {
            $this->command->warn('⚠️  Ejecuta primero AdminSeeder.');
            return;
        }

        $demoProducts = [
            [
                'name'              => 'Steam Wallet $20 USD',
                'description'       => 'Recarga tu billetera de Steam con $20 USD. Compatible con todas las cuentas Steam globales.',
                'short_description' => 'Gift card Steam $20 — entrega inmediata',
                'platform'          => 'Steam',
                'region'            => 'Global',
                'price_usd'         => 20.00,
                'cashback_nt'       => 6,    // 3% de $20 / $0.10
                'is_featured'       => true,
                'category_slug'     => 'steam',
                'keys'              => ['STEAM-DEMO1-XXXXX-11111', 'STEAM-DEMO2-XXXXX-22222'],
            ],
            [
                'name'              => 'Netflix 1 Mes Premium',
                'description'       => 'Acceso a Netflix Premium por 1 mes con calidad 4K y 4 pantallas simultáneas.',
                'short_description' => 'Netflix Premium 1 mes — 4K · 4 screens',
                'platform'          => 'Netflix',
                'region'            => 'Latinoamérica',
                'price_usd'         => 15.99,
                'cashback_nt'       => 8,    // 5% de $15.99 / $0.10
                'is_featured'       => true,
                'category_slug'     => 'netflix',
                'keys'              => ['NFLX-DEMO1-YYYY-11111'],
            ],
            [
                'name'              => 'PlayStation Network $50',
                'description'       => 'Tarjeta de regalo PSN de $50 USD. Canjeable en PlayStation Store.',
                'short_description' => 'PSN Gift Card $50 — US Store',
                'platform'          => 'PlayStation',
                'region'            => 'USA',
                'price_usd'         => 50.00,
                'cashback_nt'       => 10,
                'is_featured'       => true,
                'category_slug'     => 'playstation',
                'keys'              => ['PSN-DEMO-ZZZZ-33333'],
            ],
            [
                'name'              => 'Microsoft Office 365 Personal 1 año',
                'description'       => 'Office 365 Personal: Word, Excel, PowerPoint, 1 TB OneDrive. Activación online.',
                'short_description' => 'Office 365 Personal — 1 año · 1 usuario',
                'platform'          => 'Microsoft',
                'region'            => 'Global',
                'price_usd'         => 69.99,
                'cashback_nt'       => 28,   // 4% de $69.99 / $0.10
                'is_featured'       => false,
                'category_slug'     => 'software',
                'keys'              => ['MSFT-DEMO-AAAA-44444'],
            ],
            [
                'name'              => 'Spotify Premium 3 meses',
                'description'       => 'Spotify Premium sin anuncios por 3 meses. Descarga offline e ilimitada.',
                'short_description' => 'Spotify Premium 3 meses — Latam',
                'platform'          => 'Spotify',
                'region'            => 'Latinoamérica',
                'price_usd'         => 9.99,
                'cashback_nt'       => 3,    // 3% de $9.99 / $0.10
                'is_featured'       => true,
                'category_slug'     => 'streaming',
                'keys'              => ['SPTF-DEMO-BBBB-55555'],
            ],
            [
                'name'              => 'ChatGPT Plus 1 mes',
                'description'       => 'Acceso a GPT-4o, DALL·E 3, plugins y navegación web por 1 mes.',
                'short_description' => 'ChatGPT Plus — GPT-4o por 1 mes',
                'platform'          => 'OpenAI',
                'region'            => 'Global',
                'price_usd'         => 20.00,
                'cashback_nt'       => 10,   // 5% de $20 / $0.10
                'is_featured'       => true,
                'category_slug'     => 'ia-herramientas',
                'keys'              => ['CGPT-DEMO-CCCC-66666'],
            ],
        ];

        foreach ($demoProducts as $data) {
            $category = Category::where('slug', $data['category_slug'])->first()
                ?? Category::first();

            $slug = Str::slug($data['name']);

            $product = Product::updateOrCreate(
                ['slug' => $slug],
                [
                    'seller_id'             => $seller->id,
                    'category_id'           => $category?->id,
                    'name'                  => $data['name'],
                    'description'           => $data['description'],
                    'short_description'     => $data['short_description'],
                    'platform'              => $data['platform'],
                    'region'                => $data['region'],
                    'delivery_type'         => 'auto',
                    'status'                => 'active',
                    // ── Pricing: real column names ──────────────────────────
                    'price_usd'             => $data['price_usd'],
                    'price_pen'             => round($data['price_usd'] * self::PEN_RATE, 2),
                    'cashback_amount_nt'    => $data['cashback_nt'],
                    // ── Stock ───────────────────────────────────────────────
                    'stock_count'           => count($data['keys']),
                    'max_activations_per_key' => 1,
                    // ── Flags ───────────────────────────────────────────────
                    'is_featured'           => $data['is_featured'],
                    'is_preorder'           => false,
                ]
            );

            // ── Digital keys ─────────────────────────────────────────────────
            foreach ($data['keys'] as $rawKey) {
                DigitalKey::firstOrCreate(
                    [
                        'product_id' => $product->id,
                        'key_value'  => $rawKey,       // real column: key_value ✓
                    ],
                    [
                        'seller_id'      => $seller->id,
                        'status'         => 'available',
                        'is_license'     => false,
                        'max_activations' => 1,
                    ]
                );
            }

            $this->command->info("✅ {$product->name} — \${$data['price_usd']} USD | {$product->stock_count} claves");
        }
    }
}
