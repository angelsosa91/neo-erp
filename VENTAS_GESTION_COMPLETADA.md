# Módulo de Gestión de Ventas - Completado

## Resumen

Se ha completado la implementación del módulo de gestión de ventas que permite confirmar las pre-ventas creadas desde el POS, asignar clientes, y gestionar el ciclo completo de las ventas.

## Funcionalidades Implementadas

### 1. Listado de Ventas
**Vista:** `resources/views/sales/index.blade.php`

**Características:**
- Listado completo de ventas con DataGrid de EasyUI
- Filtros por estado (draft, confirmed, cancelled)
- Búsqueda por número de venta o cliente
- Columnas: Número, Fecha, Cliente, Totales por IVA, Estado, Pago, Vendedor
- Badges de colores para estados:
  - 🟢 **Confirmada** (verde)
  - 🟡 **Borrador** (gris)
  - 🔴 **Anulada** (rojo)

**Acciones disponibles:**
- ✅ Nueva Venta
- 👁️ Ver Detalle
- ✔️ Confirmar (solo borradores)
- ❌ Anular
- 🗑️ Eliminar (solo borradores)

### 2. Detalle de Venta
**Vista:** `resources/views/sales/detail.blade.php`

**Información mostrada:**
- Número de venta, fecha, cliente, estado
- Vendedor, tipo de venta (contado/crédito), forma de pago
- Vencimiento (si es a crédito)
- Cuenta por cobrar asociada (si existe)
- Asiento contable asociado (si existe)
- Notas adicionales

**Tabla de Items:**
- Columnas: Tipo, Descripción, Cantidad, Precio, IVA, Subtotal
- Badges para diferenciar:
  - 🔵 **Producto** (azul)
  - 🟦 **Servicio** (info/cyan)
- Muestra tanto productos como servicios

**Totales:**
- Total Exento
- Gravado 5% + IVA 5%
- Gravado 10% + IVA 10%
- **TOTAL GENERAL**

**Acciones contextuales:**
- 🔙 Volver al listado
- 👤 Asignar Cliente (solo si es borrador sin cliente)
- ✅ Confirmar (solo borradores)
- ❌ Anular (excepto ya anuladas)
- 📄 Ver PDF
- ⬇️ Descargar PDF
- 🖨️ Imprimir

### 3. Asignación de Cliente
**Modal:** Dialog de EasyUI con combobox

**Funcionalidad:**
- Solo disponible para ventas en estado `draft` sin cliente asignado
- Búsqueda inteligente de clientes
- Selección mediante combobox
- Actualización AJAX sin recargar página
- Validación de cliente requerido

## Backend - SaleController

### Métodos Implementados/Actualizados

#### 1. `index()` - Listado de ventas
**Ruta:** `GET /sales`
**Retorna:** Vista `sales.index`

#### 2. `data(Request $request)` - Datos para DataGrid
**Ruta:** `GET /sales/data`
**Parámetros:**
- `page`: Página actual
- `rows`: Filas por página
- `sort`: Campo de ordenamiento
- `order`: Dirección (asc/desc)
- `search`: Término de búsqueda

**Retorna:** JSON con ventas paginadas

#### 3. `show(Sale $sale)` - Detalle JSON
**Ruta:** `GET /sales/{sale}`
**Retorna:** JSON con venta e items (productos + servicios)

**Cambio importante:**
```php
// Ahora carga tanto productos como servicios
$sale->load(['customer', 'user', 'items.product', 'serviceItems.service']);

// Combina ambos tipos de items en la respuesta
$items = $productItems->merge($serviceItems)
```

#### 4. `detail(Sale $sale)` - Vista de detalle
**Ruta:** `GET /sales/{sale}/detail`
**Retorna:** Vista `sales.detail`

#### 5. `updateCustomer(Request $request, Sale $sale)` - **NUEVO**
**Ruta:** `POST /sales/{sale}/update-customer`

**Validaciones:**
- Solo ventas en estado `draft`
- `customer_id` requerido y debe existir

**Proceso:**
1. Valida que la venta esté en borrador
2. Asigna el cliente
3. Guarda y retorna venta actualizada

```php
public function updateCustomer(Request $request, Sale $sale)
{
    if ($sale->status !== 'draft') {
        return response()->json([
            'errors' => ['general' => ['Solo se puede asignar cliente a ventas en borrador']]
        ], 422);
    }

    $validated = $request->validate([
        'customer_id' => 'required|exists:customers,id',
    ]);

    $sale->customer_id = $validated['customer_id'];
    $sale->save();

    return response()->json([
        'success' => true,
        'message' => 'Cliente asignado correctamente',
        'sale' => $sale->load('customer')
    ]);
}
```

#### 6. `confirm(Sale $sale)` - Confirmar venta - **ACTUALIZADO**
**Ruta:** `POST /sales/{sale}/confirm`

**Cambio importante:**
Ahora usa el método `confirm()` del modelo Sale que maneja tanto productos como servicios:

