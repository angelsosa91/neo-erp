# POS - Sistema de Pre-Ventas (Borradores)

## Cambio Implementado

El POS ha sido modificado para funcionar como un **sistema de pre-ventas** en lugar de ventas confirmadas directamente.

## Flujo de Pre-Venta

### 1. Vendedor en el POS
El vendedor utiliza el POS para:
- Seleccionar servicios y/o productos
- Agregar items al carrito
- Especificar cantidades
- Seleccionar método de pago
- **Crear una PRE-VENTA (borrador)**

### 2. Estado de la Venta Creada
Cuando el vendedor confirma desde el POS:
- Se crea un registro en `sales` con `status = 'draft'`
- Se genera un `sale_number` único
- Se calculan todos los totales e IVA
- **NO se descuenta stock** de productos
- `customer_id` queda en NULL (se asignará después)

### 3. Confirmación de la Venta
La venta debe confirmarse desde el **módulo de Ventas** (que aún debe implementarse) donde:
- Se puede asignar un cliente
- Se pueden modificar datos si es necesario
- Al confirmar:
  - Estado cambia de `draft` → `confirmed`
  - **Recién ahí se descuenta el stock** de productos
  - Se puede generar factura/comprobante
  - Se puede registrar el pago efectivo

## Ventajas de este Enfoque

### ✅ Separación de Responsabilidades
- **Vendedor POS**: Crea pedidos rápidamente
- **Administrador/Encargado**: Revisa, asigna cliente, y confirma

### ✅ Control de Stock
- El stock NO se descuenta automáticamente
- Permite revisar antes de comprometer inventario
- Evita errores de vendedores

### ✅ Flexibilidad
- Se puede cancelar una pre-venta sin afectar stock
- Se puede modificar antes de confirmar
- Se puede asignar cliente después

### ✅ Proceso Formal
- Más apropiado para negocios que requieren:
  - Facturación con datos de cliente
  - Aprobación de ventas
  - Control de inventario estricto

## Cambios Técnicos Realizados

### 1. Backend - PosAuthController.php

**Método `storeSale()` modificado:**

```php
// ANTES
'status' => 'confirmed',
// Stock se descontaba inmediatamente

// AHORA
'status' => 'draft', // Pre-venta
// Stock NO se descuenta
```

**Línea 338:** Estado de venta cambiado a `draft`
**Líneas 368-370:** Verificación de stock pero sin descuento
**Líneas 386-387:** Comentario explicando que no se descuenta stock
**Línea 400:** Mensaje actualizado indicando que es pre-venta

### 2. Modelo Sale.php

**Nuevos métodos agregados:**

#### `confirm(): bool`
Confirma una pre-venta:
- Valida que esté en estado `draft`
- Descuenta stock de productos con `track_stock = true`
- Cambia estado a `confirmed`
- Usa transacciones para integridad

```php
public function confirm(): bool
{
    if ($this->status !== 'draft') {
        throw new \Exception('Solo se pueden confirmar ventas en estado borrador');
    }

    \DB::beginTransaction();
    try {
        // Descontar stock de los productos
        foreach ($this->items as $item) {
            if ($item->product && $item->product->track_stock) {
                if ($item->product->stock < $item->quantity) {
                    throw new \Exception("Stock insuficiente");
                }
                $item->product->decrement('stock', $item->quantity);
            }
        }

        $this->status = 'confirmed';
        $this->save();

        \DB::commit();
        return true;
    } catch (\Exception $e) {
        \DB::rollBack();
        throw $e;
    }
}
```

#### `canBeConfirmed(): bool`
Verifica si la venta puede confirmarse:
```php
public function canBeConfirmed(): bool
{
    return $this->status === 'draft';
}
```

### 3. Frontend - pos/index.blade.php

**Mensaje actualizado (línea 938):**
```javascript
alert('Pre-venta creada exitosamente!\n\nNúmero: ' + response.sale.sale_number +
      '\nTotal: ₲ ' + formatNumber(response.sale.total) +
      '\n\nEstado: BORRADOR\n\n' +
      'Debe confirmarse desde el módulo de Ventas para descontar stock.');
```

## Estados de una Venta

### `draft` (Borrador)
- Estado inicial cuando se crea desde el POS
- Stock **NO** descontado
- Puede editarse y cancelarse sin consecuencias
- No genera comprobante fiscal

### `confirmed` (Confirmada)
- Estado después de confirmar
- Stock **descontado**
- Genera comprobante fiscal
- Afecta inventario y contabilidad

### `cancelled` (Anulada)
- Venta cancelada
- Si estaba confirmada, debe revertirse stock
- No afecta inventario ni contabilidad

## Flujo Completo

```
┌─────────────────┐
│  VENDEDOR POS   │
└────────┬────────┘
         │
         │ Crea carrito y confirma
         ▼
┌─────────────────────────┐
│  PRE-VENTA CREADA       │
│  Status: draft          │
│  Stock: NO descontado   │
│  Cliente: NULL          │
└────────┬────────────────┘
         │
         │ Módulo de Ventas
         ▼
┌─────────────────────────┐
│  REVISAR PRE-VENTA      │
│  - Asignar cliente      │
│  - Verificar datos      │
│  - Modificar si necesario│
└────────┬────────────────┘
         │
         │ Confirmar
         ▼
┌─────────────────────────┐
│  VENTA CONFIRMADA       │
│  Status: confirmed      │
│  Stock: DESCONTADO      │
│  Cliente: Asignado      │
│  Factura: Generada      │
└─────────────────────────┘
```

