<template>
  <AppLayout>
    <Head :title="t('home.hero.title', { accent: '' }) + ' — Nexo Digital Store'" />

    <!-- Hero Section -->
    <section class="hero-section">
      <div class="hero-bg">
        <div class="hero-orb hero-orb-1" />
        <div class="hero-orb hero-orb-2" />
        <div class="hero-grid" />
      </div>
      <div class="hero-content" v-motion-fade-visible>
        <div class="hero-badge" v-motion-slide-visible-top>
          <i class="pi pi-shield" />
          <span>{{ t('home.hero.badge') }}</span>
        </div>
        <h1 class="hero-title font-display">
          {{ t('home.hero.title', { accent: '' }).split('{accent}')[0] }}
          <span class="hero-gradient-text">{{ t('home.hero.subtitle') }}</span>
          {{ t('home.hero.title', { accent: '' }).split('{accent}')[1] }}
        </h1>
        <p class="hero-description">
          {{ t('home.hero.description') }}
        </p>
        <div class="hero-actions">
          <Link :href="route('products.index')" class="btn btn-primary btn-lg shadow-lg shadow-indigo-500/20 hover:scale-105 transition-transform">
            <i class="pi pi-shopping-bag" />
            {{ t('home.hero.explore') }}
          </Link>
          <Link :href="route('register')" class="btn btn-ghost btn-lg hover:bg-surface-800 transition-colors">
            {{ t('home.hero.createAccount') }}
            <i class="pi pi-arrow-right" />
          </Link>
        </div>
        
        <div class="hero-stats" v-motion-slide-visible-bottom>
          <div class="hero-stat">
            <span class="hero-stat-value">50K+</span>
            <span class="hero-stat-label">{{ t('home.stats.products') }}</span>
          </div>
          <div class="hero-stat-divider" />
          <div class="hero-stat">
            <span class="hero-stat-value">200K+</span>
            <span class="hero-stat-label">{{ t('home.stats.clients') }}</span>
          </div>
          <div class="hero-stat-divider" />
          <div class="hero-stat">
            <span class="hero-stat-value">99.9%</span>
            <span class="hero-stat-label">{{ t('home.stats.uptime') }}</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Features Bar -->
    <section class="features-bar" v-motion-fade-visible-once>
      <div class="feature-item group">
        <i class="pi pi-bolt feature-icon text-brand-400 group-hover:scale-110 transition-transform" />
        <div>
          <p class="feature-title">{{ t('home.features.delivery') }}</p>
          <p class="feature-desc">{{ t('home.features.deliveryDesc') }}</p>
        </div>
      </div>
      <div class="feature-item group">
        <i class="pi pi-shield feature-icon text-accent-400 group-hover:scale-110 transition-transform" />
        <div>
          <p class="feature-title">{{ t('home.features.payment') }}</p>
          <p class="feature-desc">{{ t('home.features.paymentDesc') }}</p>
        </div>
      </div>
      <div class="feature-item group">
        <i class="pi pi-wallet feature-icon text-amber-500 group-hover:scale-110 transition-transform" />
        <div>
          <p class="feature-title">{{ t('home.features.tokens') }}</p>
          <p class="feature-desc">{{ t('home.features.tokensDesc') }}</p>
        </div>
      </div>
      <div class="feature-item group">
        <i class="pi pi-headphones feature-icon text-pink-500 group-hover:scale-110 transition-transform" />
        <div>
          <p class="feature-title">{{ t('home.features.support') }}</p>
          <p class="feature-desc">{{ t('home.features.supportDesc') }}</p>
        </div>
      </div>
    </section>

    <!-- Categories -->
    <section class="section" v-if="topCategories.length" v-motion-fade-visible-once>
      <div class="section-header">
        <h2 class="section-title font-display">{{ t('home.sections.categories') }}</h2>
        <Link :href="route('products.index')" class="section-link">
          {{ t('home.sections.viewAll') }} <i class="pi pi-arrow-right" />
        </Link>
      </div>
      <div class="categories-grid">
        <Link
          v-for="(cat, idx) in topCategories"
          :key="cat.id"
          :href="route('products.category', cat.slug)"
          class="category-card glass group"
          v-motion
          :initial="{ opacity: 0, y: 20 }"
          :enter="{ opacity: 1, y: 0, transition: { delay: idx * 50 } }"
        >
          <div class="category-icon-wrap" :style="{ background: cat.color ? cat.color + '20' : 'rgba(99,102,241,0.1)', borderColor: cat.color ? cat.color + '30' : 'rgba(99,102,241,0.2)' }">
            <i :class="cat.icon || 'pi pi-tag'" :style="{ color: cat.color || 'var(--color-brand-400)' }" />
          </div>
          <p class="category-name group-hover:text-brand-400 transition-colors">{{ cat.name }}</p>
        </Link>
      </div>
    </section>

    <!-- Featured Products -->
    <section class="section" v-if="featuredProducts.length" v-motion-fade-visible-once>
      <div class="section-header">
        <h2 class="section-title font-display">
          <i class="pi pi-star-fill text-amber-500 mr-2" />
          {{ t('home.sections.featured') }}
        </h2>
        <Link :href="route('products.index')" class="section-link">
          {{ t('home.sections.viewMore') }} <i class="pi pi-arrow-right" />
        </Link>
      </div>
      <div class="products-grid">
        <ProductCard
          v-for="product in featuredProducts"
          :key="product.id"
          :product="product"
        />
      </div>
    </section>

    <!-- New Arrivals -->
    <section class="section" v-if="newArrivals.length" v-motion-fade-visible-once>
      <div class="section-header">
        <h2 class="section-title font-display">
          <i class="pi pi-sparkles text-brand-400 mr-2" />
          {{ t('home.sections.newArrivals') }}
        </h2>
      </div>
      <div class="products-grid">
        <ProductCard
          v-for="product in newArrivals"
          :key="product.id"
          :product="product"
        />
      </div>
    </section>

    <!-- Best Sellers -->
    <section class="section" v-if="bestSellers.length" v-motion-fade-visible-once>
      <div class="section-header">
        <h2 class="section-title font-display">
          <i class="pi pi-chart-line text-accent-400 mr-2" />
          {{ t('home.sections.bestSellers') }}
        </h2>
      </div>
      <div class="products-grid">
        <ProductCard
          v-for="product in bestSellers"
          :key="product.id"
          :product="product"
        />
      </div>
    </section>

    <!-- Empty State -->
    <section class="empty-state" v-if="!featuredProducts.length && !newArrivals.length">
      <div class="empty-card glass" v-motion-pop-visible>
        <div class="empty-icon shadow-indigo-500/20">
          <i class="pi pi-shopping-bag" />
        </div>
        <h3 class="font-display">{{ t('home.empty.title') }}</h3>
        <p>{{ t('home.empty.description') }}</p>
        <div class="flex gap-3 justify-center mt-6">
          <Link :href="route('register')" class="btn btn-primary">{{ t('nav.register') }}</Link>
          <Link :href="route('login')" class="btn btn-ghost">{{ t('nav.login') }}</Link>
        </div>
      </div>
    </section>

    <!-- NexoTokens Banner -->
    <section class="nt-banner glass overflow-hidden relative" v-motion-slide-visible-bottom>
      <div class="hero-orb hero-orb-2 scale-150 -bottom-1/2 -right-1/4 opacity-10" />
      <div class="nt-banner-inner relative z-10">
        <div class="nt-banner-icon shadow-xl">
          <i class="pi pi-wallet" />
        </div>
        <div class="nt-banner-text">
          <h3 class="font-display">{{ t('home.ntBanner.title') }}</h3>
          <p>{{ t('home.ntBanner.description') }}</p>
        </div>
        <Link :href="route('register')" class="btn btn-primary shadow-lg shadow-indigo-500/20 hover:scale-105 transition-transform">
          {{ t('home.ntBanner.button') }}
        </Link>
      </div>
    </section>
  </AppLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import ProductCard from '@/Components/ProductCard.vue';

