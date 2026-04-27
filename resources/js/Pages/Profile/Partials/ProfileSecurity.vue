<!-- resources/js/Pages/Profile/Partials/ProfileSecurity.vue -->
<template>
  <div class="tab-pane">
    <div class="pane-grid">
      <!-- Cambiar contraseña -->
      <section class="pcard">
        <div class="pcard-header">
          <span class="pcard-icon"><i class="pi pi-lock" /></span>
          <div>
            <h2 class="pcard-title">Cambiar contraseña</h2>
            <p class="pcard-sub">Actualiza tu contraseña de acceso</p>
          </div>
        </div>
        <form @submit.prevent="$emit('savePassword')" class="profile-form">
          <div class="field">
            <label class="field-label">Contraseña actual</label>
            <Password v-model="passwordForm.current_password" :feedback="false" toggleMask fluid
              :invalid="!!passwordErrors.current_password" placeholder="Tu contraseña actual" />
            <Message v-if="passwordErrors.current_password" severity="error" size="small" variant="simple">{{ passwordErrors.current_password }}</Message>
          </div>
          <div class="field">
            <label class="field-label">Nueva contraseña</label>
            <Password v-model="passwordForm.password" :feedback="true" toggleMask fluid
              :invalid="!!passwordErrors.password"
              promptLabel="Crea una contraseña segura"
              weakLabel="Débil" mediumLabel="Media" strongLabel="Fuerte" />
            <Message v-if="passwordErrors.password" severity="error" size="small" variant="simple">{{ passwordErrors.password }}</Message>
          </div>
          <div class="field">
            <label class="field-label">Confirmar nueva contraseña</label>
            <Password v-model="passwordForm.password_confirmation" :feedback="false" toggleMask fluid placeholder="Repite la nueva contraseña" />
          </div>
          <div class="form-actions">
            <Button label="Actualizar contraseña" icon="pi pi-check" :loading="savingPassword" type="submit" class="save-btn" />
          </div>
        </form>
      </section>

      <!-- 2FA -->
      <section class="pcard">
        <div class="pcard-header">
          <span class="pcard-icon" :class="twofa.enabled ? 'icon-green' : ''"><i class="pi pi-mobile" /></span>
          <div>
            <h2 class="pcard-title">Autenticación de dos factores</h2>
            <p class="pcard-sub">Protege tu cuenta con 2FA</p>
          </div>
        </div>
        <div class="twofa-section">
          <div class="twofa-status-row">
            <div class="twofa-status-icon" :class="twofa.enabled ? 'twofa-on' : 'twofa-off'">
              <i :class="`pi ${twofa.enabled ? 'pi-check-circle' : 'pi-times-circle'}`" />
            </div>
            <div class="twofa-status-text">
              <p class="twofa-label">{{ twofa.enabled ? '2FA Activado' : '2FA Desactivado' }}</p>
              <p class="twofa-sub">{{ twofa.enabled ? 'Tu cuenta está protegida.' : 'Agrega una capa extra de seguridad.' }}</p>
            </div>
            <Button v-if="!twofa.enabled && !twofa.qrCode" label="Activar" icon="pi pi-lock" size="small" :loading="loadingTwoFA" @click="$emit('start2fa')" class="ml-auto" />
            <Button v-if="twofa.enabled" label="Desactivar" icon="pi pi-lock-open" severity="danger" outlined size="small" @click="$emit('showDisable2fa')" class="ml-auto" />
          </div>

          <template v-if="twofa.qrCode">
            <Divider />
            <div class="qr-section">
              <p class="qr-label">Escanea con Google Authenticator, Authy, etc.</p>
              <div class="qr-wrap" v-html="twofa.qrCode" />
              <p class="qr-label">O ingresa el código manualmente:</p>
              <InputText :value="twofa.secret" readonly class="secret-input" />
              <div v-if="twofa.recoveryCodes?.length" class="recovery-box">
                <p class="qr-label"><strong>Guarda estos códigos de recuperación:</strong></p>
                <div class="recovery-grid">
                  <code v-for="code in twofa.recoveryCodes" :key="code">{{ code }}</code>
                </div>
              </div>
              <div class="field">
                <label class="field-label">Código OTP para confirmar</label>
                <InputOtp v-model="twofa.otp" :length="6" />
              </div>
              <Button label="Confirmar activación" icon="pi pi-check" :loading="confirmingTwoFA" :disabled="twofa.otp.length !== 6" @click="$emit('confirm2fa')" />
            </div>
          </template>
        </div>
      </section>

      <!-- Historial de sesiones -->
      <section class="pcard pcard--span2">
        <div class="pcard-header">
          <span class="pcard-icon"><i class="pi pi-desktop" /></span>
          <div>
            <h2 class="pcard-title">Historial de sesiones</h2>
            <p class="pcard-sub">Actividad reciente de tu cuenta</p>
          </div>
          <Button label="Cerrar otras sesiones" icon="pi pi-sign-out" severity="danger" outlined size="small" class="ml-auto" @click="$emit('showRevoke')" />
        </div>
        <div class="sessions-list">
          <div v-for="session in sessions" :key="session.id" class="session-row">
            <div class="session-icon" :class="eventClass(session.event)">
              <i :class="`pi ${session.is_mobile ? 'pi-mobile' : 'pi-desktop'}`" />
            </div>
            <div class="session-info">
              <p class="session-agent">{{ eventLabel(session.event) }} · <span class="session-ua">{{ shortUA(session.user_agent) }}</span></p>
              <p class="session-meta">{{ session.ip_address || 'IP desconocida' }} · {{ fmtDate(session.last_activity) }}</p>
            </div>
            <Tag v-if="session.provider" :value="session.provider" severity="info" size="small" rounded />
          </div>
          <div v-if="!sessions.length" class="empty-sessions">
            <i class="pi pi-desktop" />
            <p>No hay eventos de sesión registrados aún.</p>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
