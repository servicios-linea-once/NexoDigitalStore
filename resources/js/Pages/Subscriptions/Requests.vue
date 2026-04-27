<template>
  <AppLayout>
    <Head title="Solicitudes de Suscripción" />

    <div class="req-page">
      <!-- Header -->
      <div class="req-header">
        <div class="req-header-text">
          <h1 class="req-title">
            Gestión de <span class="req-title-accent">Suscripciones Privadas</span>
          </h1>
          <p class="req-subtitle">Control de accesos y membresías aprobadas por administración.</p>
        </div>
        <Button
          v-if="$page.props.auth.user.role === 'seller' || $page.props.auth.user.role === 'admin'"
          label="Nueva Solicitud"
          icon="pi pi-plus"
          @click="showingRequestModal = true"
          raised
        />
      </div>

      <!-- Table -->
      <div class="req-table-wrap">
        <DataTable
          :value="requests"
          stripedRows
          paginator
          :rows="10"
          responsiveLayout="stack"
          breakpoint="960px"
          size="small"
        >
          <template #empty>
            <div class="table-empty">
              <i class="pi pi-inbox table-empty-icon" />
              <h3 class="table-empty-title">No hay solicitudes registradas</h3>
              <p class="table-empty-desc">Las nuevas solicitudes aparecerán aquí una vez que se realicen.</p>
            </div>
          </template>

          <Column field="customer_email" header="Cliente" sortable>
            <template #body="{ data }">
              <div class="cell-user">
                <i class="pi pi-envelope cell-icon" />
                <span class="cell-email">{{ data.customer_email }}</span>
              </div>
            </template>
          </Column>

          <Column header="Plan" sortable sortField="plan.name">
            <template #body="{ data }">
              <Tag :value="data.plan?.name || 'N/A'" severity="info" />
            </template>
          </Column>

          <Column header="Vendedor" sortable sortField="seller.name">
            <template #body="{ data }">
              <span class="cell-muted">{{ data.seller?.name || 'Desconocido' }}</span>
            </template>
          </Column>

          <Column field="status" header="Estado" sortable>
            <template #body="{ data }">
              <Tag
                :severity="getStatusSeverity(data.status)"
                :value="getStatusLabel(data.status)"
                rounded
              />
            </template>
          </Column>

          <Column field="created_at" header="Fecha" sortable>
            <template #body="{ data }">
              <span class="cell-date">{{ formatDate(data.created_at) }}</span>
            </template>
          </Column>

          <Column header="Acciones" headerClass="col-right" bodyClass="col-right">
            <template #body="{ data }">
              <div
                v-if="$page.props.auth.user.role === 'admin' && data.status === 'pending'"
                class="cell-actions"
              >
                <Button
                  icon="pi pi-check"
                  severity="success"
                  text
                  rounded
                  v-tooltip.top="'Aprobar'"
                  @click="approveRequest(data.id)"
                  :loading="processingId === data.id"
                />
                <Button
                  icon="pi pi-times"
                  severity="danger"
                  text
                  rounded
                  v-tooltip.top="'Rechazar'"
                  @click="openRejectionModal(data.id)"
                />
              </div>
              <span v-else class="cell-done">Finalizado</span>
            </template>
          </Column>
        </DataTable>
      </div>
    </div>

    <!-- New Request Dialog -->
    <Dialog
      v-model:visible="showingRequestModal"
      header="Nueva Solicitud de Acceso"
      :modal="true"
      :breakpoints="{ '960px': '75vw', '640px': '90vw' }"
      :style="{ width: '30vw' }"
    >
      <div class="dialog-body">
        <div class="field">
          <label for="req-email" class="field-label">Correo del Cliente</label>
          <InputText
            id="req-email"
            v-model="form.customer_email"
            type="email"
            placeholder="ejemplo@cliente.com"
            fluid
            :invalid="!!form.errors.customer_email"
          />
          <Message v-if="form.errors.customer_email" severity="error" size="small" variant="simple">
            {{ form.errors.customer_email }}
          </Message>
        </div>

        <div class="field">
          <label for="req-plan" class="field-label">Seleccionar Plan</label>
          <Select
            id="req-plan"
            v-model="form.plan_id"
            :options="plans"
            optionLabel="name"
            optionValue="id"
            placeholder="Elige un plan"
            fluid
            :invalid="!!form.errors.plan_id"
          >
            <template #option="{ option }">
              <div class="plan-option">
                <span>{{ option?.name }}</span>
                <span class="plan-option-days">{{ option?.duration_days }} días</span>
              </div>
            </template>
          </Select>
          <Message v-if="form.errors.plan_id" severity="error" size="small" variant="simple">
            {{ form.errors.plan_id }}
          </Message>
        </div>

        <Message v-if="form.hasErrors" severity="error">Verifica los datos ingresados.</Message>
      </div>

      <template #footer>
        <div class="dialog-footer">
          <Button label="Cancelar" text severity="secondary" @click="showingRequestModal = false" />
          <Button label="Enviar Solicitud" icon="pi pi-send" @click="submitRequest" :loading="form.processing" />
        </div>
      </template>
    </Dialog>

    <!-- Rejection Dialog -->
    <Dialog
      v-model:visible="showingRejectionModal"
      header="Rechazar Solicitud"
      :modal="true"
      :style="{ width: '25vw' }"
    >
      <div class="dialog-body">
        <p class="dialog-desc">Indica el motivo del rechazo para que el vendedor pueda corregirlo.</p>
        <div class="field">
          <label for="rej-notes" class="field-label">Motivo (Opcional)</label>
          <Textarea id="rej-notes" v-model="rejectionNotes" rows="3" autoResize fluid />
        </div>
      </div>

      <template #footer>
        <div class="dialog-footer">
          <Button label="Cancelar" text severity="secondary" @click="showingRejectionModal = false" />
          <Button label="Confirmar Rechazo" severity="danger" @click="rejectRequest" />
        </div>
      </template>
    </Dialog>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  requests: Array,
  plans:    Array,
});

