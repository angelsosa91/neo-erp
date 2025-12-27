# Guía de Implementación: Sistema de Permisos para Dashboard

## Resumen de Cambios

Se ha implementado un sistema de permisos robusto que protege el acceso al Dashboard y redirige automáticamente a los usuarios a su módulo principal según sus permisos.

---

## 🎯 Objetivos Alcanzados

1. **Dashboard protegido**: Solo usuarios con permiso `dashboard.view` pueden acceder
2. **Menú dinámico**: Todas las opciones del menú se muestran solo si el usuario tiene permisos
3. **Redirección inteligente**: Usuarios sin acceso al dashboard son redirigidos a su módulo principal
4. **Roles actualizados**: Roles administrativos tienen acceso automático al dashboard

---

## 📋 Pasos para Aplicar los Cambios

### 1. Ejecutar las Migraciones y Seeders

Ejecuta los seeders para actualizar permisos y roles:

```bash
php artisan db:seed --class=PermissionSeeder
php artisan db:seed --class=RolesSeeder
```

**IMPORTANTE**: Si ya tienes usuarios en producción, este comando actualizará los permisos de los roles existentes.

---

## 🔐 Nuevo Permiso Agregado

### Dashboard
- **Permiso**: `dashboard.view`
- **Nombre**: "Ver Dashboard"
- **Módulo**: `dashboard`
- **Descripción**: Permite acceder a la vista principal del dashboard con métricas y estadísticas

---

## 👥 Roles con Acceso al Dashboard

Los siguientes roles **TIENEN** acceso al dashboard por defecto:

### ✅ Con Acceso al Dashboard:
1. **Administrador** (super-admin) - Acceso total (todos los permisos)
2. **Contador** - Dashboard + módulos contables y financieros
3. **Supervisor de Ventas** - Dashboard + módulos de ventas completos

### ❌ Sin Acceso al Dashboard (serán redirigidos):
1. **Cajero** → Redirigido a ventas o POS
2. **Vendedor** → Redirigido a POS o ventas
3. **Almacenero** → Redirigido a productos/inventario

---

## 🔄 Sistema de Redirección Inteligente

Cuando un usuario sin permiso `dashboard.view` intenta acceder al dashboard, será redirigido automáticamente según este orden de prioridad:

### Orden de Redirección:
1. **POS** - Si tiene permiso `pos.use` → `route('pos.login')`
2. **Ventas** - Si tiene permiso `sales.view` → `route('sales.index')`
3. **Reportes** - Si tiene permiso `reports.view` → `route('reports.index')`
4. **Productos** - Si tiene permiso `products.view` → `route('products.index')`
5. **Contabilidad** - Si tiene permiso `account-chart.view` → `route('account-chart.index')`
6. **Error 403** - Si no tiene ningún permiso → Mensaje de contactar al administrador

---

## 📁 Archivos Modificados

### 1. `database/seeders/PermissionSeeder.php`
**Cambio**: Agregado permiso `dashboard.view`

```php
// Dashboard
['name' => 'Ver Dashboard', 'slug' => 'dashboard.view', 'module' => 'dashboard'],
```

### 2. `database/seeders/RolesSeeder.php`
**Cambio**: Asignado permiso `dashboard.view` a roles administrativos

```php
// En Contador
'dashboard.view',

// En Supervisor de Ventas
'dashboard.view',
```

### 3. `resources/views/layouts/app.blade.php`
**Cambio**: Dashboard protegido en el menú

```blade
@canany(['dashboard.view'])
<li>
    <a href="{{ route('dashboard') }}">
        <i class="bi bi-speedometer2"></i>
        <span class="menu-text">Dashboard</span>
    </a>
</li>
@endcanany
```

### 4. `app/Http/Middleware/CheckDashboardAccess.php`
**Cambio**: Middleware completamente reescrito para usar sistema de permisos

- Usa `user_can('dashboard.view')` en lugar de verificar roles hardcodeados
- Implementa redirección inteligente basada en permisos
- Elimina dependencia de roles específicos

---

