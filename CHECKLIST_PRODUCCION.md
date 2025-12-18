# Checklist para Desplegar Servicios en Producción

## ✅ Lo que YA está listo

### 1. Base de Datos
- ✅ Migración `create_services_table` creada
- ✅ Campos incluyen: code, name, price, tax_rate, commission_percentage, color, icon, sort_order
- ✅ Relación con categories configurada
- ✅ Tenant_id incluido para multi-tenancy

### 2. Modelo
- ✅ Service model con BelongsToTenant trait
- ✅ Scopes: active(), popular(), search()
- ✅ Método generateCode() para códigos automáticos
- ✅ Métodos de cálculo: calculateTax(), calculateSubtotal()
- ✅ Relaciones: category(), saleServiceItems()

### 3. Controlador
- ✅ ServiceController con CRUD completo
- ✅ Validaciones exhaustivas
- ✅ Verificación de tenant_id en todos los métodos
- ✅ Prevención de eliminación con ventas asociadas
- ✅ Endpoints: index, create, store, show, edit, update, destroy, data, list, popular

### 4. Rutas
- ✅ 10 rutas registradas
- ✅ Middleware de permisos aplicado
- ✅ Separadas por tipo de operación

### 5. Permisos
- ✅ services.view
- ✅ services.create
- ✅ services.edit
- ✅ services.delete
- ✅ Incluidos en PermissionSeeder
- ✅ Asignados automáticamente a Super Admin y Admin

### 6. Vista
- ✅ DataGrid con jEasyUI configurado
- ✅ Modal de creación/edición
- ✅ Validaciones en formulario
- ✅ Búsqueda en tiempo real
- ✅ Permisos con @can/@canany

### 7. Seguridad
- ✅ CSRF token configurado globalmente ($.ajaxSetup)
- ✅ Meta tag csrf-token en layout
- ✅ Validación de tenant_id en todos los endpoints
- ✅ Middleware de permisos en rutas

### 8. Seeder
- ✅ ServiceSeeder con 20 ejemplos
- ✅ 3 categorías creadas (Cabello, Uñas, Belleza)
- ✅ Datos realistas para salón de belleza

---

## ⚠️ PASOS OBLIGATORIOS ANTES DE PRODUCCIÓN

### Paso 1: Respaldo de Base de Datos
```bash
# Hacer backup ANTES de cualquier cambio
mysqldump -u root -p nombre_bd > backup_antes_servicios_$(date +%Y%m%d_%H%M%S).sql
```

### Paso 2: Verificar Entorno
```bash
# En producción, verificar:
php artisan --version  # Laravel 12
php -v                 # PHP 8.2+
```

### Paso 3: Ejecutar Migraciones
```bash
# En producción:
php artisan migrate

# Verificar que la tabla services se creó:
php artisan tinker --execute="echo 'Tabla services existe: ' . (Schema::hasTable('services') ? 'SÍ' : 'NO');"
```

### Paso 4: Ejecutar Seeders de Permisos
```bash
# Solo ejecutar PermissionSeeder (para agregar permisos de servicios):
php artisan db:seed --class=PermissionSeeder

# Verificar que los permisos existen:
php artisan tinker --execute="echo 'Permisos de servicios: ' . App\Models\Permission::where('module', 'servicios')->count();"
```

### Paso 5: Asignar Permisos a Roles Existentes
```bash
# Ejecutar este script para asignar permisos a roles existentes:
php artisan tinker --execute="
use App\Models\Role;
use App\Models\Permission;

\$servicesPermissions = Permission::where('module', 'servicios')->pluck('id')->toArray();

// Asignar a Super Admin
\$superAdmin = Role::where('slug', 'super-admin')->first();
if (\$superAdmin) {
    \$currentPerms = \$superAdmin->permissions->pluck('id')->toArray();
    \$superAdmin->permissions()->sync(array_unique(array_merge(\$currentPerms, \$servicesPermissions)));
    echo 'Super Admin actualizado\n';
}

// Asignar a Admin
\$admin = Role::where('slug', 'admin')->first();
if (\$admin) {
    \$currentPerms = \$admin->permissions->pluck('id')->toArray();
    \$admin->permissions()->sync(array_unique(array_merge(\$currentPerms, \$servicesPermissions)));
    echo 'Admin actualizado\n';
}

echo 'Permisos de servicios asignados correctamente';
"
```

