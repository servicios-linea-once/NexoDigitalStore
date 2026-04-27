import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * Composable for managing Admin Index filters and pagination
 * @param {string} routeName - The Ziggy route name (e.g., 'admin.users.index')
 * @param {object} initialValues - Initial filter values (usually from props.filters.filter)
 * @param {object} options - Configuration options
 */
export function useFilters(routeName, initialValues = {}, options = {}) {
  const loading = ref(false);
  
  // Flatten initial values if they come nested from Spatie QueryBuilder
  const filters = reactive({ ...initialValues });

  /**
   * Builds the query object compatible with Spatie QueryBuilder
   */
  const buildQuery = (page = null) => {
    const query = {};
    if (page) query.page = page;

    // Map filters to filter[key] format
    Object.keys(filters).forEach(key => {
      const value = filters[key];
      if (value !== null && value !== undefined && value !== '') {
        query[`filter[${key}]`] = value;
      }
    });

    return query;
  };

  let timeout = null;

  /**
   * Apply filters and navigate to the route with a 1s debounce
   */
  const applyFilters = () => {
    // Clear previous timeout to reset the 1s wait
    if (timeout) clearTimeout(timeout);

    loading.value = true;
    
    timeout = setTimeout(() => {
      router.get(route(routeName), buildQuery(), {
        preserveState: true,
        replace: true,
        onFinish: () => { loading.value = false; },
        ...options
      });
    }, 1000); // 1 second delay to "improve server response" (debounce)
  };

  /**
   * Go to a specific page while preserving filters
   */
  const goPage = (page) => {
    router.get(route(routeName), buildQuery(page), {
      preserveState: true,
      ...options
    });
  };

  return {
    filters,
    loading,
    applyFilters,
    goPage,
  };
}
