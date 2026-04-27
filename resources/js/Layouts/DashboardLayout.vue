<template>
  <div class="dash-root">
    <Head :title="title" />

    <!-- Toast global -->
    <Toast position="top-right" />
    <ConfirmDialog />

    <!-- Flash messages (Admin) -->
    <div class="flash-container-admin" v-if="flash.success || flash.error || flash.warning">
      <TransitionGroup name="flash">
        <div v-if="flash.success" class="flash flash-success shadow-lg shadow-green-500/20" key="success">
          <i class="pi pi-check-circle" />
          <span>{{ flash.success }}</span>
          <button class="flash-close" @click="clearFlash('success')"><i class="pi pi-times" /></button>
        </div>
        <div v-if="flash.error" class="flash flash-error shadow-lg shadow-red-500/20" key="error">
          <i class="pi pi-times-circle" />
          <span>{{ flash.error }}</span>
          <button class="flash-close" @click="clearFlash('error')"><i class="pi pi-times" /></button>
        </div>
        <div v-if="flash.warning" class="flash flash-warning shadow-lg shadow-amber-500/20" key="warning">
          <i class="pi pi-exclamation-triangle" />
          <span>{{ flash.warning }}</span>
          <button class="flash-close" @click="clearFlash('warning')"><i class="pi pi-times" /></button>
        </div>
      </TransitionGroup>
    </div>

    <!-- Sidebar -->
    <AdminSidebar
      :active="active"
      :can="can"
      :collapsed="sidebarCollapsed"
      @toggle="sidebarCollapsed = !sidebarCollapsed"
    />

    <!-- Main area -->
    <div class="dash-main" :class="{ 'sidebar-collapsed': sidebarCollapsed }">
      <!-- Top bar -->
      <header class="dash-topbar shadow-sm">
        <!-- Mobile sidebar toggle -->
        <Button
          icon="pi pi-bars"
          text
          severity="secondary"
          class="mobile-toggle sm:!hidden"
          @click="mobileSidebarOpen = true"
        />

        <!-- Page title (mobile only) -->
        <span class="dash-topbar-title font-display font-bold truncate max-w-[150px] sm:hidden">{{ title }}</span>

        <!-- Right controls -->
        <div class="topbar-right flex items-center">
          <div class="hidden xs:flex items-center gap-1 sm:gap-2 mr-2">
            <ThemeSwitcher />
            <LangSwitcher />
          </div>

          <Divider layout="vertical" class="mx-0 h-8 hidden xs:block" />

          <!-- User menu -->
          <div class="topbar-user group ml-1 sm:ml-2" @click="userMenuRef.toggle($event)">
            <div class="avatar-wrap p-0.5 rounded-full bg-surface-700 group-hover:bg-brand-500 transition-colors">
              <Avatar
                :label="userInitials"
                :image="user?.avatar"
                shape="circle"
                class="topbar-avatar"
              />
            </div>
            <span class="topbar-username font-semibold hidden md:block">{{ user?.name?.split(' ')[0] }}</span>
            <i class="pi pi-angle-down topbar-chevron group-hover:text-brand-400 transition-colors hidden sm:block" />
          </div>
          <Menu ref="userMenuRef" :model="userMenuItems" popup class="glass-dark border border-surface-800 shadow-2xl" />
        </div>
      </header>

      <!-- Content -->
      <main class="dash-content overflow-x-hidden" v-motion-fade-visible>
        <div class="max-w-[1600px] mx-auto">
          <slot />
        </div>
      </main>
    </div>

    <!-- Quick Actions (Floating Dial in all pages) -->
    <QuickActions />

    <!-- Mobile sidebar overlay -->
    <Drawer v-model:visible="mobileSidebarOpen" position="left" :showCloseIcon="true" class="mobile-sidebar-drawer glass-dark">
      <template #header>
        <span class="drawer-title font-display font-black text-brand-400">Nexo Admin</span>
      </template>
      <AdminSidebar :active="active" :can="can" :collapsed="false" @toggle="mobileSidebarOpen = false" />
    </Drawer>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AdminSidebar from '@/Components/Admin/AdminSidebar.vue';
import QuickActions from '@/Components/Admin/QuickActions.vue';
import ThemeSwitcher from '@/Components/ui/ThemeSwitcher.vue';
import LangSwitcher from '@/Components/ui/LangSwitcher.vue';

const { t } = useI18n();

defineProps({
  active: { type: String, default: '' },
  title:  { type: String, default: 'Dashboard' },
});

