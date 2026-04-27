<?php

namespace App\Authorization;

use App\Enums\Permission;

/**
 * Maps roles to their allowed permissions.
 * Hierarchy: admin > seller > buyer
 */
class RolePermissions
{
    private static array $map = [
        'admin' => [
            // Admin has ALL permissions
            Permission::USERS_VIEW,
            Permission::USERS_CREATE,
            Permission::USERS_EDIT,
            Permission::USERS_DELETE,
            Permission::USERS_ROLE,

            Permission::ROLES_VIEW,
            Permission::ROLES_ASSIGN,

            Permission::PRODUCTS_VIEW,
            Permission::PRODUCTS_CREATE,
            Permission::PRODUCTS_EDIT,
            Permission::PRODUCTS_DELETE,
            Permission::PRODUCTS_MODERATE,

            // Admin also has all seller permissions
            Permission::OWN_PRODUCTS_VIEW,
            Permission::OWN_PRODUCTS_CREATE,
            Permission::OWN_PRODUCTS_EDIT,
            Permission::OWN_PRODUCTS_DELETE,

            Permission::ORDERS_VIEW,
            Permission::ORDERS_REFUND,
            Permission::OWN_ORDERS_VIEW,

            Permission::CATEGORIES_VIEW,
            Permission::CATEGORIES_CREATE,
            Permission::CATEGORIES_EDIT,
            Permission::CATEGORIES_DELETE,

            Permission::REVIEWS_VIEW,
            Permission::REVIEWS_MODERATE,

            Permission::KEYS_VIEW,
            Permission::KEYS_IMPORT,
            Permission::KEYS_DELETE,

            Permission::PROMOTIONS_VIEW,
            Permission::PROMOTIONS_CREATE,
            Permission::PROMOTIONS_EDIT,
            Permission::PROMOTIONS_DELETE,

            Permission::DELIVERIES_VIEW,
            Permission::DELIVERIES_DELIVER,

            Permission::EARNINGS_VIEW,

            Permission::SUBSCRIPTIONS_VIEW,
            Permission::SUBSCRIPTIONS_ASSIGN,
            Permission::SUBSCRIPTIONS_REVOKE,

            Permission::AUDIT_VIEW,

            Permission::DASHBOARD_ADMIN,
            Permission::DASHBOARD_SELLER,
        ],

        'seller' => [
            Permission::OWN_PRODUCTS_VIEW,
            Permission::OWN_PRODUCTS_CREATE,
            Permission::OWN_PRODUCTS_EDIT,
            Permission::OWN_PRODUCTS_DELETE,

            Permission::OWN_ORDERS_VIEW,

            Permission::KEYS_VIEW,
            Permission::KEYS_IMPORT,
            Permission::KEYS_DELETE,

            Permission::PROMOTIONS_VIEW,
            Permission::PROMOTIONS_CREATE,
            Permission::PROMOTIONS_EDIT,
            Permission::PROMOTIONS_DELETE,

            Permission::DELIVERIES_VIEW,
            Permission::DELIVERIES_DELIVER,

            Permission::EARNINGS_VIEW,

            Permission::DASHBOARD_SELLER,
        ],

        'buyer' => [
            Permission::OWN_ORDERS_VIEW,
        ],
    ];

    /**
     * Get all permissions for a given role.
     * @return Permission[]
     */
    public static function for(string $role): array
    {
        return self::$map[$role] ?? [];
    }

    /**
     * Check if a role has a specific permission.
     */
    public static function has(string $role, Permission $permission): bool
    {
        return in_array($permission, self::for($role), true);
    }

    /**
     * Get all permissions as string values for a role (for frontend).
     */
    public static function stringsFor(string $role): array
    {
        return array_map(fn (Permission $p) => $p->value, self::for($role));
    }
}
