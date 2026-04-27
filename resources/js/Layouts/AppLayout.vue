<template>
  <div class="app-shell">
    <!-- Navbar -->
    <header class="navbar" :class="{ 'navbar-scrolled': scrolled }">
      <div class="navbar-inner">
        <!-- Logo -->
        <Link :href="route('home')" class="navbar-logo">
          <div class="logo-icon">
            <svg width="28" height="28" viewBox="0 0 32 32" fill="none">
              <path d="M16 2L4 8v8c0 7.732 5.148 14.972 12 17 6.852-2.028 12-9.268 12-17V8L16 2z" fill="url(#logoGrad)" />
              <path d="M11 16l3 3 7-7" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
              <defs>
                <linearGradient id="logoGrad" x1="4" y1="2" x2="28" y2="30" gradientUnits="userSpaceOnUse">
                  <stop stop-color="#818cf8" />
                  <stop offset="1" stop-color="#6366f1" />
                </linearGradient>
              </defs>
            </svg>
          </div>
          <span class="logo-text font-display">Nexo<span class="logo-accent">DS</span></span>
        </Link>

        <!-- Search Bar -->
        <div class="navbar-search hidden lg:block">
          <div class="search-wrapper">
            <i class="pi pi-search search-icon" />
            <input
              v-model="searchQuery"
              type="text"
              :placeholder="t('nav.searchPlaceholder')"
              class="search-input"
              @keyup.enter="doSearch"
            />
            <kbd class="search-kbd">⌘K</kbd>
          </div>
        </div>

        <!-- Nav Actions -->
        <nav class="navbar-actions">
          <!-- Mobile search icon -->
          <button class="nav-icon-btn lg:hidden" @click="router.visit(route('products.index'))">
            <i class="pi pi-search" />
          </button>
          <!-- Currency Selector -->
          <div class="currency-selector hidden sm:block">
            <button class="currency-btn" @click="currencyOpen = !currencyOpen">
              <span>{{ activeCurrency }}</span>
              <i class="pi pi-chevron-down currency-chevron" />
            </button>
            <div v-if="currencyOpen" class="currency-dropdown glass">
              <button
                v-for="c in currencies"
                :key="c"
                class="currency-option"
                :class="{ active: c === activeCurrency }"
                @click="selectCurrency(c)"
              >{{ c }}</button>
            </div>
          </div>

          <!-- Wishlist -->
          <Link :href="route('wishlist.index')" class="nav-icon-btn" v-if="auth.user" :title="t('nav.wishlist')">
            <i class="pi pi-heart" />
            <span v-if="wishlistCount > 0" class="nav-badge nav-badge-pink">{{ wishlistCount }}</span>
          </Link>

          <!-- Cart -->
          <Link :href="route('cart.index')" class="nav-icon-btn" v-if="auth.user" :title="t('nav.cart')">
            <i class="pi pi-shopping-cart" />
            <span v-if="cartCount > 0" class="nav-badge">{{ cartCount }}</span>
          </Link>

          <!-- Notifications -->
          <button class="nav-icon-btn" v-if="auth.user">
            <i class="pi pi-bell" />
            <span v-if="unreadNotifs > 0" class="nav-badge">{{ unreadNotifs }}</span>
          </button>

          <!-- Theme + Lang switchers -->
          <ThemeSwitcher />
          <LangSwitcher />

          <!-- User Menu -->
          <div v-if="auth.user" class="user-menu-wrapper" ref="userMenuRef">
            <button class="user-avatar-btn" @click="userMenuOpen = !userMenuOpen">
              <img :src="auth.user.avatar || defaultAvatar" :alt="auth.user.name" class="user-avatar" />
              <span class="user-name-label">{{ auth.user.name.split(' ')[0] }}</span>
              <i class="pi pi-chevron-down user-chevron" />
            </button>

            <Transition name="dropdown">
              <div v-if="userMenuOpen" class="user-dropdown ">
                <div class="dropdown-header">
                  <p class="dropdown-name">{{ auth.user.name }}</p>
                  <p class="dropdown-email">{{ auth.user.email }}</p>
                  <div class="nt-balance" v-if="wallet">
                    <i class="pi pi-wallet" />
                    <span>{{ wallet.balance }} NT</span>
                  </div>
                </div>
                <div class="dropdown-divider" />
                <Link :href="route('orders.index')" class="dropdown-item"><i class="pi pi-receipt" />{{ t('nav.orders') }}</Link>
                <Link :href="route('licenses.index')" class="dropdown-item"><i class="pi pi-key" />{{ t('nav.licenses') }}</Link>
                <Link :href="route('wishlist.index')" class="dropdown-item"><i class="pi pi-heart" />{{ t('nav.wishlist') }}
                  <span v-if="wishlistCount > 0" class="dropdown-badge">{{ wishlistCount }}</span>
                </Link>
                <Link :href="route('profile.index', { tab: 'billetera' })" class="dropdown-item"><i class="pi pi-wallet" />NexoTokens</Link>
                <Link :href="route('subscriptions.index')" class="dropdown-item"><i class="pi pi-star" />💎 {{ t('nav.subscriptions') }}</Link>
                <Link :href="route('profile.index')" class="dropdown-item"><i class="pi pi-user" />{{ t('nav.profile') }}</Link>
                <Link v-if="auth.user.role === 'admin'"
                  :href="route('admin.dashboard')"
                  class="dropdown-item accent"
                >
                  <i class="pi pi-shield" />
                  {{ t('nav.adminPanel') }}
                </Link>
                <div class="dropdown-divider" />
                <button class="dropdown-item danger" @click="logout">
                  <i class="pi pi-sign-out" />{{ t('nav.logout') }}
                </button>
              </div>
            </Transition>
          </div>

          <!-- Auth buttons (guest) -->
          <div v-else class="auth-buttons">
            <Link :href="route('login')" class="btn btn-ghost !px-3 sm:!px-4">{{ t('nav.login') }}</Link>
            <Link :href="route('register')" class="btn btn-primary !px-3 sm:!px-4">{{ t('nav.register') }}</Link>
          </div>
        </nav>
      </div>

      <!-- Category Nav -->
      <div class="category-nav">
        <div class="category-nav-inner">
          <Link
            v-for="cat in categories"
            v-if="cat.slug"
            :key="cat.id"
            :href="route('products.category', cat.slug)"
            class="category-nav-item"
            :class="{ active: isActiveCategory(cat.slug) }"
          >
            <i v-if="cat.icon" :class="cat.icon" />
            <span>{{ cat.name }}</span>
          </Link>
        </div>
      </div>
    </header>

    <!-- Flash messages -->
    <div class="flash-container" v-if="flash.success || flash.error || flash.warning">
      <TransitionGroup name="flash">
        <div v-if="flash.success" class="flash flash-success" key="success">
          <i class="pi pi-check-circle" />
          <span>{{ flash.success }}</span>
          <button class="flash-close" @click="clearFlash('success')"><i class="pi pi-times" /></button>
        </div>
        <div v-if="flash.error" class="flash flash-error" key="error">
          <i class="pi pi-times-circle" />
          <span>{{ flash.error }}</span>
          <button class="flash-close" @click="clearFlash('error')"><i class="pi pi-times" /></button>
        </div>
        <div v-if="flash.warning" class="flash flash-warning" key="warning">
          <i class="pi pi-exclamation-triangle" />
          <span>{{ flash.warning }}</span>
          <button class="flash-close" @click="clearFlash('warning')"><i class="pi pi-times" /></button>
        </div>
      </TransitionGroup>
    </div>

    <!-- Main Content -->
    <main class="main-content">
      <slot />
    </main>

    <!-- Global PrimeVue Components -->
    <Toast />
    <ConfirmDialog />

    <!-- Footer -->
    <footer class="site-footer">
      <div class="footer-inner">
        <div class="footer-brand">
          <div class="logo-icon">
            <svg width="24" height="24" viewBox="0 0 32 32" fill="none">
              <path d="M16 2L4 8v8c0 7.732 5.148 14.972 12 17 6.852-2.028 12-9.268 12-17V8L16 2z" fill="url(#footerGrad)" />
              <defs>
                <linearGradient id="footerGrad" x1="4" y1="2" x2="28" y2="30" gradientUnits="userSpaceOnUse">
                  <stop stop-color="#818cf8" /><stop offset="1" stop-color="#6366f1" />
                </linearGradient>
              </defs>
            </svg>
          </div>
          <p class="footer-brand-name font-display">Nexo Digital Store</p>
          <p class="footer-brand-desc">
            Tu marketplace de productos digitales de confianza.
          </p>
          <p class="footer-brand-copy">
            Desarrollado por <strong>Línea Once</strong> &amp; <strong>AGX Digital Store</strong>
          </p>
        </div>
        <div class="footer-links">
          <div class="footer-col">
            <h4>Marketplace</h4>
            <Link :href="route('products.index')">{{ t('nav.products') }}</Link>
            <Link href="#">{{ t('common.games') || 'Juegos' }}</Link>
            <Link href="#">{{ t('common.software') || 'Software' }}</Link>
            <Link href="#">{{ t('nav.subscriptions') }}</Link>
          </div>
          <div class="footer-col">
            <h4>{{ t('common.account') || 'Cuenta' }}</h4>
            <Link :href="route('orders.index')" v-if="auth.user">{{ t('nav.orders') }}</Link>
            <Link :href="route('licenses.index')" v-if="auth.user">{{ t('nav.licenses') }}</Link>
            <Link :href="route('wishlist.index')" v-if="auth.user">{{ t('nav.wishlist') }}</Link>
            <Link :href="route('profile.index', { tab: 'billetera' })" v-if="auth.user">{{ t('nav.wallet') }}</Link>
            <Link :href="route('login')" v-else>{{ t('nav.login') }}</Link>
          </div>
          <div class="footer-col">
            <h4>{{ t('nav.support') }}</h4>
            <Link href="#">{{ t('nav.helpCenter') }}</Link>
            <Link href="#">{{ t('nav.reportProblem') }}</Link>
            <Link href="#">{{ t('nav.disputes') }}</Link>
            <Link href="#">{{ t('nav.contact') }}</Link>
          </div>
        </div>
      </div>
      <div class="footer-bottom">
        <p>&copy; {{ new Date().getFullYear() }} Nexo Digital Store. {{ t('nav.allRightsReserved') }}</p>
        <div class="footer-bottom-links">
          <Link href="#">{{ t('nav.privacy') }}</Link>
          <Link :href="route('terms')">{{ t('nav.terms') }}</Link>
          <Link :href="route('cookies')">{{ t('nav.cookies') }}</Link>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import ThemeSwitcher from '@/Components/ui/ThemeSwitcher.vue';
