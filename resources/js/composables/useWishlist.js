import { ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

/**
 * Composable for shared wishlist logic
 */
export function useWishlist(initialInWishlist = false) {
  const inWishlist = ref(initialInWishlist);
  const loading = ref(false);

  /**
   * Toggle a product in/out of the wishlist
   * @param {number} productId 
   */
  const toggleWishlist = async (productId) => {
    const page = usePage();
    if (!page.props.auth?.user) {
      router.visit(route('login'));
      return;
    }

    loading.value = true;
    try {
      const res = await fetch(route('wishlist.toggle'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ product_id: productId }),
      });
      
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      
      const data = await res.json();
      inWishlist.value = data.in_wishlist;
      
      // Optional: emit an event or update global state if needed
    } catch (e) {
      console.error('Wishlist toggle error', e);
    } finally {
      loading.value = false;
    }
  };

  return {
    inWishlist,
    loading,
    toggleWishlist,
  };
}