### Paso 6: (Opcional) Crear Servicios de Ejemplo
```bash
# Solo si quieres datos de ejemplo en producción:
php artisan db:seed --class=ServiceSeeder

# NOTA: Probablemente NO quieras esto en producción.
# El cliente debería crear sus propios servicios.
```

### Paso 7: Limpiar Caché
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize
```

### Paso 8: Verificar Permisos de Archivos
```bash
# En servidor Linux:
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---

## 🧪 PRUEBAS EN PRODUCCIÓN

### 1. Verificar Acceso al Menú
- [ ] Iniciar sesión como Admin
- [ ] Verificar que aparece menú "Servicios" en sidebar bajo "Productos"
- [ ] Click en "Servicios" debe abrir la vista

### 2. Probar Crear Servicio
- [ ] Click en "Nuevo Servicio"
- [ ] Verificar que se genera código automático (SRV-00001)
- [ ] Completar formulario:
  - Código: (auto)
  - Nombre: "Corte de Cabello"
  - Precio: 50000
  - IVA: 10%
  - Duración: 30 min
  - Color: #3498db
- [ ] Click en "Guardar"
- [ ] Verificar mensaje de éxito
- [ ] Verificar que aparece en el DataGrid

### 3. Probar Editar Servicio
- [ ] Seleccionar el servicio creado
- [ ] Click en "Editar"
- [ ] Cambiar nombre a "Corte de Cabello Dama"
- [ ] Cambiar precio a 60000
- [ ] Click en "Guardar"
- [ ] Verificar mensaje de éxito
- [ ] Verificar cambios en el DataGrid

### 4. Probar Búsqueda
- [ ] Escribir "Corte" en el searchbox
- [ ] Verificar que filtra correctamente

### 5. Probar Eliminar Servicio
- [ ] Seleccionar el servicio de prueba
- [ ] Click en "Eliminar"
- [ ] Confirmar en el diálogo
- [ ] Verificar mensaje de éxito
- [ ] Verificar que desapareció del DataGrid

### 6. Probar Validaciones
- [ ] Intentar crear servicio sin nombre → debe mostrar error
- [ ] Intentar crear servicio sin precio → debe mostrar error
- [ ] Intentar crear servicio con código duplicado → debe mostrar error

### 7. Verificar Permisos
- [ ] Crear usuario con rol "Vendedor" (sin permisos de servicios)
- [ ] Iniciar sesión como vendedor
- [ ] Verificar que NO aparece menú "Servicios"
- [ ] Verificar que no puede acceder a /services (debe redirigir o mostrar 403)

---

## ❌ COSAS QUE NO DEBES HACER

### ❌ NO ejecutar `php artisan migrate:fresh` en producción
- Esto eliminará TODOS los datos existentes
- Solo usar `php artisan migrate`

### ❌ NO ejecutar DatabaseSeeder completo
- Esto puede duplicar permisos y crear datos de prueba
- Solo ejecutar seeders específicos

### ❌ NO modificar directamente en producción
- Hacer cambios en desarrollo primero
- Probar localmente
- Luego desplegar

### ❌ NO olvidar el backup
- SIEMPRE hacer backup antes de cambios en BD
- Guardar el backup en lugar seguro

---

## 🐛 PROBLEMAS COMUNES Y SOLUCIONES

### Problema: "Permission denied" al guardar
**Causa:** Permisos de archivos incorrectos
**Solución:**
```bash
chmod -R 755 storage
chown -R www-data:www-data storage
```