import LangSwitcher from '@/Components/ui/LangSwitcher.vue';
import { useTheme } from '@/composables/useTheme';

const { t } = useI18n();
const page = usePage();
const auth = computed(() => page.props.auth);
const flash = computed(() => page.props.flash);

// Initialize theme from DB props on every page load
useTheme();

// Categories from shared props (populated by Inertia middleware)
const categories = computed(() => page.props.navCategories ?? []);
const wallet = computed(() => page.props.wallet ?? null);
const cartCount = computed(() => page.props.cartCount ?? 0);
const unreadNotifs = computed(() => page.props.unreadNotifs ?? 0);
const wishlistCount = computed(() => page.props.wishlistCount ?? 0);

// State
const scrolled = ref(false);
const searchQuery = ref('');
const userMenuOpen = ref(false);
const currencyOpen = ref(false);
const activeCurrency = ref(localStorage.getItem('nexo-currency') || 'PEN');
const currencies = ['PEN', 'USD', 'COP', 'MXN', 'NT'];

const defaultAvatar = computed(() =>
  `https://ui-avatars.com/api/?name=${encodeURIComponent(auth.value.user?.name ?? 'U')}&background=6366f1&color=fff&bold=true`
);

const userMenuRef = ref(null);

// Flash management
function clearFlash(key) {
  if (page.props.flash[key]) {
    page.props.flash[key] = null;
  }
}

