<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Database\Seeder;

class SubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Planes ─────────────────────────────────────────────────────────
        $free = SubscriptionPlan::updateOrCreate(['slug' => 'free'], [
            'name'             => 'Free',
            'description'      => 'Acceso básico al marketplace. Perfecto para comenzar.',
            'features'         => [
                'Acceso al catálogo completo',
                'Compras ilimitadas',
                'Soporte por email',
                'Cashback en NT',
            ],
            'price_usd'        => 0,
            'price_pen'        => 0,
            'duration_days'    => 0,    // 0 = vitalicio (expires_at = null)
            'discount_percent' => 0.00,
            'is_active'        => true,
            'is_visible'       => true,
        ]);

        $pro = SubscriptionPlan::updateOrCreate(['slug' => 'pro'], [
            'name'             => 'Pro',
            'description'      => '5% de descuento en todas tus compras + beneficios extra.',
            'features'         => [
                'Todo lo de Free',
                '5% descuento en compras',
                'Soporte prioritario',
                'Acceso anticipado a ofertas',
                'Badge Pro en perfil',
            ],
            'price_usd'        => 4.99,
            'price_pen'        => 18.50, // Asumiendo ~3.7 PEN/USD
            'duration_days'    => 30,
            'discount_percent' => 5.00,
            'is_active'        => true,
            'is_visible'       => true,
        ]);

        $business = SubscriptionPlan::updateOrCreate(['slug' => 'business'], [
            'name'             => 'Business',
            'description'      => '12% de descuento + máximos beneficios para compradores frecuentes.',
            'features'         => [
                'Todo lo de Pro',
                '12% descuento en compras',
                'Soporte 24/7 dedicado',
                'Acceso a API REST v1',
                'Badge Business en perfil',
                'Descuentos exclusivos',
            ],
            'price_usd'        => 9.99,
            'price_pen'        => 37.00, // Asumiendo ~3.7 PEN/USD
            'duration_days'    => 30,
            'discount_percent' => 12.00,
            'is_active'        => true,
            'is_visible'       => true,
        ]);

        $ultimate = SubscriptionPlan::updateOrCreate(['slug' => 'ultimate'], [
            'name'             => 'Ultimate',
            'description'      => '20% de descuento + acceso total y soporte prioritario instantáneo.',
            'features'         => [
                'Todo lo de Business',
                '20% descuento en compras',
                'Soporte por WhatsApp dedicado',
                'Badge Ultimate en perfil',
                'Promociones relámpago exclusivas',
                'Sin comisiones en retiros NT',
            ],
            'price_usd'        => 19.99,
            'price_pen'        => 74.00,
            'duration_days'    => 30,
            'discount_percent' => 20.00,
            'is_active'        => true,
            'is_visible'       => true,
        ]);

        $this->command->info('✅ Planes creados/actualizados: Free, Pro, Business, Ultimate');

        // ── 2. Asignar Free a todos los usuarios sin suscripción activa ────────
        $users = User::whereDoesntHave('subscriptions', function ($q) {
            $q->where('status', 'active');
        })->get();

        foreach ($users as $user) {
            UserSubscription::create([
                'user_id'           => $user->id,
                'plan_id'           => $free->id,
                'status'            => 'active',
                'payment_gateway'   => 'manual',
                'payment_reference' => 'auto-free',
                'amount_paid'       => 0,
                'currency'          => 'USD',
                'starts_at'         => now(),
                'expires_at'        => null,   // Free = vitalicio
            ]);
        }

        $this->command->info("✅ Plan Free asignado a {$users->count()} usuarios.");
    }
}
