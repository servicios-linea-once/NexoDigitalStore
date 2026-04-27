<template>
  <AppLayout>
    <Head title="Productos — Nexo Digital Store" />

    <div class="catalog-layout">
      <!-- ── Sidebar Filters (Desktop) ─────────────────────────────────── -->
      <aside class="catalog-sidebar hidden lg:flex">
        <FilterContent :categories="categories" :local="local" @apply="apply" @clear="clearFilters" @toggle="toggle" @debounced="debouncedApply" />
      </aside>

      <!-- ── Main ──────────────────────────────────────────────────────── -->
      <main class="catalog-main">
        <!-- Toolbar -->
        <div class="catalog-toolbar">
          <div class="toolbar-left">
            <Button
              icon="pi pi-sliders-h"
              class="lg:hidden"
              outlined
              severity="secondary"
              size="small"
              @click="mobileFiltersOpen = true"
            />
            <span class="results-count hidden sm:block"><strong>{{ products.total }}</strong> productos</span>
            <div class="active-chips">
              <Chip v-if="local.q"        removable :label="'&quot;' + local.q + '&quot;'"  @remove="rm('q')" />
              <Chip v-if="local.category" removable :label="catName"             @remove="rm('category')" />
            </div>
          </div>
          <Select
            v-model="local.sort"
            :options="sortOptions"
            optionLabel="label"
            optionValue="value"
            class="sort-select h-9 text-sm"
            @change="apply"
          />
        </div>
...
    <!-- Mobile Filters Drawer -->
    <Drawer v-model:visible="mobileFiltersOpen" header="Filtros" position="left" class="!w-full max-w-[320px] catalog-drawer">
      <FilterContent :categories="categories" :local="local" @apply="apply" @clear="clearFilters" @toggle="toggle" @debounced="debouncedApply" />
    </Drawer>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ProductCard from '@/Components/ProductCard.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import FilterContent from './Partials/FilterContent.vue'; // Need to create this

