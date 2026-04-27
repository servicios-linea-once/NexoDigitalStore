<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            ['code' => 'USD', 'name' => 'US Dollar',          'symbol' => '$',  'rate_to_usd' => 1.0000, 'is_active' => true,  'is_default' => false],
            ['code' => 'PEN', 'name' => 'Sol Peruano',         'symbol' => 'S/', 'rate_to_usd' => 3.7500, 'is_active' => true,  'is_default' => true],
            ['code' => 'COP', 'name' => 'Peso Colombiano',     'symbol' => '$',  'rate_to_usd' => 4050.0, 'is_active' => true,  'is_default' => false],
            ['code' => 'MXN', 'name' => 'Peso Mexicano',       'symbol' => '$',  'rate_to_usd' => 17.500, 'is_active' => true,  'is_default' => false],
            ['code' => 'EUR', 'name' => 'Euro',                'symbol' => '€',  'rate_to_usd' => 0.9200, 'is_active' => true,  'is_default' => false],
            ['code' => 'NT',  'name' => 'NexoToken',           'symbol' => 'NT', 'rate_to_usd' => 0.1000, 'is_active' => true,  'is_default' => false],
        ];

        foreach ($currencies as $currency) {
            Currency::updateOrCreate(['code' => $currency['code']], $currency);
        }
    }
}