```php
// ANTES: Solo productos
foreach ($sale->items as $item) {
    if ($item->product && $item->product->track_stock) {
        $item->product->decrement('stock', $item->quantity);
    }
}
$sale->status = 'confirmed';
$sale->save();

// AHORA: Delega al modelo
$sale->load(['items.product', 'serviceItems']);
$sale->confirm();
```

**Proceso completo:**
1. Valida que esté en estado `draft`
2. Verifica caja abierta (si es efectivo)
3. Llama a `$sale->confirm()` que:
   - Verifica stock disponible
   - Descuenta stock de productos
   - Cambia estado a `confirmed`
4. Crea cuenta por cobrar (si es crédito)
5. Registra en caja (si es efectivo)
6. Registra transacción bancaria (si es transferencia)
7. Crea asiento contable

#### 7. `cancel(Sale $sale)` - Anular venta
**Ruta:** `POST /sales/{sale}/cancel`

**Proceso:**
- Devuelve stock (si estaba confirmada)
- Reversa movimiento de caja
- Cancela transacción bancaria
- Reversa asiento contable
- Cambia estado a `cancelled`

#### 8. `destroy(Sale $sale)` - Eliminar venta
**Ruta:** `DELETE /sales/{sale}`
**Solo permite eliminar ventas en estado `draft`**

## Modelo Sale

### Método `confirm()` - Implementado anteriormente
**Ubicación:** `app/Models/Sale.php:179-207`

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
                    throw new \Exception("Stock insuficiente para {$item->product_name}");
                }
                $item->product->decrement('stock', $item->quantity);
            }
        }

        // Cambiar estado a confirmada
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

**Ventajas:**
- Maneja productos automáticamente
- Los servicios no requieren descuento de stock
- Valida stock antes de descontar
- Usa transacciones para integridad
- Lanza excepciones claras

## Rutas Agregadas

**Archivo:** `routes/web.php`

```php
// Nueva ruta para asignar cliente
Route::post('/sales/{sale}/update-customer', [SaleController::class, 'updateCustomer'])
    ->name('sales.update-customer');
```

**Rutas existentes que ya funcionan:**
- `GET /sales` - Listado
- `GET /sales/data` - Datos JSON
- `GET /sales/{sale}/detail` - Detalle vista
- `POST /sales/{sale}/confirm` - Confirmar
- `POST /sales/{sale}/cancel` - Anular
- `DELETE /sales/{sale}` - Eliminar

## Flujo Completo: POS → Confirmación

### Paso 1: Vendedor en POS
```
1. Vendedor inicia sesión en POS
2. Agrega productos/servicios al carrito
3. Selecciona método de pago
4. Confirma → Crea PRE-VENTA (draft)
```

**Resultado:**
- `Sale` creado con `status = 'draft'`
- `customer_id = NULL`
- Stock **NO** descontado
- Items guardados en `sale_items` y `sale_service_items`

### Paso 2: Gestión de Venta (Admin/Encargado)
```
1. Navegar a Ventas → Ver listado
2. Identificar venta en estado "Borrador"
3. Hacer clic en "Ver Detalle"
4. Asignar cliente (botón "Asignar")
5. Verificar items y totales
6. Hacer clic en "Confirmar"
```

**Resultado:**
- Cliente asignado
- Stock descontado
- `status = 'confirmed'`
- Se crean registros contables:
  - Cuenta por cobrar (si es crédito)
  - Movimiento de caja (si es efectivo)
  - Transacción bancaria (si es transferencia)
  - Asiento contable

### Paso 3: Post-Confirmación
- Generar PDF de factura
- Imprimir comprobante
- Registrar pagos (si es crédito)
- Crear nota de crédito (si hay devolución)

## Estados de una Venta

### 🟡 `draft` (Borrador)
**Características:**
- Estado inicial
- Stock NO descontado
- Puede editarse
- Puede eliminarse
- Puede asignarse cliente
- Puede confirmarse

**Acciones permitidas:**
- Asignar/cambiar cliente
- Confirmar → `confirmed`
- Eliminar (DELETE)
- Anular → `cancelled`

### 🟢 `confirmed` (Confirmada)
**Características:**
- Stock descontado
- Genera comprobante fiscal
- Afecta inventario
- Afecta contabilidad
- NO puede editarse
- NO puede eliminarse

**Acciones permitidas:**
- Ver/descargar PDF
- Crear nota de crédito
- Anular → `cancelled` (reversa stock y contabilidad)

### 🔴 `cancelled` (Anulada)
**Características:**
- Stock revertido (si estaba confirmada)
- Movimientos contables revertidos
- NO puede modificarse
- NO puede eliminarse
- Registro histórico

**Acciones permitidas:**
- Solo visualización

## Diferencias: Productos vs Servicios

### Productos
- Tienen `stock`
- Se descuenta stock al confirmar
- Se guardan en `sale_items`
- Relación con `products`
- Badge azul en detalle

### Servicios
- NO tienen stock
- NO se descuenta nada al confirmar
- Se guardan en `sale_service_items`
- Relación con `services`
- Badge cyan en detalle
- Incluyen `commission_percentage`

### Totales (Ambos)
- Aplica misma fórmula de IVA paraguayo
- Se combinan en `calculateTotals()` del modelo
- Separados por tasa: 0%, 5%, 10%

