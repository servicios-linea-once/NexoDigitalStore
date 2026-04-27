<template>
  <DashboardLayout :title="t('nav.adminPanel')" active="dashboard">
    <Head :title="t('nav.adminPanel') + ' — Admin'" />

    <PageHeader :title="t('nav.adminPanel')" :subtitle="t('home.hero.badge')" icon="pi-chart-bar"
      :breadcrumb="[{ label: 'Admin' }, { label: 'Dashboard' }]">
      <template #actions>
        <SelectButton v-model="period" :options="periods" optionLabel="label" optionValue="value" @change="reload" />
        <Button :label="t('common.create')" icon="pi pi-plus" size="small" @click="router.visit(route('admin.store.products.create'))" class="hidden sm:flex" />
      </template>
    </PageHeader>

    <!-- ── Key Stats ─────────────────────────────────────────────── -->
    <div class="stats-grid mb-8" v-motion-slide-visible-top>
      <StatsCard icon="pi-users"        :label="t('common.users')"          :value="stats.total_users"    :trend="null"              color="primary" />
      <StatsCard icon="pi-shopping-bag" :label="t('nav.orders')"           :value="stats.total_orders"   :trend="stats.orders_pct"  color="success" />
      <StatsCard icon="pi-dollar"       :label="'Ingreso Bruto'"           :value="formatCurrency(stats.financials.gross, 'USD')" :trend="stats.revenue_pct" color="warn" />
      <StatsCard icon="pi-wallet"       label="Cashback (NT)"              :value="stats.financials.cashback" :trend="null" color="info" />
      <StatsCard icon="pi-chart-line"   label="Ingreso Neto"               :value="formatCurrency(stats.financials.net, 'USD')" :trend="null" color="success" />
      <StatsCard icon="pi-box"          :label="t('nav.products')"         :value="stats.active_products" :trend="null"              color="primary" />
    </div>

    <!-- ── Main Grid ─────────────────────────────────────────────── -->
    <div class="main-grid">

      <!-- Revenue Chart (Enhanced) -->
      <DataCard icon="pi-chart-line" title="Análisis de Ingresos" subtitle="Ingreso Bruto vs Cashback otorgado" class="col-span-full shadow-xl" v-motion-fade-visible>
        <div class="chart-container"><canvas ref="revenueCanvas" /></div>
      </DataCard>

      <!-- Recent Orders -->
      <DataCard icon="pi-list" :title="t('nav.orders')" v-motion-slide-visible-left>
        <DataTable :value="recentOrders" size="small" :rowHover="true" responsiveLayout="scroll">
          <Column header="ID" class="w-20">
            <template #body="{ data }"><span class="mono text-xs">#{{ data.ulid?.slice(-6).toUpperCase() }}</span></template>
          </Column>
          <Column :header="t('common.user')">
            <template #body="{ data }">
              <div class="flex items-center gap-2">
                <Avatar :label="getInitials(data.buyer?.name)" shape="circle" size="small" />
                <span class="font-medium text-sm truncate max-w-[100px] sm:max-w-none">{{ data.buyer?.name ?? '—' }}</span>
              </div>
            </template>
          </Column>
          <Column :header="t('checkout.total')" class="w-28">
            <template #body="{ data }">
              <div class="flex flex-col leading-tight">
                <strong class="text-white text-sm">{{ formatCurrency(data.total, 'USD') }}</strong>
                <small class="text-surface-500 text-[10px]">{{ formatCurrency(data.total, 'PEN') }}</small>
              </div>
            </template>
          </Column>
          <Column :header="t('common.status')" class="w-24 text-right sm:text-left">
            <template #body="{ data }"><StatusBadge :status="data.status" /></template>
          </Column>
          <template #empty><EmptyState icon="pi-list" :title="t('common.noData')" /></template>
        </DataTable>
        <template #footer>
          <Button :label="t('common.viewAll')" icon="pi pi-arrow-right" iconPos="right" text size="small"
            @click="router.visit(route('admin.store.orders.index'))" />
        </template>
      </DataCard>

      <!-- Top Products -->
      <DataCard icon="pi-star" :title="t('home.sections.bestSellers')" v-motion-slide-visible-right>
        <DataTable :value="topProducts" size="small" :rowHover="true">
          <Column header="#" class="w-8">
            <template #body="s"><span class="rank text-xs">{{ s.index + 1 }}</span></template>
          </Column>
          <Column :header="t('common.product')">
            <template #body="{ data }"><span class="text-xs font-bold text-white line-clamp-1">{{ data.name }}</span></template>
          </Column>
          <Column header="Ventas" class="w-16">
            <template #body="{ data }"><span class="text-xs">{{ data.sold_count }}</span></template>
          </Column>
          <Column header="Stock" class="w-16">
            <template #body="{ data }">
              <Tag :value="String(data.stock_count)"
                :severity="data.stock_count > 5 ? 'success' : data.stock_count > 0 ? 'warn' : 'danger'"
                rounded class="text-[10px] px-1" />
            </template>
          </Column>
          <template #empty><EmptyState icon="pi-box" :title="t('common.noData')" /></template>
        </DataTable>
        <template #footer>
          <Button :label="t('nav.products')" icon="pi pi-arrow-right" iconPos="right" text size="small"
            @click="router.visit(route('admin.store.products.index'))" />
        </template>
      </DataCard>

      <!-- Role Distribution -->
      <DataCard icon="pi-users" title="Distribución de Usuarios" v-motion-fade-visible>
        <div class="flex flex-col gap-4 py-2">
          <div v-for="r in roleStats" :key="r.role" class="flex items-center gap-3">
            <div class="flex items-center gap-2 min-w-[100px]">
              <Tag :value="r.role" :severity="getRoleSeverity(r.role)" rounded size="small" class="capitalize" />
              <span class="text-xs text-surface-400">{{ r.count }}</span>
            </div>
            <ProgressBar :value="r.pct" class="flex-1 h-2 rounded-full shadow-inner" :showValue="false" />
            <span class="text-xs font-bold w-10 text-right">{{ r.pct }}%</span>
          </div>
          <div v-if="!roleStats?.length" class="text-center py-8 italic text-surface-500">{{ t('common.noData') }}</div>
        </div>
      </DataCard>

      <!-- Inventory Summary -->
      <DataCard icon="pi-box" title="Resumen de Inventario" v-motion-fade-visible>
        <div class="grid grid-cols-2 gap-4 py-2">
           <div class="p-3 bg-surface-800/50 rounded-xl border border-surface-700/50 flex flex-col items-center justify-center">
              <span class="text-surface-400 text-xs mb-1 uppercase tracking-wider">Licencias Disponibles</span>
              <span class="text-2xl font-bold text-primary-400">{{ stats.available_keys }}</span>
           </div>
           <div class="p-3 bg-surface-800/50 rounded-xl border border-surface-700/50 flex flex-col items-center justify-center">
              <span class="text-surface-400 text-xs mb-1 uppercase tracking-wider">Stock Bajo</span>
              <span class="text-2xl font-bold" :class="stats.low_stock > 0 ? 'text-warn-400' : 'text-success-400'">{{ stats.low_stock }}</span>
           </div>
           <div class="col-span-2 p-3 bg-surface-800/50 rounded-xl border border-surface-700/50 flex items-center justify-between">
              <div class="flex items-center gap-3">
                <i class="pi pi-box text-xl text-info-400"></i>
                <span class="text-sm font-medium">Productos Activos</span>
              </div>
              <span class="text-xl font-bold">{{ stats.active_products }}</span>
           </div>
        </div>
      </DataCard>

    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { Chart, registerables } from 'chart.js';
