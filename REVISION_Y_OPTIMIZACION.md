# Reporte de Auditoría: Nexo Digital Store
**Fecha:** 26 de Abril, 2026
**Estado:** Crítico / Necesita Optimización

---

## 🛑 1. Problema Visual Detectado: "Ventana encima"
Tras analizar el layout y los componentes, el problema de la "ventana que no se quita" se debe a:

### Causas Probables:
1.  **Flash Messages Persistentes:** En `AppLayout.vue` (línea 184), el `flash-container` tiene posición `fixed` y `z-index: 200`. Si una variable de sesión `success` o `error` no se limpia, el mensaje se queda pegado arriba.
    *   **Solución:** Agregar un temporizador (`setTimeout`) en el layout para limpiar los mensajes o un botón de cerrar (X).
2.  **Duplicidad de Overlays de PrimeVue:** Se detectó que `Toast` y `ConfirmDialog` están declarados en casi todas las páginas (Index, Show, Checkout). Esto causa conflictos de z-index y múltiples capas oscuras (overlays) si se activan simultáneamente.
    *   **Solución:** Mover `<Toast />` y `<ConfirmDialog />` **únicamente** a `AppLayout.vue`.

---

## 🚀 2. Optimización de Rendimiento (Backend)

### A. N+1 Crítico en Descuentos (ProductPresenter)
En `app/Presenters/ProductPresenter.php`, el método `getFinalDiscount` ejecuta una consulta a la base de datos **por cada producto** mostrado en una lista para verificar la suscripción del usuario.
*   **Impacto:** En el Home con 24 productos, se hacen 24 consultas extra.
*   **Recomendación:** Cargar la suscripción activa del usuario una sola vez en el controlador o compartirla mediante Inertia y pasarla como parámetro al Presenter.

### B. N+1 en StoreSetting::public()
En `HandleInertiaRequests.php`, se llama a `StoreSetting::public()`. Este método filtra las configuraciones, pero por cada una ejecuta `parent::where('is_public', true)`.
*   **Impacto:** Multiplica las consultas por el número de settings.
*   **Recomendación:** Modificar `buildMap` para que incluya el campo `is_public` en el mapa cacheado y filtrar en memoria.

### C. Falta de Paginación
Controladores como `Wishlist\IndexController`, `Licenses\IndexController` y `Admin\DashboardController` usan `->get()`.
*   **Riesgo:** Si un usuario tiene 500 productos en wishlist o hay 10,000 órdenes, la página colapsará.
*   **Recomendación:** Cambiar `->get()` por `->paginate(12)`.

### D. Overhead en Permisos
En cada request (Inertia share), se itera sobre **todos** los casos del Enum `Permission` y se consulta a la DB si el usuario lo tiene.
*   **Recomendación:** Cachear los permisos del usuario o solo enviar los necesarios para la página actual.

---

## 🎨 3. Optimización Frontend (Vue/Vite)

### A. Componentes Globales vs Auto-import
Vite usa `PrimeVueResolver`. Es excelente, pero se deben eliminar las importaciones manuales innecesarias en `app.js` para reducir el tamaño del bundle inicial.

### B. Lazy Loading de Rutas
Actualmente, todas las páginas se resuelven con `import.meta.glob('./Pages/**/*.vue')`.
*   **Recomendación:** Usar `resolvePageComponent` con `import` dinámico para que el navegador solo descargue el JS de la página que el usuario está viendo.

### C. Assets de Imagen
Se detectó el uso de `ui-avatars.com` y placeholders externos.
*   **Recomendación:** Implementar un fallback local o usar transformaciones de Cloudinary (que ya está en el proyecto) para redimensionar imágenes y ahorrar ancho de banda.

---

## 🛠 4. Lista de Tareas Sugeridas

1.  [ ] **Surgical Fix:** Centralizar `Toast` y `ConfirmDialog` en `AppLayout.vue`.
2.  [ ] **Surgical Fix:** Agregar `transition` y botón de cierre al `flash-container`.
3.  [ ] **Refactor:** Optimizar `ProductPresenter` para recibir el descuento de suscripción pre-cargado.
4.  [ ] **Refactor:** Cambiar `get()` por `simplePaginate()` o `paginate()` en rutas de listado.
5.  [ ] **Cache:** Mejorar `StoreSetting` para que el filtro `public()` no consulte la DB repetidamente.

---
**¿Deseas que proceda a aplicar los "Surgical Fixes" (punto 1 y 2) para quitar la ventana que te molesta ahora mismo?**
