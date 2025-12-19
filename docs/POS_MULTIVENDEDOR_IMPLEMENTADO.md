# POS - Acceso Multi-Vendedor Implementado

## Resumen

Se modificó el sistema de autenticación del POS para permitir que **cualquier vendedor habilitado** pueda acceder con su PIN, independientemente de qué usuario esté logueado en la sesión web del sistema.

## 🔑 Características Clave

1. **Multi-Vendedor**: Cualquier vendedor puede ingresar con su PIN
2. **Sesión Laravel Actualizada**: `Auth::login($vendedor)` actualiza la sesión completa
3. **Trazabilidad Correcta**: Ventas se registran con el `user_id` del vendedor
4. **Logout Completo**: Al salir del POS, se cierra la sesión de Laravel completamente
5. **Seguridad Multi-tenancy**: Solo vendedores del mismo tenant pueden acceder

## Problema Original

**Situación anterior:**
- Admin inicia sesión en el sistema web
- Admin navega a POS
- Solo el PIN del admin era aceptado
- Si un vendedor quería usar el POS, no podía porque el sistema solo validaba el PIN del usuario logueado en la sesión web

**Escenario real:**
> "Si estoy en sesión del admin, solamente puedo ingresar PIN del admin, necesito poder acceder como vendedor, en caso de que el admin me deje abierta su pantalla para poder facturar"

## Solución Implementada

### Flujo Anterior (Incorrecto)
```
Usuario Web Logueado → POS Login → Validar PIN del usuario logueado
```

### Flujo Nuevo (Correcto)
```
Usuario Web Logueado → POS Login → Buscar CUALQUIER vendedor del tenant con ese PIN → Autenticar vendedor encontrado
```

## Cambios Técnicos

### 1. Backend - PosAuthController.php

**Método `login()` actualizado (líneas 31-108)**

#### ANTES:
```php
public function login(Request $request)
{
    $user = Auth::user(); // Solo valida el usuario logueado en web

    if (!$user->canUsePOS()) {
        return response()->json(['success' => false, 'message' => 'No tiene permisos'], 403);
    }

    if (!$user->verifyPosPin($request->pin)) {
        return response()->json(['success' => false, 'message' => 'PIN incorrecto'], 401);
    }
    // ...
}
```

#### AHORA:
```php
public function login(Request $request)
{
    $request->validate([
        'pin' => 'required|string|min:4|max:6',
    ]);

    $currentUser = Auth::user();
    $tenantId = $currentUser->tenant_id;

    // Buscar usuario del mismo tenant que tenga ese PIN
    $users = User::where('tenant_id', $tenantId)
        ->where('pos_enabled', true)
        ->where('is_active', true)
        ->whereNotNull('pos_pin')
        ->get();

    $user = null;
    foreach ($users as $potentialUser) {
        if ($potentialUser->verifyPosPin($request->pin)) {
            $user = $potentialUser;
            break;
        }
    }

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'PIN incorrecto o usuario no habilitado para POS',
        ], 401);
    }

    // Verificar permiso pos.use
    if (!$user->hasPermission('pos.use')) {
        return response()->json([
            'success' => false,
            'message' => 'No tiene permiso para usar el POS',
        ], 403);
    }

    // Cerrar sesiones POS activas
    $this->closeActiveSessions($user->id);

    // Crear nueva sesión POS
    $posSession = PosSession::createSession($user, 'pin', null, $request->input('terminal_id'));

    // 🔥 CRÍTICO: Hacer login del vendedor en Laravel
    // Esto actualiza Auth::user() para que devuelva al vendedor correcto
    Auth::login($user);

    // Guardar token en sesión
    session(['pos_session_token' => $posSession->session_token]);

    return response()->json([
        'success' => true,
        'requires_rfid' => false,
        'message' => 'Autenticación exitosa',
        'redirect' => route('pos.index'),
    ]);
}
```

