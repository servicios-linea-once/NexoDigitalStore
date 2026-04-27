/**
 * Composable for shared utility functions (formatting, UI helpers, etc.)
 */
export function useUtils() {
  
  /**
   * Format a number as currency with dynamic conversion
   * @param {number|string} value - The amount in USD (base)
   * @param {string|object} targetCurrency - Target currency code or Vue Ref
   */
  const formatCurrency = (value, targetCurrency = 'USD') => {
    // Force unwrap if it's a Vue ref
    const currencyCode = (typeof targetCurrency === 'object' && targetCurrency?.value) 
      ? targetCurrency.value 
      : targetCurrency;

    const amount = parseFloat(value);
    if (isNaN(amount)) return '—';

    const rates = {
      'USD': 1.0,
      'PEN': 3.80,
      'COP': 3900,
      'MXN': 17.00,
      'NT':  10.00,
    };

    const rate = rates[currencyCode] || 1.0;
    const convertedAmount = amount * rate;

    try {
      return new Intl.NumberFormat('es-PE', {
        style: 'currency',
        currency: currencyCode === 'NT' ? 'USD' : (currencyCode || 'USD'),
      }).format(convertedAmount).replace('US$', '').trim() + (currencyCode === 'NT' ? ' NT' : '');
    } catch (e) {
      return convertedAmount.toFixed(2);
    }
  };

  /**
   * Format a date to a readable string
   */
  const formatDate = (dateString, format = 'short') => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    if (format === 'relative') {
      // Basic relative time logic (simplified)
      const now = new Date();
      const diff = Math.floor((now - date) / 1000);
      if (diff < 60) return 'hace un momento';
      if (diff < 3600) return `hace ${Math.floor(diff / 60)} min`;
      if (diff < 86400) return `hace ${Math.floor(diff / 3600)} horas`;
      return date.toLocaleDateString();
    }
    return date.toLocaleDateString('es-PE', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  /**
   * Get initials from a name (max 2 characters)
   */
  const getInitials = (name) => {
    if (!name) return '?';
    return name
      .split(' ')
      .map(word => word[0])
      .join('')
      .toUpperCase()
      .slice(0, 2);
  };

  /**
   * Map roles to PrimeVue severities
   */
  const getRoleSeverity = (role) => {
    const maps = {
      admin: 'danger',
      seller: 'success',
      buyer: 'info',
    };
    return maps[role] || 'secondary';
  };

  return {
    formatCurrency,
    formatDate,
    getInitials,
    getRoleSeverity,
  };
}
