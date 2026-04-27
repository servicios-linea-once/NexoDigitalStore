<!-- resources/js/Pages/Profile/Index.vue -->
<template>
  <AppLayout>
    <Head :title="t('profile.title') + ' — Nexo Digital Store'" />

    <div class="profile-root">
      <!-- ══ HERO BANNER ══════════════════════════════════════════════ -->
      <div class="hero-banner" v-motion-fade-visible-once>
        <div class="hero-glow hero-glow--a" />
        <div class="hero-glow hero-glow--b" />
        <div class="hero-inner relative z-10">
          <div class="hero-avatar-wrap group" v-motion-pop-visible>
            <div class="avatar-ring p-1 rounded-full bg-gradient-to-tr from-brand-600 to-indigo-400">
              <Avatar :image="user.avatar" :label="initials" shape="circle" size="xlarge" class="hero-avatar shadow-2xl" />
            </div>
            <input type="file" ref="avatarInput" class="hidden" accept="image/*" @change="uploadAvatar" />
            <button class="change-photo-btn shadow-lg hover:scale-110 transition-transform" @click="$refs.avatarInput.click()" :disabled="uploadingAvatar">
              <i :class="['pi', uploadingAvatar ? 'pi-spin pi-spinner' : 'pi-camera']" />
            </button>
          </div>

          <div class="hero-info" v-motion-slide-visible-right>
            <div class="hero-name-row flex items-center gap-4 mb-2">
              <h1 class="hero-name text-3xl font-black text-white font-display">{{ user.name }}</h1>
              <Tag :value="user.role" :severity="roleSev(user.role)" rounded class="hero-tag shadow-md" />
            </div>
            <p class="hero-email text-surface-400 flex items-center gap-2 mb-6"><i class="pi pi-envelope text-brand-400" /> {{ user.email }}</p>
            <div class="hero-stats flex flex-wrap gap-3">
              <div class="stat-pill glass" v-tooltip.bottom="t('profile.personalInfo')">
                <i class="pi pi-dollar text-green-400" /><span>{{ t('checkout.total') }}</span><strong>${{ stats?.totalSpent?.toLocaleString() ?? 0 }}</strong>
              </div>
              <div class="stat-pill glass" v-tooltip.bottom="t('nav.orders')">
                <i class="pi pi-shopping-bag text-brand-400" /><span>{{ t('nav.orders') }}</span><strong>{{ stats?.totalOrders ?? 0 }}</strong>
              </div>
              <div class="stat-pill glass" v-tooltip.bottom="`Miembro desde ${memberSince}`">
                <i class="pi pi-calendar text-indigo-400" /><span>Antigüedad</span><strong>{{ memberDuration }}</strong>
              </div>
              <div v-if="subscription" class="stat-pill stat-pill--gold glass" v-tooltip.bottom="t('nav.subscriptions')">
                <i class="pi pi-star-fill text-amber-500" /><span>{{ subscription.plan }}</span>
                <strong v-if="!subscription.is_lifetime">{{ subscription.days_remaining }}d</strong>
                <strong v-else>∞</strong>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ══ TABS ═════════════════════════════════════════════════════ -->
      <div class="tabs-container" v-motion-fade-visible-once>
        <div class="tab-header glass shadow-lg">
          <button v-for="tab in tabs" :key="tab.key" class="tab-btn group" :class="{ 'tab-btn--active': activeTab === tab.key }" @click="setTab(tab.key)">
            <i :class="['pi', tab.icon, 'group-hover:scale-110 transition-transform']" />
            <span>{{ t(`nav.${tab.key_nav}`) }}</span>
          </button>
        </div>

        <div class="tab-content glass-dark border-t-0 shadow-2xl">
          <Transition name="tab-fade" mode="out-in">
            <component
              :is="activeTabComponent"
              v-bind="tabProps"
              @save="saveProfile"
              @generateTg="generateTgLink"
              @copyTg="copyTgLink"
              @delete="confirmDelete"
              @savePassword="savePassword"
              @start2fa="startEnable"
              @confirm2fa="confirmTwoFA"
              @showDisable2fa="showDisableDialog = true"
              @showRevoke="showRevokeDialog = true"
              @page="goPage"
              v-model:filterType="filterType"
            />
          </Transition>
        </div>
      </div>
    </div>

    <!-- ── Diálogos Globales ────────────────────────────────────────── -->
    <Dialog v-model:visible="showRevokeDialog" modal :header="t('common.confirm')" :style="{width:'420px'}" :draggable="false" class="glass-dialog">
      <div class="dialog-body py-4">
        <p class="dialog-text text-surface-300 mb-6">Por seguridad, ingresa tu contraseña para cerrar todas las otras sesiones activas.</p>
        <div class="field">
          <label class="field-label mb-2 block font-bold text-xs uppercase text-surface-500 tracking-wider">Contraseña actual</label>
          <Password v-model="revokePassword" :feedback="false" toggleMask fluid :invalid="!!revokeError" class="shadow-inner" />
          <Message v-if="revokeError" severity="error" size="small" variant="simple">{{ revokeError }}</Message>
        </div>
      </div>
      <template #footer>
        <Button :label="t('common.cancel')" severity="secondary" text @click="showRevokeDialog = false" />
        <Button :label="t('nav.logout')" icon="pi pi-sign-out" severity="danger" :loading="revokingSessions" @click="revokeOtherSessions" class="shadow-lg shadow-red-500/20" />
      </template>
    </Dialog>

    <Dialog v-model:visible="showDisableDialog" modal header="Desactivar 2FA" :style="{width:'420px'}" :draggable="false" class="glass-dialog">
      <div class="dialog-body py-4">
        <p class="dialog-text text-surface-300 mb-6">Para desactivar 2FA ingresa tu contraseña actual.</p>
        <div class="field">
          <label class="field-label mb-2 block font-bold text-xs uppercase text-surface-500 tracking-wider">Contraseña actual</label>
          <Password v-model="disablePassword" :feedback="false" toggleMask fluid :invalid="!!disableError" class="shadow-inner" />
          <Message v-if="disableError" severity="error" size="small" variant="simple">{{ disableError }}</Message>
        </div>
      </div>
      <template #footer>
        <Button :label="t('common.cancel')" severity="secondary" text @click="showDisableDialog = false" />
        <Button label="Desactivar" icon="pi pi-lock-open" severity="danger" :loading="disablingTwoFA" @click="disableTwoFA" class="shadow-lg shadow-red-500/20" />
      </template>
    </Dialog>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { Head, router, usePage }  from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useConfirm }             from 'primevue/useconfirm';
