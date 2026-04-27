<!-- resources/js/Pages/Profile/Partials/ProfileWallet.vue -->
<template>
  <div class="tab-pane">
    <!-- Balance banner -->
    <div class="balance-banner glass shadow-2xl relative overflow-hidden" v-motion-pop-visible>
      <div class="absolute -right-20 -top-20 w-64 h-64 bg-amber-500/10 rounded-full blur-3xl pointer-events-none" />
      <div class="balance-left relative z-10">
        <div class="balance-icon shadow-lg shadow-amber-500/20"><i class="pi pi-star-fill" /></div>
        <div>
          <p class="balance-label font-bold tracking-widest">{{ t('profile.wallet') }}</p>
          <p class="balance-amount font-display">{{ wallet?.balance?.toLocaleString() ?? 0 }} <span class="balance-unit">NT</span></p>
          <div class="calc-row">
            <p class="balance-usd">≈ ${{ ((wallet?.balance ?? 0) * 0.01).toFixed(2) }} USD</p>
            <div class="nt-calc" v-tooltip.top="'100 NT = $1.00'">
              <i class="pi pi-info-circle" />
            </div>
          </div>
        </div>
      </div>
      <div class="balance-stats relative z-10">
        <div class="bstat group">
          <p class="bstat-val text-green-400 group-hover:scale-105 transition-transform">+{{ walletIncoming.toLocaleString() }} NT</p>
          <p class="bstat-label">Recargado / Cashback</p>
        </div>
        <div class="bstat group">
          <p class="bstat-val text-red-400 group-hover:scale-105 transition-transform">-{{ walletSpent.toLocaleString() }} NT</p>
          <p class="bstat-label">Utilizado en compras</p>
        </div>
        <div class="bstat">
          <Button :label="t('profile.topUp')" icon="pi pi-bolt" size="small" class="topup-btn shadow-lg shadow-amber-500/30" @click="router.visit(route('wallet.topup'))" />
        </div>
      </div>
    </div>

    <!-- Uso -->
    <section class="pcard glass relative" style="margin-bottom:1rem" v-motion-slide-visible-bottom>
      <div class="pcard-header">
        <span class="pcard-icon"><i class="pi pi-chart-bar" /></span>
        <div>
          <h2 class="pcard-title font-bold">Uso de NexoTokens</h2>
          <p class="pcard-sub text-surface-400">{{ walletUsagePct }}% de tus NT han sido utilizados</p>
        </div>
        <Button :label="t('profile.topUp')" icon="pi pi-plus-circle" size="small" text class="ml-auto text-brand-400" @click="router.visit(route('wallet.topup'))" />
      </div>
      <div class="usage-section mt-4 px-2">
        <div class="usage-labels mb-2">
          <span class="text-xs uppercase font-bold tracking-tighter">Disponible: {{ wallet?.balance?.toLocaleString() }} NT</span>
          <span class="text-xs uppercase font-bold tracking-tighter">Histórico: {{ (walletIncoming + (wallet?.balance ?? 0)).toLocaleString() }} NT</span>
        </div>
        <ProgressBar :value="walletUsagePct" class="usage-bar shadow-inner" :showValue="false" :class="usageColorClass" />
      </div>
    </section>

    <!-- Transacciones -->
    <section class="pcard pcard--no-pad glass" v-motion-fade-visible>
      <div class="pcard-header">
        <span class="pcard-icon"><i class="pi pi-list" /></span>
        <div>
          <h2 class="pcard-title font-bold">Movimientos</h2>
          <p class="pcard-sub text-surface-400">{{ transactions.total }} {{ t('common.actions').toLowerCase() }}</p>
        </div>
        <div class="ml-auto flex items-center gap-3">
          <Select v-model="filterProxy" :options="typeOptions" optionLabel="label" optionValue="value"
            :placeholder="t('common.filter')" showClear style="min-width:180px" class="shadow-sm" />
        </div>
      </div>
      <DataTable :value="transactions.data" :loading="loading" size="small" :rowHover="true" class="tx-table">
        <Column :header="t('common.role')" style="min-width:150px">
          <template #body="{ data }">
            <div class="tx-cell">
              <div class="tx-icon shadow-sm" :class="txIconClass(data.type)">
                <i :class="`pi ${txIcon(data.type)}`" />
              </div>
              <span class="tx-label font-semibold">{{ txLabel(data.type) }}</span>
            </div>
          </template>
        </Column>
        <Column header="Descripción" style="min-width:180px">
          <template #body="{ data }">
            <p class="tx-desc italic text-surface-400">{{ data.description ?? txLabel(data.type) }}</p>
          </template>
        </Column>
        <Column header="Monto" style="width:120px">
          <template #body="{ data }">
            <span :class="['tx-amount font-black', isIncoming(data.type) ? 'amount-in' : 'amount-out']">
              {{ isIncoming(data.type) ? '+' : '-' }}{{ Number(data.amount).toFixed(0) }} NT
            </span>
          </template>
        </Column>
        <Column header="Saldo resultante" style="width:130px">
          <template #body="{ data }">
            <span class="balance-after font-mono text-surface-500">{{ Number(data.balance_after ?? 0).toFixed(0) }} NT</span>
          </template>
        </Column>
        <Column :header="t('common.date')" style="width:130px">
          <template #body="{ data }">
            <span class="date-text text-surface-500 font-medium">{{ fmtDate(data.created_at) }}</span>
          </template>
        </Column>
        <template #empty><EmptyState icon="pi-list" :title="t('common.noData')" /></template>
      </DataTable>
      <div class="pcard-footer glass">
        <Paginator
          :rows="transactions.per_page"
          :totalRecords="transactions.total"
          :first="(transactions.current_page - 1) * transactions.per_page"
          @page="$emit('page', $event.page + 1)"
        />
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import EmptyState from '@/Components/ui/EmptyState.vue';

