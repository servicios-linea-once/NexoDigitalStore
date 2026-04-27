<template>
  <AppLayout>
    <Head :title="t('checkout.title') + ' — Nexo Digital Store'" />
    <div class="checkout-page">
      <PageHeader :title="t('checkout.title')" :subtitle="t('checkout.subtitle')" icon="pi-lock"
        :breadcrumb="[{ label:t('cart.title'), route:'cart.index' }, { label:t('checkout.title') }]" />

      <div class="checkout-grid">
        <!-- LEFT: Steps -->
        <div class="checkout-main" v-motion-slide-visible-left>
          <Stepper v-model:value="step" linear class="checkout-stepper">
            <!-- Step List -->
            <StepList>
              <Step value="1">{{ t('checkout.steps.review') }}</Step>
              <Step value="2">{{ t('checkout.steps.payment') }}</Step>
              <Step value="3">{{ t('checkout.steps.confirmation') }}</Step>
            </StepList>

            <StepPanels>
              <!-- Review -->
              <StepPanel value="1">
                <DataCard icon="pi-list" :title="t('checkout.itemsInOrder')">
                  <div class="order-items">
                    <div v-for="item in cart" :key="item.ulid" class="order-item group hover:bg-surface-800 transition-colors">
                      <img v-if="item.cover_image" :src="item.cover_image" class="item-thumb shadow-md" :alt="item.name" />
                      <div v-else class="item-thumb-ph"><i class="pi pi-box" /></div>
                      <div class="item-info">
                        <p class="item-name font-semibold">{{ item.name }}</p>
                        <div class="item-tags">
                          <Chip v-if="item.platform" :label="item.platform" class="chip-xs" />
                          <Chip v-if="item.region" :label="item.region" class="chip-xs" />
                        </div>
                      </div>
                      <div class="item-price text-right">
                        <span v-if="item.discount_percent" class="price-original text-xs line-through text-surface-500">${{ item.base_price.toFixed(2) }}</span>
                        <strong class="price-final text-lg font-bold">${{ item.final_price.toFixed(2) }}</strong>
                      </div>
                    </div>
                  </div>

                  <!-- NT Tokens -->
                  <Divider />
                  <div class="nt-section bg-surface-900/50 p-4 rounded-xl border border-surface-800">
                    <div class="nt-header flex items-center justify-between mb-4">
                      <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-amber-500/10 flex items-center justify-center border border-amber-500/20">
                          <i class="pi pi-star-fill text-amber-500" />
                        </div>
                        <span class="nt-label font-bold text-base">{{ t('checkout.useTokens') }}</span>
                      </div>
                      <ToggleSwitch v-model="useNt" />
                    </div>
                    <Transition name="slide-down">
                      <div v-if="useNt" class="nt-input space-y-4">
                        <p class="nt-balance text-sm text-surface-400">{{ t('checkout.tokensBalance') }}: <strong class="text-white">{{ wallet.balance ?? 0 }} NT</strong></p>
                        <InputNumber v-model="ntAmount" :min="0" :max="wallet.balance ?? 0" :step="1" showButtons fluid
                          suffix=" NT" class="shadow-inner" />
                        <div class="nt-equiv text-amber-500 font-semibold flex items-center gap-2">
                          <i class="pi pi-info-circle" />
                          ≈ ${{ ntToUsd(ntAmount) }} {{ t('checkout.tokensEquiv') }}
                        </div>
                      </div>
                    </Transition>
                  </div>
                </DataCard>

                <div class="step-nav flex justify-end mt-6">
                  <Button :label="t('checkout.continueToPayment')" icon="pi pi-arrow-right" iconPos="right" @click="step='2'" class="shadow-lg shadow-indigo-500/20" />
                </div>
              </StepPanel>

              <!-- Payment -->
              <StepPanel value="2">
                <DataCard icon="pi-credit-card" :title="t('checkout.paymentMethod')">
                  <div class="payment-methods space-y-3">
                    <div
                      v-for="method in paymentMethods" :key="method.id"
                      class="payment-option group relative"
                      :class="{ 'selected border-brand-500 bg-brand-500/10': selectedMethod === method.id }"
                      @click="selectedMethod = method.id"
                    >
                      <div class="flex items-center gap-4 flex-1">
                        <RadioButton v-model="selectedMethod" :value="method.id" />
                        <div class="w-12 h-12 rounded-xl bg-surface-800 flex items-center justify-center border border-surface-700 group-hover:border-surface-600 transition-colors">
                          <i :class="['pi', method.icon, 'text-xl', selectedMethod === method.id ? 'text-brand-400' : 'text-surface-400']" />
                        </div>
                        <div class="method-info">
                          <p class="method-name font-bold text-base text-white">{{ method.name }}</p>
                          <p class="method-desc text-xs text-surface-400">{{ method.desc }}</p>
                        </div>
                      </div>
                      <Tag v-if="method.badge" :value="method.badge" severity="success" size="small" rounded class="absolute top-2 right-2" />
                    </div>
                  </div>
                </DataCard>

                <div class="step-nav flex justify-between mt-6">
                  <Button :label="t('common.back')" severity="secondary" text @click="step='1'" />
                  <Button :label="t('checkout.confirmAndPay')" icon="pi pi-lock" :loading="processing" :disabled="!selectedMethod" @click="doCheckout" class="shadow-lg shadow-brand-500/20" />
                </div>
              </StepPanel>

              <!-- Confirmation -->
              <StepPanel value="3">
                <div class="success-panel" v-motion-pop-visible>
                  <div class="success-icon-wrap w-24 h-24 rounded-full bg-green-500/10 flex items-center justify-center border border-green-500/20 mb-6">
                    <i class="pi pi-check-circle text-6xl text-green-500" />
                  </div>
                  <h2 class="font-display">{{ t('checkout.successTitle') }}</h2>
                  <p class="max-w-md mx-auto text-surface-400 mb-8">{{ t('checkout.successDesc') }}</p>
                  <Button :label="t('checkout.viewLicenses')" icon="pi pi-key" @click="$inertia.visit(route('licenses.index'))" size="large" class="shadow-xl shadow-green-500/20" />
                </div>
              </StepPanel>
            </StepPanels>
          </Stepper>
        </div>

        <!-- RIGHT: Summary -->
        <aside class="checkout-summary" v-motion-slide-visible-right>
          <DataCard icon="pi-calculator" :title="t('checkout.summary')" class="sticky top-24">
            <div class="sum-rows space-y-3 mb-6">
              <div class="sum-row flex justify-between text-surface-400 text-sm">
                <span>{{ t('checkout.subtotal') }}</span>
                <span class="font-semibold text-white">${{ totals.subtotal.toFixed(2) }}</span>
              </div>
              <div v-if="totals.savings > 0" class="sum-row flex justify-between text-green-400 text-sm font-semibold bg-green-500/5 p-2 rounded-lg border border-green-500/10">
                <span class="flex items-center gap-2"><i class="pi pi-tag text-xs" /> {{ t('checkout.savings') }}</span>
                <span>-${{ totals.savings.toFixed(2) }}</span>
              </div>
              <div v-if="useNt && ntAmount > 0" class="sum-row flex justify-between text-amber-500 text-sm font-semibold bg-amber-500/5 p-2 rounded-lg border border-amber-500/10">
                <span class="flex items-center gap-2"><i class="pi pi-star-fill text-xs" /> {{ t('checkout.tokensApplied') }}</span>
                <span>-${{ ntToUsd(ntAmount) }}</span>
              </div>
            </div>
            <Divider />
            <div class="sum-total flex justify-between items-center mb-6">
              <span class="font-bold text-base text-white">{{ t('checkout.total') }}</span>
              <span class="total-amount text-3xl font-black text-white font-display">${{ grandTotal }}</span>
            </div>
            <div class="trust-box space-y-3 pt-2">
              <div class="flex items-center gap-3 text-xs text-surface-400 bg-surface-900/50 p-2 rounded-lg border border-surface-800">
                <i class="pi pi-shield text-brand-400" />
                <span>{{ t('checkout.securePayment') }}</span>
              </div>
              <div class="flex items-center gap-3 text-xs text-surface-400 bg-surface-900/50 p-2 rounded-lg border border-surface-800">
                <i class="pi pi-bolt text-amber-500" />
                <span>{{ t('checkout.instantDelivery') }}</span>
              </div>
            </div>
          </DataCard>
        </aside>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useToast } from 'primevue/usetoast';