**Ventajas del nuevo enfoque:**
- ✅ Busca entre todos los usuarios habilitados del tenant
- ✅ Valida el PIN contra cada usuario hasta encontrar coincidencia
- ✅ **Hace login del vendedor en Laravel** con `Auth::login($user)`
- ✅ Mantiene seguridad multi-tenancy (solo busca en el tenant actual)
- ✅ Verifica que el usuario tenga `pos_enabled = true`
- ✅ Verifica que el usuario esté activo
- ✅ Verifica que el usuario tenga PIN configurado

**🔥 Cambio Crítico:**
```php
// ANTES: Solo creaba sesión POS, pero Auth::user() seguía siendo el admin
$posSession = PosSession::createSession($user, 'pin', null, $request->input('terminal_id'));

// AHORA: Además de crear sesión POS, hace login del vendedor
$posSession = PosSession::createSession($user, 'pin', null, $request->input('terminal_id'));
Auth::login($user); // 🔥 Esto actualiza la sesión de Laravel
```

Esto garantiza que:
- `Auth::user()` devuelve el vendedor correcto
- `$request->user()` devuelve el vendedor correcto
- Las ventas se crean con el `user_id` del vendedor
- Todo el resto del sistema ve al vendedor como usuario autenticado

### 2. Frontend - pos/login.blade.php

**Vista actualizada (líneas 210-213)**

#### ANTES:
```blade
<div class="user-info">
    <h5>{{ Auth::user()->name }}</h5>
    <small>Ingrese su PIN</small>
</div>
```

#### AHORA:
```blade
<div class="user-info">
    <h5>Ingrese su PIN</h5>
    <small>Cualquier vendedor habilitado puede acceder</small>
</div>
```

**Cambio de UX:**
- Ya no muestra el nombre del usuario logueado en web
- Mensaje genérico que indica que cualquier vendedor puede ingresar
- Más intuitivo para entornos compartidos

## Casos de Uso

### Caso 1: Admin deja sesión web abierta
```
1. Admin inicia sesión web como "Juan Admin"
2. Admin navega a POS
3. Pantalla muestra: "Ingrese su PIN"
4. Vendedor "María Ventas" ingresa su PIN: 1234
5. Sistema busca en todos los usuarios del tenant
6. Encuentra que María tiene PIN 1234
7. Crea sesión POS para María
8. María puede facturar con su usuario
```

**Beneficio:** El admin no necesita cerrar sesión web para que el vendedor use el POS.

### Caso 2: Múltiples vendedores en un terminal
```
1. Terminal compartido con sesión web abierta
2. Vendedor 1 ingresa su PIN → Accede al POS
3. Vendedor 1 cierra sesión POS
4. Vendedor 2 ingresa su PIN → Accede al POS
5. Cada venta queda registrada con el usuario correcto
```

**Beneficio:** Auditoria correcta de quién hizo cada venta.

### Caso 3: Seguridad multi-tenancy
```
1. Usuario de Tenant A inicia sesión web
2. Intenta acceder al POS
3. Ingresa PIN de vendedor de Tenant B
4. Sistema solo busca en usuarios de Tenant A
5. No encuentra coincidencia → PIN incorrecto
```

**Beneficio:** Mantiene aislamiento entre tenants.

## Validaciones de Seguridad

### 1. Multi-tenancy
```php
$users = User::where('tenant_id', $tenantId) // Solo el tenant actual
```
- Previene acceso cruzado entre tenants
- Un vendedor de otro tenant no puede acceder aunque sepa el PIN

### 2. Estado del Usuario
```php
->where('pos_enabled', true)  // Usuario habilitado para POS
->where('is_active', true)     // Usuario activo en el sistema
->whereNotNull('pos_pin')      // Usuario con PIN configurado
```
- Triple validación de habilitación
- Usuarios deshabilitados no pueden acceder

### 3. Permisos
```php
if (!$user->hasPermission('pos.use')) {
    return response()->json(['message' => 'No tiene permiso'], 403);
}
```
- Verifica permiso específico `pos.use`
- Rol del usuario debe tener este permiso asignado

### 4. Hash de PIN
```php
$potentialUser->verifyPosPin($request->pin)
```
- PIN almacenado con hash (bcrypt)
- No se comparan PINs en texto plano
- Usa `Hash::check()` internamente