import { useToast }               from 'primevue/usetoast';
import AppLayout from '@/Layouts/AppLayout.vue';

const { t } = useI18n();

// Partials
import ProfileInfo     from './Partials/ProfileInfo.vue';
import ProfileOrders   from './Partials/ProfileOrders.vue';
import ProfileSecurity from './Partials/ProfileSecurity.vue';
import ProfileWallet   from './Partials/ProfileWallet.vue';

const page    = usePage();
const confirm = useConfirm();
const toast   = useToast();

const user = computed(() => page.props.auth.user);

const props = defineProps({
  stats: Object, subscription: Object, hasTwoFa: Boolean, sessions: Array,
  linkedGoogle: Boolean, linkedSteam: Boolean, linkedTelegram: String,
  botUsername: String, orders: Array, wallet: Object, transactions: Object,
  walletStats: Object, filters: Object, activeTab: String,
});

const tabs = [
  { key: 'perfil',    key_nav: 'profile',      icon: 'pi-user',         component: ProfileInfo },
  { key: 'pedidos',   key_nav: 'orders',       icon: 'pi-shopping-bag', component: ProfileOrders },
  { key: 'seguridad', key_nav: 'security',     icon: 'pi-shield',       component: ProfileSecurity },
  { key: 'billetera', key_nav: 'wallet',       icon: 'pi-wallet',       component: ProfileWallet },
];

const activeTab = ref(props.activeTab);
const activeTabComponent = computed(() => tabs.find(t => t.key === activeTab.value)?.component || ProfileInfo);

function setTab(key) {
  activeTab.value = key;
  router.get(route('profile.index'), { tab: key }, { preserveState: true, replace: true, preserveScroll: true });
}

// ── Computed Props ───────────────────────────────────────────────────
const initials = computed(() => (user.value?.name || '?').split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2));
const memberSince = computed(() => props.stats?.memberSince || '—');
const memberDuration = computed(() => {
  const m = props.stats?.monthsMember ?? 0;
  if (m === 0) return 'Nuevo miembro';
  if (m < 12) return `${m} ${m === 1 ? 'mes' : 'meses'}`;
  const y = Math.floor(m / 12);
  return `${y} ${y === 1 ? 'año' : 'años'}`;
});

// Props para componentes hijos
const tabProps = computed(() => {
  if (activeTab.value === 'perfil') return { form, errors: errors.value, saving: saving.value, linkedTelegram: props.linkedTelegram, generatingTg: generatingTg.value, tgLink: tgLink.value, subscription: props.subscription };
  if (activeTab.value === 'pedidos') return { orders: props.orders };
  if (activeTab.value === 'seguridad') return { passwordForm, passwordErrors: passwordErrors.value, savingPassword: savingPassword.value, twofa, loadingTwoFA: loadingTwoFA.value, confirmingTwoFA: confirmingTwoFA.value, sessions: props.sessions };
  if (activeTab.value === 'billetera') return { wallet: props.wallet, walletIncoming: walletStats.incoming, walletSpent: walletStats.spent, transactions: props.transactions, loading: walletLoading.value, filterType: filterType.value };
  return {};
});

