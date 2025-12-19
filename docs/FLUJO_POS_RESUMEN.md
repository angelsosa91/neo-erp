# Flujo POS - Resumen Ejecutivo

## ✅ Flujo Correcto Implementado

### 1️⃣ Admin hace Login
```
Admin → Login con email/password
      ↓
   Dashboard (SIEMPRE)
      ↓
   Ve menú "Punto de Venta"
      ↓
   Click en "Punto de Venta"
      ↓
   Abre pantalla de PIN (/pos/login)
      ↓
   CUALQUIER vendedor puede ingresar su PIN
      ↓
   Vendedor accede al POS
      ↓
   Vendedor cierra sesión POS
      ↓
   Vuelve al Dashboard (sesión admin sigue activa)
```

**Ventaja:** Admin puede abrir el POS para que cualquier vendedor lo use, sin cerrar su propia sesión administrativa.

---

### 2️⃣ Vendedor hace Login
```
Vendedor → Login con email/password
         ↓
      POS Login (DIRECTO)
         ↓
      Ingresa su PIN
         ↓
      Accede al POS
```

**Ventaja:** Vendedor no ve dashboard ni opciones administrativas, va directo a su herramienta de trabajo.

---

## 🔒 Reglas de Seguridad

### Dashboard
- ✅ Admin → Acceso COMPLETO siempre
- ✅ Super Admin → Acceso COMPLETO siempre
- ❌ Vendedor → Bloqueado (redirige a POS)
- ✅ Otros roles → Según permisos

### POS
- ✅ Admin → Desde menú (pantalla PIN)
- ✅ Super Admin → Desde menú (pantalla PIN)
- ✅ Vendedor → Login directo + PIN
- ❌ Sin permiso `pos.use` → No ve menú POS

---

## 🎯 Casos de Uso Reales

### Caso 1: Admin supervisa ventas
```
1. Admin llega al salón
2. Login al sistema → Dashboard
3. Revisa reportes, inventario, etc.
4. Cliente llega para pagar
5. Click en "Punto de Venta"
6. Vendedor ingresa su PIN (ej: 1234)
7. Vendedor cobra al cliente
8. Vendedor cierra sesión POS
9. Admin sigue en el Dashboard
```

### Caso 2: Vendedor trabaja todo el día
```
1. Vendedor llega al salón
2. Login → Va directo a POS Login
3. Ingresa su PIN
4. Usa POS todo el día
5. Al final del día, cierra sesión
6. Sistema cierra sesión Laravel también
```

### Caso 3: Múltiples vendedores, un dispositivo
```
1. Admin abre "Punto de Venta" desde menú
2. Vendedor 1 ingresa PIN (1234) → Atiende cliente → Cierra sesión
3. Vendedor 2 ingresa PIN (5678) → Atiende cliente → Cierra sesión
4. Vendedor 3 ingresa PIN (9012) → Atiende cliente → Cierra sesión
5. Admin sigue en Dashboard, puede ver reportes en tiempo real
```

---

## 🔑 Configuración de PINs

### Desde Tinker (Temporal):
```bash
php artisan tinker

# Configurar PIN para admin
$admin = User::where('email', 'admin@neoerp.com')->first();
$admin->setPosPin('1111');
$admin->save();

# Configurar PIN para vendedor 1
$v1 = User::where('email', 'vendedor1@ejemplo.com')->first();
$v1->setPosPin('1234');
$v1->save();

# Configurar PIN para vendedor 2
$v2 = User::where('email', 'vendedor2@ejemplo.com')->first();
$v2->setPosPin('5678');
$v2->save();
```

### Requisitos:
- ✅ PIN debe tener 4-6 dígitos
- ✅ Se guarda hasheado (bcrypt)
- ✅ No se puede ver en la BD
- ✅ Cada usuario tiene su propio PIN

---

## ⚙️ Código Clave

### LoginController - Redirección
```php
protected function redirectPath($user): string
{
    // Solo vendedores van al POS automáticamente
    if ($user->hasRole('vendedor')) {
        return '/pos/login';
    }

    // Todos los demás → Dashboard
    return '/dashboard';
}
```

### Middleware Dashboard
```php
// Admins SIEMPRE pasan
if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
    return $next($request);
}

// Vendedores son redirigidos al POS
if ($user->hasRole('vendedor')) {
    return redirect()->route('pos.login');
}

// Otros roles pasan (acceso limitado por permisos)
return $next($request);
```

---

## 📊 Matriz Rápida

| Acción | Admin | Vendedor |
|--------|-------|----------|
| Login → | Dashboard | POS Login |
| Ve dashboard | ✅ | ❌ |
| Puede abrir POS | ✅ (menú) | ✅ (directo) |
| Necesita PIN para POS | ✅ | ✅ |
| Cierra sesión POS | Vuelve a Dashboard | Sale del sistema |

---

## ✨ Ventajas del Flujo

1. **Flexibilidad:** Admin puede usar POS cuando quiera
2. **Seguridad:** Cada vendedor tiene su PIN
3. **Trazabilidad:** Se sabe quién hizo cada venta
4. **UX Optimizada:** Vendedores no se confunden con opciones administrativas
5. **Multi-usuario:** Varios vendedores en un dispositivo
6. **Sesiones independientes:** POS y Dashboard son separados

---

## 🚫 Lo que NO se debe hacer

❌ No configurar `pos_enabled` en admin (no es necesario)
❌ No verificar permisos para redirigir (solo el rol importa)
❌ No complicar la lógica con múltiples if/else
❌ No olvidar que la sesión POS es independiente de Laravel

---

## ✅ Checklist de Implementación

- [x] LoginController redirige por rol
- [x] CheckDashboardAccess protege dashboard
- [x] Menú "Punto de Venta" visible para admins
- [x] POS Login acepta PIN de cualquier vendedor
- [x] Sesiones POS son independientes
- [x] Middleware registrado en bootstrap/app.php
- [x] Documentación actualizada

---

## 🎓 Para Recordar

> **Regla de Oro:** El ROL determina el destino después del login, no los permisos POS.

> **Regla de Plata:** Admin NUNCA va al POS automáticamente, SIEMPRE va al Dashboard.

> **Regla de Bronce:** Vendedor SIEMPRE va al POS, NUNCA al Dashboard.
