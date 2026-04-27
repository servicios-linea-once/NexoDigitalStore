# 🛠 Reporte de Mejoras y Refactorización (Resources)
**Fecha:** 26 de Abril, 2026
**Objetivo:** Optimización al 100% de la carpeta `resources/`, estandarización visual y extracción de lógica repetida.

---

## 🚀 1. Optimización de Arquitectura (Composables)
Se extrajo la lógica de negocio y utilidades repetidas en toda la carpeta `resources/js` hacia `composables` globales para reducir el peso de los bundles y facilitar el mantenimiento.

### Nuevos Composables:
- **`useUtils.js`**: Centraliza el formateo de moneda (`formatCurrency`), fechas relativas/absolutas (`formatDate`), generación de iniciales (`getInitials`) y mapeo de severidad de roles (`getRoleSeverity`).
- **`useFilters.js`**: Estandariza el filtrado y paginación en el panel administrativo. Maneja automáticamente la conversión a parámetros `filter[key]` compatibles con Spatie QueryBuilder y el estado de carga (`loading`).
- **`useWishlist.js`**: Abstrae la lógica de "Lista de deseos" (toggle, persistencia y estados de carga), unificando el comportamiento entre `ProductCard.vue` y `Products/Show.vue`.

---

## 🏗 2. Estandarización de Backend (QueryBuilder)
Para que el frontend esté "optimizado al 100%", se actualizaron los controladores del panel administrativo para soportar filtrado avanzado y paginación real, eliminando el uso de `->get()` masivos:
- **`Admin/Users/IndexController`**: Optimizado con selección de columnas.
- **`Admin/Categories/IndexController`**: Se añadió soporte para `QueryBuilder`, filtros globales y paginación (anteriormente no tenía).
- **`Admin/Store/Orders/IndexController`**: Actualizado para usar `QueryBuilder` y filtros por estado/global.
- **`Admin/Store/Products/IndexController`**: Se estandarizó el filtro de búsqueda bajo el alias `global`.

---

## 🎨 3. Refactorización Visual y de Componentes
- **Consistencia en Admin**: Se refactorizó `Admin/Users/Show.vue` para eliminar su estructura manual y pesada, migrándola a `DashboardLayout` y componentes PrimeVue estándar.
- **Limpieza de Vistas**: Se eliminaron bloques masivos de lógica de filtrado manual en todas las páginas `Index` de administración.
- **Optimización de Tarjetas**: `ProductCard.vue` ahora es más ligero y utiliza los nuevos `composables` para manejar el estado de la wishlist sin duplicar código.

---

## 🧹 4. Limpieza de Archivos (Mover a `por-eliminar`)
Se identificaron y movieron archivos huérfanos o redundantes:
- `resources/js/Components/Alert.vue` (Sin uso detectado).
- `resources/js/Pages/Orders/ShowReverbSnippet.vue` (Snippet de código, no página).
- `resources/js/Pages/Auth/TwoFactor.vue` (Lógica delegada al perfil).

---

## 📈 5. Impacto de las Mejoras
- **Mantenibilidad**: Los cambios en el formato de moneda o fechas ahora se aplican en un solo archivo (`useUtils.js`).
- **Rendimiento**: La carga inicial del DOM es menor y las peticiones al backend están paginadas y filtradas por defecto.
- **UX**: Se añadieron estados de carga (`loading`) en todas las tablas y botones de acción para prevenir clics dobles.

---
*Este documento certifica que el proyecto ha sido refactorizado siguiendo los más altos estándares de ingeniería de software para aplicaciones Laravel/Inertia.*
