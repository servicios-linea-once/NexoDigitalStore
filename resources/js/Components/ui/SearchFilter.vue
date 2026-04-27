<!-- Components/ui/SearchFilter.vue — Barra de búsqueda con debounce reutilizable -->
<template>
  <IconField>
    <InputIcon class="pi pi-search" />
    <InputText
      v-model="localValue"
      :placeholder="placeholder"
      class="w-full"
      @input="onInput"
    />
    <InputIcon v-if="localValue" class="pi pi-times cursor-pointer" @click="clear" />
  </IconField>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  modelValue:  { type: String, default: '' },
  placeholder: { type: String, default: 'Buscar...' },
  debounce:    { type: Number, default: 300 },
});
const emit = defineEmits(['update:modelValue', 'search']);

const localValue = ref(props.modelValue);
let timer = null;

watch(() => props.modelValue, v => { localValue.value = v; });

function onInput() {
  clearTimeout(timer);
  timer = setTimeout(() => {
    emit('update:modelValue', localValue.value);
    emit('search', localValue.value);
  }, props.debounce);
}

function clear() {
  localValue.value = '';
  emit('update:modelValue', '');
  emit('search', '');
}
</script>