watch(() => page.props.flash, (newFlash) => {
  if (newFlash.success || newFlash.error || newFlash.warning) {
    setTimeout(() => {
      if (newFlash.success) clearFlash('success');
      if (newFlash.error) clearFlash('error');
      if (newFlash.warning) clearFlash('warning');
    }, 5000);
  }
}, { deep: true, immediate: true });

function doSearch() {
  if (searchQuery.value.trim()) {
    router.get(route('products.index'), { search: searchQuery.value });
  }
}

function selectCurrency(c) {
  activeCurrency.value = c;
  localStorage.setItem('nexo-currency', c);
  currencyOpen.value = false;
  window.dispatchEvent(new CustomEvent('nexo-currency-changed', { detail: c }));
}

function isActiveCategory(slug) {
  return window.location.pathname.includes(slug);
}

function logout() {
  router.post(route('logout'));
}

function handleScroll() {
  scrolled.value = window.scrollY > 20;
}

function handleClickOutside(e) {
  if (userMenuRef.value && !userMenuRef.value.contains(e.target)) {
    userMenuOpen.value = false;
  }
}

onMounted(() => {
  window.addEventListener('scroll', handleScroll, { passive: true });
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll);
  document.removeEventListener('click', handleClickOutside);
});
</script>

<style scoped>
/* ── App Shell ───────────────────────────────────────────────────────────── */
.app-shell {
  display: flex;
  flex-direction: column;
  min-height: 100dvh;
}

