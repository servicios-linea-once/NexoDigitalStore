<!-- resources/js/Pages/Profile/Partials/ProfileInfo.vue -->
<template>
  <div class="tab-pane">
    <div class="pane-grid">
      <!-- Información personal -->
      <section class="pcard pcard--span2">
        <div class="pcard-header">
          <span class="pcard-icon"><i class="pi pi-user-edit" /></span>
          <div>
            <h2 class="pcard-title">Información personal</h2>
            <p class="pcard-sub">Actualiza tu nombre, email y usuario</p>
          </div>
        </div>
        <form @submit.prevent="$emit('save')" class="profile-form">
          <div class="field-group">
            <div class="field">
              <label class="field-label">Nombre completo</label>
              <div class="input-wrap">
                <i class="pi pi-user input-icon" />
                <InputText v-model="form.name" :invalid="!!errors.name" class="with-icon" fluid />
              </div>
              <Message v-if="errors.name" severity="error" size="small" variant="simple">{{ errors.name }}</Message>
            </div>
            <div class="field">
              <label class="field-label">Email</label>
              <div class="input-wrap">
                <i class="pi pi-envelope input-icon" />
                <InputText v-model="form.email" type="email" :invalid="!!errors.email" class="with-icon" fluid />
              </div>
              <Message v-if="errors.email" severity="error" size="small" variant="simple">{{ errors.email }}</Message>
            </div>
          </div>
          <div class="field">
            <label class="field-label">Nombre de usuario</label>
            <InputGroup>
              <InputGroupAddon><i class="pi pi-at" /></InputGroupAddon>
              <InputText v-model="form.username" :invalid="!!errors.username" fluid />
            </InputGroup>
            <Message v-if="errors.username" severity="error" size="small" variant="simple">{{ errors.username }}</Message>
          </div>
          <div class="form-actions">
            <Button label="Guardar cambios" icon="pi pi-check" type="submit" :loading="saving" class="save-btn" />
          </div>
        </form>
      </section>

      <!-- Telegram -->
      <section class="pcard">
        <div class="pcard-header">
          <span class="pcard-icon tg-color"><i class="pi pi-telegram" /></span>
          <div>
            <h2 class="pcard-title">Vincular Telegram</h2>
            <p class="pcard-sub">Recibe notificaciones automáticas</p>
          </div>
        </div>
        <div class="tg-body">
          <div class="tg-illustration">
            <div class="tg-bubble tg-bubble--1"><i class="pi pi-bell" /></div>
            <div class="tg-circle"><i class="pi pi-telegram" /></div>
            <div class="tg-bubble tg-bubble--2"><i class="pi pi-check-circle" /></div>
          </div>
          <p class="tg-desc">Vincula tu cuenta de Telegram para recibir notificaciones de pedidos y entregas en tiempo real.</p>
          <div v-if="linkedTelegram" class="tg-linked-badge">
            <i class="pi pi-check-circle" />
            <span>Vinculado como @{{ linkedTelegram }}</span>
          </div>
          <template v-else>
            <Button label="Generar enlace" icon="pi pi-external-link" class="tg-btn" @click="$emit('generateTg')" :loading="generatingTg" />
            <Transition name="slide-fade">
              <div v-if="tgLink" class="tg-link-box">
                <code class="tg-code">{{ tgLink }}</code>
                <Button icon="pi pi-copy" size="small" text rounded @click="$emit('copyTg')" />
              </div>
            </Transition>
          </template>
        </div>
      </section>

      <!-- Suscripción activa -->
      <section class="pcard" v-if="subscription">
        <div class="pcard-header">
          <span class="pcard-icon gold-icon"><i class="pi pi-star-fill" /></span>
          <div>
            <h2 class="pcard-title">Plan activo</h2>
            <p class="pcard-sub">Tu suscripción actual</p>
          </div>
        </div>
        <div class="sub-body">
          <div class="sub-plan-name">{{ subscription.plan }}</div>
          <div class="sub-discount" v-if="subscription.discount_percent > 0">
            <i class="pi pi-tag" /> {{ subscription.discount_percent }}% de descuento en compras
          </div>
          <div class="sub-expiry" v-if="!subscription.is_lifetime">
            <i class="pi pi-clock" /> Vence en {{ subscription.days_remaining }} días
          </div>
          <div class="sub-expiry sub-lifetime" v-else>
            <i class="pi pi-infinity" /> Plan de por vida
          </div>
        </div>
      </section>

      <!-- Zona de peligro -->
      <section class="pcard danger-pcard pcard--span2">
        <div class="pcard-header">
          <span class="pcard-icon danger-icon"><i class="pi pi-exclamation-triangle" /></span>
          <div>
            <h2 class="pcard-title">Zona de peligro</h2>
            <p class="pcard-sub">Acciones irreversibles</p>
          </div>
        </div>
        <div class="danger-body">
          <p class="danger-desc">
            Eliminar tu cuenta es una acción <strong>permanente e irreversible</strong>.
            Todos tus datos, pedidos y licencias serán eliminados sin posibilidad de recuperación.
          </p>
          <Button label="Eliminar cuenta" icon="pi pi-trash" severity="danger" outlined @click="$emit('delete')" class="delete-btn" />
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
defineProps({
  form:           Object,
  errors:         Object,
  saving:         Boolean,
  linkedTelegram: String,
  generatingTg:   Boolean,
  tgLink:         String,
  subscription:   Object,
});
defineEmits(['save', 'generateTg', 'copyTg', 'delete']);
</script>

