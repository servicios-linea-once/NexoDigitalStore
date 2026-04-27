<?php

namespace App\Enums;

/**
 * All system permissions.
 * Format: resource.action
 */
enum Permission: string
{
    // Users
    case USERS_VIEW   = 'users.view';
    case USERS_CREATE = 'users.create';
    case USERS_EDIT   = 'users.edit';
    case USERS_DELETE = 'users.delete';
    case USERS_ROLE   = 'users.role';   // change role

    // Roles
    case ROLES_VIEW   = 'roles.view';
    case ROLES_CREATE = 'roles.create';
    case ROLES_EDIT   = 'roles.edit';
    case ROLES_DELETE = 'roles.delete';
    case ROLES_ASSIGN = 'roles.assign';

    // Products (marketplace oversight)
    case PRODUCTS_VIEW     = 'products.view';
    case PRODUCTS_CREATE   = 'products.create';
    case PRODUCTS_EDIT     = 'products.edit';
    case PRODUCTS_DELETE   = 'products.delete';
    case PRODUCTS_MODERATE = 'products.moderate'; // approve/reject

    // Own products (seller)
    case OWN_PRODUCTS_VIEW   = 'own_products.view';
    case OWN_PRODUCTS_CREATE = 'own_products.create';
    case OWN_PRODUCTS_EDIT   = 'own_products.edit';
    case OWN_PRODUCTS_DELETE = 'own_products.delete';

    // Orders
    case ORDERS_VIEW   = 'orders.view';     // all orders
    case ORDERS_REFUND = 'orders.refund';
    case OWN_ORDERS_VIEW = 'own_orders.view'; // own/seller orders

    // Categories
    case CATEGORIES_VIEW   = 'categories.view';
    case CATEGORIES_CREATE = 'categories.create';
    case CATEGORIES_EDIT   = 'categories.edit';
    case CATEGORIES_DELETE = 'categories.delete';

    // Reviews
    case REVIEWS_VIEW     = 'reviews.view';
    case REVIEWS_MODERATE = 'reviews.moderate';

    // Keys / Licenses
    case KEYS_VIEW   = 'keys.view';
    case KEYS_IMPORT = 'keys.import';
    case KEYS_DELETE = 'keys.delete';

    // Promotions
    case PROMOTIONS_VIEW   = 'promotions.view';
    case PROMOTIONS_CREATE = 'promotions.create';
    case PROMOTIONS_EDIT   = 'promotions.edit';
    case PROMOTIONS_DELETE = 'promotions.delete';

    // Deliveries
    case DELIVERIES_VIEW    = 'deliveries.view';
    case DELIVERIES_DELIVER = 'deliveries.deliver';

    // Earnings
    case EARNINGS_VIEW = 'earnings.view';

    // Subscriptions
    case SUBSCRIPTIONS_VIEW   = 'subscriptions.view';
    case SUBSCRIPTIONS_ASSIGN = 'subscriptions.assign';
    case SUBSCRIPTIONS_REVOKE = 'subscriptions.revoke';

    // Audit
    case AUDIT_VIEW = 'audit.view';

    // Dashboard
    case DASHBOARD_ADMIN  = 'dashboard.admin';
    case DASHBOARD_SELLER = 'dashboard.seller';
}