// ── Perfil Logic ─────────────────────────────────────────────────────
const form   = reactive({ name: user.value?.name || '', email: user.value?.email || '', username: user.value?.username || '' });
const errors = ref({});
const saving = ref(false);
const uploadingAvatar = ref(false);
const generatingTg = ref(false);

function uploadAvatar(e) {
  const file = e.target.files[0];
  if (!file) return;

  const formData = new FormData();
  formData.append('avatar_file', file);
  formData.append('_method', 'PUT');

  uploadingAvatar.value = true;
  router.post(route('profile.update'), formData, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => toast.add({ severity: 'success', summary: 'Avatar actualizado', life: 3000 }),
    onFinish: () => uploadingAvatar.value = false,
  });
}
const tgLink = ref('');

function roleSev(r) { return { admin: 'danger', seller: 'success', buyer: 'info' }[r] || 'secondary'; }
function saveProfile() {
  saving.value = true;
  router.put(route('profile.update'), form, {
    preserveScroll: true,
    onSuccess: () => toast.add({ severity: 'success', summary: '¡Perfil actualizado!', detail: 'Tus cambios han sido guardados.', life: 3000 }),
    onError: e => errors.value = e,
    onFinish: () => saving.value = false,
  });
}
function generateTgLink() {
  generatingTg.value = true;
  router.post(route('profile.telegram.token'), {}, {
    onSuccess: r => tgLink.value = r.props?.tg_link || '',
    onFinish: () => generatingTg.value = false,
  });
}
async function copyTgLink() {
  await navigator.clipboard.writeText(tgLink.value);
  toast.add({ severity: 'success', summary: 'Enlace copiado', life: 2000 });
}
function confirmDelete() {
  confirm.require({
    header: '¿Eliminar tu cuenta?', message: 'Esta acción es permanente e irreversible.', icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Sí, eliminar', rejectLabel: 'Cancelar', acceptClass: 'p-button-danger',
    accept: () => router.delete(route('profile.destroy')),
  });
}

// ── Seguridad Logic ──────────────────────────────────────────────────
const savingPassword = ref(false), loadingTwoFA = ref(false), confirmingTwoFA = ref(false), disablingTwoFA = ref(false), revokingSessions = ref(false);
const passwordErrors = ref({}), showRevokeDialog = ref(false), showDisableDialog = ref(false);
const revokePassword = ref(''), revokeError = ref(null), disablePassword = ref(''), disableError = ref(null);
const passwordForm = reactive({ current_password: '', password: '', password_confirmation: '' });
const twofa = reactive({ enabled: props.hasTwoFa, qrCode: null, secret: null, recoveryCodes: [], otp: '' });