## 🛠️ Cómo Personalizar el Acceso al Dashboard

### Opción 1: Asignar Permiso a un Usuario Específico

```php
// En un controller o seeder
$user = User::find($userId);
$dashboardPermission = Permission::where('slug', 'dashboard.view')->first();

// Asignar el permiso al rol del usuario
$user->roles->first()->permissions()->attach($dashboardPermission->id);

// O crear un nuevo rol personalizado
$customRole = Role::create([
    'name' => 'Gerente',
    'slug' => 'gerente',
    'description' => 'Gerente con acceso al dashboard'
]);
$customRole->permissions()->attach($dashboardPermission->id);
$user->roles()->attach($customRole->id);
```

### Opción 2: Crear un Nuevo Rol con Acceso al Dashboard

Edita `database/seeders/RolesSeeder.php` y agrega:

```php
// 7. Gerente de Operaciones
$gerenteOperaciones = Role::updateOrCreate(
    ['slug' => 'gerente-operaciones'],
    [
        'name' => 'Gerente de Operaciones',
        'description' => 'Supervisión general de operaciones con acceso al dashboard',
        'is_system' => false,
    ]
);

$gerentePermisos = Permission::whereIn('slug', [
    'dashboard.view',  // ← IMPORTANTE: Incluir este permiso
    'sales.view',
    'purchases.view',
    'inventory-adjustments.view',
    'reports.view',
    // ... más permisos según necesites
])->pluck('id');

$gerenteOperaciones->permissions()->sync($gerentePermisos);
```

---

## 🧪 Cómo Probar los Cambios

### Test 1: Usuario con Permiso de Dashboard
1. Login con usuario que tenga rol `contador` o `supervisor-ventas`
2. Debería ver el Dashboard en el menú
3. Debería poder acceder a `/dashboard`

### Test 2: Usuario sin Permiso de Dashboard
1. Login con usuario con rol `vendedor` o `cajero`
2. NO debería ver el Dashboard en el menú
3. Al intentar acceder a `/dashboard` debe ser redirigido a su módulo principal (POS o Ventas)

### Test 3: Usuario sin Permisos
1. Crear un usuario sin roles o con un rol vacío
2. Al intentar acceder debe recibir error 403 con mensaje claro

---

## 📊 Comparación: Antes vs Después

### ❌ ANTES (Problema):
- Dashboard visible para TODOS los usuarios autenticados
- Usuarios sin permisos podían ver opciones del menú que les daban error 403
- Sistema basado en roles hardcodeados (no flexible)
- Vendedores podían ver el dashboard aunque no tuvieran datos relevantes

### ✅ DESPUÉS (Solución):
- Dashboard solo visible para usuarios autorizados (`dashboard.view`)
- Menú completamente dinámico - solo muestran opciones con permisos
- Sistema basado en permisos (flexible y escalable)
- Redirección automática a módulo principal del usuario
- Mejor experiencia de usuario - cada rol ve solo lo que necesita

---

## 🔍 Verificar Permisos de un Usuario

### Desde Tinker:
```bash
php artisan tinker
```

```php
// Obtener usuario
$user = User::find(1);

// Ver si tiene permiso de dashboard
$user->hasPermission('dashboard.view'); // true o false

// Ver todos sus permisos
$user->permissions()->pluck('slug');

// Ver sus roles
$user->roles()->pluck('name');
```

### Desde Blade (en vistas):
```blade
@canany(['dashboard.view'])
    <p>Tienes acceso al dashboard</p>
@else
    <p>No tienes acceso al dashboard</p>
@endcanany
```

### Desde Controller:
```php
if (user_can('dashboard.view')) {
    // Usuario tiene acceso
    return view('dashboard');
}

// O lanzar excepción si no tiene permiso
abort_unless_can('dashboard.view', 'No tiene permisos para ver el dashboard');
```

---

## 🚨 Consideraciones Importantes

### 1. Super Admin
El usuario con rol `super-admin` o `administrador` **SIEMPRE** tiene acceso a TODO, incluyendo el dashboard. Esto está implementado en `PermissionHelper.php` y no requiere asignar permisos específicos.

