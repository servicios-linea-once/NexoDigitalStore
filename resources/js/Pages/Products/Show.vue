<template>
  <AppLayout>
    <Head :title="`${product.name} — Nexo Digital Store`" />

    <!-- Breadcrumb -->
    <nav class="breadcrumb" v-motion-fade-visible>
      <Link :href="route('home')">{{ t('nav.home') }}</Link>
      <i class="pi pi-chevron-right" />
      <Link :href="route('products.index')">{{ t('nav.products') }}</Link>
      <i class="pi pi-chevron-right" />
      <Link v-if="product.category" :href="route('products.category', product.category.slug)">
        {{ product.category.name }}
      </Link>
      <i class="pi pi-chevron-right" />
      <span>{{ product.name }}</span>
    </nav>

    <!-- Product Detail -->
    <div class="product-detail-grid">

      <!-- Left: Images -->
      <div class="product-images" v-motion-slide-visible-left>
        <div class="product-main-image glass" :class="{ 'has-image': product.cover_image }">
          <!-- Glow backdrop -->
          <div v-if="product.cover_image" class="image-glow" :style="`background-image: url('${product.cover_image}')`" />
          <img v-if="product.cover_image" :src="product.cover_image" :alt="product.name" class="main-img" loading="lazy" />
          <div v-else class="main-img-placeholder">
            <div class="placeholder-icon-wrap">
              <i class="pi pi-tag" />
            </div>
            <span class="placeholder-name">{{ product.name }}</span>
          </div>
          <!-- Badges -->
          <div class="detail-badges">
            <span v-if="discountedPercent > 0" class="badge badge-discount shadow-lg shadow-red-500/20">
              <i class="pi pi-tag" /> -{{ discountedPercent }}%
            </span>
            <span v-if="product.is_featured" class="badge badge-featured shadow-lg shadow-amber-500/20">
              <i class="pi pi-star-fill" /> {{ t('catalog.featured') }}
            </span>
            <span v-if="product.is_preorder" class="badge badge-preorder shadow-lg shadow-blue-500/20">
              <i class="pi pi-clock" /> Pre-order
            </span>
          </div>
        </div>

        <!-- Trust features -->
        <div class="trust-features glass">
          <div class="trust-feat group">
            <div class="trust-icon-wrap trust-green group-hover:scale-110 transition-transform"><i class="pi pi-bolt" /></div>
            <div>
              <p class="trust-feat-title">{{ t('home.features.delivery') }}</p>
              <p class="trust-feat-sub">{{ t('home.features.deliveryDesc') }}</p>
            </div>
          </div>
          <div class="trust-feat group">
            <div class="trust-icon-wrap trust-blue group-hover:scale-110 transition-transform"><i class="pi pi-shield" /></div>
            <div>
              <p class="trust-feat-title">Clave auténtica</p>
              <p class="trust-feat-sub">100% original</p>
            </div>
          </div>
          <div class="trust-feat group">
            <div class="trust-icon-wrap trust-purple group-hover:scale-110 transition-transform"><i class="pi pi-refresh" /></div>
            <div>
              <p class="trust-feat-title">Garantía 24h</p>
              <p class="trust-feat-sub">Reposición gratis</p>
            </div>
          </div>
          <div class="trust-feat group">
            <div class="trust-icon-wrap trust-orange group-hover:scale-110 transition-transform"><i class="pi pi-headphones" /></div>
            <div>
              <p class="trust-feat-title">{{ t('home.features.support') }}</p>
              <p class="trust-feat-sub">{{ t('home.features.supportDesc') }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Right: Info -->
      <div class="product-info-panel" v-motion-slide-visible-right>

        <!-- Category + Platform -->
        <div class="product-meta-row">
          <Link v-if="product.category" :href="route('products.category', product.category.slug)" class="meta-link">
            {{ product.category.name }}
          </Link>
          <span v-if="product.platform" class="meta-badge">{{ product.platform }}</span>
          <span v-if="product.region" class="meta-badge meta-badge-region">{{ product.region }}</span>
        </div>

        <h1 class="product-detail-name font-display">{{ product.name }}</h1>

        <!-- Rating -->
        <div class="product-detail-rating" v-if="product.rating_count > 0">
          <div class="stars-lg">
            <i v-for="i in 5" :key="i" class="pi" :class="i <= Math.round(product.rating) ? 'pi-star-fill' : 'pi-star'" />
          </div>
          <span class="rating-val">{{ Number(product.rating).toFixed(1) }}</span>
          <span class="rating-cnt">({{ product.rating_count }} {{ t('catalog.reviews').toLowerCase() }})</span>
        </div>

        <!-- Divider -->
        <div class="section-divider" />

        <!-- Price -->
        <div class="product-price-block">
          <div v-if="discountedPercent > 0" class="discount-banner" v-motion-pop-visible>
            <i class="pi pi-percentage" />
            <span>{{ t('catalog.onSale') }}: <strong>{{ discountedPercent }}%</strong> {{ t('catalog.discount').toLowerCase() }}</span>
          </div>
          <div class="price-row">
            <span v-if="discountedPercent > 0" class="price-orig">{{ formatCurrency(basePriceUSD, currentCurrency.value) }}</span>
            <span class="price-now">{{ formatCurrency(finalPriceUSD, currentCurrency.value) }}</span>
          </div>
          <div class="cashback-row shadow-sm" v-if="product.cashback_percent > 0">
            <i class="pi pi-wallet" />
            {{ t('catalog.cashback') }}: <strong>{{ product.cashback_percent }}% NT</strong>
          </div>
        </div>

        <!-- Stock indicator -->
        <div class="stock-indicator" :class="stockClass">
          <div class="stock-dot" />
          <span>{{ stockLabel }}</span>
        </div>

        <!-- Variants -->
        <div class="product-variants" v-if="product.variants && product.variants.length > 0">
          <p class="variants-label">Selecciona una variante:</p>
          <div class="variants-grid">
            <button
              v-for="v in product.variants"
              :key="v.id"
              class="variant-btn"
              :class="{ active: selectedVariant?.id === v.id }"
              @click="selectedVariant = v"
            >
              <div class="v-name">{{ v.variant_name || v.name }}</div>
              <div class="v-price">{{ formatCurrency(v.discounted_price_usd ?? v.price_usd, currentCurrency.value) }}</div>
            </button>
          </div>
        </div>

        <!-- Quantity + CTA -->
        <div class="product-actions" v-if="stockCount > 0">
          <div class="qty-control shadow-inner">
            <button class="qty-btn" @click="qty > 1 && qty--"><i class="pi pi-minus" /></button>
            <span class="qty-value">{{ qty }}</span>
            <button class="qty-btn" @click="qty < Math.min(stockCount, 10) && qty++"><i class="pi pi-plus" /></button>
          </div>
          <button class="btn btn-outline add-cart-btn" @click="addToCart" :disabled="inCart">
            <i class="pi" :class="inCart ? 'pi-check' : 'pi-shopping-cart'" />
            {{ inCart ? t('cart.title') : t('catalog.addToCart') }}
          </button>
          <button class="btn btn-primary buy-btn shadow-lg shadow-indigo-500/20" @click="buyNow">
            <i class="pi pi-bolt" />
            {{ t('catalog.buyNow') }} — {{ formatCurrency(finalPriceUSD * qty, currentCurrency.value) }}
          </button>
          <button
            class="wishlist-btn group"
            :class="{ 'in-wishlist': inWishlist, 'is-loading': wishlistLoading }"
            @click="toggleWishlist(product.id)"
            :disabled="wishlistLoading"
            :title="t('nav.wishlist')"
          >
            <i :class="['pi', wishlistLoading ? 'pi-spin pi-spinner' : (inWishlist ? 'pi-heart-fill' : 'pi-heart'), 'group-hover:scale-110 transition-transform']" />
          </button>
        </div>

        <!-- Out of stock -->
        <div v-else class="oos-actions">
          <button class="btn btn-oos" disabled>
            <i class="pi pi-times-circle" /> {{ t('catalog.outOfStock') }}
          </button>
          <button class="btn-wl-oos" :class="{ 'in-wishlist': isAuth && inWishlist, 'is-loading': wishlistLoading }" @click="toggleWishlist(product.id)" :disabled="wishlistLoading">
            <i :class="['pi', wishlistLoading ? 'pi-spin pi-spinner' : (isAuth && inWishlist ? 'pi-heart-fill' : 'pi-heart')]" />
            {{ isAuth ? (inWishlist ? t('nav.wishlist') : 'Avisarme cuando esté disponible') : t('auth.login') }}
          </button>
        </div>

        <!-- Description -->
        <div class="product-description">
          <h3><i class="pi pi-align-left" /> Descripción</h3>
          <p>{{ product.description }}</p>
        </div>

        <!-- Trust Box -->
        <div class="store-trust-box glass">
          <div class="trust-item">
            <div class="trust-item-icon trust-check shadow-lg shadow-green-500/20"><i class="pi pi-check-circle" /></div>
            <div>
              <p class="trust-title">Garantía Nexo Digital</p>
              <p class="trust-sub">Claves 100% originales y soporte vitalicio.</p>
            </div>
          </div>
          <div class="trust-divider" />
          <div class="trust-item">
            <div class="trust-item-icon trust-bolt shadow-lg shadow-amber-500/20"><i class="pi pi-bolt" /></div>
            <div>
              <p class="trust-title">{{ t('home.features.delivery') }}</p>
              <p class="trust-sub">Recibe tus licencias al instante tras confirmar el pago.</p>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- Reviews Section -->
    <section class="reviews-section" v-if="product.reviews && product.reviews.length > 0" v-motion-fade-visible-once>
      <h2 class="section-title font-display">
        <i class="pi pi-star-fill text-amber-500 mr-2" /> {{ t('catalog.reviews') }}
      </h2>
      <div class="reviews-grid">
        <div v-for="review in product.reviews" :key="review.id" class="review-card glass group hover:border-brand-400 transition-colors">
          <div class="review-header">
            <img
              :src="review.user?.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(review.user?.name ?? 'U')}&background=27272a&color=fff`"
              class="review-avatar shadow-md"
            />
            <div class="review-meta">
              <p class="review-name">{{ review.user?.name }}</p>
              <div class="stars-sm">
                <i v-for="i in 5" :key="i" class="pi" :class="i <= review.rating ? 'pi-star-fill text-amber-400' : 'pi-star text-surface-700'" />
              </div>
            </div>
            <span class="review-rating-badge">{{ review.rating }}.0</span>
          </div>
          <p class="review-body">{{ review.comment }}</p>
        </div>
      </div>
    </section>

    <!-- Related -->
    <section class="section" v-if="related.length > 0" v-motion-fade-visible-once>
      <h2 class="section-title font-display">Productos relacionados</h2>
      <div class="products-grid mt-6">
        <ProductCard v-for="p in related" :key="p.id" :product="p" />
      </div>
    </section>

  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useUtils } from '@/composables/useUtils';
