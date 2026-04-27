<template>
  <Link :href="route('products.show', product.slug)" class="product-card" v-ripple>
    <!-- Badges -->
    <div class="product-badges">
      <Tag v-if="product.discount_percent > 0" :value="`-${product.discount_percent}%`" severity="danger" rounded />
      <Tag v-if="product.is_featured" icon="pi pi-star-fill" severity="warn" rounded />
      <Tag v-if="product.is_preorder" value="Pre-order" severity="info" rounded />
      <Tag v-if="product.stock_count === 0" :value="t('catalog.outOfStock')" severity="secondary" rounded />
    </div>

    <!-- Cover -->
    <div class="product-cover">
      <img v-if="product.cover_image" :src="product.cover_image" :alt="product.name" loading="lazy" />
      <div v-else class="product-cover-placeholder">
        <i class="pi pi-tag" />
      </div>
      <!-- Hover overlay -->
      <div class="product-overlay">
        <Button
          icon="pi pi-eye"
          :label="t('common.viewDetails')"
          size="small"
          class="overlay-btn"
        />
      </div>
      <!-- Wishlist heart button -->
      <button
        v-if="auth.user"
        class="wishlist-heart"
        :class="{ 'in-wishlist': inWishlist, 'is-loading': wishlistLoading }"
        @click.prevent="toggleWishlist(product.id)"
        :title="inWishlist ? t('nav.wishlist') : t('nav.wishlist')"
        :disabled="wishlistLoading"
      >
        <i :class="['pi', wishlistLoading ? 'pi-spin pi-spinner' : (inWishlist ? 'pi-heart-fill' : 'pi-heart')]" />
      </button>
    </div>

    <!-- Info -->
    <div class="product-info">
      <div class="product-meta">
        <Chip v-if="product.platform" :label="product.platform" class="chip-xs" />
        <Chip v-if="product.region"   :label="product.region"   class="chip-xs chip-region" />
      </div>

      <h3 class="product-name">{{ product.name }}</h3>

      <Rating
        v-if="product.rating_count > 0"
        :modelValue="Math.round(product.rating)"
        readonly
        :cancel="false"
        class="product-rating"
      />

      <div class="product-pricing">
        <div class="prices">
          <span v-if="product.has_discount" class="price-original">{{ formatCurrency(product.price_usd, currentCurrency.value) }}</span>
          <span class="price-current">{{ formatCurrency(product.discounted_price_usd ?? product.price_usd, currentCurrency.value) }}</span>
          <Tag v-if="product.is_subscription_discount" value="Plus" severity="info" size="small" class="plus-badge" v-tooltip.top="'Precio especial por tu suscripción'" />
        </div>
        <div v-if="product.cashback_percent > 0" class="cashback-badge">
          <i class="pi pi-wallet" />
          {{ product.cashback_percent }}% NT
        </div>
      </div>

      <Button
        :label="product.stock_count === 0 ? t('catalog.outOfStock') : t('catalog.addToCart')"
        :icon="product.stock_count === 0 ? 'pi pi-times-circle' : 'pi pi-cart-plus'"
        :disabled="product.stock_count === 0"
        size="small"
        class="w-full"
        @click.prevent="addToCart"
      />

      <small v-if="product.stock_count > 0 && product.stock_count <= 5" class="stock-warning">
        <i class="pi pi-exclamation-triangle" /> {{ t('home.features.deliveryDesc') }} ({{ product.stock_count }})
      </small>
    </div>
  </Link>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useUtils } from '@/composables/useUtils';
import { useWishlist } from '@/composables/useWishlist';

const { t } = useI18n();
const props = defineProps({
  product: { type: Object, required: true },
});

const page = usePage();
const auth = computed(() => page.props.auth);
const { formatCurrency } = useUtils();
const { inWishlist, loading: wishlistLoading, toggleWishlist } = useWishlist(props.product.in_wishlist ?? false);

const currentCurrency = ref(localStorage.getItem('nexo-currency') || 'PEN');

function handleCurrencyChange(e) {
  currentCurrency.value = e.detail;
}

