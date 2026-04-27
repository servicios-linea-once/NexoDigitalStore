<template>
  <AppLayout>
    <Head title="Suscripciones — Nexo Digital Store" />

    <div class="page-wrap">
      <PageHeader
        title="Planes de Suscripción"
        subtitle="Elige el plan que mejor se adapta a tu actividad"
        icon="pi-crown"
        :breadcrumb="[{ label: 'Mi cuenta' }, { label: 'Suscripciones' }]"
      />

      <!-- Current subscription banner -->
      <div v-if="currentSub" class="current-banner">
        <div class="current-icon"><i class="pi pi-crown" /></div>
        <div class="current-body">
          <p class="current-label">Plan actual</p>
          <p class="current-plan">{{ currentSub.plan?.name }}</p>
          <p class="current-meta">
            <template v-if="currentSub.expires_at">Vence el {{ fmtDate(currentSub.expires_at) }}</template>
            <template v-else>Vigencia indefinida (plan gratuito)</template>
          </p>
        </div>
        <Tag value="Activo" severity="success" rounded />
      </div>

      <!-- Plans grid -->
      <div class="plans-grid">
        <div v-for="plan in plans" :key="plan.id" :class="['plan-card', { 'plan-current': isCurrentPlan(plan), 'plan-featured': plan.slug === 'pro' }]">
          <div v-if="plan.slug === 'pro'" class="featured-badge"><i class="pi pi-star-fill" /> Más popular</div>

          <div class="plan-header">
            <div class="plan-icon" :class="`icon-${plan.slug}`">
              <i :class="`pi ${planIcon(plan.slug)}`" />
            </div>
            <h3 class="plan-name">{{ plan.name }}</h3>
            <p class="plan-desc">{{ plan.description }}</p>
          </div>

          <div class="plan-price">
            <span v-if="plan.price_usd == 0" class="price-val">Gratis</span>
            <template v-else>
              <span class="price-val">${{ plan.price_usd }}</span>
              <span class="price-period">/mes</span>
            </template>
            <p v-if="plan.discount_percent > 0" class="plan-discount">
              <i class="pi pi-tag" /> {{ plan.discount_percent }}% de descuento en compras
            </p>
          </div>

          <Divider />

          <ul class="plan-features">
            <li v-for="f in getPlanFeatures(plan)" :key="f.text" :class="{ disabled: !f.active }">
              <i :class="`pi ${f.active ? 'pi-check-circle' : 'pi-times-circle'}`" />
              {{ f.text }}
            </li>
          </ul>

          <div class="plan-action">
            <Button v-if="isCurrentPlan(plan)" label="Plan actual" icon="pi pi-check" severity="success" outlined disabled fluid />
            <Button v-else-if="plan.price_usd == 0" label="Plan básico" severity="secondary" outlined fluid disabled />
            <Button v-else :label="`Suscribirse a ${plan.name}`" :severity="plan.slug === 'pro' ? undefined : 'secondary'" fluid @click="requestUpgrade(plan)" />
          </div>
        </div>
      </div>

      <!-- History -->
      <DataCard v-if="history.length" class="mt-6" :noPadding="true">
        <template #header>
          <div class="card-head"><i class="pi pi-history" /> Historial</div>
        </template>
        <DataTable :value="history" size="small">
          <Column header="Plan">
            <template #body="{ data }"><span class="cell-bold">{{ data.plan?.name }}</span></template>
          </Column>
          <Column header="Estado" style="width:110px">
            <template #body="{ data }">
              <Tag :value="subStatusLabel(data.status)" :severity="subStatusSeverity(data.status)" size="small" rounded />
            </template>
          </Column>
          <Column header="Inicio" style="width:130px">
            <template #body="{ data }"><span class="date-text">{{ fmtDate(data.starts_at) }}</span></template>
          </Column>
          <Column header="Vencimiento" style="width:130px">
            <template #body="{ data }"><span class="date-text">{{ data.expires_at ? fmtDate(data.expires_at) : '—' }}</span></template>
          </Column>
          <Column header="Pagado" style="width:90px">
            <template #body="{ data }"><span class="date-text">${{ Number(data.amount_paid).toFixed(2) }}</span></template>
          </Column>
        </DataTable>
      </DataCard>

      <!-- Upgrade dialog -->
      <Dialog v-model:visible="upgradeDialog" modal header="Solicitar plan" :style="{ width: '420px' }" :draggable="false">
        <div class="upgrade-content">
          <p>Estás solicitando el plan <strong>{{ upgradeTarget?.name }}</strong> por <strong>${{ upgradeTarget?.price_usd }}/mes</strong>.</p>
          <p class="upgrade-note">Un administrador revisará tu solicitud y te activará el plan.</p>
        </div>
        <template #footer>
          <Button label="Cancelar" severity="secondary" text @click="upgradeDialog = false" />
          <Button label="Enviar solicitud" icon="pi pi-send" :loading="requesting" @click="submitRequest" />
        </template>
      </Dialog>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useToast }     from 'primevue/usetoast';
import AppLayout  from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import DataCard   from '@/Components/ui/DataCard.vue';

const props = defineProps({
  plans:      { type: Array,  default: () => [] },
  currentSub: { type: Object, default: null },
  history:    { type: Array,  default: () => [] },
});

const toast = useToast();
const upgradeDialog = ref(false);
const upgradeTarget = ref(null);
const requesting    = ref(false);

