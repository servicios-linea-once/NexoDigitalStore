# Cloudinary — Gestión de Imágenes

## Configuración

```env
CLOUDINARY_CLOUD_NAME=dlekpa6gn
CLOUDINARY_API_KEY=663352452411449
CLOUDINARY_API_SECRET=D2UWuNi0yl5bIJLLhMMTLxfo318
CLOUDINARY_FOLDER=nexo-digital-store
```

---

## CloudinaryService

`app/Services/CloudinaryService.php`

### Métodos disponibles

```php
$service = app(CloudinaryService::class);

// Subir imagen de producto
$result = $service->uploadProductImage($file, $productSlug);
// Retorna: ['url' => '...', 'public_id' => '...']

// Subir avatar de usuario
$result = $service->uploadAvatar($file, $userId);

// Subir banner de vendedor
$result = $service->uploadBanner($file, $sellerId);

// Eliminar imagen
$service->delete($publicId);

// Obtener URL con transformación custom
$url = $service->transform($publicId, [
    'width'  => 800,
    'height' => 600,
    'crop'   => 'fill',
    'quality' => 'auto',
    'fetch_format' => 'auto',
]);
```

---

## Transformaciones por Tipo

### Imágenes de productos

| Contexto | Transformación | Dimensiones |
|---|---|---|
| Card thumbnail | `c_fill, q_auto, f_auto` | 400×300 |
| Detalle (cover) | `c_fill, q_auto, f_auto` | 800×600 |
| Galería modal | `c_limit, q_auto, f_auto` | 1200×800 |
| OG Image | `c_fill, q_80` | 1200×630 |

### Avatares de usuarios

| Contexto | Transformación |
|---|---|
| Navbar | `w_32, h_32, c_thumb, g_face, r_max` |
| Perfil | `w_128, h_128, c_thumb, g_face, r_max` |

### Banners de vendedor

| Contexto | Transformación |
|---|---|
| Tienda | `w_1200, h_400, c_fill, q_auto` |
| Thumbnail | `w_400, h_133, c_fill` |

---

## Estructura de Carpetas en Cloudinary

```
nexo-digital-store/
├── products/
│   └── {product-slug}/       # Ej: cyberpunk-2077-steam/
│       ├── cover.jpg         # Imagen principal
│       ├── screenshot-1.jpg
│       └── screenshot-2.jpg
├── avatars/
│   └── user-{id}.jpg
├── sellers/
│   ├── banners/
│   │   └── linea-once-banner.jpg
│   └── avatars/
│       └── linea-once-avatar.jpg
├── categories/
│   ├── juegos.png
│   └── streaming.png
└── kyc/                      # Access restricted
    └── user-{id}/
        └── document.pdf
```

---

## Upload con Vue + Inertia

```vue
<!-- En el formulario de creación de producto -->
<template>
  <input
    type="file"
    accept="image/*"
    multiple
    @change="handleImages"
  />
  
  <!-- Preview de imágenes subidas -->
  <div v-for="img in uploadedImages" :key="img.public_id">
    <img :src="img.url" />
    <button @click="removeImage(img.public_id)">Eliminar</button>
  </div>
</template>

<script setup>
const form = useForm({
  images: [],
  // ...resto del formulario
});

function handleImages(event) {
  form.images = [...event.target.files];
}
</script>
```

```php
// En SellerProductController@store
public function store(Request $request): RedirectResponse
{
    // Validar y crear producto...
    
    // Subir imágenes
    $cloudinary = app(CloudinaryService::class);
    
    foreach ($request->file('images', []) as $i => $file) {
        $result = $cloudinary->uploadProductImage($file, $product->slug);
        
        ProductImage::create([
            'product_id' => $product->id,
            'url'        => $result['url'],
            'public_id'  => $result['public_id'],
            'is_cover'   => $i === 0,  // Primera imagen = portada
            'sort_order' => $i,
        ]);
    }
    
    // Actualizar stock del producto
    $product->increment('stock_count', $keysCount);
}
```

---

## Presets de Subida (Opcional)

En el dashboard de Cloudinary, crear Upload Presets:

| Preset | Folder | Transformación | Uso |
|---|---|---|---|
| `nexo_products` | `nexo-digital-store/products` | `w_1200, q_80` | Imágenes productos |
| `nexo_avatars` | `nexo-digital-store/avatars` | `w_256, h_256, c_thumb` | Avatares |
| `nexo_banners` | `nexo-digital-store/sellers/banners` | `w_1200, h_400, c_fill` | Banners vendedor |

---

## Consideraciones de Seguridad

- Las imágenes de **KYC** se suben con `type: 'private'` — solo accesibles con URL firmada
- No exponer `CLOUDINARY_API_SECRET` al frontend nunca
- Las URLs de Cloudinary con transformaciones son **inmutables** (no se reusan para diferentes recursos)
