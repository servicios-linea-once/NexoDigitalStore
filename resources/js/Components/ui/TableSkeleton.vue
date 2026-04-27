<!-- Components/ui/TableSkeleton.vue — Skeleton de carga para tablas -->
<template>
  <div class="table-skeleton">
    <div v-for="i in rows" :key="i" class="skeleton-row">
      <Skeleton v-for="j in cols" :key="j" height="2.25rem" class="skeleton-cell" :style="{ flex: colWidths[j-1] || 1 }" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  rows:      { type: Number, default: 5 },
  cols:      { type: Number, default: 4 },
  colWidths: { type: Array,  default: () => [] },
});
</script>

<style scoped>
.table-skeleton { display: flex; flex-direction: column; gap: 0.625rem; padding: 0.75rem; }
.skeleton-row   { display: flex; gap: 0.75rem; align-items: center; }
.skeleton-cell  { border-radius: 8px !important; flex-shrink: 0; }
</style>