import { useWishlist } from '@/composables/useWishlist';
import AppLayout from '@/Layouts/AppLayout.vue';
import ProductCard from '@/Components/ProductCard.vue';

const { t }     = useI18n();
const page   = usePage();
const isAuth = computed(() => !!page.props.auth?.user);

const props = defineProps({
  product: { type: Object, required: true },
  related: { type: Array,  default: () => [] },
});
console.log(props.product);
const { formatCurrency } = useUtils();
const { inWishlist, loading: wishlistLoading, toggleWishlist } = useWishlist(props.product.in_wishlist ?? false);

const qty         = ref(1);
const inCart      = ref(false);
const currentCurrency = ref(localStorage.getItem('nexo-currency') || 'PEN');

const selectedVariant = ref(
  props.product.variants?.length > 0 ? props.product.variants[0] : null
);

const currentProduct = computed(() => selectedVariant.value ?? props.product);

const discountedPercent = computed(() => Number(currentProduct.value.discount_percent ?? 0));

const finalPriceUSD = computed(() => {
  const item = currentProduct.value;
  const p = parseFloat(item.discounted_price_usd ?? item.price_usd ?? 0);
  return isNaN(p) ? 0 : p;
});

const basePriceUSD = computed(() => {
  const p = parseFloat(currentProduct.value.price_usd ?? 0);
  return isNaN(p) ? 0 : p;
});

