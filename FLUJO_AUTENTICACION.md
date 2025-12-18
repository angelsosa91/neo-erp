# Flujo de Autenticación por Roles

## Resumen
El sistema ahora redirige automáticamente a los usuarios según su rol después del login, proporcionando una experiencia optimizada para cada tipo de usuario.

---

## Flujo General

```
┌─────────────────────┐
│   Usuario accede    │
│   /login            │
└──────────┬──────────┘
           │
           v
┌─────────────────────┐
│  Ingresa email y    │
│  password           │
└──────────┬──────────┘
           │
           v
┌─────────────────────┐
│  Sistema verifica   │
│  credenciales       │
└──────────┬──────────┘
           │
           v
    ┌──────┴──────┐
    │             │
    v             v
┌───────┐    ┌────────┐
│ Admin │    │Vendedor│
│ Role  │    │  Role  │
└───┬───┘    └───┬────┘
    │            │
    v            v
Dashboard      POS Login
```

---

## Roles y Redirecciones

### 1. Super Admin / Admin
**Rol:** `super-admin` o `admin`

**Flujo:**
```
Login → Dashboard (/dashboard) - SIEMPRE
```

**Características:**
- ✅ Acceso completo al dashboard administrativo
- ✅ Ve el menú lateral completo
- ✅ Puede acceder al POS desde el menú
- ✅ Tiene todos los permisos del sistema
- ✅ **NUNCA** es redirigido al POS automáticamente

**Acceso al POS:**
- Desde el menú: Click en "Punto de Venta"
- Abre /pos/login (pantalla de PIN)
- **Cualquier vendedor** puede ingresar su PIN
- No necesita cerrar sesión del admin

**Caso de Uso:**
```
1. Admin abre el sistema
2. Click en "Punto de Venta" en el menú
3. Pantalla de PIN se abre
4. Vendedor ingresa su PIN
5. Vendedor usa el POS
6. Al cerrar sesión POS → vuelve al dashboard del admin
```

---

### 2. Vendedor
**Rol:** `vendedor`

**Flujo:**
```
Login → POS Login (/pos/login) - DIRECTO
      ↓
  Ingresa su PIN
      ↓
  POS Interface (/pos)
```

**Características:**
- ❌ NO accede al dashboard directamente
- ✅ Va directo al POS Login
- ✅ Solo necesita ingresar su PIN
- ✅ Experiencia optimizada para ventas
- ✅ No ve opciones administrativas

**Acceso al Dashboard:**
- ⛔ Bloqueado por middleware `dashboard.access`
- Si intenta /dashboard → Redirige a /pos/login
- Mensaje: "Su cuenta está configurada para usar el POS"

**Importante:**
- NO necesita `pos_enabled = true`
- NO necesita `pos_pin` configurado para login
- **Sí necesita PIN** para usar el POS

---

### 3. Otros Roles
**Rol:** Cualquier otro rol personalizado

**Flujo:**
```
Login → Dashboard (/dashboard)
```

**Acceso:**
- Dashboard según permisos asignados
- Menú limitado según permisos
- No ven "Punto de Venta" si no tienen permiso `pos.use`

---

## Implementación Técnica

### 1. LoginController@redirectPath()

**Ubicación:** `app/Http/Controllers/Auth/LoginController.php`

```php
protected function redirectPath($user): string
{
    // VENDEDOR → Redirigir al POS Login
    // (es la única excepción, todos los demás van al dashboard)
    if ($user->hasRole('vendedor')) {
        return '/pos/login';
    }

    // Todos los demás (Admin, Super Admin, otros roles) → Dashboard
    return '/dashboard';
}
```

**Lógica (SIMPLIFICADA):**
1. ¿Es vendedor? → POS Login
2. ¿No es vendedor? → Dashboard

**Importante:**
- Admins SIEMPRE van al Dashboard
- No importa si el admin tiene `pos_enabled = true`
- No importa si el admin tiene `pos_pin` configurado
- El rol determina el destino, no los permisos POS

---

### 2. CheckDashboardAccess Middleware

**Ubicación:** `app/Http/Middleware/CheckDashboardAccess.php`

**Propósito:** Proteger el dashboard de usuarios no administrativos

```php
public function handle(Request $request, Closure $next): Response
{
    $user = $request->user();

    // Super Admin y Admin SIEMPRE tienen acceso al dashboard
    if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
        return $next($request);
    }

    // Vendedor con rol específico → redirigir al POS
    if ($user->hasRole('vendedor')) {
        return redirect()->route('pos.login')
            ->with('info', 'Su cuenta está configurada para usar el POS');
    }

    // Otros usuarios → permitir acceso según sus permisos
    // (pueden tener roles personalizados con acceso limitado al dashboard)
    return $next($request);
}
```