import AppLayout  from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import DataCard   from '@/Components/ui/DataCard.vue';

const { t } = useI18n();

const props = defineProps({
  cart:   { type:Array,  default:()=>[] },
  totals: { type:Object, default:()=>({ subtotal:0, savings:0 }) },
  wallet: { type:Object, default:()=>({ balance:0 }) },
});

const toast      = useToast();
const step       = ref('1');
const useNt      = ref(false);
const ntAmount   = ref(0);
const processing = ref(false);
const selectedMethod = ref(null);

const NT_RATE = 0.10;
function ntToUsd(nt){ return (nt * NT_RATE).toFixed(2); }
const grandTotal = computed(() => {
  let t = props.totals.subtotal;
  if (useNt.value && ntAmount.value > 0) t = Math.max(0, t - ntAmount.value * NT_RATE);
  return t.toFixed(2);
});

const paymentMethods = [
  { id:'paypal',      icon:'pi-paypal',   name:'PayPal',       desc: t('checkout.securePayment') + ' via PayPal.', badge: t('catalog.featured') },
  { id:'mercadopago', icon:'pi-wallet',   name:'Mercado Pago', desc:'Pago en PEN con MP.',         badge:null },
  { id:'nt',          icon:'pi-star-fill',name:'NexoTokens',   desc:'Paga 100% con tus tokens NT.',badge:null },
];