const stockCount = computed(() => currentProduct.value.stock_count ?? 0);

const stockClass = computed(() => {
  if (stockCount.value === 0) return 'stock-out';
  if (stockCount.value <= 5)  return 'stock-low';
  return 'stock-ok';
});

const stockLabel = computed(() => {
  if (stockCount.value === 0) return t('catalog.outOfStock');
  if (stockCount.value <= 5)  return `${t('home.features.deliveryDesc')} (${stockCount.value})`;
  return `${stockCount.value} ${t('catalog.inStock').toLowerCase()}`;
});

function handleCurrencyChange(e) {
  currentCurrency.value = e.detail;
}

onMounted(() => {
  window.addEventListener('nexo-currency-changed', handleCurrencyChange);
});

onUnmounted(() => {
  window.removeEventListener('nexo-currency-changed', handleCurrencyChange);
});

function requireAuth() {
  if (!isAuth.value) {
    router.visit(route('login'));
    return false;
  }
  return true;
}

function addToCart() {
  if (!requireAuth()) return;
  router.post(route('cart.add'), { ulid: currentProduct.value.ulid }, {
    preserveScroll: true,
    onSuccess: () => { inCart.value = true; },
  });
}

function buyNow() {
  if (!requireAuth()) return;
  router.post(route('cart.add'), { ulid: currentProduct.value.ulid }, {
    preserveScroll: false,
    onSuccess: () => { router.visit(route('cart.index')); },
  });
}
</script>