## Próximos Pasos Necesarios

### 1. Módulo de Gestión de Ventas
Se necesita crear un módulo que permita:
- **Listar todas las ventas** (filtrar por estado: draft, confirmed, cancelled)
- **Ver detalle de pre-ventas**
- **Asignar cliente** a la pre-venta
- **Botón "Confirmar Venta"** que ejecuta `$sale->confirm()`
- **Editar pre-ventas** antes de confirmar
- **Cancelar pre-ventas** (cambiar a estado cancelled)

### 2. Funcionalidad de Confirmación
Crear endpoint en SaleController:

```php
public function confirm(Sale $sale)
{
    try {
        $sale->confirm();

        return response()->json([
            'success' => true,
            'message' => 'Venta confirmada exitosamente'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 400);
    }
}
```

### 3. Interfaz de Confirmación
Vista para confirmar ventas que incluya:
- Búsqueda/selección de cliente
- Resumen de la venta (items, totales)
- Stock actual vs cantidad solicitada
- Método de pago confirmado
- Botón de confirmar

### 4. Reversión de Stock al Cancelar
Si se cancela una venta confirmada, debe revertirse el stock:

```php
public function cancel(): bool
{
    if ($this->status === 'cancelled') {
        throw new \Exception('La venta ya está cancelada');
    }

    \DB::beginTransaction();
    try {
        // Si estaba confirmada, reversar stock
        if ($this->status === 'confirmed') {
            foreach ($this->items as $item) {
                if ($item->product && $item->product->track_stock) {
                    $item->product->increment('stock', $item->quantity);
                }
            }
        }

        $this->status = 'cancelled';
        $this->save();

        \DB::commit();
        return true;
    } catch (\Exception $e) {
        \DB::rollBack();
        throw $e;
    }
}
```

## Validaciones Importantes

### Al Confirmar Pre-Venta
- ✅ Verificar que esté en estado `draft`
- ✅ Verificar stock disponible de TODOS los productos
- ✅ Validar que el cliente esté asignado (si es requerido)
- ✅ Usar transacción de base de datos

### Al Cancelar Venta Confirmada
- ✅ Reversar stock de productos
- ✅ Registrar motivo de cancelación
- ✅ Notificar a contabilidad (si aplica)
- ✅ Usar transacción de base de datos

## Consideraciones de Negocio

### ¿Cuándo usar Pre-Ventas?
- Negocios con facturación formal
- Control estricto de inventario
- Aprobación de ventas por supervisor
- Ventas B2B (empresa a empresa)
- Ventas con crédito

### ¿Cuándo NO usar Pre-Ventas?
- Ventas al mostrador rápidas (retail)
- Cliente anónimo/consumidor final
- Pago inmediato sin crédito
- No requiere factura con datos

## Archivos Modificados

1. **app/Http/Controllers/PosAuthController.php**
   - Línea 338: Estado cambiado a `draft`
   - Líneas 386-387: Comentario sobre no descontar stock
   - Línea 400: Mensaje actualizado

2. **app/Models/Sale.php**
   - Líneas 176-207: Método `confirm()` agregado
   - Líneas 217-223: Método `canBeConfirmed()` agregado

3. **resources/views/pos/index.blade.php**
   - Línea 938: Mensaje de pre-venta creada

## Notas de Migración

Si ya tienes ventas creadas desde el POS anterior:
- Están con `status = 'confirmed'` y stock descontado
- No requieren migración
- Las nuevas ventas del POS serán `draft`
- Conviven ambos tipos sin problemas

## Ejemplo de Uso

```php
// Crear pre-venta desde POS
$sale = Sale::create([
    'status' => 'draft',
    // ... otros campos
]);

// Listar pre-ventas pendientes
$preventas = Sale::where('status', 'draft')->get();

// Asignar cliente y confirmar
$sale->customer_id = $customerId;
$sale->save();
$sale->confirm(); // Descuenta stock y cambia a confirmed

// O cancelar sin afectar stock
$sale->status = 'cancelled';
$sale->save();
```

## Testing Recomendado

### Caso 1: Pre-venta con stock suficiente
- ✅ Crear pre-venta
- ✅ Verificar status = draft
- ✅ Verificar stock NO descontado
- ✅ Confirmar pre-venta
- ✅ Verificar status = confirmed
- ✅ Verificar stock descontado

### Caso 2: Pre-venta con stock insuficiente
- ✅ Crear pre-venta
- ✅ Reducir stock del producto
- ✅ Intentar confirmar
- ✅ Verificar error de stock insuficiente
- ✅ Verificar status sigue siendo draft

### Caso 3: Cancelar pre-venta
- ✅ Crear pre-venta
- ✅ Cancelar (status = cancelled)
- ✅ Verificar stock NO afectado

### Caso 4: Confirmar venta ya confirmada
- ✅ Crear y confirmar pre-venta
- ✅ Intentar confirmar nuevamente
- ✅ Verificar error (solo draft puede confirmarse)