function savePassword() {
  savingPassword.value = true; passwordErrors.value = {};
  router.put(route('password.change'), passwordForm, {
    preserveScroll: true,
    onSuccess: () => {
      toast.add({ severity: 'success', summary: 'Contraseña actualizada' });
      Object.assign(passwordForm, { current_password: '', password: '', password_confirmation: '' });
    },
    onError: e => passwordErrors.value = e,
    onFinish: () => savingPassword.value = false,
  });
}
async function startEnable() {
  loadingTwoFA.value = true;
  try {
    const res = await fetch(route('two-factor.enable'), { method: 'POST', credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' } });
    const data = await res.json();
    twofa.qrCode = data.qr_code; twofa.secret = data.secret; twofa.recoveryCodes = data.recovery_codes ?? [];
  } catch (e) { toast.add({ severity: 'error', summary: 'Error', detail: e.message }); }
  finally { loadingTwoFA.value = false; }
}
function confirmTwoFA() {
  confirmingTwoFA.value = true;
  router.post(route('two-factor.confirm'), { code: twofa.otp }, {
    onSuccess: () => { toast.add({ severity: 'success', summary: '2FA activado' }); twofa.enabled = true; twofa.qrCode = null; },
    onError: () => toast.add({ severity: 'error', summary: 'Código inválido' }),
    onFinish: () => confirmingTwoFA.value = false,
  });
}
function disableTwoFA() {
  disablingTwoFA.value = true; disableError.value = null;
  router.delete(route('two-factor.disable'), {
    data: { password: disablePassword.value },
    onSuccess: () => { toast.add({ severity: 'info', summary: '2FA desactivado' }); twofa.enabled = false; showDisableDialog.value = false; },
    onError: e => disableError.value = e.password ?? 'Error',
    onFinish: () => disablingTwoFA.value = false,
  });
}
function revokeOtherSessions() {
  revokingSessions.value = true; revokeError.value = null;
  router.delete(route('sessions.destroy'), {
    data: { password: revokePassword.value },
    onSuccess: () => { toast.add({ severity: 'success', summary: 'Sesiones cerradas' }); showRevokeDialog.value = false; },
    onError: e => revokeError.value = e.password ?? 'Error',
    onFinish: () => revokingSessions.value = false,
  });
}

// ── Wallet Logic ─────────────────────────────────────────────────────
const walletLoading = ref(false), filterType = ref(props.filters.type || null);
const walletStats = reactive({ incoming: props.walletStats?.incoming ?? 0, spent: props.walletStats?.spent ?? 0 });

watch(filterType, () => {
  walletLoading.value = true;
  router.get(route('profile.index'), { tab: 'billetera', type: filterType.value }, { preserveState: true, replace: true, onFinish: () => walletLoading.value = false });
});
function goPage(page) {
  router.get(route('profile.index'), { tab: 'billetera', type: filterType.value, page }, { preserveState: true });
}
</script>

<style scoped>
.profile-root { max-width: 1000px; margin: 0 auto; padding: 0 0 4rem; }
.hero-banner {
  position: relative; overflow: hidden; border-radius: 24px; padding: 3rem 2.5rem; margin-bottom: 2rem;
  background: linear-gradient(135deg, #09090b 0%, #11111f 50%, #07070f 100%); 
  border: 1px solid rgba(139, 92, 246, 0.25);
  box-shadow: 0 25px 60px rgba(0,0,0,0.4);
}
.hero-glow { position: absolute; border-radius: 50%; filter: blur(100px); pointer-events: none; opacity: 0.4; }
.hero-glow--a { width: 400px; height: 400px; background: radial-gradient(circle, rgba(139,92,246,0.3) 0%, transparent 70%); top: -100px; left: -80px; }
.hero-glow--b { width: 350px; height: 350px; background: radial-gradient(circle, rgba(99,102,241,0.2) 0%, transparent 70%); bottom: -80px; right: -60px; }
.hero-inner { position: relative; z-index: 10; display: flex; align-items: center; gap: 2.5rem; }

.hero-avatar { 
  width: 110px !important; height: 110px !important; 
  background: #09090b !important; 
  border: 3px solid #11111f !important;
}
.hero-avatar-wrap { position: relative; }

.change-photo-btn {
  position: absolute; bottom: 4px; right: 4px; width: 32px; height: 32px; border-radius: 50%; 
  background: var(--color-brand-600);
  color: #fff; display: flex; align-items: center; justify-content: center; border: 2px solid #09090b; cursor: pointer;
  z-index: 20;
}

.hero-name { margin: 0; line-height: 1.1; }
.hero-email { margin-top: 0.5rem; }

.stat-pill {
  display: flex; align-items: center; gap: 0.625rem; padding: 0.5rem 1rem; border-radius: 14px;
  background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); font-size: 0.8rem; color: rgba(255,255,255,0.6);
  font-weight: 600;
}
.stat-pill strong { color: #fff; font-weight: 800; font-size: 0.9rem; }
.stat-pill--gold { border-color: rgba(245,158,11,0.3); background: rgba(245,158,11,0.08); }

.tab-header { display: flex; gap: 0.5rem; background: rgba(15,15,20, 0.8); border: 1px solid rgba(255,255,255,0.08); border-radius: 20px 20px 0 0; padding: 0.625rem; }
.tab-btn {
  display: flex; align-items: center; gap: 0.625rem; padding: 0.75rem 1.5rem; border-radius: 14px; border: 1.5px solid transparent;
  background: transparent; color: var(--color-surface-400); font-weight: 700; font-size: 0.85rem; cursor: pointer;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.tab-btn:hover { color: white; background: rgba(255,255,255,0.03); }
.tab-btn--active { 
  background: rgba(99,102,241,0.12) !important; 
  color: var(--color-brand-400) !important; 
  border-color: rgba(99,102,241,0.25);
  box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.tab-content { 
  background: rgba(9,9,11, 0.5); 
  border: 1px solid rgba(255,255,255,0.08); 
  border-radius: 0 0 20px 24px; padding: 2rem; min-height: 450px; 
}

.tab-fade-enter-active, .tab-fade-leave-active { transition: all 0.3s ease; }
.tab-fade-enter-from, .tab-fade-leave-to { opacity: 0; transform: translateY(10px); }

@media (max-width: 768px) {
  .hero-inner { flex-direction: column; text-align: center; gap: 1.5rem; }
  .hero-name-row { justify-content: center; }
  .hero-stats { justify-content: center; }
  .tab-header { overflow-x: auto; scrollbar-width: none; }
  .tab-header::-webkit-scrollbar { display: none; }
  .tab-btn span { display: none; }
  .tab-btn { padding: 0.75rem; aspect-ratio: 1; justify-content: center; }
}
</style>