<style scoped>
/* ─── Breadcrumb ─── */
.breadcrumb {
  display: flex; align-items: center; gap: 0.5rem;
  font-size: 0.8rem; color: var(--color-surface-500);
  margin-bottom: 2rem; flex-wrap: wrap;
}
.breadcrumb a { color: var(--color-surface-400); text-decoration: none; transition: color 0.2s; font-weight: 500; }
.breadcrumb a:hover { color: var(--color-brand-400); }
.breadcrumb .pi { font-size: 0.6rem; color: var(--color-surface-700); }
.breadcrumb span { color: var(--color-surface-200); font-weight: 600; }

/* ─── Grid ─── */
.product-detail-grid {
  display: grid;
  grid-template-columns: 420px 1fr;
  gap: 3rem;
  margin-bottom: 4rem;
  align-items: start;
}
@media (max-width: 1024px) { .product-detail-grid { grid-template-columns: 1fr; gap: 2rem; } }

/* ─── Image Section ─── */
.product-main-image {
  position: relative;
  border-radius: 24px;
  overflow: hidden;
  aspect-ratio: 4/3;
  border: 1px solid rgba(255,255,255,0.08);
  box-shadow: 0 20px 50px rgba(0,0,0,0.3);
}
.image-glow {
  position: absolute; inset: -30px;
  background-size: cover; background-position: center;
  filter: blur(50px) saturate(1.8);
  opacity: 0.3;
  z-index: 0;
}
.main-img {
  width: 100%; height: 100%;
  object-fit: cover;
  position: relative; z-index: 1;
}
.main-img-placeholder {
  width: 100%; height: 100%;
  display: flex; flex-direction: column;
  align-items: center; justify-content: center; gap: 1.25rem;
  background: linear-gradient(135deg, var(--color-surface-950) 0%, var(--color-surface-800) 100%);
}
.placeholder-icon-wrap {
  width: 80px; height: 80px;
  border-radius: 24px;
  background: linear-gradient(135deg, var(--color-brand-600), var(--color-brand-400));
  display: flex; align-items: center; justify-content: center;
  font-size: 2rem; color: white;
  box-shadow: 0 0 40px rgba(99, 102, 241, 0.4);
}
.placeholder-name {
  font-size: 0.875rem; color: var(--color-surface-400);
  text-align: center; padding: 0 2rem; line-height: 1.5; font-weight: 500;
}

/* Badges */
.detail-badges {
  position: absolute; top: 1.25rem; left: 1.25rem;
  display: flex; gap: 0.5rem; flex-wrap: wrap; z-index: 2;
}
.badge {
  display: inline-flex; align-items: center; gap: 0.4rem;
  padding: 0.4rem 0.875rem; border-radius: 10px;
  font-size: 0.75rem; font-weight: 800; backdrop-filter: blur(12px);
  text-transform: uppercase; letter-spacing: 0.02em;
}
.badge-discount { background: rgba(239, 68, 68, 0.95); color: white; }
.badge-featured { background: rgba(245, 158, 11, 0.95); color: white; }
.badge-preorder { background: rgba(59, 130, 246, 0.95); color: white; }

/* Trust features */
.trust-features {
  display: grid; grid-template-columns: 1fr 1fr;
  gap: 1px; border-radius: 20px;
  background: var(--color-surface-800);
  margin-top: 1.25rem;
  border: 1px solid var(--color-surface-800);
  overflow: hidden;
  box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}