<style scoped>
.tab-pane { display: flex; flex-direction: column; gap: 1.25rem; }
.pane-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
.pcard--span2 { grid-column: 1 / -1; }

.pcard {
  border-radius: 14px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.07); padding: 1.25rem; transition: border-color 0.2s, box-shadow 0.2s;
}
.pcard:hover { border-color: rgba(139,92,246,0.2); box-shadow: 0 4px 24px rgba(139,92,246,0.06); }
.pcard-header { display: flex; align-items: flex-start; gap: 0.85rem; margin-bottom: 1.25rem; }
.pcard-icon { width: 38px; height: 38px; border-radius: 10px; background: rgba(139,92,246,0.12); border: 1px solid rgba(139,92,246,0.2); display: flex; align-items: center; justify-content: center; color: #a78bfa; font-size: 0.95rem; flex-shrink: 0; }
.tg-color   { background: rgba(34,158,217,0.12) !important; border-color: rgba(34,158,217,0.2) !important; color: #38bdf8 !important; }
.gold-icon  { background: rgba(245,158,11,0.12) !important; border-color: rgba(245,158,11,0.2) !important; color: #f59e0b !important; }
.danger-icon { background: rgba(239,68,68,0.12) !important; border-color: rgba(239,68,68,0.2) !important; color: #f87171 !important; }

.pcard-title { margin: 0 0 0.15rem; font-size: 0.9rem; font-weight: 700; color: var(--p-text-color, #e2e8f0); }
.pcard-sub   { margin: 0; font-size: 0.75rem; color: rgba(255,255,255,0.38); }

.profile-form { display: flex; flex-direction: column; gap: 1rem; }
.field-group { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.field { display: flex; flex-direction: column; gap: 0.35rem; }
.field-label { font-size: 0.74rem; font-weight: 600; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 0.04em; }
.input-wrap { position: relative; }
.input-icon { position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); font-size: 0.85rem; color: rgba(255,255,255,0.3); pointer-events: none; z-index: 1; }
.input-wrap :deep(.p-inputtext.with-icon) { padding-left: 2.2rem; }
.form-actions { display: flex; justify-content: flex-end; padding-top: 0.25rem; }
.save-btn { background: linear-gradient(135deg, #7c3aed, #4f46e5) !important; border: none !important; padding: 0.55rem 1.4rem !important; border-radius: 10px !important; font-weight: 600 !important; }

.tg-body { display: flex; flex-direction: column; align-items: center; text-align: center; gap: 0.875rem; }
.tg-illustration { position: relative; display: flex; align-items: center; justify-content: center; height: 68px; }
.tg-circle { width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg, #0088cc, #229ed9); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.4rem; box-shadow: 0 0 20px rgba(34,158,217,0.35); animation: tg-pulse 3s ease-in-out infinite; }
@keyframes tg-pulse { 0%,100% { box-shadow: 0 0 20px rgba(34,158,217,0.35); } 50% { box-shadow: 0 0 36px rgba(34,158,217,0.6); } }
.tg-bubble { position: absolute; width: 30px; height: 30px; border-radius: 50%; background: rgba(34,158,217,0.1); border: 1px solid rgba(34,158,217,0.22); display: flex; align-items: center; justify-content: center; color: #38bdf8; font-size: 0.75rem; }
.tg-bubble--1 { left: 10px; top: 4px; animation: float 4s ease-in-out infinite; }
.tg-bubble--2 { right: 10px; bottom: 4px; animation: float 4s ease-in-out infinite 1s; }
@keyframes float { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-5px); } }
.tg-desc { font-size: 0.8rem; color: rgba(255,255,255,0.4); line-height: 1.6; margin: 0; max-width: 240px; }
.tg-linked-badge { display: flex; align-items: center; gap: 0.5rem; padding: 0.55rem 1rem; border-radius: 100px; background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.22); color: #4ade80; font-size: 0.8rem; font-weight: 600; }
.tg-btn { background: linear-gradient(135deg, #0088cc, #229ed9) !important; border: none !important; border-radius: 10px !important; font-weight: 600 !important; color: #fff !important; width: 100%; }
.tg-link-box { display: flex; align-items: center; gap: 0.5rem; background: rgba(0,0,0,0.2); padding: 0.5rem 0.7rem; border-radius: 10px; border: 1px solid rgba(255,255,255,0.07); width: 100%; }
.tg-code { flex: 1; font-size: 0.7rem; color: rgba(255,255,255,0.45); word-break: break-all; text-align: left; }
.slide-fade-enter-active { transition: all 0.3s ease; }
.slide-fade-leave-active { transition: all 0.2s ease; }
.slide-fade-enter-from, .slide-fade-leave-to { opacity: 0; transform: translateY(-6px); }

.sub-body { display: flex; flex-direction: column; gap: 0.75rem; }
.sub-plan-name { font-size: 1.2rem; font-weight: 800; color: #f59e0b; }
.sub-discount, .sub-expiry { display: flex; align-items: center; gap: 0.5rem; font-size: 0.82rem; color: rgba(255,255,255,0.5); }
.sub-discount .pi { color: #a78bfa; }
.sub-expiry .pi   { color: rgba(255,255,255,0.35); }
.sub-lifetime     { color: #10b981; }
.sub-lifetime .pi { color: #10b981; }

.danger-pcard { border-color: rgba(239,68,68,0.15); background: linear-gradient(135deg, rgba(127,29,29,0.05), rgba(19,13,13,0.3)); }
.danger-pcard:hover { border-color: rgba(239,68,68,0.28) !important; }
.danger-body { display: flex; align-items: center; justify-content: space-between; gap: 2rem; flex-wrap: wrap; }
.danger-desc { margin: 0; font-size: 0.82rem; color: rgba(255,255,255,0.38); line-height: 1.6; flex: 1; }
.danger-desc strong { color: rgba(248,113,113,0.75); }
.delete-btn { border-radius: 10px !important; font-weight: 600 !important; white-space: nowrap; flex-shrink: 0; }

@media (max-width: 768px) {
  .pane-grid { grid-template-columns: 1fr; }
  .pcard--span2 { grid-column: 1; }
  .field-group { grid-template-columns: 1fr; }
  .danger-body { flex-direction: column; align-items: flex-start; gap: 1rem; }
}
</style>