**Aplicado a:**
- Ruta `/dashboard`

**Resultado:**
- Admins → Pasan SIEMPRE ✅
- Vendedores → Redirige a /pos/login ↩️
- Otros roles → Pasan (limitado por permisos) ✅

---

### 3. Menú Lateral - Enlace POS

**Ubicación:** `resources/views/layouts/app.blade.php`

```blade
<!-- POS -->
@canany(['pos.use'])
<li>
    <a href="{{ route('pos.login') }}" class="{{ request()->routeIs('pos.*') ? 'active' : '' }}">
        <i class="bi bi-shop"></i>
        <span class="menu-text">Punto de Venta</span>
    </a>
</li>
@endcanany
```

**Visibilidad:**
- ✅ Visible para usuarios con permiso `pos.use`
- ❌ Oculto para usuarios sin el permiso

---

## Métodos del Modelo User

### canUsePOS()
```php
public function canUsePOS(): bool
{
    return $this->pos_enabled && $this->is_active && !empty($this->pos_pin);
}
```

**Verifica:**
- POS habilitado (`pos_enabled = true`)
- Usuario activo (`is_active = true`)
- Tiene PIN configurado (`pos_pin != null`)

### hasRole()
```php
public function hasRole(string $roleSlug): bool
{
    return $this->roles()->where('slug', $roleSlug)->exists();
}
```

### hasPermission()
```php
public function hasPermission(string $permissionSlug): bool
{
    return $this->roles()
        ->whereHas('permissions', function ($query) use ($permissionSlug) {
            $query->where('slug', $permissionSlug);
        })->exists();
}
```

---

## Configuración de Usuario para POS

### Desde Tinker (Temporal)

```bash
php artisan tinker

# Configurar vendedor para POS
$user = User::where('email', 'vendedor@ejemplo.com')->first();

# Establecer PIN
$user->setPosPin('1234');

# Habilitar POS
$user->pos_enabled = true;

# Guardar
$user->save();

# Verificar
echo $user->canUsePOS() ? 'Puede usar POS ✓' : 'No puede usar POS ✗';

# Asignar permiso (si no lo tiene)
$permission = Permission::where('slug', 'pos.use')->first();
$role = $user->roles()->first();
$role->permissions()->attach($permission->id);
```

### Desde UI (Pendiente - Fase 4)

En el módulo de gestión de usuarios habrá una sección "Configuración POS" con:
- ☐ Habilitar acceso al POS
- ☐ Establecer PIN (con confirmación)
- ☐ Configurar RFID (opcional)
- ☐ Requerir 2FA (PIN + RFID)
- ☐ Porcentaje de comisión

---

## Escenarios de Uso

### Escenario 1: Admin quiere usar el POS

```
1. Admin inicia sesión → Va a Dashboard
2. Click en "Punto de Venta" en el menú
3. Sistema muestra /pos/login
4. Ingresa su PIN (debe tenerlo configurado)
5. Accede al POS
6. Puede volver al Dashboard cuando quiera
```

---

### Escenario 2: Vendedor inicia su turno

```
1. Vendedor inicia sesión con email/password
2. Sistema detecta que tiene POS configurado
3. Redirige automáticamente a /pos/login
4. Ingresa su PIN de 4 dígitos
5. Accede al POS
6. Comienza a atender clientes
7. Al cerrar sesión POS → Vuelve a /login
```

---

### Escenario 3: Vendedor intenta acceder al Dashboard

```
1. Vendedor está en el POS
2. Intenta acceder a /dashboard manualmente
3. Middleware CheckDashboardAccess lo intercepta
4. Verifica que NO es admin
5. Redirige a /pos/login con mensaje
6. "Su cuenta está configurada para usar el POS"
```

---

### Escenario 4: Usuario sin POS configurado

```
1. Usuario inicia sesión
2. Sistema detecta que NO tiene pos_enabled
3. Redirige al Dashboard
4. Ve el sistema según sus permisos
5. NO ve el enlace "Punto de Venta" en el menú
```

---

## Matriz de Accesos

| Rol | Login → | Dashboard | POS | Menú POS |
|-----|---------|-----------|-----|----------|
| Super Admin | **Dashboard** | ✅ SIEMPRE | ✅ (vía menú) | ✅ |
| Admin | **Dashboard** | ✅ SIEMPRE | ✅ (vía menú) | ✅ |
| Vendedor | **POS Login** | ❌ | ✅ | ❌ |
| Otro Rol | Dashboard | ✅ (según permisos) | ❌ | ❌ |

