# 🔒 Seguridad Redis - Configuración Crítica

## 🚨 Alerta de Seguridad

**Fecha**: 2025-12-19
**Severidad**: CRÍTICA
**Problema**: Redis expuesto públicamente en puerto 6379

---

## ❌ Problema Detectado

DigitalOcean detectó que Redis estaba **expuesto públicamente** en el puerto 6379, permitiendo que cualquier persona en internet pueda:

- ✅ Conectarse a Redis sin autenticación
- ✅ Leer todos los datos (sesiones, cache)
- ✅ Escribir datos arbitrarios
- ✅ Ejecutar comandos peligrosos
- ✅ Potencialmente comprometer el servidor

### Causa Raíz

En `docker-compose.yml` línea 76:

```yaml
ports:
  - "${REDIS_PORT:-6379}:6379"  # ❌ EXPONE Redis públicamente
```

**Esto mapea el puerto 6379 del contenedor al puerto 6379 del host**, haciendo que Redis sea accesible desde internet.

---

## ✅ Solución Implementada

### 1. Eliminado el Mapeo de Puerto Público

**Archivo**: `docker-compose.yml`

```yaml
redis:
  image: redis:7-alpine
  container_name: neo-erp-redis
  restart: unless-stopped
  command: redis-server --appendonly yes --requirepass "${REDIS_PASSWORD}"
  volumes:
    - redis-data:/data
  networks:
    - neo-erp-network
  # SEGURIDAD: NO exponer Redis públicamente
  # Los contenedores dentro de neo-erp-network pueden acceder vía redis:6379
  # NO se necesita mapear el puerto al host
  # ports:
  #   - "${REDIS_PORT:-6379}:6379"  # ❌ ELIMINADO por seguridad
```

**Resultado**:
- ✅ Redis solo accesible dentro de la red Docker `neo-erp-network`
- ✅ Contenedores `app`, `worker`, `scheduler` pueden acceder vía `redis:6379`
- ✅ **NO accesible desde internet** ✅

### 2. Agregado Autenticación con Contraseña

**Comando Redis actualizado**:

```yaml
command: redis-server --appendonly yes --requirepass "${REDIS_PASSWORD}"
```

**Archivo `.env`**: (Debes configurar)

```env
REDIS_PASSWORD=tu_contraseña_muy_segura_aquí
```

**Generación de contraseña segura**:

```bash
# Opción 1: OpenSSL (recomendado)
openssl rand -base64 32

# Opción 2: /dev/urandom
cat /dev/urandom | tr -dc 'a-zA-Z0-9!@#$%^&*' | fold -w 32 | head -n 1

# Opción 3: pwgen (si está instalado)
pwgen -s 32 1
```

---

## 🚀 Pasos de Despliegue en Producción

### Paso 1: Actualizar Archivo `.env` en Producción

Conéctate a tu Droplet de DigitalOcean:

```bash
ssh root@146.190.120.242
cd /ruta/a/neo-erp
```

Edita el archivo `.env`:

```bash
nano .env
```

Actualiza las siguientes variables:

```env
# Redis Configuration
REDIS_HOST=redis
REDIS_PASSWORD=TU_CONTRASEÑA_SEGURA_AQUI  # Genera una con openssl rand -base64 32
REDIS_PORT=6379

# Cache y Session usando Redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

**⚠️ IMPORTANTE**: Genera una contraseña segura única para producción.

### Paso 2: Actualizar `docker-compose.yml`

Ya actualizado en este commit. Solo verifica que tienes la última versión:

```bash
git pull origin main
```

### Paso 3: Detener y Recrear Contenedores

**⚠️ ADVERTENCIA**: Esto cerrará sesiones activas y limpiará cache.

```bash
# Detener todos los servicios
docker-compose down

# Eliminar volumen de Redis (opcional, para empezar limpio)
docker volume rm neo-erp_redis-data

# Recrear servicios con nueva configuración
docker-compose up -d

# Verificar que Redis está corriendo
docker-compose ps

# Ver logs de Redis
docker-compose logs redis
```

### Paso 4: Verificar que Redis NO es Accesible Públicamente

Desde tu computadora local (NO desde el servidor):

```bash
telnet 146.190.120.242 6379
```

**Resultado esperado**:

```
Trying 146.190.120.242...
telnet: Unable to connect to remote host: Connection refused
```

✅ **Si recibes "Connection refused" = ÉXITO** - Redis no es accesible públicamente.

❌ **Si te conectas = FALLO** - Redis sigue expuesto, revisar configuración.

### Paso 5: Verificar Conexión Interna de Laravel a Redis

```bash
# Entrar al contenedor de la aplicación
docker-compose exec app sh