onMounted(() => {
  window.addEventListener('nexo-currency-changed', handleCurrencyChange);
});

onUnmounted(() => {
  window.removeEventListener('nexo-currency-changed', handleCurrencyChange);
});

function addToCart() {
  router.post(route('cart.add'), { ulid: props.product.ulid || props.product.id });
}
</script>

<style scoped>
.product-card {
  display: flex; flex-direction: column;
  border-radius: 16px; overflow: hidden;
  text-decoration: none;
  background: var(--c-surface);
  border: 1px solid var(--c-border);
  transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
  cursor: pointer; position: relative;
}
.product-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 16px 40px var(--c-shadow);
  border-color: color-mix(in srgb, var(--c-primary) 40%, transparent);
}

.product-badges {
  position: absolute; top: 0.5rem; left: 0.5rem;
  display: flex; gap: 0.25rem; z-index: 2; flex-wrap: wrap;
}

.product-cover {
  aspect-ratio: 16/9; overflow: hidden;
  background: var(--c-card); position: relative;
}
.product-cover img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s ease; }
.product-card:hover .product-cover img { transform: scale(1.06); }

.product-cover-placeholder {
  width: 100%; height: 100%;
  display: flex; align-items: center; justify-content: center;
  color: var(--c-text-subtle); font-size: 2.5rem;
}

.product-overlay {
  position: absolute; inset: 0; background: var(--c-overlay);
  display: flex; align-items: center; justify-content: center;
  opacity: 0; transition: opacity 0.25s;
}
.product-card:hover .product-overlay { opacity: 1; }
.overlay-btn { pointer-events: none; }

/* Wishlist heart */
.wishlist-heart {
  position: absolute; top: 0.5rem; right: 0.5rem; z-index: 3;
  width: 32px; height: 32px; border-radius: 8px;
  background: rgba(0,0,0,0.55); border: none; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  color: white; font-size: 0.875rem;
  opacity: 0; transition: all 0.2s;
  backdrop-filter: blur(4px);
}
.product-card:hover .wishlist-heart { opacity: 1; }
.wishlist-heart.in-wishlist { opacity: 1; color: #ec4899; background: rgba(236,72,153,0.2); }
.wishlist-heart:hover { color: #ec4899; background: rgba(236,72,153,0.25); transform: scale(1.1); }
.wishlist-heart.is-loading { cursor: wait; opacity: 0.7 !important; }

.product-info { padding: 0.875rem; display: flex; flex-direction: column; gap: 0.5rem; flex: 1; }

.product-meta { display: flex; gap: 0.375rem; flex-wrap: wrap; }
:deep(.chip-xs .p-chip) { padding: 0.1rem 0.4rem !important; font-size: 0.65rem !important; font-weight: 700 !important; }
:deep(.chip-region .p-chip) { background: var(--c-card) !important; }

.product-name {
  font-size: 0.875rem; font-weight: 600; color: var(--c-text); line-height: 1.4;
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin: 0;
}

:deep(.product-rating .p-rating-icon) { font-size: 0.7rem !important; }

.product-pricing {
  display: flex; align-items: center; justify-content: space-between; margin-top: auto;
}
.prices { display: flex; align-items: baseline; gap: 0.4rem; }
.price-original { font-size: 0.75rem; color: var(--c-text-subtle); text-decoration: line-through; }
.price-current  { font-size: 1.1rem; font-weight: 800; color: var(--c-text); }
.plus-badge     { font-size: 0.6rem !important; height: 1.2rem; padding: 0 0.3rem !important; }

.cashback-badge {
  display: flex; align-items: center; gap: 0.25rem;
  font-size: 0.68rem; font-weight: 700;
  color: #06b6d4; background: rgba(6,182,212,0.1);
  border: 1px solid rgba(6,182,212,0.2); padding: 0.15rem 0.375rem; border-radius: 5px;
}

.stock-warning {
  font-size: 0.7rem; font-weight: 600; color: #f59e0b;
  display: flex; align-items: center; gap: 0.25rem;
}
</style>
