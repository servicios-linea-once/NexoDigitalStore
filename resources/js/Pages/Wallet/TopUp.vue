<template>
  <AppLayout>
    <Head title="Recargar Wallet — Nexo Digital Store" />

    <div class="page-wrap">
      <PageHeader
        title="Recargar NexoTokens"
        subtitle="Añade NexoTokens (NT) a tu billetera para usarlos en compras"
        icon="pi-wallet"
        :breadcrumb="[{ label: 'Mi cuenta' }, { label: 'Recargar Wallet' }]"
      />

      <div class="topup-grid">
        <div class="col-main">
          <DataCard>
            <template #header>
              <div class="card-head"><i class="pi pi-star-fill nt-icon" /> Selecciona el paquete</div>
            </template>
            <div class="packages-grid">
              <button v-for="pkg in packages" :key="pkg.nt" :class="['pkg-btn', { selected: selectedPkg?.nt === pkg.nt }]" @click="selectPkg(pkg)">
                <span class="pkg-nt">{{ pkg.nt }} NT</span>
                <span class="pkg-price">${{ pkg.usd.toFixed(2) }} USD</span>
                <span v-if="pkg.bonus" class="pkg-bonus">+{{ pkg.bonus }} NT gratis</span>
              </button>
            </div>
            <Divider />
            <div class="field field--spaced">
              <label class="field-label">O ingresa monto personalizado (NT)</label>
              <InputNumber v-model="customNT" :min="10" :max="10000" :step="10" placeholder="Ej: 200" suffix=" NT" fluid @input="selectedPkg = null" />
              <small class="field-hint">Mínimo 10 NT · 1 NT = $0.01 USD</small>
            </div>
            <Divider />
            <div class="field">
              <label class="field-label">Método de pago</label>
              <div class="payment-methods">
                <button v-for="m in paymentMethods" :key="m.id" :class="['method-btn', { selected: paymentMethod === m.id }]" @click="paymentMethod = m.id">
                  <i :class="`pi ${m.icon}`" /><span>{{ m.label }}</span>
                </button>
              </div>
            </div>
            <div class="submit-row">
              <Button :label="`Recargar ${ntAmount} NT por $${usdAmount.toFixed(2)}`" icon="pi pi-bolt" :disabled="!ntAmount || !paymentMethod" :loading="processing" @click="submit" size="large" fluid />
            </div>
          </DataCard>
        </div>

        <div class="col-side">
          <DataCard class="card-wallet">
            <template #header><div class="card-head"><i class="pi pi-wallet" /> Tu billetera</div></template>
            <div class="wallet-display">
              <i class="pi pi-star-fill nt-icon-lg" />
              <div>
                <p class="balance-val">{{ wallet?.balance?.toLocaleString() ?? 0 }} NT</p>
                <p class="balance-sub">Balance actual</p>
              </div>
            </div>
          </DataCard>
          <DataCard class="card-summary">
            <template #header><div class="card-head"><i class="pi pi-receipt" /> Resumen</div></template>
            <div class="summary-list">
              <div class="summary-row"><span>NexoTokens</span><span class="summary-val">{{ ntAmount || 0 }} NT</span></div>
              <div v-if="bonusNT > 0" class="summary-row text-green"><span>Bonus</span><span class="summary-val">+{{ bonusNT }} NT</span></div>
              <Divider />
              <div class="summary-row summary-total"><span>Total</span><span>${{ usdAmount.toFixed(2) }} USD</span></div>
            </div>
          </DataCard>
          <DataCard>
            <template #header><div class="card-head"><i class="pi pi-info-circle" /> ¿Cómo funciona?</div></template>
            <ul class="info-list">
              <li><i class="pi pi-check-circle" /> 1 NT = $0.01 USD</li>
              <li><i class="pi pi-check-circle" /> Se acreditan al instante</li>
              <li><i class="pi pi-check-circle" /> Úsalos como descuento al comprar</li>
              <li><i class="pi pi-check-circle" /> Acumula NT con cashback</li>
              <li><i class="pi pi-info-circle" /> Los NT no son reembolsables</li>
            </ul>
          </DataCard>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, router }  from '@inertiajs/vue3';
import { useToast }      from 'primevue/usetoast';
import AppLayout  from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import DataCard   from '@/Components/ui/DataCard.vue';

const props = defineProps({ wallet: { type: Object, default: null } });
const toast = useToast();

const packages = [
  { nt:100,  usd:1.00,  bonus:0   },
  { nt:500,  usd:5.00,  bonus:25  },
  { nt:1000, usd:9.50,  bonus:75  },
  { nt:2500, usd:22.50, bonus:250 },
  { nt:5000, usd:42.00, bonus:800 },
  { nt:10000,usd:80.00, bonus:2000},
];
const paymentMethods = [
  { id:'paypal',      label:'PayPal',      icon:'pi-paypal'      },
  { id:'mercadopago', label:'MercadoPago', icon:'pi-credit-card' },
];