.trust-feat {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 1.25rem; background: var(--color-surface-900);
}
.trust-icon-wrap {
  width: 38px; height: 38px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1rem; flex-shrink: 0;
}
.trust-green  { background: rgba(34, 197, 94, 0.12);  color: #4ade80; }
.trust-blue   { background: rgba(59, 130, 246, 0.12); color: #60a5fa; }
.trust-purple { background: rgba(168, 85, 247, 0.12); color: #c084fc; }
.trust-orange { background: rgba(249, 115, 22, 0.12); color: #fb923c; }
.trust-feat-title {
  font-size: 0.8rem; font-weight: 700; color: white; line-height: 1.2;
}
.trust-feat-sub {
  font-size: 0.7rem; color: var(--color-surface-500); margin-top: 2px;
}

/* ─── Info Panel ─── */
.product-meta-row {
  display: flex; align-items: center; gap: 0.625rem; flex-wrap: wrap;
  margin-bottom: 1.25rem;
}
.meta-link {
  font-size: 0.78rem; font-weight: 700;
  color: var(--color-brand-400); text-decoration: none;
  padding: 0.25rem 0.75rem;
  background: rgba(99, 102, 241, 0.1);
  border: 1px solid rgba(99, 102, 241, 0.2);
  border-radius: 8px; transition: all 0.2s;
}
.meta-link:hover { background: rgba(99, 102, 241, 0.2); border-color: var(--color-brand-400); }
.meta-badge {
  font-size: 0.7rem; font-weight: 800;
  padding: 0.25rem 0.625rem;
  background: var(--color-surface-800);
  border: 1px solid var(--color-surface-700);
  border-radius: 8px; color: var(--color-surface-300);
  text-transform: uppercase; letter-spacing: 0.04em;
}
.meta-badge-region { color: #f59e0b; border-color: rgba(245,158,11,0.2); background: rgba(245,158,11,0.08); }

.product-detail-name {
  font-size: clamp(1.75rem, 4vw, 2.5rem);
  font-weight: 900; color: white; line-height: 1.15;
  margin-bottom: 1rem; letter-spacing: -0.02em;
}

/* Rating */
.product-detail-rating {
  display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem;
}
.stars-lg { display: flex; gap: 3px; }
.stars-lg .pi { font-size: 1rem; color: #f59e0b; }
.stars-lg .pi-star { color: var(--color-surface-700); }
.rating-val { font-weight: 800; color: white; font-size: 1rem; }
.rating-cnt { font-size: 0.85rem; color: var(--color-surface-500); font-weight: 500; }

/* Divider */
.section-divider {
  height: 1px;
  background: linear-gradient(90deg, var(--color-surface-800), transparent);
  margin: 1.5rem 0;
}

/* ─── Price ─── */
.product-price-block { margin-bottom: 1.5rem; }

.discount-banner {
  display: inline-flex; align-items: center; gap: 0.5rem;
  padding: 0.4rem 1rem; border-radius: 10px; margin-bottom: 1rem;
  background: linear-gradient(135deg, rgba(239,68,68,0.15), rgba(239,68,68,0.05));
  border: 1px solid rgba(239,68,68,0.2);
  font-size: 0.85rem; color: #fca5a5; font-weight: 500;
}
.discount-banner strong { color: #f87171; font-weight: 800; }
.discount-banner .pi { font-size: 0.8rem; color: #ef4444; }

.price-row {
  display: flex; align-items: baseline; gap: 1rem; margin-bottom: 0.5rem;
}
.price-orig {
  font-size: 1.25rem; color: var(--color-surface-500);
  text-decoration: line-through; font-weight: 500;
}
.price-now {
  font-size: 3rem; font-weight: 950; color: white;
  font-family: var(--font-display); line-height: 1;
  background: linear-gradient(135deg, #fff 40%, var(--color-brand-300));
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  background-clip: text;
  filter: drop-shadow(0 10px 20px rgba(0,0,0,0.2));
}
.cashback-row {
  display: inline-flex; align-items: center; gap: 0.5rem;
  padding: 0.4rem 0.875rem; border-radius: 10px; margin-top: 0.5rem;
  font-size: 0.85rem; color: var(--color-accent-400); font-weight: 600;
  background: rgba(16,185,129, 0.08);
  border: 1px solid rgba(16,185,129, 0.15);
}

/* ─── Stock ─── */
.stock-indicator {
  display: inline-flex; align-items: center; gap: 0.625rem;
  padding: 0.5rem 1rem; border-radius: 12px;
  font-size: 0.85rem; font-weight: 700; margin-bottom: 2rem;
}
.stock-dot { width: 10px; height: 10px; border-radius: 50%; animation: pulse-dot 2.5s infinite; }
.stock-ok  { color: #4ade80; background: rgba(34,197,94,0.1);  border: 1px solid rgba(34,197,94,0.2); }
.stock-ok .stock-dot  { background: #22c55e; box-shadow: 0 0 10px rgba(34,197,94,0.5); }
.stock-low { color: #fbbf24; background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.2); }
.stock-low .stock-dot { background: #f59e0b; box-shadow: 0 0 10px rgba(245,158,11,0.5); }
.stock-out { color: #f87171; background: rgba(239,68,68,0.1);  border: 1px solid rgba(239,68,68,0.2); }
.stock-out .stock-dot { background: #ef4444; animation: none; }

@keyframes pulse-dot {
  0%, 100% { opacity: 1; transform: scale(1); }
  50%       { opacity: 0.4; transform: scale(0.75); }
}

/* ─── Actions ─── */
.product-actions {
  display: flex; gap: 0.75rem; align-items: center;
  margin-bottom: 1.5rem; flex-wrap: wrap;
}
.qty-control {
  display: flex; align-items: center;
  background: var(--color-surface-950);
  border: 2px solid var(--color-surface-800);
  border-radius: 14px; overflow: hidden; flex-shrink: 0;
}
.qty-btn {
  width: 44px; height: 50px;
  display: flex; align-items: center; justify-content: center;
  background: none; border: none;
  color: var(--color-surface-400); cursor: pointer;
  transition: all 0.2s; font-size: 0.9rem;
}
.qty-btn:hover { background: var(--color-surface-800); color: white; }
.qty-value {
  padding: 0 1rem;
  font-weight: 800; color: white; font-size: 1.1rem;
  min-width: 40px; text-align: center;
}
.add-cart-btn { height: 50px; border-radius: 14px; font-weight: 700; }
.buy-btn { flex: 1; height: 50px; min-width: 180px; border-radius: 14px; font-weight: 800; font-size: 1rem; }

/* ─── Wishlist ─── */
.wishlist-btn {
  width: 50px; height: 50px; border-radius: 14px;
  border: 2px solid var(--color-surface-800);
  background: var(--color-surface-950);
  cursor: pointer; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  color: var(--color-surface-400); font-size: 1.25rem; transition: all 0.3s;
}
.wishlist-btn:hover { border-color: #ec4899; color: #ec4899; background: rgba(236,72,153,0.08); }
.wishlist-btn.in-wishlist {
  border-color: #ec4899; color: #ec4899;
  background: rgba(236,72,153,0.12);
  box-shadow: 0 0 15px rgba(236,72,153,0.25);
}
.wishlist-btn.is-loading { cursor: wait; opacity: 0.7; }

/* Trust Box */
.store-trust-box {
  display: flex; gap: 0;
  border-radius: 20px; overflow: hidden;
  border: 1px solid rgba(255,255,255,0.08);
}
.trust-item { display: flex; align-items: flex-start; gap: 1rem; padding: 1.5rem; flex: 1; }
.trust-divider { width: 1px; background: rgba(255,255,255,0.08); flex-shrink: 0; }
.trust-item-icon {
  width: 44px; height: 44px; border-radius: 14px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.25rem;
}
.trust-check { background: rgba(34,197,94,0.15); color: #4ade80; }
.trust-bolt  { background: rgba(251,191,36,0.15); color: #fbbf24; }
.trust-title { font-size: 0.9rem; font-weight: 800; color: white; margin-bottom: 0.25rem; }
.trust-sub   { font-size: 0.78rem; color: var(--color-surface-400); line-height: 1.5; }

@media (max-width: 640px) {
  .buy-btn { width: 100%; order: 1; }
  .qty-control { flex: 1; }
  .store-trust-box { flex-direction: column; }
  .trust-divider { width: auto; height: 1px; }
}
</style>
