<!-- AdminSidebar v3 — estructura unificada, sin duplicados -->
<template>
  <div class="admin-sidebar" :class="{ collapsed }">

    <!-- Brand -->
    <div class="admin-brand">
      <div class="brand-logo"><i class="pi pi-shield" /></div>
      <Transition name="label">
        <div v-show="!collapsed" class="brand-info">
          <p class="brand-title">Admin Panel</p>
          <p class="brand-sub">Nexo Digital Store</p>
        </div>
      </Transition>
     <!-- <button class="collapse-btn" v-tooltip.right="collapsed ? 'Expandir' : 'Colapsar'" @click="$emit('toggle')">
        <i :class="['pi', collapsed ? 'pi-angle-right' : 'pi-angle-left']" />
      </button>-->
    </div>

    <!-- Nav -->
    <nav class="admin-nav">

      <!-- Dashboard -->
      <Link :href="safeRoute('admin.dashboard')"
         :class="['nav-item', { active: active === 'dashboard' }]"
         v-tooltip.right="collapsed ? 'Dashboard' : ''">
        <i class="pi pi-chart-bar" />
        <Transition name="label"><span v-show="!collapsed">Dashboard</span></Transition>
      </Link>

      <!-- ── COMERCIO ─────────────────────────────────────── -->
      <p class="nav-section-label" v-show="!collapsed">Comercio</p>
      <Divider class="nav-divider" v-show="collapsed" />

      <Link v-show="can('products.view')" :href="safeRoute('admin.store.products.index')"
         :class="['nav-item', { active: active === 'products' }]"
         v-tooltip.right="collapsed ? 'Productos' : ''">
        <i class="pi pi-box" />
        <Transition name="label"><span v-show="!collapsed">Productos</span></Transition>
      </Link>

      <Link v-show="can('keys.view')" :href="safeRoute('admin.store.keys.index')"
         :class="['nav-item', { active: active === 'keys' }]"
         v-tooltip.right="collapsed ? 'Claves' : ''">
        <i class="pi pi-key" />
        <Transition name="label"><span v-show="!collapsed">Claves</span></Transition>
      </Link>

      <Link v-show="can('orders.view')" :href="safeRoute('admin.store.orders.index')"
         :class="['nav-item', { active: active === 'orders' }]"
         v-tooltip.right="collapsed ? 'Órdenes' : ''">
        <i class="pi pi-shopping-bag" />
        <Transition name="label"><span v-show="!collapsed">Órdenes</span></Transition>
      </Link>

      <Link v-show="can('deliveries.view')" :href="safeRoute('admin.store.deliveries.index')"
         :class="['nav-item', { active: active === 'deliveries' }]"
         v-tooltip.right="collapsed ? 'Entregas' : ''">
        <i class="pi pi-inbox" />
        <Transition name="label"><span v-show="!collapsed">Entregas</span></Transition>
      </Link>

      <Link v-show="can('promotions.view')" :href="safeRoute('admin.store.promotions.index')"
         :class="['nav-item', { active: active === 'promotions' }]"
         v-tooltip.right="collapsed ? 'Promociones' : ''">
        <i class="pi pi-percentage" />
        <Transition name="label"><span v-show="!collapsed">Promociones</span></Transition>
      </Link>

      <Link v-show="can('earnings.view')" :href="safeRoute('admin.store.earnings')"
         :class="['nav-item', { active: active === 'earnings' }]"
         v-tooltip.right="collapsed ? 'Ganancias' : ''">
        <i class="pi pi-dollar" />
        <Transition name="label"><span v-show="!collapsed">Ganancias</span></Transition>
      </Link>

      <!-- ── SISTEMA ──────────────────────────────────────── -->
      <p class="nav-section-label" v-show="!collapsed">Sistema</p>
      <Divider class="nav-divider" v-show="collapsed" />

      <Link v-show="can('users.view')" :href="safeRoute('admin.users.index')"
         :class="['nav-item', { active: active === 'users' }]"
         v-tooltip.right="collapsed ? 'Usuarios' : ''">
        <i class="pi pi-users" />
        <Transition name="label"><span v-show="!collapsed">Usuarios</span></Transition>
      </Link>

      <Link v-show="can('roles.view')" :href="safeRoute('admin.roles.index')"
         :class="['nav-item', { active: active === 'roles' }]"
         v-tooltip.right="collapsed ? 'Roles' : ''">
        <i class="pi pi-shield" />
        <Transition name="label"><span v-show="!collapsed">Roles</span></Transition>
      </Link>

      <Link v-show="can('categories.view')" :href="safeRoute('admin.categories.index')"
         :class="['nav-item', { active: active === 'categories' }]"
         v-tooltip.right="collapsed ? 'Categorías' : ''">
        <i class="pi pi-folder" />
        <Transition name="label"><span v-show="!collapsed">Categorías</span></Transition>
      </Link>

      <Link v-show="can('subscriptions.view')" :href="safeRoute('admin.subscriptions.index')"
         :class="['nav-item', { active: active === 'subscriptions' }]"
         v-tooltip.right="collapsed ? 'Suscripciones' : ''">
        <i class="pi pi-star" />
        <Transition name="label"><span v-show="!collapsed">Suscripciones</span></Transition>
      </Link>

      <Link v-show="can('reviews.view')" :href="safeRoute('admin.reviews.index')"
         :class="['nav-item', { active: active === 'reviews' }]"
         v-tooltip.right="collapsed ? 'Reseñas' : ''">
        <i class="pi pi-star-fill" />
        <Transition name="label"><span v-show="!collapsed">Reseñas</span></Transition>
      </Link>

      <Link v-show="can('settings.view')" :href="safeRoute('settings.general')"
         :class="['nav-item', { active: active === 'settings-general' }]"
         v-tooltip.right="collapsed ? 'Ajustes Generales' : ''">
        <i class="pi pi-cog" />
        <Transition name="label"><span v-show="!collapsed">Ajustes Generales</span></Transition>
      </Link>

      <Link v-show="can('dashboard.admin')" :href="safeRoute('admin.settings')"
         :class="['nav-item', { active: active === 'settings' }]"
         v-tooltip.right="collapsed ? 'Ajustes de Tienda' : ''">
        <i class="pi pi-shopping-bag" />
        <Transition name="label"><span v-show="!collapsed">Ajustes de Tienda</span></Transition>
      </Link>

      <Link v-show="can('audit.view')" :href="safeRoute('admin.audit-logs')"
         :class="['nav-item', { active: active === 'audit' }]"
         v-tooltip.right="collapsed ? 'Auditoría' : ''">
        <i class="pi pi-history" />
        <Transition name="label"><span v-show="!collapsed">Auditoría</span></Transition>
      </Link>

    </nav>

    <!-- Footer -->
    <div class="admin-footer">
      <Divider class="nav-divider" />
      <Link :href="safeRoute('home')" class="nav-item" v-tooltip.right="collapsed ? 'Ver tienda' : ''">
        <i class="pi pi-storefront" />
        <Transition name="label"><span v-show="!collapsed">Ver tienda</span></Transition>
      </Link>
    </div>

  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