const { t } = useI18n();

const props = defineProps({
  wallet:          { type: Object,  default: null },
  walletIncoming:  { type: Number,  default: 0 },
  walletSpent:     { type: Number,  default: 0 },
  transactions:    { type: Object,  required: true },
  loading:         { type: Boolean, default: false },
  filterType:      { type: String,  default: null },
});

const emit = defineEmits(['update:filterType', 'page']);

const filterProxy = computed({
  get: () => props.filterType,
  set: (val) => emit('update:filterType', val)
});

const walletUsagePct = computed(() => {
  const incoming = Number(props.walletIncoming) || 0;
  const balance  = Number(props.wallet?.balance) || 0;
  const spent    = Number(props.walletSpent) || 0;
  const total    = incoming + balance;
  return total > 0 ? Math.min(Math.round((spent / total) * 100), 100) : 0;
});

const usageColorClass = computed(() => {
  const pct = walletUsagePct.value;
  if (pct < 40) return 'usage-low';
  if (pct < 80) return 'usage-med';
  return 'usage-high';
});

const typeOptions = [
  { label: 'Recarga',   value: 'topup' },
  { label: 'Cashback',  value: 'cashback' },
  { label: 'Compra',    value: 'purchase' },
  { label: 'Reembolso', value: 'refund' },
  { label: 'Ajuste',    value: 'adjustment' },
];

const txLabel     = t_key => ({ topup: 'Recarga NT', cashback: 'Cashback', purchase: 'Compra', refund: 'Reembolso', adjustment: 'Ajuste' }[t_key] ?? t_key);
const txIcon      = t_key => ({ topup: 'pi-bolt', cashback: 'pi-star-fill', purchase: 'pi-shopping-bag', refund: 'pi-refresh', adjustment: 'pi-wrench' }[t_key] ?? 'pi-circle');
const txIconClass = t_key => ({ topup: 'icon-success', cashback: 'icon-warn', purchase: 'icon-danger', refund: 'icon-info', adjustment: 'icon-info' }[t_key] ?? 'icon-info');
const isIncoming  = t_key => ['topup', 'cashback', 'refund', 'adjustment'].includes(t_key);

function fmtDate(d) {
  if (!d) return '—';
  const ts = typeof d === 'number' ? d * 1000 : d;
  return new Date(ts).toLocaleString('es-PE', { dateStyle: 'short', timeStyle: 'short' });
}
</script>

<style scoped>
.tab-pane { display: flex; flex-direction: column; gap: 1.5rem; }
.pcard {
  border-radius: 20px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); padding: 1.5rem;
}
.pcard--no-pad { padding: 0; overflow: hidden; }
.pcard-header { display: flex; align-items: flex-start; gap: 1rem; padding: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.08); }
.pcard-icon { width: 44px; height: 44px; border-radius: 12px; background: rgba(99,102,241,0.12); border: 1px solid rgba(99,102,241,0.2); display: flex; align-items: center; justify-content: center; color: var(--color-brand-400); font-size: 1.1rem; }
.pcard-title { font-size: 1rem; color: white; margin-bottom: 0.25rem; }

.balance-banner { 
  display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 2rem; 
  background: linear-gradient(135deg, rgba(245,158,11,0.1) 0%, rgba(251,191,36,0.05) 100%); 
  border: 1.5px solid rgba(245,158,11,0.25); border-radius: 24px; padding: 2rem 2.5rem; 
}
.balance-icon { width: 60px; height: 60px; border-radius: 18px; background: linear-gradient(135deg, #f59e0b, #fbbf24); color: #000; display: flex; align-items: center; justify-content: center; font-size: 1.75rem; }
.balance-amount { font-size: 2.75rem; font-weight: 950; color: white; margin: 0.25rem 0; line-height: 1; }
.balance-unit   { font-size: 1.5rem; color: #fbbf24; }

.bstat-val     { font-size: 1.25rem; font-weight: 900; margin: 0; }
.topup-btn { background: #f59e0b !important; border: none !important; color: #000 !important; font-weight: 800 !important; border-radius: 12px !important; padding: 0.6rem 1.25rem !important; }

.usage-bar     { height: 12px !important; border-radius: 10px !important; }
.usage-low :deep(.p-progressbar-value) { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
.usage-med :deep(.p-progressbar-value) { background: linear-gradient(90deg, #8b5cf6, #a78bfa); }
.usage-high :deep(.p-progressbar-value) { background: linear-gradient(90deg, #f59e0b, #fbbf24); }

.tx-icon  { width: 32px; height: 32px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; }
.icon-success { background: rgba(16,185,129,0.15);  color: #4ade80; }
.icon-warn    { background: rgba(245,158,11,0.15);  color: #fbbf24; }
.icon-danger  { background: rgba(239,68,68,0.15);   color: #f87171; }
.icon-info    { background: rgba(99,102,241,0.15);  color: #818cf8; }

:deep(.tx-table .p-datatable-thead > tr > th) { background: rgba(255,255,255,0.02) !important; padding: 1.25rem 1.5rem !important; }
:deep(.tx-table .p-datatable-tbody > tr > td) { padding: 1.25rem 1.5rem !important; border-bottom: 1px solid rgba(255,255,255,0.04) !important; }

@media (max-width: 768px) {
  .balance-banner { padding: 1.5rem; }
  .balance-amount { font-size: 2.25rem; }
}
</style>
