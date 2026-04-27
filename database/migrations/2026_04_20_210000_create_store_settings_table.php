<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * [PUNTO-2] Tabla store_settings — Reemplaza seller_profiles para el modelo Single-Vendor.
 *
 * Almacena la configuración global de Nexo eStore como registros clave-valor
 * en lugar de estar ligada a un usuario específico.
 *
 * Uso:  StoreSetting::get('store_name')
 *       StoreSetting::set('default_cashback_rate', '5.00')
 *       StoreSetting::all()  // devuelve array clave-valor
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique()->index();
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string'); // string, integer, decimal, boolean, json
            $table->string('group', 50)->default('general'); // general, payments, shipping, notifications
            $table->string('label', 150)->nullable();       // UI label for admin panel
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);   // if true, exposed to frontend
            $table->timestamps();
        });

        // ── Seed default values ────────────────────────────────────────────
        $defaults = [
            // General
            ['key' => 'store_name',             'value' => 'Nexo eStore',                   'type' => 'string',  'group' => 'general',       'label' => 'Nombre de la tienda',      'is_public' => true],
            ['key' => 'store_tagline',           'value' => 'Tu tienda de claves digitales', 'type' => 'string',  'group' => 'general',       'label' => 'Tagline',                  'is_public' => true],
            ['key' => 'support_email',           'value' => 'soporte@nexo.store',            'type' => 'string',  'group' => 'general',       'label' => 'Email de soporte',         'is_public' => true],
            ['key' => 'store_currency',          'value' => 'USD',                           'type' => 'string',  'group' => 'general',       'label' => 'Moneda principal',         'is_public' => true],
            ['key' => 'store_logo_url',          'value' => null,                            'type' => 'string',  'group' => 'general',       'label' => 'URL del logo',             'is_public' => true],
            ['key' => 'store_favicon_url',       'value' => null,                            'type' => 'string',  'group' => 'general',       'label' => 'URL del favicon',          'is_public' => true],
            // Commerce
            ['key' => 'default_cashback_rate',   'value' => '5.00',                          'type' => 'decimal', 'group' => 'commerce',      'label' => 'Cashback por defecto (%)', 'is_public' => false],
            ['key' => 'nt_rate_to_usd',          'value' => '0.10',                          'type' => 'decimal', 'group' => 'commerce',      'label' => '1 NT = X USD',             'is_public' => true],
            ['key' => 'max_cart_items',          'value' => '10',                            'type' => 'integer', 'group' => 'commerce',      'label' => 'Máximo items en carrito',  'is_public' => false],
            ['key' => 'reservation_minutes',     'value' => '15',                            'type' => 'integer', 'group' => 'commerce',      'label' => 'Minutos de reserva de clave','is_public' => false],
            // Notifications
            ['key' => 'notify_on_order',         'value' => 'true',                          'type' => 'boolean', 'group' => 'notifications', 'label' => 'Notificar al completar pedido','is_public' => false],
            ['key' => 'admin_notify_email',      'value' => null,                            'type' => 'string',  'group' => 'notifications', 'label' => 'Email admin para notificaciones','is_public' => false],
            // Legal
            ['key' => 'terms_url',               'value' => '/terms',                        'type' => 'string',  'group' => 'legal',         'label' => 'URL Términos y Condiciones','is_public' => true],
            ['key' => 'privacy_url',             'value' => '/privacy',                      'type' => 'string',  'group' => 'legal',         'label' => 'URL Política de Privacidad','is_public' => true],
        ];

        foreach ($defaults as $row) {
            DB::table('store_settings')->insert(array_merge($row, [
                'description' => null,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};
