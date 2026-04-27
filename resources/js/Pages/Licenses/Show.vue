<template>
  <AppLayout>
    <Head :title="`Licencia — ${license.product?.name ?? 'Nexo'}`" />

    <div class="page-wrap">
      <nav class="breadcrumb">
        <Link :href="route('licenses.index')" class="bc-link">
          <i class="pi pi-arrow-left" style="font-size:0.7rem" /> Mis Licencias
        </Link>
        <i class="pi pi-chevron-right bc-sep" />
        <span>{{ license.product?.name ?? `#${license.id}` }}</span>
      </nav>

      <div class="license-grid">
        <!-- LEFT -->
        <div class="col-main">
          <DataCard class="mb-4">
            <div class="prod-hero">
              <img v-if="license.product?.cover_image" :src="license.product.cover_image" class="prod-cover" :alt="license.product.name" />
              <div v-else class="prod-cover-ph"><i class="pi pi-box" /></div>
              <div class="prod-info">
                <h1 class="prod-title">{{ license.product?.name ?? 'Producto' }}</h1>
                <div class="prod-tags">
                  <Chip v-if="license.product?.platform" :label="license.product.platform" class="chip-xs" />
                  <Chip v-if="license.product?.region"   :label="license.product.region" class="chip-xs" />
                  <Tag :value="licenseTypeLabel(license.license_type)" severity="info" size="small" rounded />
                </div>
                <Tag :value="statusLabel(license.status)" :severity="statusSeverity(license.status)" rounded class="mt-3" />
              </div>
            </div>
          </DataCard>

          <DataCard class="mb-4">
            <template #header>
              <div class="card-head"><i class="pi pi-key" /> Clave de Activación</div>
            </template>
            <div class="key-section">
              <div class="key-value-row">
                <InputText
                  :value="revealed ? license.key_value : masked(license.key_value)"
                  readonly
                  :class="['key-input', { revealed }]"
                />
                <Button :icon="revealed ? 'pi pi-eye-slash' : 'pi pi-eye'" text @click="revealed = !revealed" v-tooltip.top="revealed?'Ocultar':'Revelar'" />
                <Button icon="pi pi-copy" text :severity="copied?'success':'secondary'" @click="copyKey" v-tooltip.top="'Copiar'" />
              </div>
              <p v-if="copied" class="copied-hint"><i class="pi pi-check" /> ¡Copiado al portapapeles!</p>
              <Message v-if="license.notes" severity="info" class="mt-3">{{ license.notes }}</Message>
            </div>
          </DataCard>

          <DataCard v-if="license.activations?.length">
            <template #header>
              <div class="card-head"><i class="pi pi-desktop" /> Activaciones ({{ license.activations.length }}/{{ license.max_activations }})</div>
            </template>
            <DataTable :value="license.activations" size="small">
              <Column header="Dispositivo" field="device_fingerprint" />
              <Column header="IP" field="ip_address" style="width:130px" />
              <Column header="Fecha" style="width:150px">
                <template #body="{ data }"><span class="date-text">{{ fmtDate(data.created_at) }}</span></template>
              </Column>
              <Column header="Estado" style="width:110px">
                <template #body="{ data }">
                  <Tag :value="data.is_revoked?'Revocada':'Activa'" :severity="data.is_revoked?'secondary':'success'" size="small" rounded />
                </template>
              </Column>
            </DataTable>
          </DataCard>
        </div>

        <!-- RIGHT -->
        <div class="col-side">
          <DataCard class="mb-4">
            <template #header>
              <div class="card-head"><i class="pi pi-info-circle" /> Detalles</div>
            </template>
            <div class="detail-list">
              <div class="detail-row">
                <span>Tipo de licencia</span><span>{{ licenseTypeLabel(license.license_type) }}</span>
              </div>
              <div class="detail-row">
                <span>Obtenida el</span><span>{{ fmtDate(license.created_at) }}</span>
              </div>
              <div v-if="license.expires_at" class="detail-row">
                <span>Vence el</span><span :class="isExpired ? 'text-danger' : ''">{{ fmtDate(license.expires_at) }}</span>
              </div>
              <div class="detail-row">
                <span>Estado</span>
                <Tag :value="statusLabel(license.status)" :severity="statusSeverity(license.status)" size="small" rounded />
              </div>
              <Divider />
              <div class="detail-row font-bold-row">
                <span>Activaciones usadas</span>
                <span>{{ license.activations_used }}/{{ license.max_activations }}</span>
              </div>
            </div>
            <ProgressBar :value="actPct" class="act-bar" :showValue="false" v-tooltip.top="`${license.activations_used}/${license.max_activations} activaciones`" />
          </DataCard>

          <DataCard v-if="license.order_item?.order_ulid">
            <template #header>
              <div class="card-head"><i class="pi pi-receipt" /> Orden de compra</div>
            </template>
            <Button
              :label="`Orden #${license.order_item.order_ulid.slice(-8).toUpperCase()}`"
              icon="pi pi-external-link"
              severity="secondary" outlined
              @click="$inertia.visit(route('orders.show', license.order_item.order_ulid))"
              fluid
            />
          </DataCard>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link }    from '@inertiajs/vue3';