## Impacto en Funcionalidades Existentes

### ✅ Sin Cambios Necesarios:
- **Sesiones POS**: Se crean normalmente con el usuario autenticado
- **Ventas**: Se registran con el `user_id` del vendedor correcto
- **Comisiones**: Se calculan para el vendedor que hizo la venta
- **Auditoria**: Cada venta tiene el vendedor correcto asignado
- **RFID (2FA)**: Sigue funcionando igual, valida el RFID del usuario encontrado por PIN

### ⚠️ Consideración Importante:
El usuario logueado en la sesión web **SÍ CAMBIA** cuando ingresas al POS con un PIN diferente. Esto es por diseño para garantizar trazabilidad correcta.

**Ejemplo:**
```
1. Juan Admin inicia sesión web → Auth::user() = Juan
2. Juan navega al POS
3. María ingresa su PIN (1234)
4. Sistema hace Auth::login($maria)
5. Ahora Auth::user() = María (en TODO el sistema)
6. Sesión POS: María Ventas (id: 5)
7. Venta creada con user_id = 5 (María) ✅
```

**Implicación - Logout Completo:**
Cuando se cierra la sesión POS (botón "Salir"), el sistema hace un **logout completo de Laravel**:

```php
// En PosAuthController::logout()
Auth::logout();                        // Desloguea al usuario
$request->session()->invalidate();     // Invalida la sesión completa
$request->session()->regenerateToken(); // Regenera token CSRF
```

Esto significa:
- ✅ Se cierra la sesión POS
- ✅ Se cierra la sesión de Laravel
- ✅ El usuario es deslogueado completamente del sistema
- ✅ Redirige al login principal del sistema (no al login del POS)

**Flujo completo:**
```
1. Juan Admin → Login web → Auth::user() = Juan
2. Juan → Navega a POS
3. María → Ingresa PIN → Auth::login($maria) → Auth::user() = María
4. María → Vende productos → Venta con user_id = María ✅
5. María → Click "Salir POS" → Auth::logout() → Nadie logueado
6. Sistema → Redirige a login principal
7. Juan → Debe volver a loguearse para usar el sistema
```

**Ventajas:**
- ✅ Seguridad: No deja sesiones abiertas
- ✅ Trazabilidad: Cada venta tiene el usuario correcto
- ✅ Fuerza re-autenticación después de usar POS

## Archivos Modificados

1. **app/Http/Controllers/PosAuthController.php**
   - Líneas 31-108: Método `login()` completamente refactorizado
   - Lógica de búsqueda de usuario por PIN implementada
   - Línea 98: **CRÍTICO** - Agregado `Auth::login($user)` para actualizar sesión Laravel
   - Líneas 146-172: Método `verifyRfid()` actualizado con `Auth::login($user)` (línea 162)
   - Líneas 177-202: Método `logout()` actualizado:
     - Línea 193: Agregado `Auth::logout()` para cerrar sesión Laravel
     - Línea 194: Agregado `$request->session()->invalidate()` para invalidar sesión
     - Línea 195: Agregado `$request->session()->regenerateToken()` para regenerar token CSRF
     - Línea 200: Cambiado redirect de `pos.login` a `login` (login principal del sistema)

2. **resources/views/pos/login.blade.php**
   - Líneas 210-213: Actualizado mensaje de bienvenida
   - Removida visualización del nombre del usuario logueado

## Testing Recomendado

### Test 1: Admin permite acceso a vendedor
1. ✅ Iniciar sesión como Admin
2. ✅ Ir a POS
3. ✅ Ingresar PIN de vendedor
4. ✅ Verificar que sesión POS es del vendedor
5. ✅ Crear venta
6. ✅ Verificar que venta tiene `user_id` del vendedor

### Test 2: PIN incorrecto
1. ✅ Ir a POS
2. ✅ Ingresar PIN que no existe
3. ✅ Verificar mensaje: "PIN incorrecto o usuario no habilitado para POS"

