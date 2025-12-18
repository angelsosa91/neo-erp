# Implementación del Sistema de Permisos

## ✅ Componentes Implementados

### 1. Middleware `CheckPermission`
Ubicación: `app/Http/Middleware/CheckPermission.php`

**Características:**
- Verifica si el usuario tiene el permiso requerido
- Super admin tiene acceso a todo automáticamente
- Retorna JSON para peticiones AJAX
- Redirige con mensaje para peticiones normales

### 2. Permisos Actualizados
Se ejecutó el seeder con **168 permisos** agrupados en **22 módulos**:

- usuarios
- roles
- clientes
- proveedores
- productos
- categorias
- ventas
- notas_credito
- remisiones
- compras
- gastos
- inventario
- cuentas_cobrar
- cuentas_pagar
- caja
- bancos
- cuentas_bancarias
- transacciones_bancarias
- cheques
- conciliacion_bancaria (✨ NUEVO)
- contabilidad
- asientos_contables
- libro_mayor
- estados_financieros
- reportes
- configuracion

### 3. Middleware Registrado
El middleware está registrado en `bootstrap/app.php` con el alias `permission`.

---

## 📖 Cómo Aplicar Permisos a las Rutas

### Opción 1: Por Ruta Individual
```php
Route::get('/users', [UserController::class, 'index'])
    ->middleware('permission:users.view')
    ->name('users.index');
```

### Opción 2: Por Grupo de Rutas
```php
Route::middleware(['auth', 'permission:users.view'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/data', [UserController::class, 'data'])->name('users.data');
});
```

### Opción 3: Múltiples Permisos por Módulo
```php
// Usuarios - Solo lectura
Route::middleware('permission:users.view')->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/data', [UserController::class, 'data'])->name('users.data');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
});

// Usuarios - Crear
Route::post('/users', [UserController::class, 'store'])
    ->middleware('permission:users.create')
    ->name('users.store');

// Usuarios - Editar
Route::put('/users/{user}', [UserController::class, 'update'])
    ->middleware('permission:users.edit')
    ->name('users.update');

// Usuarios - Eliminar
Route::delete('/users/{user}', [UserController::class, 'destroy'])
    ->middleware('permission:users.delete')
    ->name('users.destroy');
```

---

## 🎯 Ejemplo Implementado: Conciliación Bancaria

Las rutas de conciliación bancaria ya están listas para aplicar permisos:

```php
// Ver conciliaciones
Route::middleware('permission:bank-reconciliations.view')->group(function () {
    Route::get('/bank-reconciliations', [BankReconciliationController::class, 'index'])
        ->name('bank-reconciliations.index');
    Route::get('/bank-reconciliations/data', [BankReconciliationController::class, 'data'])
        ->name('bank-reconciliations.data');
    Route::get('/bank-reconciliations/{id}', [BankReconciliationController::class, 'show'])
        ->name('bank-reconciliations.show');
});

// Crear conciliaciones
Route::middleware('permission:bank-reconciliations.create')->group(function () {
    Route::get('/bank-reconciliations/create', [BankReconciliationController::class, 'create'])
        ->name('bank-reconciliations.create');
    Route::post('/bank-reconciliations', [BankReconciliationController::class, 'store'])
        ->name('bank-reconciliations.store');
});

// Editar conciliaciones
Route::middleware('permission:bank-reconciliations.edit')->group(function () {
    Route::get('/bank-reconciliations/{id}/edit', [BankReconciliationController::class, 'edit'])
        ->name('bank-reconciliations.edit');
    Route::put('/bank-reconciliations/{id}', [BankReconciliationController::class, 'update'])
        ->name('bank-reconciliations.update');
});

// Publicar conciliaciones
Route::post('/bank-reconciliations/{id}/post', [BankReconciliationController::class, 'post'])
    ->middleware('permission:bank-reconciliations.post')
    ->name('bank-reconciliations.post');

// Cancelar conciliaciones
Route::post('/bank-reconciliations/{id}/cancel', [BankReconciliationController::class, 'cancel'])
    ->middleware('permission:bank-reconciliations.cancel')
    ->name('bank-reconciliations.cancel');

// Eliminar conciliaciones
Route::delete('/bank-reconciliations/{id}', [BankReconciliationController::class, 'destroy'])
    ->middleware('permission:bank-reconciliations.delete')
    ->name('bank-reconciliations.delete');
```

