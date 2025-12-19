# Módulo POS - Fase 1 Completada ✅

## Resumen

Se ha completado exitosamente la **Fase 1: Base de Datos y Modelos** del módulo POS (Punto de Venta) para el sistema Neo ERP.

## ✅ Lo que se implementó

### 1. Migraciones de Base de Datos (6 tablas)

#### `services` - Tabla de Servicios
- Gestión de servicios separada de productos
- Campos: código, nombre, descripción, duración, precio, IVA, comisión
- Campos para UI: color (hex), icono (Bootstrap Icons), orden de visualización
- Soporte para categorización

#### `pos_sessions` - Sesiones POS
- Control de sesiones de vendedores en POS
- Autenticación: PIN, RFID, o PIN+RFID (2FA)
- Tracking de actividad con timeout automático
- Estados: active, expired, closed
- Información de dispositivo y navegador

#### `sales_commissions` - Comisiones de Ventas
- Registro de comisiones por vendedor
- Soporte para productos Y servicios
- Cálculo automático de montos
- Estados: pending, paid
- Tracking de pagos con referencias

#### `sale_service_items` - Items de Servicio en Ventas
- Similar a `sale_items` pero para servicios
- No afecta inventario (servicios no tienen stock)
- Cálculo automático de IVA paraguayo
- Snapshot de comisión por item

#### Campos agregados a `users`
- `pos_pin` - PIN hasheado para acceso rápido al POS
- `rfid_code` - Código de tarjeta RFID única
- `pos_enabled` - Boolean para habilitar acceso POS
- `pos_require_rfid` - Requiere 2FA (PIN + RFID)
- `commission_percentage` - Porcentaje de comisión por defecto

#### Campos agregados a `sales`
- `pos_session_id` - FK a la sesión que creó la venta
- `tip_amount` - Monto de propina

---

### 2. Modelos Eloquent (4 modelos)

#### `Service` Model
**Ubicación:** `app/Models/Service.php`

**Características:**
- Usa trait `BelongsToTenant` para multi-tenancy
- Generación automática de códigos (SRV-00001, SRV-00002, etc.)
- Cálculo de IVA paraguayo (incluido en precio)
- Scopes útiles: `active()`, `popular()`, `search()`
- Atributos computados: `display_name`, `formatted_duration`

**Métodos principales:**
```php
generateCode($tenantId)          // Generar código único
calculateTax($amount)            // Calcular IVA incluido
calculateSubtotal($amount)       // Calcular sin IVA
```

#### `PosSession` Model
**Ubicación:** `app/Models/PosSession.php`

**Características:**
- Control de sesiones activas por vendedor
- Generación de tokens únicos de 64 caracteres
- Verificación de expiración por timeout
- Tracking de duración de sesión

**Métodos principales:**
```php
generateToken()                  // Token único aleatorio
updateActivity()                 // Actualizar timestamp
isExpired($timeoutMinutes)       // Verificar timeout
close()                          // Cerrar sesión
getActiveForUser($userId)        // Obtener sesión activa
createSession($user, ...)        // Crear nueva sesión
```

#### `SalesCommission` Model
**Ubicación:** `app/Models/SalesCommission.php`

**Características:**
- Cálculo automático de comisiones
- Relación polimórfica con productos/servicios
- Consultas por período y usuario
- Gestión de pagos

**Métodos principales:**
```php
calculateCommission($amount, $%)     // Calcular monto
markAsPaid($reference)               // Marcar como pagado
createFromSaleItem($item, ...)       // Desde producto
createFromServiceItem($item, ...)    // Desde servicio
getTotalPendingForUser($userId)      // Total pendiente
getTotalPaidForUser($userId, ...)    // Total pagado
```

#### `SaleServiceItem` Model
**Ubicación:** `app/Models/SaleServiceItem.php`

**Características:**
- Cálculo automático de valores (subtotal, IVA, total)
- Usa fórmula paraguaya de IVA
- Auto-cálculo en eventos `creating` y `updating`
- Factory method desde Service

**Métodos principales:**
```php
calculateValues()                    // Calcular subtotal/IVA/total
createFromService($sale, $service)   // Crear desde servicio
```

---

### 3. Modelo User Actualizado

**Ubicación:** `app/Models/User.php` (modificado)

**Campos agregados:** `pos_pin`, `rfid_code`, `pos_enabled`, `pos_require_rfid`, `commission_percentage`

**Nuevas relaciones:**
```php
posSessions()           // Todas las sesiones del usuario
activePosSession()      // Sesión activa actual
commissions()           // Todas las comisiones
```

**Métodos POS agregados:**
```php
// Autenticación
verifyPosPin($pin)               // Verificar PIN
setPosPin($pin)                  // Establecer PIN
verifyRfidCode($code)            // Verificar RFID
canUsePOS()                      // Puede usar POS
posRequires2FA()                 // Requiere PIN+RFID

// Sesiones
getActivePosSession()            // Obtener sesión activa
hasActivePosSession()            // Tiene sesión activa

// Comisiones
getPendingCommissions()          // Total pendiente
getEffectiveCommissionPercentage() // % comisión efectivo
```

---

### 4. Permisos Agregados

**Total nuevo: 10 permisos**

#### Módulo Servicios (4)
- `services.view` - Ver Servicios
- `services.create` - Crear Servicios
- `services.edit` - Editar Servicios
- `services.delete` - Eliminar Servicios

#### Módulo POS (2)
- `pos.use` - Usar POS
- `pos.history` - Ver Historial POS

