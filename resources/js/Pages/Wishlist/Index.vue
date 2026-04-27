<template>
  <AppLayout>
    <Head title="Lista de Deseos — Nexo Digital Store" />

    <!-- Header -->
    <div class="wishlist-header">
      <div class="wl-title-row">
        <div class="wl-icon-wrap">
          <i class="pi pi-heart-fill" />
        </div>
        <div>
          <h1 class="wl-title font-display">Lista de Deseos</h1>
          <p class="wl-subtitle">{{ items.length }} producto{{ items.length !== 1 ? 's' : '' }} guardado{{ items.length !== 1 ? 's' : '' }}</p>
        </div>
      </div>

      <button v-if="items.length > 0" class="btn-clear" @click="clearAll">
        <i class="pi pi-trash" />
        Vaciar lista
      </button>
    </div>

    <!-- Empty State -->
    <div v-if="items.length === 0" class="wl-empty glass">
      <div class="empty-icon-wrap">
        <i class="pi pi-heart" />
      </div>
      <h2 class="font-display">Tu lista está vacía</h2>
      <p>Guarda productos que te interesen para comprarlos más tarde. Usa el <i class="pi pi-heart" style="color:#ec4899"/> en cualquier producto.</p>
      <Link :href="route('products.index')" class="btn btn-primary">
        <i class="pi pi-shopping-bag" /> Explorar productos
      </Link>
    </div>

    <!-- Grid -->
    <div v-else class="wl-grid">
      <div
        v-for="item in items"
        :key="item.id"
        class="wl-card glass"
        :class="{ 'out-of-stock': !item.in_stock }"
      >
        <!-- Out of stock ribbon -->
        <div v-if="!item.in_stock" class="oos-ribbon">Sin stock</div>

        <!-- Cover -->
        <Link :href="route('products.show', item.slug)" class="wl-cover">
          <img v-if="item.cover_image" :src="item.cover_image" :alt="item.name" loading="lazy" />
          <div v-else class="wl-cover-ph"><i class="pi pi-tag" /></div>
          <!-- Discount badge -->
          <span v-if="item.discount_percent > 0" class="wl-discount-badge">-{{ item.discount_percent }}%</span>
        </Link>

        <!-- Info -->
        <div class="wl-info">
          <div class="wl-meta">
            <span v-if="item.platform" class="meta-chip">{{ item.platform }}</span>
            <span v-if="item.region"   class="meta-chip">{{ item.region }}</span>
          </div>

          <Link :href="route('products.show', item.slug)" class="wl-name">{{ item.name }}</Link>

          <!-- Price -->
          <div class="wl-price-row">
            <span v-if="item.discount_percent > 0" class="price-orig">${{ fmt(item.price_usd) }}</span>
            <span class="price-now">${{ fmt(item.discounted_price_usd || item.price_usd) }}</span>
            <span v-if="item.cashback_percent > 0" class="cashback-pill">
              <i class="pi pi-wallet" /> {{ item.cashback_percent }}% NT
            </span>
          </div>

          <p class="wl-added">Guardado {{ item.added_at }}</p>

          <!-- Actions -->
          <div class="wl-actions">
            <button
              class="btn-cart"
              :class="{ 'btn-cart-disabled': !item.in_stock }"
              :disabled="!item.in_stock"
              @click="addToCart(item)"
            >
              <i class="pi" :class="item.in_stock ? 'pi-cart-plus' : 'pi-clock'" />
              {{ item.in_stock ? 'Añadir al carrito' : 'Sin stock' }}
            </button>
            <button class="btn-remove" @click="removeItem(item.id)" title="Quitar de wishlist">
              <i class="pi pi-heart-fill" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
  items: { type: Array, default: () => [] },
});

const toast = useToast();

function fmt(v) { return parseFloat(v || 0).toFixed(2); }

function addToCart(item) {
  router.post(route('cart.add'), { ulid: item.ulid }, {
    preserveScroll: true,
    onSuccess: () => toast.add({ severity: 'success', summary: '¡Añadido al carrito!', life: 2500 }),
  });
}

function removeItem(id) {
  router.delete(route('wishlist.destroy', id), {
    preserveScroll: true,
    onSuccess: () => toast.add({ severity: 'info', summary: 'Producto eliminado de la lista', life: 2500 }),
  });
}

function clearAll() {
  router.delete(route('wishlist.clear'), {
    preserveScroll: true,
    onSuccess: () => toast.add({ severity: 'info', summary: 'Lista de deseos vaciada', life: 2500 }),
  });
}
</script>

<style scoped>
/* Header */
.wishlist-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;
}
.wl-title-row { display: flex; align-items: center; gap: 1rem; }
.wl-icon-wrap {
  width: 52px; height: 52px; border-radius: 14px;
  background: rgba(236,72,153,0.12);
  border: 1px solid rgba(236,72,153,0.25);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.35rem; color: #ec4899;
}
.wl-title   { font-size: clamp(1.25rem, 3vw, 1.75rem); font-weight: 800; color: var(--c-text); margin: 0; }
.wl-subtitle{ font-size: 0.85rem; color: var(--c-text-muted); margin: 0.2rem 0 0; }

