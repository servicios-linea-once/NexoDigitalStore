/**
 * usePermissions.js
 * Composable para verificar permisos Spatie en componentes Vue.
 * Los permisos vienen de page.props.can (inyectados por HandleInertiaRequests).
 *
 * Uso:
 *   const { can, canAny, isAdmin, isSeller } = usePermissions()
 *   v-if="can('users.edit')"
 */
import { computed } from 'vue';
import { usePage }  from '@inertiajs/vue3';

export function usePermissions() {
    const page = usePage();

    /** Map: permission_name → bool (from Spatie via Inertia) */
    const permMap = computed(() => page.props.can ?? {});

    /** Auth user info */
    const user    = computed(() => page.props.auth?.user ?? null);
    const role    = computed(() => user.value?.role ?? 'guest');
    const roles   = computed(() => user.value?.roles ?? []);

    /**
     * Check a Spatie permission.
     * @param {string} permission  e.g. 'users.edit'
     */
    function can(permission) {
        return permMap.value[permission] === true;
    }

    /**
     * True if user has ANY of the given permissions.
     */
    function canAny(...perms) {
        const list = perms.flat();
        return list.some(p => can(p));
    }

    /**
     * True if user has ALL of the given permissions.
     */
    function canAll(...perms) {
        const list = perms.flat();
        return list.every(p => can(p));
    }

    /** Role shorthands (checks both legacy role field and Spatie roles) */
    const isAdmin  = computed(() => role.value === 'admin' || roles.value.includes('admin'));
    const isSeller = computed(() => ['seller','admin'].includes(role.value) || roles.value.some(r => ['seller','admin'].includes(r)));
    const isBuyer  = computed(() => role.value === 'buyer' || roles.value.includes('buyer'));
    const isGuest  = computed(() => !user.value);

    return {
        // Functions
        can, canAny, canAll,
        // State
        permMap, user, role, roles,
        // Shorthands
        isAdmin, isSeller, isBuyer, isGuest,
    };
}