defineProps({
  passwordForm:    Object,
  passwordErrors:  Object,
  savingPassword:  Boolean,
  twofa:           Object,
  loadingTwoFA:    Boolean,
  confirmingTwoFA: Boolean,
  sessions:        Array,
});

defineEmits(['savePassword', 'start2fa', 'showDisable2fa', 'confirm2fa', 'showRevoke']);

function eventLabel(evt) {
  return { login_success: 'Inicio de sesión', login_failed: 'Intento fallido', logout: 'Cierre de sesión', registered_oauth: 'Registro (OAuth)', sessions_revoked: 'Sesiones revocadas' }[evt] ?? evt;
}
function eventClass(evt) {
  if (evt === 'login_failed') return 'evt-failed';
  if (evt === 'logout' || evt === 'sessions_revoked') return 'evt-neutral';
  return 'evt-ok';
}
function shortUA(ua) {
  if (!ua) return 'Agente desconocido';
  if (/Chrome/.test(ua))  return 'Chrome';
  if (/Firefox/.test(ua)) return 'Firefox';
  if (/Safari/.test(ua))  return 'Safari';
  if (/Edge/.test(ua))    return 'Edge';
  return ua.slice(0, 40);
}
function fmtDate(d) {
  if (!d) return '—';
  const ts = typeof d === 'number' ? d * 1000 : d;
  return new Date(ts).toLocaleString('es-PE', { dateStyle: 'short', timeStyle: 'short' });
}
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
.pcard-title { margin: 0 0 0.15rem; font-size: 0.9rem; font-weight: 700; color: var(--p-text-color, #e2e8f0); }
.pcard-sub   { margin: 0; font-size: 0.75rem; color: rgba(255,255,255,0.38); }
.ml-auto { margin-left: auto; }

.profile-form { display: flex; flex-direction: column; gap: 1rem; }
.field { display: flex; flex-direction: column; gap: 0.35rem; }
.field-label { font-size: 0.74rem; font-weight: 600; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 0.04em; }
.form-actions { display: flex; justify-content: flex-end; padding-top: 0.25rem; }
.save-btn { background: linear-gradient(135deg, #7c3aed, #4f46e5) !important; border: none !important; padding: 0.55rem 1.4rem !important; border-radius: 10px !important; font-weight: 600 !important; }

.twofa-section { display: flex; flex-direction: column; gap: 0.875rem; }
.twofa-status-row { display: flex; align-items: center; gap: 0.875rem; }
.twofa-status-icon { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; }
.twofa-on  { background: rgba(16,185,129,0.1); color: #10b981; }
.twofa-off { background: rgba(239,68,68,0.1);  color: #ef4444; }
.twofa-status-text { flex: 1; }
.twofa-label { font-size: 0.88rem; font-weight: 700; color: var(--p-text-color, #e2e8f0); margin: 0; }
.twofa-sub   { font-size: 0.76rem; color: rgba(255,255,255,0.38); margin: 0; }
.icon-green  { background: rgba(16,185,129,0.12) !important; border-color: rgba(16,185,129,0.2) !important; color: #10b981 !important; }

.qr-section  { display: flex; flex-direction: column; gap: 0.625rem; }
.qr-label    { font-size: 0.78rem; color: rgba(255,255,255,0.45); margin: 0; }
.qr-wrap     { display: flex; justify-content: center; padding: 1rem; background: white; border-radius: 12px; width: fit-content; }
.secret-input { font-family: monospace; font-size: 0.82rem; width: 100%; }
.recovery-box { background: rgba(0,0,0,0.2); border-radius: 10px; padding: 0.875rem; display: flex; flex-direction: column; gap: 0.5rem; }
.recovery-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.35rem; }
.recovery-grid code { font-family: monospace; font-size: 0.76rem; background: rgba(255,255,255,0.05); padding: 0.3rem 0.5rem; border-radius: 6px; text-align: center; }

.sessions-list { display: flex; flex-direction: column; gap: 0.625rem; }
.session-row   { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 10px; }
.session-icon  { width: 34px; height: 34px; border-radius: 9px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.evt-ok      { background: rgba(139,92,246,0.1); color: #a78bfa; }
.evt-failed  { background: rgba(239,68,68,0.1);  color: #ef4444; }
.evt-neutral { background: rgba(100,116,139,0.1); color: #64748b; }
.session-info  { flex: 1; min-width: 0; }
.session-agent { font-size: 0.8rem; font-weight: 600; color: var(--p-text-color, #e2e8f0); margin: 0; }
.session-ua    { font-weight: 500; color: rgba(255,255,255,0.38); }
.session-meta  { font-size: 0.7rem; color: rgba(255,255,255,0.35); margin: 0.1rem 0 0; }
.empty-sessions { display: flex; flex-direction: column; align-items: center; gap: 0.5rem; padding: 2rem; color: rgba(255,255,255,0.3); font-size: 0.82rem; }
.empty-sessions .pi { font-size: 1.5rem; }

@media (max-width: 768px) {
  .pane-grid { grid-template-columns: 1fr; }
  .pcard--span2 { grid-column: 1; }
}
</style>