/* ── Navbar ───────────────────────────────────────────────────────────────── */
.navbar {
  position: sticky;
  top: 0;
  z-index: 100;
  background: rgba(9, 9, 11, 0.85);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border-bottom: 1px solid transparent;
  transition: border-color 0.3s ease, box-shadow 0.3s ease;
}
.navbar-scrolled {
  border-bottom-color: var(--color-surface-800);
  box-shadow: 0 4px 24px rgba(0,0,0,0.4);
}

.navbar-inner {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 1.5rem;
  height: 64px;
  display: flex;
  align-items: center;
  gap: 1rem;
}

/* Logo */
.navbar-logo {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  text-decoration: none;
  flex-shrink: 0;
}
.logo-icon {
  width: 36px; height: 36px;
  display: flex; align-items: center; justify-content: center;
  background: rgba(99, 102, 241, 0.1);
  border: 1px solid rgba(99, 102, 241, 0.2);
  border-radius: 10px;
}
.logo-text {
  font-size: 1.125rem;
  font-weight: 700;
  color: white;
  letter-spacing: -0.02em;
}
.logo-accent { color: var(--color-brand-400); }

/* Search */
.navbar-search { flex: 1; max-width: 520px; }
.search-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}
.search-icon {
  position: absolute;
  left: 0.875rem;
  color: var(--color-surface-400);
  font-size: 0.875rem;
  pointer-events: none;
}
.search-input {
  width: 100%;
  background: var(--color-surface-900);
  border: 1px solid var(--color-surface-700);
  border-radius: 10px;
  padding: 0.5rem 2.5rem 0.5rem 2.5rem;
  color: white;
  font-size: 0.875rem;
  transition: border-color 0.2s, box-shadow 0.2s;
  outline: none;
}
.search-input::placeholder { color: var(--color-surface-500); }
.search-input:focus {
  border-color: var(--color-brand-500);
  box-shadow: 0 0 0 3px rgba(99,102,241,0.12);
}
.search-kbd {
  position: absolute; right: 0.75rem;
  background: var(--color-surface-800);
  border: 1px solid var(--color-surface-600);
  border-radius: 4px;
  padding: 0.1rem 0.4rem;
  font-size: 0.7rem;
  color: var(--color-surface-400);
}

/* Actions */
.navbar-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-left: auto;
  flex-shrink: 0;
}

