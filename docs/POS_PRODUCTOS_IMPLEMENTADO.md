# POS - Soporte para Productos Implementado

## Resumen
Se implementó la capacidad del POS para vender tanto **servicios** como **productos**, unificando la interfaz para permitir la venta de ambos tipos de items desde el punto de venta.

## Cambios Implementados

### 1. Backend - Endpoint Unificado

#### Nuevo Endpoint: `/pos/items`
**Archivo:** `app/Http/Controllers/PosAuthController.php`

**Método:** `items(Request $request)`

**Funcionalidad:**
- Retorna servicios activos ordenados por `sort_order` y nombre
- Retorna productos activos con stock > 0 ordenados por nombre
- Combina ambos en un array unificado
- Cada item incluye un campo `type` ('service' o 'product')

**Estructura de respuesta:**
```json
[
  {
    "id": 1,
    "type": "service",
    "name": "Corte de Cabello",
    "price": 50000,
    "tax_rate": 10,
    "color": "#667eea",
    "icon": "bi-scissors",
    "formatted_duration": "30 min",
    "stock": null
  },
  {
    "id": 5,
    "type": "product",
    "name": "Shampoo Profesional",
    "price": 85000,
    "tax_rate": 10,
    "color": null,
    "icon": null,
    "formatted_duration": null,
    "stock": 25
  }
]
```

### 2. Backend - Procesamiento de Ventas Actualizado

#### Método Actualizado: `storeSale(Request $request)`
**Archivo:** `app/Http/Controllers/PosAuthController.php`

**Validación actualizada:**
```php
'items.*.type' => 'required|string|in:service,product',
'items.*.id' => 'required|integer',
```

**Funcionalidad:**
- Procesa items de tipo `service` creando registros en `sale_service_items`
- Procesa items de tipo `product` creando registros en `sale_items`
- Verifica stock disponible para productos con `track_stock = true`
- Descuenta automáticamente el stock de productos vendidos
- Valida que los items pertenezcan al tenant del usuario
- Calcula totales combinando items de productos y servicios

**Verificaciones de seguridad:**
- Validación de tenant para prevenir acceso cruzado
- Verificación de stock antes de procesar la venta
- Transacciones de base de datos con rollback en caso de error

### 3. Frontend - Interfaz Actualizada

#### Cambios en `resources/views/pos/index.blade.php`

**1. Carga de Items:**
```javascript
// Cambiado de /services/popular a /pos/items
url: '{{ route('pos.items') }}'
```