function doCheckout(){
  processing.value = true;
  router.post(route('checkout.process'), {
    payment_method: selectedMethod.value,
    nt_amount:      useNt.value ? ntAmount.value : 0,
  }, {
    onSuccess: () => { step.value = '3'; },
    onError:   (e) => { toast.add({ severity:'error', summary: Object.values(e)[0], life:4000 }); },
    onFinish:  () => { processing.value = false; },
  });
}
</script>

<style scoped>
.checkout-page { max-width:1100px; margin:0 auto; padding-bottom:3rem; }
.checkout-grid { display:grid; grid-template-columns:1fr 320px; gap:2rem; align-items:start; }
@media(max-width:960px){ .checkout-grid{ grid-template-columns:1fr; } }

.order-items { display:flex; flex-direction:column; gap:0.75rem; }
.order-item  { display:flex; align-items:center; gap:1rem; padding:1rem; background:var(--color-surface-900); border-radius:16px; border: 1px solid var(--color-surface-800); }
.item-thumb  { width:64px; height:44px; border-radius:10px; object-fit:cover; flex-shrink:0; }
.item-thumb-ph{ width:64px; height:44px; border-radius:10px; background:var(--color-surface-800); display:flex; align-items:center; justify-content:center; color:var(--color-surface-500); flex-shrink:0; }

.payment-option {
  display:flex; align-items:center; gap:1rem; padding:1.25rem; border:2px solid var(--color-surface-800); border-radius:20px; cursor:pointer; transition:all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.payment-option:hover   { border-color:var(--color-surface-600); background: rgba(255,255,255,0.02); }
.payment-option.selected{ border-color:var(--color-brand-500); transform: scale(1.02); box-shadow: 0 10px 25px rgba(0,0,0,0.2); }

.success-panel{ text-align:center; padding:4rem 2rem; display:flex; flex-direction:column; align-items:center; }
.success-panel h2{ font-size:2rem; font-weight:900; color:white; margin-bottom:1rem; }

.slide-down-enter-active,.slide-down-leave-active{ transition:all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); }
.slide-down-enter-from,.slide-down-leave-to{ opacity:0; transform:translateY(-12px) scale(0.95); }

:deep(.p-stepper-nav) { background: transparent !important; padding: 0 !important; margin-bottom: 2rem !important; }
:deep(.p-stepper-header) { background: transparent !important; }
:deep(.p-stepper-number) { font-weight: 800 !important; }
</style>
