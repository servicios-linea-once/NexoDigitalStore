# 📦 Stack: Laravel 11 + Vue 3 + Inertia.js v2 + PrimeVue v4 + Ziggy + TailwindCSS 3.4

## 🏗 Arquitectura
- **Backend:** Laravel 11. Controladores retornan `Inertia::render('Page', $data)`. Rutas en `routes/web.php`.
- **Frontend:** Vue 3 Composition API (`<script setup>`). Inertia gestiona navegación, props y headers.
- **UI:** PrimeVue 4 en modo `unstyled: true`. Estilos vía Tailwind + `tailwind.preset.js`. Overrides con prop `pt`.
- **Rutas:** Ziggy expone rutas a Vue. Usa `route('name', params)`. **NO leas `resources/js/ziggy.js`**.
- **Build:** Vite. Dev: `npm run dev` | Prod: `npm run build`.

## 📁 Estructura clave
app/Http/Controllers/ → Controladores Inertia
resources/js/Pages/ → Páginas (ruta = estructura de carpetas)
resources/js/Layouts/ → Layouts (App, Guest, Auth)
resources/js/Components/→ UI reutilizable (wrappers PrimeVue, inputs)
resources/views/ → Solo app.blade.php (entry point Inertia)

## ⚡ Reglas de oro (ahorro de tokens)
1. **NUNCA** uses `Read` o `Grep` en `vendor/`, `node_modules/`, `public/`, `storage/` o `ziggy.js`.
2. **Ziggy:** Si modificas rutas, ejecuta `php artisan ziggy:generate`. Claude NO debe validar el archivo resultante.
3. **PrimeVue:** Usa `pt` para overrides de clases. No copies CSS de la documentación.
4. **Inertia Props:** Mantén props planas (<3 niveles). Usa `defineProps<{ ... }>()` con TypeScript si es posible.
5. **Tailwind:** Configura solo en `tailwind.config.js` y `tailwind.preset.js`. Evita `@apply` excesivo.

## 🛠 Comandos seguros (confirma antes de ejecutar)
- `php artisan migrate:fresh --seed` ⚠️ Borra DB
- `php artisan optimize:clear` → Limpia caché
- `php artisan ziggy:generate` → Solo si cambias `routes/`
- `npm run build` → Genera assets (no leer después)

## 📉 Debug rápido
- Página blanca → Verifica `app.blade.php`, `Inertia::render()` y `defineProps`.
- Rutas 404 en Ziggy → `php artisan route:list --path=web`
- PrimeVue sin estilos → Verifica `unstyled: true` y que Tailwind escanee `resources/js/**/*.vue`.