.btn-clear {
  display: flex; align-items: center; gap: 0.5rem;
  padding: 0.5rem 1rem; border-radius: 8px;
  background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.2);
  color: #ef4444; font-size: 0.82rem; font-weight: 600; cursor: pointer;
  transition: all 0.2s;
}
.btn-clear:hover { background: rgba(239,68,68,0.15); }

/* Empty */
.wl-empty {
  max-width: 480px; margin: 4rem auto; padding: 3.5rem 2rem;
  border-radius: 20px; text-align: center; display: flex; flex-direction: column;
  align-items: center; gap: 1rem;
}
.empty-icon-wrap {
  width: 72px; height: 72px; border-radius: 20px;
  background: rgba(236,72,153,0.1); border: 1px solid rgba(236,72,153,0.2);
  display: flex; align-items: center; justify-content: center;
  font-size: 2rem; color: #ec4899;
}
.wl-empty h2 { font-size: 1.25rem; font-weight: 700; color: var(--c-text); margin: 0; }
.wl-empty p  { font-size: 0.875rem; color: var(--c-text-muted); line-height: 1.6; max-width: 340px; }

.btn {
  display: inline-flex; align-items: center; gap: 0.5rem;
  padding: 0.6rem 1.25rem; border-radius: 10px; font-size: 0.875rem;
  font-weight: 600; text-decoration: none; border: none; cursor: pointer; transition: all 0.2s;
}
.btn-primary {
  background: var(--c-primary); color: white;
  border: 1px solid color-mix(in srgb, var(--c-primary) 80%, white);
}
.btn-primary:hover { filter: brightness(1.1); }

/* Grid */
.wl-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 1.25rem;
}

/* Card */
.wl-card {
  border-radius: 16px; overflow: hidden; display: flex; flex-direction: column;
  position: relative; transition: transform 0.2s, box-shadow 0.2s;
}
.wl-card:hover { transform: translateY(-3px); box-shadow: 0 16px 40px var(--c-shadow); }
.wl-card.out-of-stock { opacity: 0.7; }

.oos-ribbon {
  position: absolute; top: 0.625rem; left: 0.625rem; z-index: 3;
  background: rgba(239,68,68,0.85); color: white;
  font-size: 0.7rem; font-weight: 700; padding: 0.2rem 0.5rem; border-radius: 5px;
}

/* Cover */
.wl-cover {
  position: relative; display: block;
  aspect-ratio: 16/9; overflow: hidden;
  background: var(--c-card);
}
.wl-cover img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s; }
.wl-card:hover .wl-cover img { transform: scale(1.05); }
.wl-cover-ph {
  width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;
  color: var(--c-text-subtle); font-size: 2.5rem;
}
.wl-discount-badge {
  position: absolute; top: 0.625rem; right: 0.625rem;
  background: rgba(239,68,68,0.85); color: white;
  font-size: 0.7rem; font-weight: 700; padding: 0.2rem 0.5rem; border-radius: 5px;
}

/* Info */
.wl-info { padding: 0.875rem; display: flex; flex-direction: column; gap: 0.5rem; flex: 1; }
.wl-meta { display: flex; gap: 0.375rem; flex-wrap: wrap; }
.meta-chip {
  font-size: 0.65rem; font-weight: 700; padding: 0.15rem 0.45rem;
  background: var(--c-card); border-radius: 4px; color: var(--c-text-muted);
  text-transform: uppercase; border: 1px solid var(--c-border);
}

.wl-name {
  font-size: 0.9rem; font-weight: 600; color: var(--c-text); text-decoration: none;
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
  transition: color 0.2s;
}
.wl-name:hover { color: var(--c-primary); }

.wl-price-row { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
.price-orig { font-size: 0.75rem; color: var(--c-text-muted); text-decoration: line-through; }
.price-now  { font-size: 1.1rem; font-weight: 800; color: var(--c-text); }
.cashback-pill {
  display: flex; align-items: center; gap: 0.2rem;
  font-size: 0.68rem; font-weight: 700; color: #06b6d4;
  background: rgba(6,182,212,0.1); border: 1px solid rgba(6,182,212,0.2);
  padding: 0.15rem 0.4rem; border-radius: 5px;
}

.wl-added { font-size: 0.72rem; color: var(--c-text-subtle); margin: 0; }

/* Actions */
.wl-actions { display: flex; gap: 0.5rem; margin-top: auto; }
.btn-cart {
  flex: 1; display: flex; align-items: center; justify-content: center; gap: 0.4rem;
  padding: 0.55rem; border-radius: 8px; font-size: 0.82rem; font-weight: 600;
  background: var(--c-primary); color: white; border: none; cursor: pointer; transition: all 0.2s;
}
.btn-cart:hover:not(:disabled) { filter: brightness(1.12); }
.btn-cart-disabled {
  background: var(--c-card); color: var(--c-text-muted);
  border: 1px solid var(--c-border); cursor: not-allowed;
}
.btn-remove {
  width: 38px; height: 38px; border-radius: 8px; border: none;
  display: flex; align-items: center; justify-content: center;
  background: rgba(236,72,153,0.1); color: #ec4899; cursor: pointer;
  transition: all 0.2s; font-size: 0.9rem; flex-shrink: 0;
}
.btn-remove:hover { background: rgba(236,72,153,0.2); }
</style>