# Probar conexión a Redis
php artisan tinker

# Dentro de tinker:
>>> Redis::ping()
# Debe retornar: "PONG"

>>> Redis::set('test', 'hello')
>>> Redis::get('test')
# Debe retornar: "hello"

>>> exit
```

Si funciona correctamente, Redis está configurado y seguro.

---

## 🔥 Configuración Adicional de Seguridad (Recomendado)

### 1. Firewall DigitalOcean Cloud Firewall

Aunque Redis ya no está expuesto, es buena práctica agregar un firewall:

**Pasos**:
1. Ir a DigitalOcean Panel → Networking → Firewalls
2. Crear nuevo Firewall:
   - **Inbound Rules**:
     - HTTP (80) desde Anywhere
     - HTTPS (443) desde Anywhere
     - SSH (22) desde **TU IP solamente** (más seguro)
   - **Outbound Rules**:
     - All Traffic
3. Aplicar a tu Droplet `ubuntu-s-2vcpu-4gb-amd-sfo3-01`

### 2. Configurar Redis ACL (Access Control Lists)

Para seguridad adicional, crear usuarios específicos en Redis:

**Crear archivo de configuración**:

```bash
# En el servidor
mkdir -p docker/redis
nano docker/redis/redis.conf
```

**Contenido** (`docker/redis/redis.conf`):

```conf
# Seguridad básica
bind 127.0.0.1
protected-mode yes
port 6379

# Contraseña por defecto (legacy)
requirepass ${REDIS_PASSWORD}

# Persistencia
appendonly yes
appendfilename "appendonly.aof"

# ACL - Usuarios específicos
# Usuario para Laravel (lectura/escritura limitada)
user laravel on >${REDIS_PASSWORD} ~* &* +@all -@dangerous

# Deshabilitar usuario default
user default off
```

**Actualizar `docker-compose.yml`**:

```yaml
redis:
  image: redis:7-alpine
  container_name: neo-erp-redis
  restart: unless-stopped
  command: redis-server /usr/local/etc/redis/redis.conf
  volumes:
    - redis-data:/data
    - ./docker/redis/redis.conf:/usr/local/etc/redis/redis.conf:ro
  networks:
    - neo-erp-network
```

### 3. Monitoreo de Redis

**Ver estadísticas de Redis**:

```bash
docker-compose exec redis redis-cli -a "${REDIS_PASSWORD}" INFO

# Ver comandos ejecutados
docker-compose exec redis redis-cli -a "${REDIS_PASSWORD}" MONITOR
```

---

## 📊 Verificación de Seguridad - Checklist

Después de aplicar los cambios:

- [ ] **Puerto 6379 NO accesible públicamente** (`telnet` falla desde fuera)
- [ ] **Redis requiere contraseña** (configurado en `.env`)
- [ ] **Laravel se conecta correctamente** (`Redis::ping()` funciona)
- [ ] **Cache funciona** (sesiones de usuario persisten)
- [ ] **Queue funciona** (jobs se procesan)
- [ ] **Firewall configurado** (solo puertos necesarios abiertos)
- [ ] **Logs de Redis sin errores** (`docker-compose logs redis`)

---

## 🔍 Comandos Útiles para Diagnóstico

### Verificar puertos abiertos en el servidor

```bash
# Desde el servidor (Droplet)
sudo netstat -tuln | grep 6379
```

**Resultado esperado**:
```
# Sin resultados = Redis NO está escuchando en interfaces públicas ✅
```

### Verificar configuración de Redis

```bash
docker-compose exec redis redis-cli -a "${REDIS_PASSWORD}" CONFIG GET "*"
```

### Ver clientes conectados

```bash
docker-compose exec redis redis-cli -a "${REDIS_PASSWORD}" CLIENT LIST
```

**Debes ver solo**:
- Conexiones desde `app` container
- Conexiones desde `worker` container
- Conexiones desde `scheduler` container

**NO debes ver**: Conexiones desde IPs externas

### Backup de Redis

```bash
# Crear backup manual
docker-compose exec redis redis-cli -a "${REDIS_PASSWORD}" BGSAVE