const showingRequestModal  = ref(false);
const showingRejectionModal = ref(false);
const processingId  = ref(null);
const rejectionNotes = ref('');

const form = useForm({ customer_email: '', plan_id: null });

function submitRequest() {
  form.post(route('seller.subscription-requests.store'), {
    onSuccess: () => { showingRequestModal.value = false; form.reset(); },
  });
}

function approveRequest(id) {
  processingId.value = id;
  useForm({}).post(route('admin.subscription-requests.approve', id), {
    onFinish: () => processingId.value = null,
  });
}

function openRejectionModal(id) {
  processingId.value = id;
  showingRejectionModal.value = true;
}

function rejectRequest() {
  useForm({ notes: rejectionNotes.value }).post(
    route('admin.subscription-requests.reject', processingId.value),
    {
      onSuccess: () => {
        showingRejectionModal.value = false;
        rejectionNotes.value = '';
        processingId.value = null;
      },
    }
  );
}

const severityMap = { pending: 'warn', approved: 'success', rejected: 'danger' };
const labelMap    = { pending: 'Pendiente', approved: 'Aprobado', rejected: 'Rechazado' };

function getStatusSeverity(s) { return severityMap[s] ?? 'info'; }
function getStatusLabel(s)    { return labelMap[s] ?? s; }

function formatDate(d) {
  return new Date(d).toLocaleDateString('es-PE', { day: '2-digit', month: 'short', year: 'numeric' });
}
</script>

<style scoped>
/* ── Page layout ──────────────────────────────────────────────────────────── */
.req-page  { max-width: 1200px; margin: 0 auto; }

.req-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1.75rem;
  flex-wrap: wrap;
}

.req-title {
  font-size: 1.75rem;
  font-weight: 800;
  color: var(--c-text);
  margin: 0 0 0.375rem;
  letter-spacing: -0.02em;
}
.req-title-accent { color: var(--c-primary); }
.req-subtitle { font-size: 0.9rem; color: var(--c-text-muted); margin: 0; }

/* ── Table wrapper ────────────────────────────────────────────────────────── */
.req-table-wrap {
  background: var(--c-card);
  border: 1px solid var(--c-border);
  border-radius: 14px;
  overflow: hidden;
}

/* ── Empty state ──────────────────────────────────────────────────────────── */
.table-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 1.5rem;
  text-align: center;
  gap: 0.5rem;
}
.table-empty-icon  { font-size: 3rem; color: var(--c-border); }
.table-empty-title { font-size: 1.05rem; font-weight: 600; color: var(--c-text); margin: 0.375rem 0 0; }
.table-empty-desc  { font-size: 0.85rem; color: var(--c-text-muted); margin: 0; }

/* ── Cell helpers ─────────────────────────────────────────────────────────── */
.cell-user    { display: flex; align-items: center; gap: 0.5rem; }
.cell-icon    { color: var(--c-text-muted); font-size: 0.85rem; }
.cell-email   { font-weight: 500; color: var(--c-text); font-size: 0.875rem; }
.cell-muted   { color: var(--c-text-muted); font-size: 0.875rem; }
.cell-date    { color: var(--c-text-subtle); font-size: 0.8rem; }
.cell-actions { display: flex; justify-content: flex-end; gap: 0.25rem; }
.cell-done    { font-size: 0.78rem; font-style: italic; color: var(--c-text-subtle); }
.col-right    { text-align: right !important; }

/* ── Dialogs ──────────────────────────────────────────────────────────────── */
.dialog-body {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  padding-top: 0.5rem;
}
.dialog-desc {
  font-size: 0.875rem;
  color: var(--c-text-muted);
  margin: 0;
}
.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}

/* ── Form fields ──────────────────────────────────────────────────────────── */
.field       { display: flex; flex-direction: column; gap: 0.375rem; }
.field-label { font-size: 0.8rem; font-weight: 600; color: var(--c-text-muted); }

/* ── Plan option in Select ────────────────────────────────────────────────── */
.plan-option {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
  gap: 1rem;
}
.plan-option-days { font-size: 0.8rem; color: var(--c-text-subtle); }
</style>