## Validaciones Implementadas

### Al Asignar Cliente
- ✅ Venta debe estar en `draft`
- ✅ Cliente debe existir en la base de datos

### Al Confirmar Venta
- ✅ Venta debe estar en `draft`
- ✅ Verificar stock disponible de TODOS los productos
- ✅ Verificar caja abierta (si es efectivo)
- ✅ Verificar cuenta bancaria configurada (si es transferencia)
- ✅ Usar transacciones de BD

### Al Anular Venta
- ✅ No puede estar ya anulada
- ✅ Reversar stock correctamente
- ✅ Reversar movimientos contables

### Al Eliminar Venta
- ✅ Solo permitir si está en `draft`
- ✅ No afecta stock (nunca fue descontado)

## Archivos Modificados/Creados

### Backend
1. **app/Http/Controllers/SaleController.php**
   - Línea 207-230: Método `updateCustomer()` agregado
   - Línea 255-257: Método `confirm()` actualizado para servicios
   - Línea 185-224: Método `show()` actualizado para incluir servicios

2. **app/Models/Sale.php**
   - Línea 176-207: Método `confirm()` (implementado previamente)
   - Línea 217-223: Método `canBeConfirmed()`

### Frontend
3. **resources/views/sales/detail.blade.php**
   - Línea 51-58: Botón "Asignar Cliente" agregado
   - Línea 135-170: Tabla actualizada para mostrar productos Y servicios
   - Línea 266-318: Función JavaScript `assignCustomer()` agregada
   - Línea 321-336: Modal de asignación de cliente agregado

### Rutas
4. **routes/web.php**
   - Línea 204: Ruta `sales.update-customer` agregada

## Testing Recomendado

### Test 1: Flujo Completo de Pre-Venta a Confirmada
1. Crear pre-venta desde POS con productos y servicios
2. Ir a Ventas → Ver detalle
3. Asignar cliente
4. Confirmar venta
5. Verificar:
   - Stock descontado solo de productos
   - Estado cambiado a `confirmed`
   - Cliente asignado correctamente
   - Totales calculados correctamente

### Test 2: Confirmación con Stock Insuficiente
1. Crear pre-venta con producto
2. Reducir stock del producto manualmente
3. Intentar confirmar
4. Verificar mensaje de error
5. Verificar que venta sigue en `draft`

### Test 3: Asignación de Cliente
1. Crear pre-venta sin cliente
2. Ir a detalle
3. Click en "Asignar Cliente"
4. Seleccionar cliente del combobox
5. Verificar que se asigna correctamente
6. Intentar asignar cliente a venta confirmada
7. Verificar que no permite

### Test 4: Anulación de Venta Confirmada
1. Confirmar una venta (descontar stock)
2. Verificar stock descontado
3. Anular la venta
4. Verificar stock devuelto
5. Verificar estado `cancelled`

## Consideraciones de Seguridad

### Multi-tenancy
- Todas las consultas filtran por `tenant_id`
- Validación en controlador antes de modificar
- Previene acceso cruzado entre tenants

### Permisos (Futuro)
Se recomienda agregar permisos específicos:
- `sales.view` - Ver ventas
- `sales.create` - Crear ventas
- `sales.confirm` - Confirmar pre-ventas
- `sales.cancel` - Anular ventas
- `sales.delete` - Eliminar borradores

### Validación de Estado
- No se puede confirmar venta ya confirmada
- No se puede eliminar venta confirmada
- No se puede anular venta ya anulada
- Validaciones en el controlador Y en el modelo

## Próximas Mejoras Sugeridas

### 1. Edición de Pre-Ventas
Permitir editar items de ventas en borrador:
- Agregar/quitar productos
- Cambiar cantidades
- Modificar precios

### 2. Notificaciones
- Email al confirmar venta
- Alerta cuando stock bajo
- Notificación a vendedor cuando se confirma su venta

### 3. Reportes
- Ventas por vendedor
- Ventas por período
- Productos más vendidos vs Servicios más vendidos
- Comisiones por vendedor (servicios)

### 4. Exportación
- Excel de ventas
- CSV para contabilidad
- PDF de listado de ventas

### 5. Filtros Avanzados
- Rango de fechas
- Por vendedor
- Por estado
- Por método de pago
- Por cliente

## Notas Importantes

### IVA Paraguayo
Se mantiene la fórmula de IVA incluido:
```
IVA = Total × Tasa / (100 + Tasa)
```

Ejemplo: Precio ₲110,000 con IVA 10%
- IVA = 110,000 × 10 / 110 = ₲10,000
- Subtotal = 110,000 - 10,000 = ₲100,000

### Servicios no afectan Stock
Los servicios solo se registran para:
- Facturación
- Cálculo de totales
- Cálculo de comisiones
- Registro histórico

No requieren verificación de disponibilidad ni descuento.

### Integración Contable
Al confirmar venta se crea automáticamente:
- Asiento contable (débito/crédito)
- Cuenta por cobrar (si es crédito)
- Movimiento de caja (si es efectivo)
- Transacción bancaria (si es transferencia)

Esto mantiene sincronizada la contabilidad con las ventas.