### 2. Multi-Tenancy
Si tu sistema usa multi-tenancy (basado en `tenant_id` en la tabla `roles`), asegúrate de que los roles se creen con el `tenant_id` correcto.

### 3. Caché de Permisos
Si notas que los cambios de permisos no se reflejan inmediatamente, limpia la caché:
```bash
php artisan cache:clear
php artisan config:clear
```

### 4. Migraciones en Producción
Si ya tienes datos en producción, **NO ejecutes** `php artisan migrate:fresh` ya que perderás todos los datos. En su lugar:
- Ejecuta solo los seeders: `php artisan db:seed --class=PermissionSeeder`
- Actualiza roles: `php artisan db:seed --class=RolesSeeder`

---

## 📝 Ejemplo de Uso en la Práctica

### Escenario: Nueva Empresa con 5 Empleados

**Personal:**
1. Dueño/Gerente General → Rol: `administrador`
2. Contador → Rol: `contador`
3. Vendedor 1 → Rol: `vendedor`
4. Vendedor 2 → Rol: `vendedor`
5. Almacenista → Rol: `almacenero`

**Resultados:**

| Usuario | Rol | Ve Dashboard? | Página Inicial |
|---------|-----|---------------|----------------|
| Dueño/Gerente | administrador | ✅ SÍ | `/dashboard` |
| Contador | contador | ✅ SÍ | `/dashboard` |
| Vendedor 1 | vendedor | ❌ NO | `/pos/login` o `/sales` |
| Vendedor 2 | vendedor | ❌ NO | `/pos/login` o `/sales` |
| Almacenista | almacenero | ❌ NO | `/products` |

---

## 🎓 Mejores Prácticas

1. **Crea roles personalizados** para tu negocio específico en lugar de modificar los roles del sistema
2. **Asigna permisos granulares** - es mejor tener un permiso específico que dar acceso completo
3. **Documenta los roles** - mantén actualizada la descripción de cada rol
4. **Revisa permisos regularmente** - audita qué usuarios tienen acceso a qué módulos
5. **Usa helpers de permisos** en lugar de verificar roles directamente
6. **Protege rutas Y vistas** - doble capa de seguridad (middleware + @canany)

---

## 🐛 Solución de Problemas

### Problema: Usuario admin no puede acceder al dashboard
**Solución**: Verifica que el usuario tenga el rol `administrador` con slug `administrador`:
```php
$user->roles()->pluck('slug'); // Debe incluir 'administrador'
```

### Problema: Cambios en permisos no se reflejan
**Solución**:
```bash
php artisan cache:clear
php artisan config:clear
# Logout y login nuevamente
```

### Problema: Error "Permission not found"
**Solución**: Ejecuta el seeder de permisos:
```bash
php artisan db:seed --class=PermissionSeeder
```

### Problema: Usuario es redirigido en loop infinito
**Solución**: Asegúrate de que el usuario tenga AL MENOS un permiso que corresponda a una de las redirecciones en `CheckDashboardAccess.php`

---

## 📞 Soporte

Si tienes problemas implementando estos cambios:

1. Verifica que todos los archivos fueron modificados correctamente
2. Ejecuta los seeders en el orden correcto
3. Limpia caché de Laravel
4. Revisa los logs en `storage/logs/laravel.log`

---

## ✅ Checklist de Implementación

- [ ] Ejecutado `php artisan db:seed --class=PermissionSeeder`
- [ ] Ejecutado `php artisan db:seed --class=RolesSeeder`
- [ ] Ejecutado `php artisan cache:clear`
- [ ] Probado acceso al dashboard con usuario admin
- [ ] Probado acceso al dashboard con usuario contador
- [ ] Probado que vendedor es redirigido correctamente
- [ ] Verificado que el menú muestra solo opciones con permisos
- [ ] Documentado roles personalizados creados (si aplica)

---

**Fecha de Implementación**: 2025-12-27
**Versión**: 1.0
**Sistema**: Neo ERP