.nav-icon-btn {
  position: relative;
  width: 38px; height: 38px;
  display: flex; align-items: center; justify-content: center;
  background: var(--color-surface-800);
  border: 1px solid var(--color-surface-700);
  border-radius: 10px;
  color: var(--color-surface-300);
  text-decoration: none;
  cursor: pointer;
  transition: all 0.2s;
}
.nav-icon-btn:hover {
  border-color: var(--color-brand-500);
  color: white;
}
.nav-badge {
  position: absolute; top: -4px; right: -4px;
  min-width: 16px; height: 16px;
  background: var(--color-brand-500);
  border-radius: 9999px;
  font-size: 0.6rem;
  font-weight: 700;
  color: white;
  display: flex; align-items: center; justify-content: center;
  padding: 0 3px;
}
.nav-badge-pink { background: #ec4899; }

/* Currency */
.currency-selector { position: relative; }
.currency-btn {
  display: flex; align-items: center; gap: 0.375rem;
  padding: 0.375rem 0.625rem;
  background: var(--color-surface-800);
  border: 1px solid var(--color-surface-700);
  border-radius: 8px;
  color: var(--color-surface-200);
  font-size: 0.8rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}
.currency-btn:hover { border-color: var(--color-brand-500); }
.currency-dropdown {
  position: absolute; top: calc(100% + 8px); right: 0;
  min-width: 100px;
  border-radius: 10px;
  padding: 0.375rem;
  z-index: 50;
}
.currency-option {
  display: block; width: 100%;
  padding: 0.375rem 0.75rem;
  border-radius: 6px;
  font-size: 0.8rem;
  color: var(--color-surface-300);
  cursor: pointer;
  transition: all 0.15s;
  text-align: left;
  background: none; border: none;
}
.currency-option:hover, .currency-option.active {
  background: rgba(99,102,241,0.15);
  color: var(--color-brand-400);
}

/* User Menu */
.user-menu-wrapper { position: relative; }
.user-avatar-btn {
  display: flex; align-items: center; gap: 0.5rem;
  padding: 0.25rem 0.625rem 0.25rem 0.25rem;
  background: var(--color-surface-800);
  border: 1px solid var(--color-surface-700);
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.2s;
  color: var(--color-surface-300);
  font-size: 0.8rem;
}
.user-avatar-btn:hover { border-color: var(--color-brand-500); }
.user-avatar {
  width: 28px; height: 28px;
  border-radius: 6px;
  object-fit: cover;
}
/* user name — hidden on small screens */
.user-name-label { font-weight: 500; color: white; }
@media (max-width: 1024px) { .user-name-label { display: none; } }
.user-chevron   { font-size: 0.65rem; color: var(--color-surface-400); }
.currency-chevron { font-size: 0.65rem; color: var(--color-surface-400); }

.user-dropdown {
  position: absolute; top: calc(100% + 8px); right: 0;
  width: 220px;
  border-radius: 12px;
  padding: 0.5rem;
  z-index: 50;
}
.dropdown-header { padding: 0.5rem 0.75rem; }
.dropdown-name  { font-size: 0.875rem; font-weight: 600; color: white; margin: 0; }
.dropdown-email { font-size: 0.75rem; color: var(--color-surface-400); margin: 0.15rem 0 0; }
.nt-balance {
  display: inline-flex; align-items: center; gap: 0.375rem;
  margin-top: 0.375rem;
  padding: 0.2rem 0.5rem;
  background: rgba(99,102,241,0.12);
  border: 1px solid rgba(99,102,241,0.2);
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--color-brand-400);
}
.dropdown-divider {
  height: 1px;
  background: var(--color-surface-700);
  margin: 0.375rem 0;
}
.dropdown-item {
  display: flex; align-items: center; gap: 0.625rem;
  padding: 0.5rem 0.75rem;
  border-radius: 8px;
  font-size: 0.8rem;
  color: var(--color-surface-300);
  text-decoration: none;
  cursor: pointer;
  transition: all 0.15s;
  background: none; border: none; width: 100%; text-align: left;
}
.dropdown-item:hover { background: var(--color-surface-700); color: white; }
.dropdown-item.accent { color: var(--color-brand-400); }
.dropdown-item.accent:hover { background: rgba(99,102,241,0.12); }
.dropdown-item.danger { color: var(--color-danger-400); }
.dropdown-item.danger:hover { background: rgba(239,68,68,0.08); }
.dropdown-item .pi { font-size: 0.875rem; width: 16px; }
.dropdown-badge {
  margin-left: auto;
  background: #ec4899;
  color: white;
  font-size: 0.6rem;
  font-weight: 700;
  padding: 0.1rem 0.35rem;
  border-radius: 9999px;
  min-width: 16px;
  text-align: center;
}

/* Auth buttons */
.auth-buttons { display: flex; gap: 0.5rem; align-items: center; }
.btn {
  display: inline-flex;
  align-items: center;
  padding: 0.4rem 0.875rem;
  border-radius: 8px;
  font-size: 0.8rem;
  font-weight: 500;
  text-decoration: none;
  cursor: pointer;
  transition: all 0.18s;
  border: 1px solid transparent;
}
.btn-ghost {
  color: var(--color-surface-300);
  border-color: var(--color-surface-700);
  background: transparent;
}
.btn-ghost:hover { color: white; border-color: var(--color-surface-500); }
.btn-primary {
  background: var(--color-brand-600);
  color: white;
  border-color: var(--color-brand-500);
}
.btn-primary:hover { background: var(--color-brand-500); }

/* Category Nav */
.category-nav {
  border-top: 1px solid var(--color-surface-800);
  background: rgba(15, 15, 18, 0.8);
}
.category-nav-inner {
  max-width: 1400px; margin: 0 auto;
  padding: 0 1.5rem;
  display: flex; align-items: center; gap: 0.25rem;
  overflow-x: auto;
  scrollbar-width: none;
  height: 44px;
}
.category-nav-inner::-webkit-scrollbar { display: none; }
.category-nav-item {
  display: flex; align-items: center; gap: 0.375rem;
  padding: 0.375rem 0.75rem;
  border-radius: 8px;
  font-size: 0.8rem;
  font-weight: 500;
  color: var(--color-surface-400);
  text-decoration: none;
  white-space: nowrap;
  transition: all 0.15s;
  flex-shrink: 0;
}
.category-nav-item:hover { color: white; background: var(--color-surface-800); }
.category-nav-item.active { color: var(--color-brand-400); background: rgba(99,102,241,0.1); }