const page    = usePage();
const user    = computed(() => page.props.auth?.user);
const flash   = computed(() => page.props.flash);
const can     = computed(() => page.props.can ?? {});

// Flash management
function clearFlash(key) {
  if (page.props.flash[key]) {
    page.props.flash[key] = null;
  }
}

watch(() => page.props.flash, (newFlash) => {
  if (newFlash.success || newFlash.error || newFlash.warning) {
    setTimeout(() => {
      if (newFlash.success) clearFlash('success');
      if (newFlash.error) clearFlash('error');
      if (newFlash.warning) clearFlash('warning');
    }, 5000);
  }
}, { deep: true, immediate: true });

const userInitials = computed(() => {
  const n = user.value?.name || '';
  return n.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
});
const sidebarCollapsed  = ref(false);
const mobileSidebarOpen = ref(false);
const userMenuRef = ref();

const userMenuItems = computed(() => [
  { label: user.value?.name, disabled: true, class: 'font-black opacity-100 text-white' },
  { separator: true },
  { label: t('nav.profile'),    icon: 'pi pi-user',      command: () => router.visit(route('profile.index')) },
  { label: t('nav.wallet'),   icon: 'pi pi-wallet',    command: () => router.visit(route('profile.index', { tab: 'billetera' })) },
  { separator: true },
  { label: t('nav.home'),   icon: 'pi pi-storefront', command: () => router.visit(route('home')) },
  { separator: true },
  { label: t('nav.logout'),icon: 'pi pi-sign-out',  command: () => router.post(route('logout')), class: 'text-red-400' },
]);
</script>

<style scoped>
.dash-root {
  display: flex;
  min-height: 100vh;
  background: var(--color-surface-950);
  color: var(--color-surface-200);
}

.dash-main {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
  transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.dash-topbar {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0 1.5rem;
  height: 64px;
  background: rgba(9, 9, 11, 0.85);
  border-bottom: 1px solid var(--color-surface-800);
  position: sticky;
  top: 0;
  z-index: 40;
  backdrop-filter: blur(12px);
}

.mobile-toggle { display: none; }
@media (max-width: 900px) { .mobile-toggle { display: flex; } }

.dash-topbar-title { font-size: 1.1rem; color: white; display: none; }
@media (max-width: 900px) { .dash-topbar-title { display: block; } }

.topbar-right { margin-left: auto; display: flex; align-items: center; }
.topbar-user {
  display: flex; align-items: center; gap: 0.625rem;
  cursor: pointer; padding: 0.375rem 0.75rem;
  border-radius: 12px; transition: all 0.2s ease;
}
.topbar-user:hover { background: var(--color-surface-900); }
.topbar-username { font-size: 0.875rem; color: white; }
@media (max-width: 640px) { .topbar-username { display: none; } }
.topbar-avatar { width: 34px !important; height: 34px !important; font-size: 0.75rem !important; font-weight: 800; }
.topbar-chevron { font-size: 0.75rem; color: var(--color-surface-500); }

.dash-content {
  flex: 1;
  padding: 2rem;
  max-width: 100%;
  overflow-x: hidden;
  width: 100%;
  background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.03), transparent 400px);
}
@media (max-width: 900px) {
  .dash-content { padding: 1.25rem; }
}

.flash-container-admin {
  position: fixed; top: 1.5rem; right: 1.5rem; z-index: 3000;
  display: flex; flex-direction: column; gap: 0.75rem; pointer-events: none;
}
.flash {
  pointer-events: auto; display: flex; align-items: center; gap: 0.875rem;
  padding: 1rem 1.25rem; border-radius: 14px; font-size: 0.9rem; font-weight: 700;
  min-width: 280px; color: white; border: 1px solid rgba(255,255,255,0.1);
  backdrop-filter: blur(8px);
}
.flash-success { background: rgba(16, 185, 129, 0.9); }
.flash-error   { background: rgba(239, 68, 68, 0.9); }
.flash-warning { background: rgba(245, 158, 11, 0.9); }

.flash-close {
  margin-left: auto; background: none; border: none; color: white;
  cursor: pointer; padding: 0.25rem; display: flex; opacity: 0.7; transition: opacity 0.2s;
}
.flash-close:hover { opacity: 1; }

.flash-enter-active, .flash-leave-active { transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); }
.flash-enter-from { opacity: 0; transform: translateX(40px); }
.flash-leave-to { opacity: 0; transform: scale(0.9); }

:deep(.mobile-sidebar-drawer .p-drawer-content) { padding: 0; background: var(--color-surface-950); }
.drawer-title { font-size: 1.1rem; font-weight: 900; }
</style>
