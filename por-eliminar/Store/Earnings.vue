<template>
  <DashboardLayout title="Ingresos de Tienda" active="earnings">
    <Head title="Ingresos — Nexo eStore" />

    <!-- [REF-4] PageHeader actualizado: eliminadas referencias a "Vendedor" -->
    <PageHeader title="Ingresos de la Tienda" subtitle="Análisis financiero de Nexo eStore" icon="pi-chart-line"
      :breadcrumb="[{ label:'Admin' }, { label:'Tienda' }, { label:'Ingresos' }]">
      <template #actions>
        <SelectButton v-model="period" :options="periods" optionLabel="label" optionValue="value" @change="reload" />
      </template>
    </PageHeader>

    <!-- [REF-4] KPIs con terminología de tienda propia (sin "Comisión plataforma") -->
    <div class="stats-row">
      <!-- Ingreso bruto = 100% de cada venta (single-vendor, sin comisión externa) -->
      <StatsCard icon="pi-dollar"       label="Ingreso Bruto"     :value="'$'+fmt(earnings.gross)"    color="success" />
      <!-- Cashback Otorgado reemplaza "Comisión plataforma" [REF-1] -->
      <StatsCard icon="pi-wallet"       label="Cashback Otorgado" :value="'$'+fmt(earnings.cashback)"  color="warn" />
      <!-- Ingresos Netos = Bruto - Cashback -->
      <StatsCard icon="pi-chart-line"   label="Ingresos Netos"    :value="'$'+fmt(earnings.net)"       color="primary" />
      <StatsCard icon="pi-shopping-bag" label="Órdenes completas" :value="earnings.orders_count"       color="info" />
    </div>

    <div class="earn-grid">
      <!-- Gráfico: Bruto vs Cashback (antes: Bruto vs Neto) -->
      <DataCard icon="pi-chart-bar" title="Ingresos diarios" class="chart-card">
        <div class="chart-wrap"><canvas ref="earningsChart" /></div>
      </DataCard>

      <!-- [REF-4] Tabla: "Comisión" → "Cashback" -->
      <DataCard icon="pi-list" title="Transacciones completadas">
        <DataTable :value="transactions" size="small" :rowHover="true">
          <Column header="Pedido" style="width:100px">
            <template #body="{ data }"><span class="mono">#{{ data.order_ulid?.slice(-6).toUpperCase() }}</span></template>
          </Column>
          <Column header="Producto">
            <template #body="{ data }"><span class="cell-name">{{ data.product_name }}</span></template>
          </Column>
          <Column header="Ingreso Bruto" style="width:110px">
            <template #body="{ data }"><span class="gross-text">${{ fmt(data.gross) }}</span></template>
          </Column>
          <!-- [REF-4] "Comisión" eliminado → "Cashback" -->
          <Column header="Cashback" style="width:90px">
            <template #body="{ data }"><span class="cashback-text">-${{ fmt(data.cashback) }}</span></template>
          </Column>
          <Column header="Ingreso Neto" style="width:100px">
            <template #body="{ data }"><strong class="net-text">${{ fmt(data.net) }}</strong></template>
          </Column>
          <Column header="Comprador" style="width:120px">
            <template #body="{ data }"><span class="buyer-text">{{ data.buyer_name }}</span></template>
          </Column>
          <Column header="Fecha" style="width:110px">
            <template #body="{ data }"><span class="date-text">{{ fmtDate(data.date) }}</span></template>
          </Column>
          <template #empty><EmptyState icon="pi-chart-line" title="Sin transacciones" description="No hay ventas completadas en el período seleccionado." /></template>
        </DataTable>
      </DataCard>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { Chart, registerables } from 'chart.js';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader  from '@/Components/ui/PageHeader.vue';
import StatsCard   from '@/Components/ui/StatsCard.vue';
import DataCard    from '@/Components/ui/DataCard.vue';
import EmptyState  from '@/Components/ui/EmptyState.vue';
Chart.register(...registerables);

const props = defineProps({
  // [REF-4] earnings.cashback reemplaza earnings.fee (sin comisión externa)
  earnings:     { type:Object, default:()=>({ gross:0, cashback:0, net:0, orders_count:0 }) },
  transactions: { type:Array,  default:()=>[] },
  chartData:    { type:Object, default:()=>({ labels:[], gross:[], cashback:[] }) },
  period:       { type:Number, default:30 },
});

const periods       = [{ label:'7d', value:7 }, { label:'30d', value:30 }, { label:'90d', value:90 }];
const period        = ref(props.period);
const earningsChart = ref(null);

function fmt(v){ return parseFloat(v||0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}); }
function fmtDate(d){ return d ? new Date(d).toLocaleDateString('es-PE',{day:'2-digit',month:'short',year:'numeric'}) : '—'; }

// [REF-4] Ruta actualizada: admin.store.earnings (eliminado seller.earnings)
function reload(){ router.get(route('admin.store.earnings'),{ period: period.value },{preserveState:true,replace:true}); }

onMounted(() => {
  if (!earningsChart.value) return;
  const isDark = document.documentElement.getAttribute('data-mode') !== 'light';
  const gc = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
  const tc = isDark ? '#94a3b8' : '#64748b';

  new Chart(earningsChart.value, {
    type:'bar',
    data:{ labels: props.chartData.labels, datasets:[
      // [REF-4] Dataset "Bruto" sin cambios — es 100% de la venta
      { label:'Ingreso Bruto', data:props.chartData.gross,    backgroundColor:'rgba(16,185,129,0.3)', borderColor:'#10b981', borderWidth:2, borderRadius:4, borderSkipped:false },
      // [REF-4] "Neto" reemplazado por "Cashback" — la única deducción variable del modelo single-vendor
      { label:'Cashback NT',   data:props.chartData.cashback, backgroundColor:'rgba(245,158,11,0.5)', borderColor:'#f59e0b', borderWidth:0, borderRadius:4, borderSkipped:false },
    ]},
    options:{ responsive:true, maintainAspectRatio:false,
      plugins:{ legend:{ position:'top', labels:{ color:tc, font:{ size:11 } } } },
      scales:{ x:{ grid:{ color:gc }, ticks:{ color:tc, font:{ size:11 } } }, y:{ grid:{ color:gc }, ticks:{ color:tc, font:{ size:11 }, callback:v=>'$'+v } } } },
  });
});
</script>

<style scoped>
.stats-row  { display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; margin-bottom:1.5rem; }
@media(max-width:900px){ .stats-row{ grid-template-columns:repeat(2,1fr); } }
.earn-grid  { display:grid; grid-template-columns:1fr; gap:1.25rem; }
.chart-wrap { height:240px; position:relative; }
.mono       { font-family:monospace; font-size:0.8rem; color:var(--c-text-muted); }
.cell-name  { font-size:0.83rem; color:var(--c-text); }
.gross-text { font-size:0.83rem; color:#10b981; }
/* [REF-4] cashback-text reemplaza fee-text — color ámbar en lugar de rojo (no es un costo externo) */
.cashback-text { font-size:0.83rem; color:#f59e0b; }
.net-text   { font-size:0.88rem; color:var(--c-primary); }
.buyer-text { font-size:0.78rem; color:var(--c-text-muted); }
.date-text  { font-size:0.78rem; color:var(--c-text-muted); }
</style>
