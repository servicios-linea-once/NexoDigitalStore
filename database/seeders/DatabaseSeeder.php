<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CurrencySeeder::class,              // Monedas (USD, PEN, COP, MXN, EUR, NT)
            CategorySeeder::class,              // Categorías (6 padres + 35 hijos)
            RolesAndPermissionsSeeder::class,   // Roles Spatie + permisos (debe ir ANTES de AdminSeeder)
            AdminSeeder::class,                 // Admin, Seller demo, Buyer demo (asigna roles Spatie)
            ProductSeeder::class,               // 6 productos demo con claves
            SubscriptionSeeder::class,          // Planes Free/Pro/Business + asignar Free a todos
        ]);
    }
}
