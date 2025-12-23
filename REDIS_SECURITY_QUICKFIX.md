# 🚨 REDIS SECURITY - QUICK FIX (15 MINUTOS)

## ⚠️ ACCIÓN INMEDIATA REQUERIDA

**Severidad**: CRÍTICA
**Tiempo**: 15 minutos
**Downtime**: ~2 minutos

---

## 📋 Checklist de Aplicación

### ✅ Paso 1: Generar Contraseña Segura (1 min)

En tu computadora local:

```bash
openssl rand -base64 32
```

**Copia el resultado**, lo necesitarás en el Paso 2.

Ejemplo de resultado: `K7mP9nQ2rT5vX8zA1bC4dE6fG7hJ9kL0mN3oP5qR8sT=`

---

### ✅ Paso 2: Conectar al Servidor (1 min)

```bash
ssh root@146.190.120.242
cd /path/to/neo-erp  # Ajusta la ruta según tu instalación
```

---

### ✅ Paso 3: Actualizar Código (2 min)

```bash
# Obtener últimos cambios
git pull origin main

# Verificar que docker-compose.yml fue actualizado
grep -A5 "redis:" docker-compose.yml

# Debes ver que el puerto 6379 está comentado:
# # ports:
# #   - "${REDIS_PORT:-6379}:6379"  # ❌ ELIMINADO por seguridad
```

---

### ✅ Paso 4: Actualizar .env (3 min)

```bash
nano .env
```

**Busca estas líneas y actualízalas**:

```env
# ANTES:
# REDIS_HOST=127.0.0.1
# REDIS_PASSWORD=null

# DESPUÉS:
REDIS_HOST=redis
REDIS_PASSWORD=PEGA_AQUI_LA_CONTRASEÑA_DEL_PASO_1
```

**Ejemplo**:
```env
REDIS_HOST=redis
REDIS_PASSWORD=K7mP9nQ2rT5vX8zA1bC4dE6fG7hJ9kL0mN3oP5qR8sT=
```

**Guardar**: `Ctrl+O` → Enter → `Ctrl+X`

---

### ✅ Paso 5: Recrear Contenedores (5 min)

**⚠️ ADVERTENCIA**: Esto cerrará todas las sesiones activas del POS y sistema web por ~2 minutos.

```bash
# Detener servicios
docker-compose down

# Verificar que todo está detenido
docker-compose ps

# Iniciar con nueva configuración
docker-compose up -d

# Esperar ~30 segundos para que Redis inicie
sleep 30

# Verificar estado
docker-compose ps
```

**Resultado esperado**:
```
NAME                 STATUS
neo-erp-app          Up
neo-erp-redis        Up
neo-erp-worker       Up
neo-erp-scheduler    Up
```

---

### ✅ Paso 6: Verificar Seguridad (2 min)

#### A. Desde FUERA del servidor (tu computadora):

```bash
telnet 146.190.120.242 6379
```

**✅ RESULTADO ESPERADO (CORRECTO)**:
```
Trying 146.190.120.242...
telnet: Unable to connect to remote host: Connection refused
```

**❌ RESULTADO INCORRECTO**:
```
Connected to 146.190.120.242.
```
Si ves esto, Redis SIGUE expuesto. Contacta soporte.

#### B. Verificar conexión interna (desde el servidor):

```bash
docker-compose exec app php artisan tinker
```

Dentro de tinker:
```php
Redis::ping()
// Debe retornar: "PONG"

Redis::set('test', 'hello')
Redis::get('test')
// Debe retornar: "hello"

exit
```

---

### ✅ Paso 7: Verificar Aplicación Funciona (1 min)

#### Accede a tu aplicación:

```
https://tu-dominio.com
```

1. Intenta **login**
2. Navega al **dashboard**
3. Intenta acceder al **POS**

**Todo debe funcionar normalmente.**

Si algo falla, revisa logs:
```bash
docker-compose logs app
docker-compose logs redis
```

---

### ✅ Paso 8: Responder a DigitalOcean (opcional)

Responde al ticket de seguridad:

```
Hello DigitalOcean Security Team,

Thank you for the security notification.

I have secured Redis by:
1. Removing public port exposure from docker-compose.yml
2. Adding password authentication
3. Verified with telnet - port 6379 is now refusing connections

Redis is only accessible internally via Docker network.

Best regards
```

---

## 🔍 Troubleshooting

### ❌ Error: "NOAUTH Authentication required"

**Solución**:
```bash
# Verificar que REDIS_PASSWORD está en .env
grep REDIS_PASSWORD .env

# Reiniciar aplicación
docker-compose restart app worker scheduler
```

### ❌ Error: "Could not connect to Redis"

**Solución**:
```bash
# Ver logs de Redis
docker-compose logs redis

# Reiniciar Redis
docker-compose restart redis
```

### ❌ Sesiones se pierden

**Solución**:
```bash
# Verificar que Redis está persistiendo datos
docker-compose exec redis redis-cli -a "TU_PASSWORD" INFO persistence

# Debe mostrar: appendonly:yes
```

---

## ✅ Verificación Final

Después de aplicar TODO:

- [ ] `telnet 146.190.120.242 6379` → **Connection refused** ✅
- [ ] `Redis::ping()` → **PONG** ✅
- [ ] Login funciona ✅
- [ ] POS funciona ✅
- [ ] Dashboard carga correctamente ✅

---

## 📞 Soporte

Si encuentras problemas:

1. Revisa logs: `docker-compose logs redis app`
2. Verifica `.env`: `cat .env | grep REDIS`
3. Revisa la documentación completa: [docs/SEGURIDAD_REDIS.md](docs/SEGURIDAD_REDIS.md)

---

**TIEMPO TOTAL**: ~15 minutos
**CRITICIDAD**: 🔴 URGENTE - Aplicar HOY

---

## 📝 Archivos Modificados

- ✅ `docker-compose.yml` - Puerto 6379 removido
- ✅ `.env.example` - Redis password agregado
- ✅ `docs/SEGURIDAD_REDIS.md` - Documentación completa creada