const isCurrentPlan = (plan) => props.currentSub?.plan_id === plan.id && props.currentSub?.status === 'active';
const planIcon = (slug) => ({ free:'pi-gift', pro:'pi-crown', business:'pi-building' }[slug] ?? 'pi-star');
const getPlanFeatures = (plan) => [
  { text: 'Acceso al catálogo completo',      active: true },
  { text: 'Claves digitales instantáneas',    active: true },
  { text: `${plan.discount_percent > 0 ? plan.discount_percent + '%' : 'Sin'} descuento en compras`, active: plan.discount_percent > 0 },
  { text: 'Soporte prioritario',              active: plan.slug !== 'free' },
  { text: 'Acceso anticipado a productos',    active: plan.slug === 'business' },
  { text: 'Comisión de vendedor reducida',    active: plan.slug === 'business' },
];

function requestUpgrade(plan) { upgradeTarget.value = plan; upgradeDialog.value = true; }
function submitRequest() {
  requesting.value = true;
  router.post(route('subscription-requests.store'), { plan_id: upgradeTarget.value.id }, {
    onSuccess: () => { upgradeDialog.value = false; toast.add({ severity: 'success', summary: 'Solicitud enviada', life: 4000 }); },
    onError:   () => toast.add({ severity: 'error', summary: 'Error al enviar', life: 3000 }),
    onFinish:  () => { requesting.value = false; },
  });
}

function fmtDate(d) { return d ? new Date(d).toLocaleDateString('es-PE', { day:'2-digit', month:'short', year:'numeric' }) : '—'; }
const subStatusLabel    = s => ({ active:'Activo', cancelled:'Cancelado', expired:'Expirado', pending:'Pendiente' }[s] ?? s);
const subStatusSeverity = s => ({ active:'success', cancelled:'secondary', expired:'danger', pending:'warn' }[s] ?? 'secondary');
</script>

<style scoped>
.page-wrap { max-width:1100px; margin:0 auto; padding-bottom:3rem; }
.current-banner {
  display:flex; align-items:center; gap:1.25rem;
  background:linear-gradient(135deg,rgba(99,102,241,0.1),rgba(139,92,246,0.08));
  border:1px solid rgba(99,102,241,0.25); border-radius:16px; padding:1.25rem 1.5rem; margin-bottom:2rem;
}
.current-icon { width:48px; height:48px; border-radius:14px; background:var(--c-primary-muted); color:var(--c-primary); display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0; }
.current-body { flex:1; }
.current-label { font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:var(--c-primary); margin:0; }
.current-plan  { font-size:1.1rem; font-weight:800; color:var(--c-text); margin:0.1rem 0; }
.current-meta  { font-size:0.78rem; color:var(--c-text-muted); margin:0; }
.plans-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:1.25rem; margin-bottom:2rem; }
.plan-card {
  background:var(--c-surface); border:1px solid var(--c-border); border-radius:20px;
  padding:1.75rem; display:flex; flex-direction:column; gap:1rem; position:relative;
  overflow:hidden; transition:transform 0.2s,box-shadow 0.2s;
}
.plan-card:hover { transform:translateY(-3px); box-shadow:0 12px 40px rgba(0,0,0,0.3); }
.plan-current  { border-color:var(--c-primary); box-shadow:0 0 0 1px var(--c-primary); }
.plan-featured { border-color:var(--c-primary); background:linear-gradient(160deg,var(--c-surface),rgba(99,102,241,0.05)); box-shadow:0 0 0 1px var(--c-primary),0 8px 32px rgba(99,102,241,0.15); }
.featured-badge { position:absolute; top:0; right:0; background:var(--c-primary); color:#fff; font-size:0.65rem; font-weight:700; padding:0.3rem 0.75rem; border-radius:0 20px 0 12px; display:flex; align-items:center; gap:0.3rem; }
.plan-header { display:flex; flex-direction:column; gap:0.5rem; }
.plan-icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; }
.icon-free     { background:rgba(16,185,129,0.1); color:#10b981; }
.icon-pro      { background:rgba(99,102,241,0.1); color:var(--c-primary); }
.icon-business { background:rgba(251,191,36,0.1); color:#f59e0b; }
.plan-name { font-size:1.15rem; font-weight:800; color:var(--c-text); margin:0; }
.plan-desc { font-size:0.8rem; color:var(--c-text-muted); margin:0; line-height:1.4; }
.plan-price  { display:flex; flex-wrap:wrap; align-items:baseline; gap:0.25rem; }
.price-val   { font-size:2rem; font-weight:900; color:var(--c-text); }
.price-period{ font-size:0.85rem; color:var(--c-text-muted); }
.plan-discount { font-size:0.75rem; color:var(--p-green-400); margin:0.25rem 0 0; width:100%; display:flex; align-items:center; gap:0.3rem; }
.plan-features { list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:0.625rem; flex:1; }
.plan-features li { display:flex; align-items:center; gap:0.6rem; font-size:0.83rem; color:var(--c-text-muted); }
.plan-features li .pi-check-circle { color:var(--p-green-400); }
.plan-features li .pi-times-circle { color:var(--c-border); }
.plan-features li.disabled { opacity:0.45; }
.plan-action { margin-top:auto; }
.card-head { display:flex; align-items:center; gap:0.5rem; padding:0.875rem 1.125rem; font-size:0.85rem; font-weight:700; color:var(--c-text); }
.cell-bold  { font-size:0.85rem; font-weight:600; color:var(--c-text); }
.date-text  { font-size:0.78rem; color:var(--c-text-muted); }
.mt-6 { margin-top:1.5rem; }
.upgrade-content { display:flex; flex-direction:column; gap:0.75rem; font-size:0.9rem; color:var(--c-text); }
.upgrade-note { font-size:0.8rem; color:var(--c-text-muted); background:var(--c-card); padding:0.75rem; border-radius:8px; }
</style>
