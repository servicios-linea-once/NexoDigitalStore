<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // ── Juegos (parent) ──────────────────────────────────────────
            [
                'name' => 'Juegos',
                'slug' => 'juegos',
                'description' => 'Claves de activación para videojuegos en todas las plataformas.',
                'icon' => 'pi pi-discord',
                'color' => '#6366f1',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 1,
                'children' => [
                    ['name' => 'Steam',        'slug' => 'steam',        'icon' => 'pi pi-server',     'color' => '#1b2838'],
                    ['name' => 'Epic Games',   'slug' => 'epic-games',   'icon' => 'pi pi-globe',      'color' => '#2d2d2d'],
                    ['name' => 'PlayStation',  'slug' => 'playstation',  'icon' => 'pi pi-desktop',    'color' => '#003087'],
                    ['name' => 'Xbox',         'slug' => 'xbox',         'icon' => 'pi pi-desktop',    'color' => '#107c10'],
                    ['name' => 'Nintendo',     'slug' => 'nintendo',     'icon' => 'pi pi-desktop',    'color' => '#e60012'],
                    ['name' => 'GOG',          'slug' => 'gog',          'icon' => 'pi pi-globe',      'color' => '#86328a'],
                    ['name' => 'Battle.net',   'slug' => 'battlenet',    'icon' => 'pi pi-globe',      'color' => '#148eff'],
                    ['name' => 'Rockstar',     'slug' => 'rockstar',     'icon' => 'pi pi-star',       'color' => '#fcaf17'],
                ],
            ],

            // ── Tarjetas de regalo ────────────────────────────────────────
            [
                'name' => 'Gift Cards',
                'slug' => 'gift-cards',
                'description' => 'Tarjetas de regalo para tus plataformas favoritas.',
                'icon' => 'pi pi-credit-card',
                'color' => '#ec4899',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 2,
                'children' => [
                    ['name' => 'Amazon Gift Card',   'slug' => 'amazon-gift-card',   'icon' => 'pi pi-shopping-cart', 'color' => '#ff9900'],
                    ['name' => 'Google Play',        'slug' => 'google-play',        'icon' => 'pi pi-mobile',        'color' => '#34a853'],
                    ['name' => 'iTunes / App Store', 'slug' => 'itunes-app-store',   'icon' => 'pi pi-apple',         'color' => '#555'],
                    ['name' => 'Steam Wallet',       'slug' => 'steam-wallet',       'icon' => 'pi pi-wallet',        'color' => '#1b2838'],
                    ['name' => 'PSN Gift Card',      'slug' => 'psn-gift-card',      'icon' => 'pi pi-desktop',       'color' => '#003087'],
                    ['name' => 'Xbox Gift Card',     'slug' => 'xbox-gift-card',     'icon' => 'pi pi-desktop',       'color' => '#107c10'],
                ],
            ],

            // ── Software ──────────────────────────────────────────────────
            [
                'name' => 'Software',
                'slug' => 'software',
                'description' => 'Licencias de software para Windows, Office, antivirus y más.',
                'icon' => 'pi pi-desktop',
                'color' => '#3b82f6',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 3,
                'children' => [
                    ['name' => 'Windows',        'slug' => 'windows',         'icon' => 'pi pi-desktop',   'color' => '#0078d4'],
                    ['name' => 'Microsoft Office', 'slug' => 'microsoft-office', 'icon' => 'pi pi-file',      'color' => '#d83b01'],
                    ['name' => 'Antivirus',      'slug' => 'antivirus',       'icon' => 'pi pi-shield',    'color' => '#16a34a'],
                    ['name' => 'Diseño',         'slug' => 'software-diseno', 'icon' => 'pi pi-pencil',    'color' => '#7c3aed'],
                ],
            ],

            // ── Streaming & Suscripciones ─────────────────────────────────
            [
                'name' => 'Streaming',
                'slug' => 'streaming',
                'description' => 'Suscripciones a plataformas de streaming y entretenimiento.',
                'icon' => 'pi pi-video',
                'color' => '#f59e0b',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 4,
                'children' => [
                    ['name' => 'Netflix',        'slug' => 'netflix',         'icon' => 'pi pi-video',     'color' => '#e50914'],
                    ['name' => 'Spotify',        'slug' => 'spotify',         'icon' => 'pi pi-volume-up', 'color' => '#1db954'],
                    ['name' => 'Disney+',        'slug' => 'disney-plus',     'icon' => 'pi pi-video',     'color' => '#113ccf'],
                    ['name' => 'YouTube Premium', 'slug' => 'youtube-premium', 'icon' => 'pi pi-youtube',   'color' => '#ff0000'],
                    ['name' => 'HBO Max',        'slug' => 'hbo-max',         'icon' => 'pi pi-video',     'color' => '#7b2fff'],
                    ['name' => 'Crunchyroll',    'slug' => 'crunchyroll',     'icon' => 'pi pi-video',     'color' => '#f47521'],
                ],
            ],

            // ── IA & Herramientas ─────────────────────────────────────────
            [
                'name' => 'IA & Herramientas',
                'slug' => 'ia-herramientas',
                'description' => 'Acceso a herramientas de inteligencia artificial y productividad.',
                'icon' => 'pi pi-bolt',
                'color' => '#10b981',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 5,
                'children' => [
                    ['name' => 'ChatGPT Plus',   'slug' => 'chatgpt-plus',    'icon' => 'pi pi-comments',  'color' => '#10a37f'],
                    ['name' => 'Midjourney',     'slug' => 'midjourney',      'icon' => 'pi pi-image',     'color' => '#4a4a4a'],
                    ['name' => 'Adobe CC',       'slug' => 'adobe-cc',        'icon' => 'pi pi-pencil',    'color' => '#ff0000'],
                    ['name' => 'Canva Pro',      'slug' => 'canva-pro',       'icon' => 'pi pi-palette',   'color' => '#00c4cc'],
                ],
            ],

            // ── Cuentas de Juego ──────────────────────────────────────────
            [
                'name' => 'Cuentas',
                'slug' => 'cuentas',
                'description' => 'Cuentas de juegos con contenido premium desbloqueado.',
                'icon' => 'pi pi-user',
                'color' => '#8b5cf6',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 6,
                'children' => [],
            ],
        ];

        foreach ($categories as $data) {
            $children = $data['children'] ?? [];
            unset($data['children']);

            $parent = Category::updateOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, ['parent_id' => null])
            );

            foreach ($children as $i => $child) {
                Category::updateOrCreate(
                    ['slug' => $child['slug']],
                    array_merge($child, [
                        'parent_id' => $parent->id,
                        'is_active' => true,
                        'is_featured' => false,
                        'sort_order' => $i + 1,
                        'description' => null,
                    ])
                );
            }
        }
    }
}