**2. Visualización de Items:**
- Los productos muestran icono de caja (`bi-box-seam`) con color azul (#3498db)
- Los servicios mantienen su icono y color personalizados
- Los productos muestran badge "Producto" y cantidad de stock
- Los servicios muestran duración si está disponible

**3. Carrito de Compras:**
```javascript
cart.push({
    id: service.id,
    type: service.type,      // NUEVO: Tipo de item
    name: service.name,
    price: parseFloat(service.price),
    tax_rate: parseInt(service.tax_rate),
    stock: service.stock || null,  // NUEVO: Stock del producto
    quantity: 1
});
```

**4. Envío de Venta:**
```javascript
items: cart.map(item => ({
    type: item.type,         // NUEVO: Incluye tipo
    id: item.id,             // NUEVO: ID genérico (product_id o service_id)
    quantity: item.quantity,
    unit_price: item.price,
    tax_rate: item.tax_rate
}))
```

### 4. Rutas

#### Nueva Ruta
**Archivo:** `routes/web.php`

```php
Route::get('/pos/items', [PosAuthController::class, 'items'])->name('pos.items');
```

**Nota:** La ruta `/services/popular` se mantiene por compatibilidad pero está marcada como DEPRECATED.

## Impacto en la Base de Datos

### Tablas Afectadas

1. **`sale_items`** (Productos)
   - Se crean registros cuando se venden productos
   - Campos: `product_id`, `product_name`, `quantity`, `unit_price`, `tax_rate`

2. **`sale_service_items`** (Servicios)
   - Se crean registros cuando se venden servicios
   - Campos: `service_id`, `service_name`, `quantity`, `unit_price`, `tax_rate`, `commission_percentage`

3. **`products`**
   - Se descuenta el stock automáticamente cuando `track_stock = true`
   - Decrementación: `$product->decrement('stock', $quantity)`

## Lógica de Negocio

### Control de Stock
- Solo se verifica stock para productos con `track_stock = true`
- Si el stock es insuficiente, la venta se rechaza con error específico
- El stock se descuenta inmediatamente al confirmar la venta
- La operación es transaccional (se revierte todo si hay error)

### Cálculo de Totales
El modelo `Sale` combina automáticamente items de productos y servicios:
```php
$allItems = $this->items->merge($this->serviceItems);
```

Esto permite calcular correctamente:
- Subtotales por tasa de IVA (0%, 5%, 10%)
- IVA total de la venta
- Total general

### IVA Incluido (Paraguay)
Se mantiene la fórmula paraguaya para ambos tipos de items:
```
IVA = Total × Tasa / (100 + Tasa)
```

## Interfaz de Usuario

### Características Visuales

1. **Servicios:**
   - Color personalizado por servicio
   - Icono personalizado
   - Muestra duración del servicio
   - No muestra stock

2. **Productos:**
   - Color azul (#3498db) por defecto
   - Icono de caja (bi-box-seam)
   - Badge "Producto"
   - Muestra cantidad de stock disponible
   - No muestra duración

### Búsqueda
La búsqueda funciona para ambos tipos de items buscando en:
- Nombre
- Código
- Descripción (si existe)

## Seguridad

### Validaciones Implementadas

1. **Multi-tenancy:**
   - Verificación de `tenant_id` en servicios y productos
   - Previene acceso cruzado entre tenants

2. **Stock:**
   - Validación de stock disponible antes de vender
   - Previene ventas con stock negativo

3. **Tipos de Item:**
   - Validación estricta del campo `type` (solo 'service' o 'product')
   - Procesamiento separado según tipo

4. **Transacciones:**
   - Uso de `DB::beginTransaction()` y `DB::commit()`
   - Rollback automático en caso de error
   - Garantiza integridad de datos

## Compatibilidad

### Retrocompatibilidad
- El endpoint `/services/popular` sigue funcionando para sistemas que aún lo usen
- Marcado como DEPRECATED en el código
- Se recomienda migrar a `/pos/items` para nuevas integraciones

### Migración
No se requieren migraciones de base de datos. Los cambios son:
- Nuevos endpoints
- Lógica de negocio mejorada
- Interfaz actualizada

## Testing Recomendado

### Casos de Prueba

1. **Venta de Solo Servicios:**
   - Agregar múltiples servicios al carrito
   - Procesar venta
   - Verificar que se crean registros en `sale_service_items`

2. **Venta de Solo Productos:**
   - Agregar productos con stock
   - Procesar venta
   - Verificar descuento de stock
   - Verificar registros en `sale_items`

3. **Venta Mixta:**
   - Agregar servicios y productos
   - Procesar venta
   - Verificar registros en ambas tablas
   - Verificar cálculo correcto de totales

4. **Stock Insuficiente:**
   - Intentar vender producto sin stock suficiente
   - Verificar mensaje de error
   - Verificar que no se descuenta stock

5. **Multi-tenancy:**
   - Intentar acceder a productos de otro tenant
   - Verificar rechazo de la operación

## Próximas Mejoras Sugeridas

1. **Validación de Stock en Tiempo Real:**
   - Mostrar advertencia en el carrito si stock es bajo
   - Deshabilitar botón de agregar si no hay stock

2. **Filtros en POS:**
   - Filtrar solo servicios
   - Filtrar solo productos
   - Filtrar por categoría

3. **Información Adicional:**
   - Mostrar código de producto/servicio
   - Mostrar categoría
   - Preview de imagen (si se implementan imágenes)

4. **Historial de Ventas:**
   - Ver detalle de productos vs servicios vendidos
   - Reportes separados por tipo de item

## Archivos Modificados

1. `app/Http/Controllers/PosAuthController.php`
   - Agregado método `items()`
   - Actualizado método `storeSale()`
   - Agregados imports: Product, Sale, Service

2. `resources/views/pos/index.blade.php`
   - Actualizado `loadServices()` para usar nuevo endpoint
   - Actualizado `renderServices()` para mostrar productos
   - Actualizado `addToCart()` para manejar tipos de items
   - Actualizado `processSale()` para enviar tipo de item

3. `routes/web.php`
   - Agregada ruta `GET /pos/items`
   - Marcada ruta `/services/popular` como DEPRECATED

## Notas Técnicas

- Los productos usan el campo `sale_price` (vs servicios que usan `price`)
- La validación de stock solo aplica si `track_stock = true`
- El cálculo de IVA es idéntico para productos y servicios
- Las comisiones solo aplican a servicios (no a productos)