const selectedPkg   = ref(packages[1]);
const customNT      = ref(null);
const paymentMethod = ref('paypal');
const processing    = ref(false);

const ntAmount  = computed(() => selectedPkg.value?.nt ?? customNT.value ?? 0);
const bonusNT   = computed(() => selectedPkg.value?.bonus ?? 0);
const usdAmount = computed(() => selectedPkg.value ? selectedPkg.value.usd : (customNT.value ?? 0) * 0.01);

function selectPkg(pkg) { selectedPkg.value = pkg; customNT.value = null; }
function submit() {
  if (!ntAmount.value || !paymentMethod.value) return;
  processing.value = true;
  router.post(route('wallet.topup.process'), { nt_amount: ntAmount.value, payment_method: paymentMethod.value }, {
    onSuccess: () => toast.add({ severity:'success', summary:'¡Recarga iniciada!', life:3000 }),
    onError:   (e) => toast.add({ severity:'error', summary:e.message ?? 'Error al procesar', life:4000 }),
    onFinish:  () => { processing.value = false; },
  });
}
</script>

<style scoped>
.page-wrap { max-width:1000px; margin:0 auto; padding-bottom:3rem; }
.topup-grid { display:grid; grid-template-columns:1fr 300px; gap:1.25rem; }
@media (max-width:800px) { .topup-grid { grid-template-columns:1fr; } }
.col-main, .col-side { display:flex; flex-direction:column; gap:1rem; }
.card-head { display:flex; align-items:center; gap:0.5rem; padding:0.875rem 1.125rem; font-size:0.85rem; font-weight:700; color:var(--c-text); }
.nt-icon    { color:#f59e0b; }
.nt-icon-lg { font-size:2rem; color:#f59e0b; }
.packages-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:0.625rem; }
@media (max-width:600px) { .packages-grid { grid-template-columns:repeat(2,1fr); } }
.pkg-btn { border:1px solid var(--c-border); border-radius:12px; padding:0.875rem 0.75rem; background:var(--c-card); cursor:pointer; display:flex; flex-direction:column; align-items:center; gap:0.2rem; transition:all 0.2s; }
.pkg-btn:hover, .pkg-btn.selected { border-color:var(--c-primary); background:var(--c-primary-muted); }
.pkg-btn.selected { box-shadow:0 0 0 2px var(--c-primary); }
.pkg-nt    { font-size:1.1rem; font-weight:800; color:var(--c-text); }
.pkg-price { font-size:0.75rem; color:var(--c-text-muted); }
.pkg-bonus { font-size:0.68rem; color:#10b981; font-weight:700; background:rgba(16,185,129,0.1); padding:0.1rem 0.4rem; border-radius:6px; }
.payment-methods { display:flex; gap:0.75rem; flex-wrap:wrap; }
.method-btn { flex:1; min-width:120px; display:flex; align-items:center; justify-content:center; gap:0.5rem; border:1px solid var(--c-border); border-radius:10px; padding:0.75rem 1rem; background:var(--c-card); cursor:pointer; font-size:0.85rem; font-weight:600; color:var(--c-text); transition:all 0.2s; }
.method-btn:hover { border-color:var(--c-primary); }
.method-btn.selected { border-color:var(--c-primary); background:var(--c-primary-muted); color:var(--c-primary); }
.field { display:flex; flex-direction:column; gap:0.375rem; }
.field-label { font-size:0.8rem; font-weight:600; color:var(--c-text-muted); }
.field-hint  { font-size:0.72rem; color:var(--c-text-subtle); }
.submit-row  { margin-top:1.25rem; }
.field--spaced { margin-top: 0.5rem; }
.card-wallet, .card-summary { /* spacing handled by gap in col-side */ }
.wallet-display { display:flex; align-items:center; gap:1rem; padding:0.5rem 0; }
.balance-val { font-size:1.75rem; font-weight:900; color:var(--c-text); margin:0; }
.balance-sub { font-size:0.75rem; color:var(--c-text-muted); margin:0; }
.summary-list { display:flex; flex-direction:column; }
.summary-row  { display:flex; justify-content:space-between; font-size:0.85rem; color:var(--c-text-muted); padding:0.5rem 0; border-bottom:1px solid var(--c-border); }
.summary-row:last-child { border-bottom:none; }
.summary-total { font-weight:800; font-size:0.95rem; color:var(--c-text); }
.summary-val { font-weight:700; color:var(--c-text); }
.text-green  { color:#10b981!important; }
.info-list   { list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:0.625rem; font-size:0.83rem; color:var(--c-text-muted); }
.info-list li { display:flex; align-items:center; gap:0.5rem; }
.info-list .pi-check-circle { color:#10b981; }
.info-list .pi-info-circle  { color:var(--c-primary); }
</style>