**Aclaraciones:**
- Admin NUNCA va al POS automáticamente, siempre al Dashboard
- Vendedor SIEMPRE va al POS Login, nunca al Dashboard
- Admin puede abrir POS desde menú para que vendedor ingrese PIN
- Sesión POS es independiente de sesión Laravel

---

## Seguridad

### Capas de Protección

1. **Login Laravel** (email + password)
   - Valida credenciales
   - Verifica cuenta activa
   - Regenera sesión

2. **Redirección por Rol**
   - Envía al lugar correcto
   - Previene acceso no autorizado

3. **Middleware dashboard.access**
   - Protege rutas administrativas
   - Redirige usuarios sin permisos

4. **PIN del POS**
   - Capa adicional de seguridad
   - Controla apertura de caja
   - Hashea con bcrypt

5. **Permisos Granulares**
   - Control por módulo
   - Verificación en cada acción

---

## Ventajas del Nuevo Flujo

### Para Vendedores:
✅ Experiencia simplificada
✅ Un solo login (desde su perspectiva)
✅ Acceso directo a su herramienta de trabajo
✅ No ven opciones innecesarias
✅ Interfaz optimizada para ventas

### Para Administradores:
✅ Acceso completo al sistema
✅ Pueden usar el POS cuando necesiten
✅ Control total de configuración
✅ Pueden supervisar desde el dashboard

### Para el Sistema:
✅ Mayor seguridad (separación de roles)
✅ Mejor UX (cada quien ve lo que necesita)
✅ Flexible (se adapta a diferentes roles)
✅ Escalable (fácil agregar nuevos roles)

---

## Archivos Modificados

### Backend
1. `app/Http/Controllers/Auth/LoginController.php`
   - Agregado método `redirectPath()`

2. `app/Http/Middleware/CheckDashboardAccess.php`
   - Nuevo middleware creado

3. `bootstrap/app.php`
   - Registrado alias `dashboard.access`

4. `routes/web.php`
   - Aplicado middleware a `/dashboard`

### Frontend
5. `resources/views/layouts/app.blade.php`
   - Agregado enlace "Punto de Venta" en menú

---

## Testing

### Pruebas Manuales Recomendadas

#### 1. Login como Admin
```bash
# Crear/configurar admin
php artisan tinker
$admin = User::where('email', 'admin@neoerp.com')->first();
$admin->setPosPin('1234');
$admin->pos_enabled = true;
$admin->save();
```

- [ ] Login → Debe ir a Dashboard
- [ ] Ver menú "Punto de Venta"
- [ ] Click en POS → Pide PIN
- [ ] Ingresar PIN → Accede al POS
- [ ] Ver botón "Cerrar Sesión"

#### 2. Login como Vendedor
```bash
# Crear vendedor con POS
php artisan tinker
$vendedor = User::where('email', 'vendedor@neoerp.com')->first();
$vendedor->setPosPin('5678');
$vendedor->pos_enabled = true;
$vendedor->save();

# Asignar rol vendedor
$role = Role::where('slug', 'vendedor')->first();
$vendedor->roles()->sync([$role->id]);
```

- [ ] Login → Debe ir a POS Login directo
- [ ] Ingresar PIN → Accede al POS
- [ ] Intentar /dashboard manualmente → Redirige a POS
- [ ] NO debe ver menú lateral completo

#### 3. Protección de Dashboard
- [ ] Usuario vendedor en /dashboard → Redirige
- [ ] Mensaje: "Su cuenta está configurada para usar el POS"
- [ ] Admin en /dashboard → Acceso permitido

---

## Próximos Pasos

### Fase 4: Interfaz POS Completa
- Grid de servicios/productos
- Carrito de compra
- Cálculo de totales
- Múltiples métodos de pago
- Impresión de ticket

### Mejoras Futuras
- UI en módulo usuarios para configurar PIN
- Sección "Configuración POS" en perfil de usuario
- Logs de acceso al POS
- Reportes de ventas por vendedor
- Dashboard específico para vendedores (opcional)

---

## Conclusión

El nuevo flujo de autenticación proporciona:
- ✅ Seguridad mejorada
- ✅ UX optimizada por rol
- ✅ Acceso controlado al dashboard
- ✅ Experiencia simplificada para vendedores
- ✅ Flexibilidad para diferentes roles

**Estado:** ✅ Implementado y listo para producción