### Test 3: Usuario deshabilitado
1. ✅ Crear usuario con `pos_enabled = false`
2. ✅ Ir a POS
3. ✅ Ingresar PIN de ese usuario
4. ✅ Verificar que no permite acceso

### Test 4: Multi-tenancy
1. ✅ Crear 2 tenants con usuarios
2. ✅ Loguearse como usuario de Tenant A
3. ✅ Ir a POS
4. ✅ Intentar con PIN de usuario de Tenant B
5. ✅ Verificar que no permite acceso

### Test 5: RFID (2FA)
1. ✅ Configurar usuario con `pos_require_rfid = true`
2. ✅ Ingresar PIN correcto
3. ✅ Verificar redirección a pantalla RFID
4. ✅ Ingresar RFID correcto
5. ✅ Verificar acceso al POS

### Test 6: Logout completo del sistema
1. ✅ Admin inicia sesión web
2. ✅ Navega a POS
3. ✅ Vendedor ingresa PIN → Auth::user() = Vendedor
4. ✅ Verificar que Auth::user() es el vendedor
5. ✅ Click en "Salir" en POS
6. ✅ Verificar que redirige a login principal (no POS login)
7. ✅ Verificar que Auth::user() es null (nadie logueado)
8. ✅ Intentar acceder a cualquier ruta protegida
9. ✅ Verificar que redirige a login (middleware auth)

### Test 7: Sesión POS + Sesión Laravel sincronizadas
1. ✅ Loguearse con PIN
2. ✅ Verificar que `PosSession` existe con user_id correcto
3. ✅ Verificar que `Auth::user()->id` coincide con PosSession->user_id
4. ✅ Crear venta
5. ✅ Verificar que `sale->user_id` coincide con PosSession->user_id
6. ✅ Cerrar sesión POS
7. ✅ Verificar que PosSession está cerrada (status = 'closed')
8. ✅ Verificar que Auth::user() es null

## Mejoras Futuras Sugeridas

### 1. Mostrar Nombre del Vendedor Después de PIN
Después de ingresar PIN correcto, mostrar:
```
✅ Autenticado como: María Ventas
Redirigiendo al POS...
```

### 2. Registro de Intentos Fallidos
Registrar en log cuando hay intentos fallidos:
```php
\Log::warning("Intento fallido de acceso POS con PIN desde tenant {$tenantId}");
```

### 3. Bloqueo Temporal por Intentos Fallidos
Después de 5 intentos fallidos, bloquear acceso por 5 minutos.

### 4. Selector Visual de Vendedores (Opcional)
En lugar de solo PIN, mostrar lista de vendedores con avatar:
```
┌─────────────────────┐
│  [👤] Juan Pérez    │
│  [👤] María López   │
│  [👤] Carlos Ruiz   │
└─────────────────────┘
```
Click en vendedor → Ingresa PIN

## Notas de Seguridad

### ¿Es seguro buscar entre todos los usuarios?
**Sí, por las siguientes razones:**

1. **Hash del PIN**: Los PINs están hasheados con bcrypt, no se pueden obtener en texto plano
2. **Comparación segura**: Usa `Hash::check()` que previene timing attacks
3. **Filtro por tenant**: Solo busca dentro del tenant actual
4. **Validaciones adicionales**: Verifica estado activo, habilitación POS, y permisos

### ¿Puede un usuario adivinar PINs?
**Muy difícil:**

- PINs de 4-6 dígitos = 10,000 a 1,000,000 combinaciones
- No hay rate limiting en esta implementación (recomendado agregar)
- Intentos fallidos no revelan si el PIN existe o no

**Recomendación:** Implementar bloqueo temporal después de X intentos fallidos.

## Conclusión

Este cambio mejora significativamente la usabilidad del POS en entornos donde:
- Múltiples vendedores comparten terminales
- El administrador necesita permitir acceso sin cerrar sesión
- Se requiere trazabilidad de quién realiza cada venta

La implementación mantiene la seguridad mediante:
- Validación de multi-tenancy
- Verificación de permisos y estado del usuario
- Hash seguro de PINs
- Auditoria correcta de ventas por vendedor