const props = defineProps({
  active:    { type: String,  default: '' },
  collapsed: { type: Boolean, default: false },
  can:       { type: Object,  default: () => ({}) },
});
defineEmits(['toggle']);

function can(perm) {
  return props.can[perm] === true;
}

function safeRoute(r) {
  try { return route(r); } catch { return '#'; }
}
</script>

<style scoped>
.admin-sidebar {
  width: 232px; height: 100vh; position: sticky; top: 0;
  display: flex; flex-direction: column;
  background: color-mix(in srgb, var(--p-surface-950) 97%, var(--p-primary-color));
  border-right: 1px solid var(--p-surface-800);
  transition: width 0.25s ease; flex-shrink: 0; overflow: hidden;
}
.admin-sidebar.collapsed { width: 60px; }

/* Brand */
.admin-brand {
  display: flex; align-items: center; gap: 0.625rem;
  padding: 1rem 0.75rem; border-bottom: 1px solid var(--p-surface-800); min-height: 60px;
}
.brand-logo {
  width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0;
  background: linear-gradient(135deg, var(--p-primary-color), var(--p-primary-400, var(--p-primary-color)));
  display: flex; align-items: center; justify-content: center; color: white; font-size: 0.95rem;
}
.brand-info { flex: 1; overflow: hidden; }
.brand-title { font-size: 0.8rem; font-weight: 700; color: var(--p-text-color); margin: 0; white-space: nowrap; }
.brand-sub   { font-size: 0.67rem; color: var(--p-text-muted-color); margin: 0; white-space: nowrap; }
.collapse-btn {
  width: 22px; height: 22px; border-radius: 6px; border: none; flex-shrink: 0;
  background: var(--p-surface-800); color: var(--p-text-muted-color); cursor: pointer;
  display: flex; align-items: center; justify-content: center; font-size: 0.72rem; transition: all 0.2s;
}
.collapse-btn:hover { background: color-mix(in srgb, var(--p-primary-color) 15%, transparent); color: var(--p-primary-color); }

/* Nav */
.admin-nav {
  flex: 1; overflow-y: auto; overflow-x: hidden; scrollbar-width: thin;
  padding: 0.625rem 0.5rem; display: flex; flex-direction: column; gap: 1px;
}
.nav-section-label {
  font-size: 0.62rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.09em;
  color: var(--p-text-muted-color); padding: 0.625rem 0.5rem 0.2rem; margin: 0;
  opacity: 0.65;
}
.nav-divider { margin: 0.375rem 0 !important; opacity: 0.4; }

/* Nav items */
.nav-item {
  display: flex; align-items: center; gap: 0.7rem; padding: 0.52rem 0.6rem;
  border-radius: 8px; color: var(--p-text-muted-color); font-size: 0.82rem; font-weight: 500;
  text-decoration: none; transition: all 0.15s; white-space: nowrap; overflow: hidden; cursor: pointer;
}
.nav-item:hover  {
  background: color-mix(in srgb, var(--p-primary-color) 10%, transparent);
  color: var(--p-primary-color);
}
.nav-item.active {
  background: color-mix(in srgb, var(--p-primary-color) 13%, transparent);
  color: var(--p-primary-color);
  font-weight: 600;
  box-shadow: inset 3px 0 0 var(--p-primary-color);
}
.nav-item .pi { font-size: 0.87rem; width: 15px; text-align: center; flex-shrink: 0; }

/* Footer */
.admin-footer { padding: 0.5rem; }

/* Transitions */
.label-enter-active, .label-leave-active { transition: opacity 0.18s; }
.label-enter-from, .label-leave-to { opacity: 0; }
</style>