const { t } = useI18n();

defineProps({
  featuredProducts: { type: Array, default: () => [] },
  topCategories:    { type: Array, default: () => [] },
  newArrivals:      { type: Array, default: () => [] },
  bestSellers:      { type: Array, default: () => [] },
});
</script>

<style scoped>
/* ── Hero ─────────────────────────────────────────────────────────────────── */
.hero-section {
  position: relative; text-align: center;
  padding: 6rem 1rem 5rem; overflow: hidden;
}
.hero-bg { position: absolute; inset: 0; pointer-events: none; z-index: 0; }
.hero-orb { position: absolute; border-radius: 50%; filter: blur(100px); opacity: 0.22; }
.hero-orb-1 {
  width: 700px; height: 700px;
  background: radial-gradient(circle, var(--c-primary, #6366f1), transparent);
  top: -250px; left: 50%; transform: translateX(-50%);
}
.hero-orb-2 {
  width: 500px; height: 500px;
  background: radial-gradient(circle, var(--nx-accent, #06b6d4), transparent);
  bottom: -150px; right: 5%;
}
.hero-grid {
  position: absolute; inset: 0;
  background-image:
    linear-gradient(color-mix(in srgb, var(--c-primary) 8%, transparent) 1px, transparent 1px),
    linear-gradient(90deg, color-mix(in srgb, var(--c-primary) 8%, transparent) 1px, transparent 1px);
  background-size: 50px 50px;
}
.hero-content { position: relative; z-index: 1; max-width: 900px; margin: 0 auto; }

.hero-badge {
  display: inline-flex; align-items: center; gap: 0.625rem;
  padding: 0.4rem 1rem;
  background: rgba(99, 102, 241, 0.12);
  border: 1px solid rgba(99, 102, 241, 0.25);
  border-radius: 9999px; font-size: 0.8rem; font-weight: 600;
  color: var(--color-brand-400); margin-bottom: 2rem;
  box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.hero-title {
  font-size: clamp(2.25rem, 6vw, 4rem); font-weight: 900;
  line-height: 1.1; letter-spacing: -0.04em;
  color: white; margin-bottom: 1.5rem;
}
.hero-gradient-text {
  background: linear-gradient(135deg, #818cf8, #6366f1, #06b6d4);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
  filter: drop-shadow(0 0 20px rgba(99, 102, 241, 0.2));
}

.hero-description {
  font-size: 1.15rem; color: var(--color-surface-400);
  max-width: 620px; margin: 0 auto 2.5rem; line-height: 1.8;
}
.hero-actions {
  display: flex; align-items: center; justify-content: center;
  gap: 1.25rem; flex-wrap: wrap; margin-bottom: 4rem;
}
.btn-lg { padding: 0.875rem 2rem; font-size: 1rem; gap: 0.625rem; border-radius: 12px; }

.hero-stats { 
  display: flex; align-items: center; justify-content: center; gap: 3rem; 
  flex-wrap: wrap;
}
.hero-stat { text-align: center; }
.hero-stat-value { display: block; font-size: 1.75rem; font-weight: 800; color: white; margin-bottom: 0.25rem; }
.hero-stat-label { font-size: 0.85rem; color: var(--color-surface-500); font-weight: 500; }
.hero-stat-divider { width: 1px; height: 44px; background: var(--color-surface-800); }

@media (max-width: 640px) {
  .hero-stats { gap: 1.5rem; display: grid; grid-template-columns: 1fr 1fr; }
  .hero-stat-divider { display: none; }
  .hero-stat:last-child { grid-column: span 2; }
  .hero-actions { flex-direction: column; width: 100%; max-width: 300px; margin-left: auto; margin-right: auto; }
  .hero-actions .btn { width: 100%; }
}

/* ── Features Bar ─────────────────────────────────────────────────────────── */
.features-bar {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1px; background: var(--color-surface-800); border: 1px solid var(--color-surface-800);
  border-radius: 20px; overflow: hidden; margin-bottom: 4rem;
  box-shadow: 0 20px 40px rgba(0,0,0,0.2);
}
@media (max-width: 640px) {
  .features-bar { grid-template-columns: 1fr; border-radius: 12px; margin-bottom: 2.5rem; }
}
.feature-item {
  display: flex; align-items: center; gap: 1rem;
  padding: 1.75rem; background: var(--color-surface-900); transition: all 0.3s;
}
.feature-item:hover { background: var(--color-surface-800); }
.feature-icon { font-size: 1.75rem; flex-shrink: 0; }
.feature-title { font-size: 0.9rem; font-weight: 700; color: white; }
.feature-desc { font-size: 0.78rem; color: var(--color-surface-500); margin-top: 2px; }

/* ── Sections ─────────────────────────────────────────────────────────────── */
.section { margin-bottom: 4rem; }
.section-header {
  display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem;
}
.section-title {
  font-size: 1.5rem; font-weight: 800; color: white;
  display: flex; align-items: center;
}
.section-link {
  display: flex; align-items: center; gap: 0.5rem;
  font-size: 0.85rem; font-weight: 600; color: var(--color-brand-400);
  text-decoration: none; transition: all 0.2s;
}
.section-link:hover { gap: 0.75rem; color: var(--color-brand-300); }

/* ── Categories Grid ──────────────────────────────────────────────────────── */
.categories-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem;
}
.category-card {
  display: flex; flex-direction: column; align-items: center; gap: 1rem;
  padding: 1.75rem 1rem; border-radius: 18px; text-decoration: none; transition: all 0.3s; cursor: pointer;
  border: 1px solid rgba(255,255,255,0.04);
}
.category-card:hover { 
  transform: translateY(-5px); 
  border-color: rgba(99, 102, 241, 0.3);
  box-shadow: 0 12px 30px rgba(0,0,0,0.3);
  background: rgba(255,255,255,0.03) !important;
}
.category-icon-wrap {
  width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;
  border-radius: 16px; border: 1.5px solid; font-size: 1.75rem; transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.category-card:hover .category-icon-wrap { transform: scale(1.15) rotate(5deg); }
.category-name { font-size: 0.875rem; font-weight: 700; color: var(--color-surface-200); text-align: center; }

/* ── Products Grid ────────────────────────────────────────────────────────── */
.products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 1.5rem; }

/* ── Empty State ──────────────────────────────────────────────────────────── */
.empty-state { display: flex; justify-content: center; padding: 4rem 1rem; }
.empty-card { max-width: 520px; width: 100%; padding: 4rem 2rem; border-radius: 24px; text-align: center; }
.empty-icon {
  width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;
  background: rgba(99, 102, 241, 0.1); border: 1px solid rgba(99, 102, 241, 0.2);
  border-radius: 24px; margin: 0 auto 2rem; font-size: 2.25rem; color: var(--color-brand-400);
}

/* ── NT Banner ────────────────────────────────────────────────────────────── */
.nt-banner {
  border-radius: 24px; margin-top: 1.5rem; margin-bottom: 2rem;
  background: linear-gradient(135deg, rgba(99,102,241,0.1), rgba(6,182,212,0.08)) !important;
  border: 1px solid rgba(99,102,241,0.2) !important;
}
.nt-banner-inner { display: flex; align-items: center; gap: 2rem; padding: 3rem; flex-wrap: wrap; }
.nt-banner-icon {
  width: 70px; height: 70px; display: flex; align-items: center; justify-content: center;
  background: linear-gradient(135deg, var(--color-brand-600), var(--color-brand-400));
  border-radius: 20px; font-size: 2rem; color: white; flex-shrink: 0;
}
.nt-banner-text { flex: 1; min-width: 250px; }
.nt-banner-text h3 { font-size: 1.5rem; font-weight: 800; color: white; margin-bottom: 0.5rem; }
.nt-banner-text p { font-size: 1rem; color: var(--color-surface-400); line-height: 1.6; }

@media (max-width: 768px) {
  .hero-stats { gap: 1.5rem; }
  .nt-banner-inner { padding: 2rem; text-align: center; justify-content: center; }
  .nt-banner-icon { margin: 0 auto; }
}
</style>