import { useToast }      from 'primevue/usetoast';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataCard  from '@/Components/ui/DataCard.vue';

const props = defineProps({ license: { type: Object, required: true } });
const toast    = useToast();
const revealed = ref(false);
const copied   = ref(false);

const actPct = computed(() => props.license.max_activations
  ? Math.round(props.license.activations_used / props.license.max_activations * 100) : 0);
const isExpired = computed(() => props.license.expires_at && new Date(props.license.expires_at) < new Date());

function masked(v) {
  if (!v) return ''; const l = v.length;
  return v.slice(0, 4) + '•'.repeat(Math.max(l - 8, 4)) + v.slice(-4);
}
async function copyKey() {
  try { await navigator.clipboard.writeText(props.license.key_value); copied.value = true;
    toast.add({ severity: 'success', summary: '¡Clave copiada!', life: 2000 });
    setTimeout(() => { copied.value = false; }, 3000);
  } catch { toast.add({ severity: 'error', summary: 'Error al copiar', life: 2000 }); }
}
function fmtDate(d) { return d ? new Date(d).toLocaleDateString('es-PE', { day:'2-digit', month:'short', year:'numeric' }) : '—'; }
const statusLabel    = s => ({ active:'Activa', revoked:'Revocada', expired:'Expirada', pending:'Pendiente' }[s] ?? s);
const statusSeverity = s => ({ active:'success', revoked:'danger', expired:'secondary', pending:'warn' }[s] ?? 'secondary');
const licenseTypeLabel = t => ({ perpetual:'Permanente', subscription:'Suscripción', trial:'Trial' }[t] ?? t);
</script>

<style scoped>
.page-wrap { max-width:1000px; margin:0 auto; padding-bottom:3rem; }
.breadcrumb { display:flex; align-items:center; gap:0.4rem; font-size:0.75rem; color:var(--c-text-muted); margin-bottom:1.25rem; }
.bc-link { color:var(--c-primary); text-decoration:none; display:flex; align-items:center; gap:0.3rem; }
.bc-sep  { font-size:0.6rem; }
.license-grid { display:grid; grid-template-columns:1fr 300px; gap:1.25rem; }
@media (max-width:800px) { .license-grid { grid-template-columns:1fr; } }
.col-main, .col-side { display:flex; flex-direction:column; }
.mb-4 { margin-bottom:1rem; }
.mt-3 { margin-top:0.75rem; }
.prod-hero { display:flex; gap:1.25rem; align-items:flex-start; }
.prod-cover { width:100px; height:76px; border-radius:10px; object-fit:cover; flex-shrink:0; }
.prod-cover-ph { width:100px; height:76px; border-radius:10px; background:var(--c-card); display:flex; align-items:center; justify-content:center; color:var(--c-text-subtle); font-size:2rem; flex-shrink:0; }
.prod-info { flex:1; min-width:0; }
.prod-title { font-size:1.1rem; font-weight:800; color:var(--c-text); margin:0 0 0.5rem; }
.prod-tags { display:flex; flex-wrap:wrap; gap:0.3rem; }
:deep(.chip-xs .p-chip) { padding:0.1rem 0.4rem!important; font-size:0.65rem!important; }
.card-head { display:flex; align-items:center; gap:0.5rem; padding:0.875rem 1.125rem; font-size:0.85rem; font-weight:700; color:var(--c-text); }
.key-section { padding:0 0.25rem 0.25rem; }
.key-value-row { display:flex; align-items:center; gap:0.25rem; }
.key-input { font-family:monospace; font-size:1rem; flex:1; color:var(--c-text-muted); }
.key-input.revealed { color:var(--c-primary); font-weight:700; }
.copied-hint { font-size:0.78rem; color:var(--p-green-400); margin-top:0.5rem; display:flex; align-items:center; gap:0.3rem; }
.detail-list { display:flex; flex-direction:column; }
.detail-row { display:flex; justify-content:space-between; align-items:center; font-size:0.85rem; color:var(--c-text-muted); padding:0.5rem 0; border-bottom:1px solid var(--c-border); }
.detail-row:last-child { border-bottom:none; }
.font-bold-row span:last-child { font-weight:700; color:var(--c-text); }
.text-danger { color:var(--p-red-400)!important; }
.act-bar { height:6px!important; margin-top:0.75rem; }
.date-text { font-size:0.78rem; color:var(--c-text-muted); }
</style>
