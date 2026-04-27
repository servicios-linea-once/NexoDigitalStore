<template>
  <div class="filter-content">
    <div class="filter-header flex items-center justify-between mb-4 pb-2 border-b border-surface-800">
      <span class="filter-title font-bold text-sm flex items-center gap-2"><i class="pi pi-sliders-h" /> Filtros</span>
      <Button v-if="hasActiveFilters" label="Limpiar" icon="pi pi-times" size="small" text severity="secondary" @click="$emit('clear')" />
    </div>

    <!-- Search -->
    <div class="filter-section mb-6">
      <label class="filter-label text-[10px] font-bold uppercase tracking-wider text-surface-500 mb-2 block">Buscar</label>
      <IconField>
        <InputIcon class="pi pi-search" />
        <InputText v-model="local.q" placeholder="Nombre..." fluid class="text-sm h-9" @input="$emit('debounced')" />
      </IconField>
    </div>

    <!-- Category -->
    <div class="filter-section mb-6">
      <label class="filter-label text-[10px] font-bold uppercase tracking-wider text-surface-500 mb-2 block">Categoría</label>
      <div class="chip-group flex flex-wrap gap-1.5">
        <Chip
          v-for="cat in categories" :key="cat.id"
          :label="cat.name"
          :class="['filter-chip cursor-pointer transition-all text-xs', { 'active-chip': local.category === cat.slug }]"
          @click="$emit('toggle', 'category', cat.slug)"
        />
      </div>
    </div>

    <!-- Platform -->
    <div class="filter-section mb-6">
      <label class="filter-label text-[10px] font-bold uppercase tracking-wider text-surface-500 mb-2 block">Plataforma</label>
      <div class="chip-group flex flex-wrap gap-1.5">
        <Chip
          v-for="p in platforms" :key="p" :label="p"
          :class="['filter-chip cursor-pointer transition-all text-xs', { 'active-chip': local.platform === p }]"
          @click="$emit('toggle', 'platform', p)"
        />
      </div>
    </div>

    <!-- Region -->
    <div class="filter-section mb-6">
      <label class="filter-label text-[10px] font-bold uppercase tracking-wider text-surface-500 mb-2 block">Región</label>
      <div class="chip-group flex flex-wrap gap-1.5">
        <Chip
          v-for="r in regions" :key="r.code" :label="r.label"
          :class="['filter-chip cursor-pointer transition-all text-xs', { 'active-chip': local.region === r.code }]"
          @click="$emit('toggle', 'region', r.code)"
        />
      </div>
    </div>

    <!-- Price Range -->
    <div class="filter-section mb-6">
      <label class="filter-label text-[10px] font-bold uppercase tracking-wider text-surface-500 mb-2 block">Precio USD</label>
      <div class="flex items-center gap-2">
        <InputNumber v-model="local.price_min" placeholder="Min" :min="0" fluid class="text-sm" @blur="$emit('apply')" />
        <span class="text-surface-600">—</span>
        <InputNumber v-model="local.price_max" placeholder="Max" :min="0" fluid class="text-sm" @blur="$emit('apply')" />
      </div>
    </div>

    <!-- In Stock -->
    <div class="filter-section mb-8 flex items-center gap-2">
      <Checkbox v-model="local.in_stock" binary input-id="instock-f" @change="$emit('apply')" />
      <label for="instock-f" class="text-sm text-surface-400 cursor-pointer">Solo con stock</label>
    </div>

    <Button label="Ver Resultados" icon="pi pi-check" class="w-full" @click="$emit('apply')" />
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  categories: { type: Array, required: true },
  local:      { type: Object, required: true },
});

const emit = defineEmits(['apply', 'clear', 'toggle', 'debounced']);

const platforms = ['Steam','Epic Games','GOG','Battle.net','PSN','Xbox','Nintendo','Netflix','Spotify','Disney+','Microsoft','Rockstar'];
const regions   = [
  { code:'Global', label:'🌍 Global' },
  { code:'PE',     label:'🇵🇪 Perú' },
  { code:'US',     label:'🇺🇸 EEUU' },
  { code:'EU',     label:'🇪🇺 Europa' },
  { code:'MX',     label:'🇲🇽 México' },
  { code:'CO',     label:'🇨🇴 Colombia' },
];

const hasActiveFilters = computed(() =>
  props.local.q || props.local.category || props.local.platform ||
  props.local.region || props.local.price_min || props.local.price_max || props.local.in_stock
);
</script>

<style scoped>
.active-chip {
  background: var(--color-brand-500) !important;
  color: white !important;
}
.filter-chip:hover:not(.active-chip) {
  border-color: var(--color-brand-400);
}
</style>