# Ver última vez que se guardó
docker-compose exec redis redis-cli -a "${REDIS_PASSWORD}" LASTSAVE

# Copiar dump.rdb del contenedor
docker cp neo-erp-redis:/data/appendonly.aof ./backup-redis-$(date +%Y%m%d).aof
```

---

## ⚠️ Errores Comunes y Soluciones

### Error: "NOAUTH Authentication required"

**Problema**: Laravel no puede conectarse porque falta contraseña.

**Solución**:
```env
# En .env
REDIS_PASSWORD=tu_contraseña_aqui
```

Luego reiniciar:
```bash
docker-compose restart app worker scheduler
```

### Error: "Could not connect to Redis"

**Problema**: Redis no está corriendo o nombre de host incorrecto.

**Solución**:
```bash
# Verificar que Redis está corriendo
docker-compose ps redis

# Ver logs
docker-compose logs redis

# Reiniciar Redis
docker-compose restart redis
```

### Error: Sesiones se pierden constantemente

**Problema**: Redis se reinicia frecuentemente o persistencia no funciona.

**Solución**:
```bash
# Verificar que appendonly está habilitado
docker-compose exec redis redis-cli -a "${REDIS_PASSWORD}" CONFIG GET appendonly

# Debe retornar: appendonly yes

# Si no, actualizar comando en docker-compose.yml
```

---

## 📝 Respuesta a DigitalOcean

Una vez aplicados los cambios, puedes responder al ticket:

```
Hello DigitalOcean Security Team,

Thank you for the security notification regarding Redis on port 6379.

I have implemented the following security measures:

1. Removed public port mapping from docker-compose.yml
   - Redis is now only accessible within the internal Docker network
   - Port 6379 is NOT exposed to the host or internet

2. Configured Redis authentication with a strong password
   - Added --requirepass flag to Redis configuration
   - Updated application to use authenticated connection

3. Verified the fix:
   - telnet 146.190.120.242 6379 → Connection refused ✅
   - Redis is only accessible internally by application containers

The Redis service is intentionally running in a Docker container and is now properly secured.
It is NOT accessible from the public internet.

Please confirm this resolves the security concern.

Best regards
```

---

## 🔐 Mejores Prácticas - Resumen

### ✅ DO (Hacer):
- Usar contraseñas fuertes para Redis (32+ caracteres)
- NO exponer puertos de servicios internos (Redis, DB)
- Usar redes Docker internas para comunicación entre servicios
- Configurar firewall (Cloud Firewall o UFW)
- Monitorear logs de Redis regularmente
- Hacer backups de Redis periódicamente

### ❌ DON'T (No hacer):
- Nunca exponer Redis públicamente sin autenticación
- Nunca usar contraseñas débiles o defaults
- Nunca deshabilitar `protected-mode` en Redis
- Nunca permitir comandos peligrosos en producción
- Nunca mapear puertos innecesariamente en Docker

---

## 📚 Referencias

- [Redis Security Documentation](https://redis.io/topics/security)
- [Docker Networking Best Practices](https://docs.docker.com/network/)
- [DigitalOcean Redis Tutorial](https://www.digitalocean.com/community/tutorials/how-to-install-and-secure-redis-on-ubuntu-20-04)
- [Redis ACL Documentation](https://redis.io/topics/acl)

---

## 📅 Historial de Cambios

| Fecha | Cambio | Autor |
|-------|--------|-------|
| 2025-12-19 | Eliminado mapeo público de puerto 6379 | Claude Code |
| 2025-12-19 | Agregada autenticación con contraseña | Claude Code |
| 2025-12-19 | Documentación de seguridad creada | Claude Code |

---

## 🚨 Acción Requerida INMEDIATA

**DEBES aplicar estos cambios en producción LO ANTES POSIBLE.**

Redis expuesto públicamente es un riesgo de seguridad **CRÍTICO** que puede resultar en:
- Robo de datos de sesiones de usuarios
- Exposición de información sensible
- Compromiso del servidor
- Pérdida de confianza de clientes

**Tiempo estimado de aplicación**: 10-15 minutos
**Downtime requerido**: ~2 minutos (durante `docker-compose down/up`)

---

**Prioridad**: 🔴 URGENTE - Aplicar hoy