---

## ⚙️ Configuración Inicial de Roles

### Super Admin (Ya existe)
El rol `super-admin` tiene acceso completo automáticamente gracias al método `isSuperAdmin()` en el User model.

### Ejemplo: Crear Rol "Cajero"
```php
$rol = Role::create([
    'tenant_id' => 1,
    'name' => 'Cajero',
    'slug' => 'cashier',
    'description' => 'Manejo de caja y ventas',
]);

// Asignar permisos
$permisos = Permission::whereIn('slug', [
    'sales.view',
    'sales.create',
    'cash-register.view',
    'cash-register.open',
    'cash-register.close',
    'cash-register.movements',
    'customers.view',
    'products.view',
])->pluck('id');

$rol->permissions()->sync($permisos);
```

### Ejemplo: Crear Rol "Contador"
```php
$rol = Role::create([
    'tenant_id' => 1,
    'name' => 'Contador',
    'slug' => 'accountant',
    'description' => 'Gestión contable completa',
]);

// Asignar todos los permisos de contabilidad
$permisos = Permission::whereIn('module', [
    'contabilidad',
    'asientos_contables',
    'libro_mayor',
    'estados_financieros',
    'bancos',
    'cuentas_bancarias',
    'conciliacion_bancaria',
    'reportes',
])->pluck('id');

$rol->permissions()->sync($permisos);
```

---

## 🚀 Tareas Pendientes para Completar la Implementación

### Prioridad ALTA
- [ ] Aplicar middleware a todas las rutas en `routes/web.php`
- [ ] Crear roles predefinidos (Cajero, Contador, Vendedor, etc.)
- [ ] Asignar permisos a roles

### Prioridad MEDIA
- [ ] Crear directivas Blade para ocultar botones según permisos
- [ ] Agregar mensaje de "Sin permisos" en el layout principal

### Prioridad BAJA
- [ ] Crear panel de auditoría de permisos
- [ ] Agregar logs de accesos denegados

---

## 📝 Notas Importantes

1. **Super Admin siempre tiene acceso**: No necesitas asignar permisos al super admin.

2. **Rutas `list` y `data`**: Estas rutas son auxiliares (para combos y datatables). Generalmente deben tener el mismo permiso que la ruta `index` o `view`.

3. **Permisos en cadena**: Si un usuario tiene permiso para "crear ventas" pero no para "ver productos", no podrá crear ventas correctamente. Asegúrate de dar permisos relacionados.

4. **Método rápido para probar**: Crea un usuario de prueba, asígnale solo ciertos permisos y verifica que las rutas se bloqueen correctamente.

---

## 🔍 Verificar que los Permisos Funcionan

### 1. Verificar permisos en base de datos
```sql
SELECT m.module, COUNT(*) as total_permisos
FROM permissions p
JOIN (SELECT DISTINCT module FROM permissions) m ON p.module = m.module
GROUP BY m.module
ORDER BY m.module;
```

### 2. Verificar middleware registrado
```bash
php artisan route:list | grep permission
```

### 3. Probar con usuario sin permisos
- Crear usuario de prueba
- NO asignarle ningún rol
- Intentar acceder a una ruta protegida
- Debe redirigir con mensaje de error

---

## 📧 Soporte

Para dudas sobre la implementación de permisos, consultar:
- Documentación de Laravel: https://laravel.com/docs/authorization
- Archivo de configuración: `config/auth.php`
- Modelos: `app/Models/Role.php`, `app/Models/Permission.php`, `app/Models/User.php`