### Problema: "Table 'services' doesn't exist"
**Causa:** Migración no ejecutada
**Solución:**
```bash
php artisan migrate
```

### Problema: "Access denied" al acceder a /services
**Causa:** Usuario no tiene permisos
**Solución:**
```bash
# Verificar permisos del usuario:
php artisan tinker --execute="
\$user = App\Models\User::where('email', 'usuario@email.com')->first();
echo 'Tiene permiso services.view: ' . (\$user->hasPermission('services.view') ? 'SÍ' : 'NO');
"
```

### Problema: Menú "Servicios" no aparece
**Causa:** Usuario no tiene permiso services.view
**Solución:** Asignar permiso al rol del usuario

### Problema: CSRF token mismatch
**Causa:** Caché desactualizada
**Solución:**
```bash
php artisan config:clear
php artisan cache:clear
```

### Problema: Modal no abre o no guarda
**Causa:** Error de JavaScript o AJAX
**Solución:**
- Abrir consola del navegador (F12)
- Ver errores en tab "Console"
- Verificar respuestas en tab "Network"

---

## 📊 VERIFICACIONES POST-DESPLIEGUE

### Base de Datos
```sql
-- Verificar tabla services
SELECT COUNT(*) as total_services FROM services;

-- Verificar permisos
SELECT * FROM permissions WHERE module = 'servicios';

-- Verificar roles con permisos de servicios
SELECT r.name, COUNT(rp.permission_id) as cant_permisos
FROM roles r
LEFT JOIN role_permission rp ON r.id = rp.role_id
LEFT JOIN permissions p ON rp.permission_id = p.id
WHERE p.module = 'servicios'
GROUP BY r.id, r.name;
```

### Logs de Laravel
```bash
# Ver últimos errores:
tail -f storage/logs/laravel.log
```

### Performance
```bash
# Verificar tiempo de carga del DataGrid
# (Abrir F12 → Network → services/data)
# Debe cargar en < 500ms
```

---

## ✅ CHECKLIST FINAL

Antes de considerar el despliegue completo:

- [ ] Backup de base de datos realizado
- [ ] Migraciones ejecutadas sin errores
- [ ] Permisos agregados al sistema
- [ ] Roles actualizados con permisos de servicios
- [ ] Caché limpiada
- [ ] Acceso al menú verificado
- [ ] CRUD completo probado (Create, Read, Update, Delete)
- [ ] Búsqueda probada
- [ ] Validaciones probadas
- [ ] Permisos probados (con usuario sin acceso)
- [ ] Sin errores en storage/logs/laravel.log
- [ ] Sin errores en consola del navegador (F12)
- [ ] Performance aceptable (< 500ms)

---

## 📝 NOTAS IMPORTANTES

1. **Multi-tenancy:** El sistema está diseñado para multi-tenancy. Cada servicio pertenece a un tenant_id. Verificar que el usuario logueado tenga tenant_id correcto.

2. **Códigos automáticos:** Los códigos se generan automáticamente (SRV-00001, SRV-00002, etc.) basados en el último servicio del tenant.

3. **Relación con ventas:** Si un servicio tiene ventas asociadas, NO se puede eliminar. Solo se puede desactivar.

4. **IVA paraguayo:** El sistema usa la fórmula paraguaya donde el IVA está incluido en el precio: `IVA = Precio × tasa / (100 + tasa)`

5. **Comisiones:** El campo commission_percentage es opcional. Si está vacío, se usará el porcentaje del usuario.

6. **POS:** Los campos color e icon son para la futura interfaz POS. Son opcionales.

---

## 🚀 SIGUIENTE FASE

Una vez que los servicios estén funcionando en producción, la siguiente fase será:

**Fase 3: Autenticación POS**
- Login con PIN
- Login con RFID
- Gestión de sesiones
- Timeout automático

No implementar Fase 3 hasta que Fase 2 esté 100% funcional y probada en producción.