/* Flash */
.flash-container {
  position: fixed;
  top: 80px;
  right: 1.5rem;
  z-index: 2000; /* Asegurar que esté por encima de casi todo */
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  pointer-events: none; /* Dejar pasar clics si no es directamente en el flash */
}
.flash {
  pointer-events: auto; /* Reactivar clics para el botón de cierre */
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  border-radius: 12px;
  font-size: 0.875rem;
  font-weight: 500;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
  backdrop-filter: blur(8px);
  min-width: 280px;
  max-width: 400px;
}
.flash-success {
  background: rgba(16, 185, 129, 0.9);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: white;
}
.flash-error {
  background: rgba(239, 68, 68, 0.9);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: white;
}
.flash-warning {
  background: rgba(245, 158, 11, 0.9);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: white;
}
.flash-close {
  margin-left: auto;
  background: none;
  border: none;
  color: white;
  opacity: 0.7;
  cursor: pointer;
  padding: 0.25rem;
  display: flex;
  transition: opacity 0.2s;
}
.flash-close:hover { opacity: 1; }

/* Transiciones Flash */
.flash-enter-active, .flash-leave-active {
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
.flash-enter-from {
  opacity: 0;
  transform: translateX(30px) scale(0.9);
}
.flash-leave-to {
  opacity: 0;
  transform: translateX(30px);
}

/* Main */
.main-content {
  flex: 1;
  max-width: 1400px;
  width: 100%;
  margin: 0 auto;
  padding: 2rem 1.5rem;
}

/* Footer */
.site-footer {
  background: var(--color-surface-900);
  border-top: 1px solid var(--color-surface-800);
  margin-top: 4rem;
}
.footer-inner {
  max-width: 1400px; margin: 0 auto;
  padding: 3rem 1.5rem;
  display: grid;
  grid-template-columns: 1fr 2fr;
  gap: 3rem;
}
@media (max-width: 900px) {
  .footer-inner { grid-template-columns: 1fr; text-align: center; gap: 2.5rem; }
  .footer-brand { align-items: center; }
}

.footer-brand { display: flex; flex-direction: column; gap: 0; }
.footer-brand .logo-icon { margin-bottom: 0.75rem; }
.footer-brand-name {
  font-size: 1.125rem;
  font-weight: 700;
  color: white;
  margin: 0 0 0.375rem;
}
.footer-brand-desc {
  font-size: 0.875rem;
  color: var(--color-surface-400);
  margin: 0 0 0.5rem;
}
.footer-brand-copy {
  font-size: 0.75rem;
  color: var(--color-surface-500);
  margin: 0;
}
.footer-brand-copy strong { color: white; }
.footer-links {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 2rem;
}
@media (max-width: 640px) {
  .footer-links { grid-template-columns: 1fr; gap: 2rem; }
}
.footer-col h4 {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--color-surface-400);
  margin-bottom: 1rem;
}
.footer-col a, .footer-col :deep(a) {
  display: block;
  color: var(--color-surface-400);
  text-decoration: none;
  font-size: 0.875rem;
  margin-bottom: 0.5rem;
  transition: color 0.15s;
}
.footer-col a:hover, .footer-col :deep(a):hover { color: white; }

.footer-bottom {
  border-top: 1px solid var(--color-surface-800);
  padding: 1.25rem 1.5rem;
  max-width: 1400px; margin: 0 auto;
  display: flex; justify-content: space-between; align-items: center;
  font-size: 0.8rem;
  color: var(--color-surface-500);
}
.footer-bottom-links { display: flex; gap: 1.5rem; }
.footer-bottom-links a {
  color: var(--color-surface-500);
  text-decoration: none;
  transition: color 0.15s;
}
.footer-bottom-links a:hover { color: white; }

/* Transitions */
.dropdown-enter-active, .dropdown-leave-active { transition: all 0.15s ease; }
.dropdown-enter-from, .dropdown-leave-to { opacity: 0; transform: translateY(-8px); }
</style>
