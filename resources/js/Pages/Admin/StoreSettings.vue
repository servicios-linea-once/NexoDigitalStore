<template>
  <DashboardLayout title="Configuración" active="settings">
    <Head title="Configuración — Admin" />

    <PageHeader title="Configuración de Tienda" subtitle="Parámetros globales de Nexo Digital Store" icon="pi-cog"
      :breadcrumb="[{ label: 'Admin' }, { label: 'Configuración' }]">
      <template #actions>
        <Button label="Guardar cambios" icon="pi pi-check" :loading="saving" @click="saveAll" />
      </template>
    </PageHeader>

    <div class="settings-grid">
      <DataCard v-for="(items, group) in localSettings" :key="group"
        :icon="groupIcon(group)" :title="groupLabel(group)" class="group-card">

        <div class="fields-list">
          <div v-for="s in items" :key="s.key" class="setting-row">
            <div class="setting-meta">
              <label :for="s.key" class="setting-label">{{ s.label }}</label>
              <p v-if="s.description" class="setting-desc">{{ s.description }}</p>
            </div>

            <div class="setting-control">
              <!-- Boolean toggle -->
              <ToggleSwitch v-if="s.type === 'boolean'" v-model="s._value"
                :inputId="s.key" />

              <!-- Textarea -->
              <Textarea v-else-if="s.type === 'text'" v-model="s._value"
                :id="s.key" rows="3" autoResize fluid />

              <!-- Number -->
              <InputNumber v-else-if="s.type === 'number'" v-model="s._value"
                :id="s.key" fluid />

              <!-- Default: string input -->
              <InputText v-else v-model="s._value" :id="s.key" fluid />

              <Tag v-if="s.is_public" value="Público" severity="info" size="small" rounded class="public-tag" />
            </div>
          </div>
        </div>
      </DataCard>
    </div>

    <Toast />
  </DashboardLayout>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import DataCard   from '@/Components/ui/DataCard.vue';

const props = defineProps({
  settings: { type: Object, default: () => ({}) },
  groups:   { type: Array,  default: () => [] },
});

const toast  = useToast();
const saving = ref(false);

// Clone settings locally adding a reactive _value field
const localSettings = reactive(
  Object.fromEntries(
    Object.entries(props.settings).map(([group, items]) => [
      group,
      items.map(s => ({
        ...s,
        _value: s.type === 'boolean' ? s.value === '1' || s.value === 'true' : s.value,
      })),
    ])
  )
);

function groupLabel(g) {
  return { general: 'General', commerce: 'Comercio', notifications: 'Notificaciones', legal: 'Legal' }[g] ?? g;
}
function groupIcon(g) {
  return { general: 'pi-home', commerce: 'pi-shopping-bag', notifications: 'pi-bell', legal: 'pi-file' }[g] ?? 'pi-cog';
}

function saveAll() {
  const payload = Object.values(localSettings).flat().map(s => ({
    key:   s.key,
    value: s.type === 'boolean' ? (s._value ? '1' : '0') : String(s._value ?? ''),
  }));

  saving.value = true;
  router.post(route('admin.settings.update'), { settings: payload }, {
    onSuccess: () => toast.add({ severity: 'success', summary: 'Configuración guardada', life: 3000 }),
    onError:   () => toast.add({ severity: 'error',   summary: 'Error al guardar', life: 4000 }),
    onFinish:  () => { saving.value = false; },
  });
}
</script>

<style scoped>
.settings-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.25rem;
}
@media (max-width: 900px) { .settings-grid { grid-template-columns: 1fr; } }

.group-card { height: fit-content; }

.fields-list {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.setting-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1.5rem;
  padding: 0.875rem 1.125rem;
  border-bottom: 1px solid var(--p-surface-800);
  transition: background 0.15s;
}
.setting-row:last-child { border-bottom: none; }
.setting-row:hover { background: color-mix(in srgb, var(--p-surface-800) 40%, transparent); }

.setting-meta { flex: 1; min-width: 0; }
.setting-label {
  font-size: 0.84rem;
  font-weight: 600;
  color: var(--p-text-color);
  display: block;
  margin-bottom: 0.15rem;
  cursor: pointer;
}
.setting-desc {
  font-size: 0.74rem;
  color: var(--p-text-muted-color);
  margin: 0;
  line-height: 1.4;
}

.setting-control {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  flex-shrink: 0;
  min-width: 180px;
  justify-content: flex-end;
}

.public-tag { flex-shrink: 0; }
</style>