const props = defineProps({
...
const mobileFiltersOpen = ref(false);
...
</script>

<style scoped>
.catalog-layout {
  display: grid;
  grid-template-columns: 260px 1fr;
  gap: 1.5rem;
  align-items: start;
  padding-bottom: 3rem;
}
@media (max-width: 1024px) {
  .catalog-layout { grid-template-columns: 1fr; }
}

/* Sidebar */
.catalog-sidebar {
  position: sticky; top: 5rem;
  max-height: calc(100vh - 6rem); overflow-y: auto;
  background: var(--c-surface);
  border: 1px solid var(--c-border);
  border-radius: 16px;
  padding: 1.125rem;
  display: flex; flex-direction: column; gap: 1rem;
  scrollbar-width: thin;
}
...
.products-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 1rem; margin-bottom: 2rem;
}
@media (max-width: 640px) {
  .products-grid { grid-template-columns: repeat(2, 1fr); gap: 0.75rem; }
}
...
</style>

        <!-- Grid -->
        <TransitionGroup name="product-list" tag="div" class="products-grid" v-if="products.data.length">
          <ProductCard v-for="p in products.data" :key="p.id" :product="adapt(p)" />
        </TransitionGroup>

        <!-- Empty -->
        <EmptyState
          v-else
          icon="pi-search"
          title="Sin resultados"
          description="Intenta con otros filtros o términos de búsqueda."
          action-label="Limpiar filtros"
          @action="clearFilters"
        />

        <!-- Pagination -->
        <Paginator
          v-if="products.last_page > 1"
          :rows="products.per_page"
          :totalRecords="products.total"
          :first="(products.current_page - 1) * products.per_page"
          @page="goPage($event.page + 1)"
          class="catalog-paginator"
        />
      </main>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ProductCard from '@/Components/ProductCard.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';

const props = defineProps({
  products:   { type: Object, required: true },
  categories: { type: Array,  default: () => [] },
  filters:    { type: Object, default: () => ({}) },
});

const local = ref({
  q:         props.filters.q         || '',
  category:  props.filters.category  || '',
  platform:  props.filters.platform  || '',
  region:    props.filters.region    || '',
  price_min: props.filters.price_min || null,
  price_max: props.filters.price_max || null,
  in_stock:  props.filters.in_stock  || false,
  sort:      props.filters.sort      || 'newest',
});

const platforms = ['Steam','Epic Games','GOG','Battle.net','PSN','Xbox','Nintendo','Netflix','Spotify','Disney+','Microsoft','Rockstar'];
const regions   = [
  { code:'Global', label:'🌍 Global' },
  { code:'PE',     label:'🇵🇪 Perú' },
  { code:'US',     label:'🇺🇸 EEUU' },
  { code:'EU',     label:'🇪🇺 Europa' },
  { code:'MX',     label:'🇲🇽 México' },
  { code:'CO',     label:'🇨🇴 Colombia' },
];
const sortOptions = [
  { label: 'Más recientes',   value: 'newest' },
  { label: 'Más vendidos',    value: 'popular' },
  { label: 'Mejor valorados', value: 'rating' },
  { label: 'Precio ↑',        value: 'price_asc' },
  { label: 'Precio ↓',        value: 'price_desc' },
];

const hasActiveFilters = computed(() =>
  local.value.q || local.value.category || local.value.platform ||
  local.value.region || local.value.price_min || local.value.price_max || local.value.in_stock
);
const catName = computed(() => props.categories.find(c => c.slug === local.value.category)?.name || '');

function apply() {
  const p = Object.fromEntries(Object.entries(local.value).filter(([,v]) => v !== '' && v !== false && v !== null));
  router.get(route('products.index'), p, { preserveState: true, replace: true });
}

let debTimer = null;
function debouncedApply() { clearTimeout(debTimer); debTimer = setTimeout(apply, 400); }

function toggle(key, val) { local.value[key] = local.value[key] === val ? '' : val; apply(); }
function rm(key) { local.value[key] = ''; apply(); }
function clearFilters() {
  Object.keys(local.value).forEach(k => { local.value[k] = k === 'sort' ? 'newest' : ''; });
  local.value.in_stock = false; local.value.price_min = null; local.value.price_max = null; apply();
}
function goPage(pg) {
  const p = { ...Object.fromEntries(Object.entries(local.value).filter(([,v]) => v !== '' && v !== false && v !== null)), page: pg };
  router.get(route('products.index'), p, { preserveState: true });
}

function adapt(p) {
  return {
    ...p,
    base_price:       parseFloat(p.base_price || 0),
    discount_percent: p.discount_percent || 0,
    cashback_percent: p.cashback_percent || 0,
    stock_count:      p.stock_count      || 0,
    rating:           parseFloat(p.rating || 0),
    rating_count:     p.rating_count     || 0,
    cover_image:      p.cover_image      || null,
  };
}
</script>

<style scoped>
.catalog-layout {
  display: grid;
  grid-template-columns: 260px 1fr;
  gap: 1.5rem;
  align-items: start;
  padding-bottom: 3rem;
}
@media (max-width: 900px) {
  .catalog-layout { grid-template-columns: 1fr; }
  .catalog-sidebar { display: none; }
}

/* Sidebar */
.catalog-sidebar {
  position: sticky; top: 5rem;
  max-height: calc(100vh - 6rem); overflow-y: auto;
  background: var(--c-surface);
  border: 1px solid var(--c-border);
  border-radius: 16px;
  padding: 1.125rem;
  display: flex; flex-direction: column; gap: 1rem;
  scrollbar-width: thin;
}
.filter-header {
  display: flex; align-items: center; justify-content: space-between;
  padding-bottom: 0.75rem; border-bottom: 1px solid var(--c-border);
}
.filter-title { font-size: 0.9rem; font-weight: 700; color: var(--c-text); display: flex; align-items: center; gap: 0.5rem; }
.filter-section { display: flex; flex-direction: column; gap: 0.5rem; }
.filter-label {
  font-size: 0.7rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: 0.06em; color: var(--c-text-subtle);
}
.chip-group { display: flex; flex-wrap: wrap; gap: 0.375rem; }

:deep(.filter-chip) { cursor: pointer; transition: all 0.15s; font-size: 0.75rem !important; }
:deep(.filter-chip.active .p-chip) { background: var(--c-primary-muted) !important; border-color: var(--c-primary) !important; color: var(--c-primary) !important; }
:deep(.filter-chip .p-chip) { border: 1px solid var(--c-border); }
:deep(.filter-chip:hover .p-chip) { border-color: var(--c-primary); color: var(--c-primary); }

.price-range { display: flex; align-items: center; gap: 0.5rem; }
.price-sep { color: var(--c-text-subtle); font-size: 0.8rem; }
.filter-check { flex-direction: row; align-items: center; }
.filter-check-label { font-size: 0.82rem; color: var(--c-text-muted); cursor: pointer; }

/* Main */
.catalog-toolbar {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem;
}
.toolbar-left  { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; }
.results-count { font-size: 0.875rem; color: var(--c-text-muted); }
.results-count strong { color: var(--c-text); }
.active-chips  { display: flex; gap: 0.375rem; flex-wrap: wrap; }

.sort-select { min-width: 180px; }

.products-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1rem; margin-bottom: 2rem;
}

/* List animation */
.product-list-enter-active { transition: all 0.3s ease; }
.product-list-leave-active  { transition: all 0.2s ease; position: absolute; }
.product-list-enter-from    { opacity: 0; transform: translateY(10px); }
.product-list-leave-to      { opacity: 0; }

.catalog-paginator { margin-top: 1.5rem; }
</style>