#### Módulo Comisiones (4)
- `commissions.view` - Ver Comisiones (todas)
- `commissions.view-own` - Ver Comisiones Propias
- `commissions.pay` - Pagar Comisiones
- `commissions.report` - Ver Reportes de Comisiones

---

## 📊 Estadísticas

- **Migraciones creadas:** 6
- **Tablas nuevas:** 4
- **Tablas modificadas:** 2 (users, sales)
- **Modelos creados:** 4
- **Modelos modificados:** 1 (User)
- **Permisos agregados:** 10
- **Líneas de código:** ~1,200

---

## 🗄️ Estructura de Base de Datos

### Tabla `services`
```
- id
- tenant_id (FK → tenants)
- category_id (FK → categories)
- code (UNIQUE: SRV-00001)
- name
- description
- duration_minutes
- price
- tax_rate (0, 5, 10)
- commission_percentage
- color (#RRGGBB)
- icon (bi-scissors, bi-cut, etc.)
- sort_order
- is_active
- timestamps
```

### Tabla `pos_sessions`
```
- id
- tenant_id (FK → tenants)
- user_id (FK → users)
- session_token (UNIQUE, 64 chars)
- authentication_method (pin|rfid|pin+rfid)
- rfid_code
- terminal_identifier
- opened_at
- last_activity_at
- closed_at
- status (active|expired|closed)
- ip_address
- user_agent
- timestamps
```

### Tabla `sales_commissions`
```
- id
- tenant_id (FK → tenants)
- sale_id (FK → sales)
- user_id (FK → users - vendedor)
- item_type (product|service)
- item_id
- item_name (snapshot)
- quantity
- sale_amount
- commission_percentage
- commission_amount
- status (pending|paid)
- paid_at
- payment_reference
- timestamps
```

### Tabla `sale_service_items`
```
- id
- sale_id (FK → sales)
- service_id (FK → services)
- service_name (snapshot)
- quantity
- unit_price
- tax_rate
- subtotal
- tax_amount
- total
- commission_percentage (snapshot)
- timestamps
```

---

## 🔗 Relaciones de Modelos

```
User
├── posSessions (HasMany)
├── activePosSession (HasOne)
└── commissions (HasMany)

Service
├── category (BelongsTo)
└── saleServiceItems (HasMany)

PosSession
├── user (BelongsTo)
└── sales (HasMany)

SalesCommission
├── sale (BelongsTo)
├── user (BelongsTo)
└── item (MorphTo - Product o Service)

SaleServiceItem
├── sale (BelongsTo)
└── service (BelongsTo)
```

---

## ✅ Testing Verificado

Todas las migraciones se ejecutaron exitosamente:
```
✓ 2025_12_18_000001_create_services_table
✓ 2025_12_18_000002_create_pos_sessions_table
✓ 2025_12_18_000003_create_sales_commissions_table
✓ 2025_12_18_000004_create_sale_service_items_table
✓ 2025_12_18_000005_add_pos_fields_to_users_table
✓ 2025_12_18_000006_add_pos_fields_to_sales_table
```

Permisos seedeados correctamente:
```
✓ PermissionSeeder ejecutado
✓ 10 nuevos permisos agregados
✓ Total en sistema: 178 permisos
```

---

## 📋 Próximos Pasos (Fase 2)

### Servicios y Configuración
1. Crear `ServiceController` (CRUD completo)
2. Crear vistas de gestión de servicios
3. Agregar configuración de PIN/RFID en perfil de usuario
4. Seed de servicios de ejemplo

### Autenticación POS (Fase 3)
5. Crear `PosAuthController`
6. Crear middleware `CheckPosSession`
7. Crear vistas de autenticación (login, PIN, RFID)
8. Implementar lógica de sesiones con timeout

### Interfaz POS (Fase 4)
9. Crear layout especial para POS
10. Crear vista principal del POS
11. Implementar grid de servicios/productos
12. Implementar carrito flotante
13. Crear CSS optimizado para touch

---

## 📖 Documentación Técnica

### Convenciones de Código
- **Códigos de servicios:** `SRV-XXXXX` (5 dígitos)
- **Tokens de sesión:** 64 caracteres aleatorios
- **Timeout de sesión:** 10 minutos por defecto (configurable)
- **Fórmula IVA:** `IVA = Monto × tasa / (100 + tasa)` (Paraguay)

### Seguridad Implementada
- PINs hasheados con bcrypt
- RFID codes únicos por tenant
- Tokens de sesión generados con `Str::random(64)`
- Hidden fields en User model (pos_pin)
- Verificación de tenancy en todos los modelos

### Índices de Base de Datos
Todos los índices necesarios fueron creados para:
- Búsquedas por tenant_id
- Consultas de sesiones activas
- Búsquedas de servicios
- Reportes de comisiones

---

## 🎯 Decisiones de Diseño

1. **Servicios separados de Productos** ✅
   - Justificación: Permite campos específicos (duración, color, icono)
   - Beneficio: Mejor UX en POS, reportes diferenciados

2. **Autenticación flexible (PIN + RFID opcional)** ✅
   - Justificación: Adaptable a recursos del negocio
   - Beneficio: No requiere hardware RFID obligatoriamente

3. **Comisiones con porcentaje configurable** ✅
   - Justificación: Flexibilidad por vendedor
   - Beneficio: Puede variar por producto/servicio también

4. **Sesión persistente con timeout** ✅
   - Justificación: Balance entre seguridad y UX
   - Beneficio: Vendedor no re-autentica cada venta

---

## 👨‍💻 Autor

Implementación realizada para Neo ERP
Fecha: 18 de Diciembre, 2025
Versión: 1.0.0-phase1
