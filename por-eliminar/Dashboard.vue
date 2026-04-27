<template>
  <DashboardLayout title="Dashboard Vendedor" active="seller-dash">
    <Head title="Dashboard Vendedor — Nexo" />
    <PageHeader :title="`Hola, ${user.name} 👋`" subtitle="Tu resumen de actividad" icon="pi-chart-line"
      :breadcrumb="[{ label: 'Vendedor' }, { label: 'Dashboard' }]">
      <template #actions>
        <Button label="Nuevo producto" icon="pi pi-plus" @click="$inertia.visit(route('admin.store.products.create'))" />
      </template>
    </PageHeader>

    <div class="stats-row">
      <StatsCard icon="pi-box"          label="Productos activos"  :value="stats.active_products"      color="primary" />
      <StatsCard icon="pi-shopping-bag" label="Órdenes (30d)"      :value="stats.orders_30d"            color="success" />
      <StatsCard icon="pi-dollar"       label="Ganancias (30d)"    :value="'$'+fmt(stats.earnings_30d)" color="warn" />
      <StatsCard icon="pi-key"          label="Claves disponibles" :value="stats.available_keys"        color="info" />
    </div>

    <div class="dash-grid">
      <DataCard icon="pi-chart-line" title="Ganancias diarias" class="chart-card">
        <div class="chart-wrap"><canvas ref="earningsCanvas" /></div>
      </DataCard>

      <DataCard icon="pi-list" title="Pedidos recientes">
        <DataTable :value="recentOrders" size="small" :rowHover="true">
          <Column header="Producto">
            <template #body="{ data }"><span class="cell-name">{{ data.items?.[0]?.product?.name ?? '—' }}</span></template>
          </Column>
          <Column header="Total" style="width:90px">
            <template #body="{ data }"><strong>${{ fmt(data.total_amount) }}</strong></template>
          </Column>
          <Column header="Estado" style="width:110px">
            <template #body="{ data }"><StatusBadge :status="data.status" /></template>
          </Column>
          <template #empty><EmptyState icon="pi-list" title="Sin pedidos" description="No hay pedidos aún." /></template>
        </DataTable>
        <template #footer>
          <Button label="Ver todos" icon="pi pi-arrow-right" iconPos="right" text size="small" @click="$inertia.visit(route('admin.store.orders.index'))" />
        </template>
      </DataCard>

      <DataCard icon="pi-star" title="Más vendidos">
        <DataTable :value="topProducts" size="small" :rowHover="true">
          <Column header="#" style="width:40px">
            <template #body="s"><span class="rank">{{ s.index + 1 }}</span></template>
          </Column>
          <Column header="Producto">
            <template #body="{ data }"><span class="cell-name">{{ data.name }}</span></template>
          </Column>
          <Column header="Ventas" style="width:75px">
            <template #body="{ data }">{{ data.sold_count }}</template>
          </Column>
          <Column header="Stock" style="width:75px">
            <template #body="{ data }">
              <Tag :value="String(data.stock_count)" :severity="data.stock_count > 5 ? 'success' : data.stock_count > 0 ? 'warn' : 'danger'" rounded size="small" />
            </template>
          </Column>
          <template #empty><EmptyState icon="pi-box" title="Sin productos" description="Crea tu primer producto." action-label="Crear" @action="$inertia.visit(route('admin.store.products.create'))" /></template>
        </DataTable>
      </DataCard>

      <DataCard icon="pi-bolt" title="Acciones rápidas">
        <div class="quick-grid">
          <a :href="route('admin.store.products.create')"   class="quick-btn"><i class="pi pi-plus" /><span>Nuevo producto</span></a>
          <a :href="route('admin.store.keys.index')"         class="quick-btn"><i class="pi pi-key" /><span>Importar claves</span></a>
          <a :href="route('admin.store.promotions.create')"  class="quick-btn"><i class="pi pi-percentage" /><span>Promoción</span></a>
          <a :href="route('admin.store.deliveries.index')"   class="quick-btn"><i class="pi pi-send" /><span>Entregas</span></a>
        </div>
      </DataCard>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { Chart, registerables } from 'chart.js';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader  from '@/Components/ui/PageHeader.vue';
import StatsCard   from '@/Components/ui/StatsCard.vue';
import DataCard    from '@/Components/ui/DataCard.vue';
import EmptyState  from '@/Components/ui/EmptyState.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
Chart.register(...registerables);

const props = defineProps({
  stats:        { type: Object, default: () => ({}) },
  recentOrders: { type: Array,  default: () => [] },
  topProducts:  { type: Array,  default: () => [] },
  chartData:    { type: Object, default: () => ({ labels: [], earnings: [] }) },
});
const user           = computed(() => usePage().props.auth.user);
const earningsCanvas = ref(null);
function fmt(v) { return parseFloat(v||0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}); }

onMounted(() => {
  if (!earningsCanvas.value) return;
  const isDark    = document.documentElement.getAttribute('data-mode') !== 'light';
  const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
  const textColor = isDark ? '#94a3b8' : '#64748b';
  new Chart(earningsCanvas.value, {
    type: 'bar',
    data: { labels: props.chartData.labels, datasets: [{ label:'Ganancias', data: props.chartData.earnings, backgroundColor:'rgba(16,185,129,0.7)', borderRadius:6, borderSkipped:false }] },
    options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false } },
      scales: { x:{ grid:{ color:gridColor }, ticks:{ color:textColor, font:{ size:11 } } }, y:{ grid:{ color:gridColor }, ticks:{ color:textColor, font:{ size:11 }, callback: v => '$'+v } } } },
  });
});
</script>

<style scoped>
.stats-row  { display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; margin-bottom:1.5rem; }
@media(max-width:900px){ .stats-row{ grid-template-columns:repeat(2,1fr); } }
.dash-grid  { display:grid; grid-template-columns:1fr 1fr; gap:1.25rem; }
@media(max-width:1024px){ .dash-grid{ grid-template-columns:1fr; } }
.chart-card { grid-column:1/-1; }
.chart-wrap { height:220px; position:relative; }
.cell-name  { font-size:0.83rem; color:var(--c-text); font-weight:500; }
.rank       { font-size:0.85rem; font-weight:700; color:var(--c-text-subtle); }
.quick-grid { display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; }
.quick-btn  { display:flex; align-items:center; gap:0.625rem; padding:0.875rem 1rem; border-radius:12px; background:var(--c-card); border:1px solid var(--c-border); color:var(--c-text-muted); font-size:0.83rem; font-weight:600; text-decoration:none; transition:all 0.18s; }
.quick-btn:hover{ background:var(--c-primary-muted); color:var(--c-primary); border-color:var(--c-primary); }
.quick-btn .pi{ font-size:1rem; }
</style>