import { useUtils } from '@/composables/useUtils';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader   from '@/Components/ui/PageHeader.vue';
import StatsCard    from '@/Components/ui/StatsCard.vue';
import DataCard     from '@/Components/ui/DataCard.vue';
import EmptyState   from '@/Components/ui/EmptyState.vue';
import StatusBadge  from '@/Components/ui/StatusBadge.vue';

Chart.register(...registerables);

const { t } = useI18n();

const props = defineProps({
  stats:        { type: Object, default: () => ({ financials: { gross:0, net:0, cashback:0 } }) },
  recentOrders: { type: Array,  default: () => [] },
  topProducts:  { type: Array,  default: () => [] },
  roleStats:    { type: Array,  default: () => [] },
  revenueChart: { type: Array,  default: () => [] },
  period:       { type: Number, default: 30 },
});

const { formatCurrency, getInitials, getRoleSeverity } = useUtils();

const periods       = [{ label: '7d', value: 7 }, { label: '30d', value: 30 }, { label: '90d', value: 90 }];
const period        = ref(props.period);
const revenueCanvas = ref(null);
let chartInstance   = null;

function reload() { router.get(route('admin.dashboard'), { period: period.value }, { preserveState: true, replace: true }); }

function initChart() {
  if (!revenueCanvas.value) return;
  if (chartInstance) chartInstance.destroy();

  const isDark    = document.documentElement.getAttribute('data-mode') !== 'light';
  const gridColor = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';
  const textColor = isDark ? '#94a3b8' : '#64748b';

  chartInstance = new Chart(revenueCanvas.value, {
    type: 'line',
    data: {
      labels:   props.revenueChart.map(r => r.date),
      datasets: [
        {
          label: 'Ingreso Bruto',
          data:  props.revenueChart.map(r => r.revenue),
          borderColor: '#6366f1', 
          backgroundColor: 'rgba(99,102,241,0.08)',
          borderWidth: 3, tension: 0.4, fill: true, 
          pointBackgroundColor: '#6366f1', pointRadius: 0, pointHoverRadius: 5,
        },
        {
          label: 'Cashback (NT)',
          data:  props.revenueChart.map(r => r.cashback),
          borderColor: '#f59e0b', 
          backgroundColor: 'transparent',
          borderWidth: 2, tension: 0.4, fill: false, 
          pointBackgroundColor: '#f59e0b', pointRadius: 0, pointHoverRadius: 5,
          borderDash: [5, 5]
        }
      ],
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      interaction: { intersect: false, mode: 'index' },
      plugins: { 
        legend: { 
          display: true, 
          position: 'top',
          align: 'end',
          labels: { color: textColor, boxWidth: 12, font: { size: 11 } } 
        } 
      },
      scales: {
        x: { grid: { display: false }, ticks: { color: textColor, font: { size: 10, weight: 'bold' } } },
        y: { grid: { color: gridColor }, border: { dash: [4, 4] }, ticks: { color: textColor, font: { size: 10 }, callback: v => '$' + v } },
      },
    },
  });
}

onMounted(() => initChart());
watch(() => props.revenueChart, () => initChart());
</script>

<style scoped>
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1.25rem;
}
@media (max-width: 640px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
  }
}

.main-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
}
@media (max-width: 1024px) {
  .main-grid {
    grid-template-columns: 1fr;
  }
}

.chart-container {
  height: 300px;
  position: relative;
  padding: 1rem 0;
}
@media (max-width: 640px) {
  .chart-container {
    height: 220px;
  }
}

.mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
.rank { font-weight: 800; color: var(--color-surface-600); }
</style>
