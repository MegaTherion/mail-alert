# DEPLOY_URL Secret - Health Check en Production

## ✅ Cambio Realizado

Se agregó soporte para verificar la salud del API en el servidor real durante el deploy.

## 📋 El Problema

**Antes**:
```bash
curl -s http://localhost:8000/api/alerts  # ❌ Incorrecto
```

En GitHub Actions, `localhost` apunta a la máquina de GitHub, no a tu servidor.

**Ahora**:
```bash
DEPLOY_URL="${{ secrets.DEPLOY_URL }}"
DEPLOY_URL=${DEPLOY_URL:-http://localhost:8000}

curl -s ${DEPLOY_URL}/api/alerts  # ✅ Correcto
```

## 🚀 Configuración Requerida

En GitHub → Settings → Secrets → Agregar nuevo secret:

```
Name: DEPLOY_URL
Value: https://tu-dominio.com
o
Value: https://api.ejemplo.com
o
Value: http://192.168.1.100
```

### Ejemplos

```
Opción 1: Con dominio (recomendado)
DEPLOY_URL = https://mail-alert.ejemplo.com

Opción 2: Con IP
DEPLOY_URL = http://192.168.1.100

Opción 3: Con puerto custom
DEPLOY_URL = https://tu-dominio.com:8443

Opción 4: Omitir (fallback a localhost, útil solo para desarrollo)
No configurar - usa http://localhost:8000
```

## 📊 Secretos Totales Requeridos

| Secret | Requerido | Ejemplo |
|--------|-----------|---------|
| SSH_PRIVATE_KEY | ✅ | Contenido de deploy_key |
| SSH_HOST | ✅ | servidor.com |
| SSH_USER | ✅ | deploy |
| SSH_PROJECT_PATH | ✅ | /var/www/mail-alert |
| ALERT_SECRET | ✅ | tu_clave_secreta |
| SSH_PORT | ❌ | 2222 (solo si ≠ 22) |
| **DEPLOY_URL** | ❌ | https://tu-dominio.com |

## 🔄 Flujo de Verificación

Ahora el workflow hace:

```
1. ✅ Tests en GitHub
2. ✅ Deploy vía SSH
   ├─ Git pull
   ├─ Composer install
   ├─ Migrations
   ├─ Cache
   └─ Permisos
3. ✅ Health Check (con DEPLOY_URL)
   └─ curl ${DEPLOY_URL}/api/alerts
4. ✅ Verificar logs en servidor
```

## 📝 Checklist

- [ ] Agregar secret `DEPLOY_URL` en GitHub
- [ ] Configurar con URL real de tu servidor
- [ ] Next push a `main` → workflow verificará salud correctamente

## 🎯 Resultado

El workflow ahora verifica que el API está respondiendo correctamente en tu servidor real, no en localhost de GitHub. 🎉

---

**Nota**: Si no configuras `DEPLOY_URL`, el workflow seguirá funcionando pero la verificación fallará (es opcional, el deploy completa incluso si falla la verificación).
