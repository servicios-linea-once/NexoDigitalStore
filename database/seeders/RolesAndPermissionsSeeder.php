<?php

namespace Database\Seeders;

use App\Enums\Permission as PermissionEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Limpiar caché de Spatie ────────────────────────────────────────
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ── 2. Crear TODOS los permisos desde el enum (idempotente) ───────────
        foreach (PermissionEnum::cases() as $perm) {
            Permission::firstOrCreate([
                'name'       => $perm->value,
                'guard_name' => 'web',
            ]);
        }

        // ── 3. Rol: buyer ─────────────────────────────────────────────────────
        // Permisos básicos de un comprador autenticado.
        $buyer = Role::firstOrCreate(['name' => 'buyer', 'guard_name' => 'web']);
        $buyer->syncPermissions([
            PermissionEnum::OWN_ORDERS_VIEW->value,    // ver sus propias órdenes
        ]);

        // ── 4. Rol: seller ────────────────────────────────────────────────────
        // Seller = gestor de la tienda (modelo single-vendor).
        // Puede gestionar productos, claves, pedidos y promociones propios.
        $seller = Role::firstOrCreate(['name' => 'seller', 'guard_name' => 'web']);
        $seller->syncPermissions([
            // Dashboard
            PermissionEnum::DASHBOARD_SELLER->value,

            // Productos propios
            PermissionEnum::OWN_PRODUCTS_VIEW->value,
            PermissionEnum::OWN_PRODUCTS_CREATE->value,
            PermissionEnum::OWN_PRODUCTS_EDIT->value,
            PermissionEnum::OWN_PRODUCTS_DELETE->value,

            // Órdenes propias
            PermissionEnum::OWN_ORDERS_VIEW->value,

            // Claves digitales
            PermissionEnum::KEYS_VIEW->value,
            PermissionEnum::KEYS_IMPORT->value,
            PermissionEnum::KEYS_DELETE->value,

            // Promociones
            PermissionEnum::PROMOTIONS_VIEW->value,
            PermissionEnum::PROMOTIONS_CREATE->value,
            PermissionEnum::PROMOTIONS_EDIT->value,
            PermissionEnum::PROMOTIONS_DELETE->value,

            // Entregas y ganancias
            PermissionEnum::DELIVERIES_VIEW->value,
            PermissionEnum::DELIVERIES_DELIVER->value,
            PermissionEnum::EARNINGS_VIEW->value,
        ]);

        // ── 5. Rol: admin ─────────────────────────────────────────────────────
        // Admin recibe TODOS los permisos del enum.
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(
            collect(PermissionEnum::cases())->pluck('value')->toArray()
        );

        // ── 6. Sincronizar roles Spatie a usuarios existentes ─────────────────
        // Usa el campo legacy `role` para asignar el rol Spatie si aún no lo tiene.
        User::query()->chunk(100, function ($users) {
            foreach ($users as $user) {
                $roleName = $user->role ?? 'buyer';
                if (in_array($roleName, ['admin', 'seller', 'buyer'], true)) {
                    if (! $user->hasRole($roleName)) {
                        $user->assignRole($roleName);
                    }
                }
            }
        });

        // ── 7. Limpiar caché nuevamente tras cambios ──────────────────────────
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Resumen ───────────────────────────────────────────────────────────
        $total = collect(PermissionEnum::cases())->count();
        $this->command->info('✅ Roles y permisos creados correctamente.');
        $this->command->table(
            ['Rol', 'Permisos'],
            [
                ['admin',  "TODOS ({$total})"],
                ['seller', '17 (tienda propia: productos, claves, pedidos, promociones, entregas, ganancias, dashboard)'],
                ['buyer',  '1  (own_orders.view)'],
            ]
        );
    }
}
