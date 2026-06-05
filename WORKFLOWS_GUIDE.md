# GitHub Actions Workflows Guide

Guía de los workflows disponibles para deployar el backend.

## 📋 Workflows Disponibles

### 1. **deploy.yml** (Recomendado para comenzar)

**Archivo**: `.github/workflows/deploy.yml`

**Propósito**: Deploy simple y directo a un servidor de producción

**Características**:
- ✅ Testing automático
- ✅ Deploy vía SSH
- ✅ Migraciones automáticas
- ✅ Cache clearing
- ✅ Verificación de salud
- ✅ Fácil de configurar

**Cuándo usar**:
- Tienes un solo servidor
- Principiante en GitHub Actions
- No necesitas staging environment

**Branches que triggerean**:
- `main` → Deploy automático

### 2. **deploy-advanced.yml.example** (Para producción con staging)

**Archivo**: `.github/workflows/deploy-advanced.yml.example`

**Propósito**: Deploy a múltiples environments con características avanzadas

**Características**:
- ✅ Staging + Production separados
- ✅ Notificaciones Slack
- ✅ Release tags automáticos
- ✅ Rollback automático en fallos
- ✅ Health checks mejorados
- ✅ Database backups
- ✅ Concurrencia controlada
- ✅ Manual workflow dispatch

**Cuándo usar**:
- Quieres staging + production
- Necesitas notificaciones Slack
- Quieres releases automáticas
- Necesitas rollback automático

**Branches que triggerean**:
- `develop` → Deploy a staging
- `main` → Deploy a production
- Tambien permite ejecución manual

## 🚀 Quick Comparison

| Característica | deploy.yml | deploy-advanced.yml |
|---|---|---|
| Servidores | 1 (Production) | 2 (Staging + Prod) |
| Complejidad | Baja | Alta |
| Notificaciones Slack | ❌ | ✅ |
| Rollback automático | ❌ | ✅ |
| Release tags | ❌ | ✅ |
| Database backup | ❌ | ✅ |
| Health checks | Básico | Avanzado |
| Ejecución manual | Automática | Manual + Automática |

## 🎯 Recomendaciones

### Para Desarrollo / Pequeño Proyecto

Usa **deploy.yml**:

```bash
# Apenas needs:
# - 1 servidor
# - SSH access
# - PHP + Composer
```

### Para Producción / Equipo

Usa **deploy-advanced.yml**:

```bash
# Beneficios:
# - Staging para testing
# - Production separada
# - Notificaciones al team
# - Rollback automático
# - Releases documentadas
```

## 📋 Configuración Requerida por Workflow

### deploy.yml

**Secrets requeridos**:
- `SSH_PRIVATE_KEY` - Clave SSH privada
- `SSH_HOST` - Dominio/IP del servidor
- `SSH_USER` - Usuario SSH
- `SSH_PROJECT_PATH` - Ruta del proyecto
- `ALERT_SECRET` - Variable de app (opcional)

**Total**: 5 secrets

### deploy-advanced.yml

**Secrets requeridos**:
- `STAGING_SSH_PRIVATE_KEY` - Clave SSH staging
- `STAGING_SSH_HOST` - Servidor staging
- `STAGING_SSH_USER` - Usuario staging
- `STAGING_SSH_PROJECT_PATH` - Ruta staging
- `SSH_PRIVATE_KEY` - Clave SSH producción
- `SSH_HOST` - Servidor producción
- `SSH_USER` - Usuario producción
- `SSH_PROJECT_PATH` - Ruta producción
- `ALERT_SECRET` - Variable de app
- `SLACK_WEBHOOK` - URL webhook Slack (opcional)

**Total**: 10 secrets

## 🔄 Flujo de Deployments

### deploy.yml

```
Push a main (backend/)
    ↓
Tests
    ↓
Deploy a Production
    ↓
Verify
    ↓
Done
```

### deploy-advanced.yml

```
Push a develop (backend/)
    ↓
Tests
    ↓
Deploy a Staging
    ↓
Health Check
    ↓
Slack Notification
    ↓
    
Push a main (backend/)
    ↓
Tests
    ↓
Pre-Deploy Checks
    ↓
Database Backup
    ↓
Deploy a Production
    ↓
Health Check
    ↓
Create Release Tag
    ↓
Slack Notification
    ↓
Done (o Rollback si falla)
```

## 📦 Cómo Usar

### Opción 1: Usar deploy.yml (Recomendado para comenzar)

```bash
# Ya está creado y listo para usar
# Solo necesitas configurar los secrets en GitHub

# Ver: GITHUB_ACTIONS_SETUP.md para configuración
```

### Opción 2: Usar deploy-advanced.yml (Más features)

```bash
# Renombrar archivo
mv .github/workflows/deploy-advanced.yml.example \
   .github/workflows/deploy-advanced.yml

# Eliminar deploy.yml si no lo necesitas
rm .github/workflows/deploy.yml

# Ver: GITHUB_ACTIONS_SETUP.md para configuración
# (Agregar secrets de staging también)
```

### Opción 3: Usar ambos

```bash
# Mantener deploy.yml para producción
# Renombrar deploy-advanced para staging en rama develop

# Editar deploy-advanced.yml:
# - Remover deploy-production job
# - Solo mantener deploy-staging
# - Cambiar rama a develop
```

## 🔍 Entender cada Workflow

### deploy.yml - Línea por línea

```yaml
# Nombre del workflow
name: Deploy Backend to Production

# Qué triggerean esto
on:
  push:
    branches:
      - main  # Solo cuando pushes a main
    paths:
      - 'backend/**'  # Solo si cambiaron archivos en backend

# Variables de entorno globales
env:
  LARAVEL_ENV: production

# Jobs = tareas a ejecutar
jobs:
  test:    # Primero ejecuta tests
    ...
  
  deploy:  # Luego deploy (solo si tests pasaron)
    needs: test
    ...
  
  notify:  # Finalmente notifica resultado
    needs: deploy
    ...
```

### deploy-advanced.yml - Estructura

```yaml
# Branches
on:
  push:
    branches:
      - develop  # → deploy-staging
      - main     # → deploy-production

# Jobs
jobs:
  test:                # Tests
  deploy-staging:      # Deploy a staging (develop)
  deploy-production:   # Deploy a production (main)
  rollback:            # Rollback si falla
```

## 🎬 Ejemplos de Uso

### Ejemplo 1: Deploy Simple a Production

```bash
# 1. Hacer cambios en backend
vim backend/app/Http/Controllers/MailAlertController.php

# 2. Commit y push a main
git add backend/
git commit -m "feat: improve alert handling"
git push origin main

# 3. GitHub Actions automáticamente:
#    - Corre tests
#    - Deploy a production
#    - Verifica salud
#    - ¡Done!
```

### Ejemplo 2: Staging + Production

```bash
# 1. Hacer cambios en rama develop
git checkout develop
vim backend/app/Services/FCMService.php
git commit -m "feat: improve FCM error handling"
git push origin develop

# 2. GitHub Actions:
#    - Tests
#    - Deploy a STAGING
#    - Notifica en Slack

# 3. Después de verificar en staging, merge a main
git checkout main
git merge develop
git push origin main

# 4. GitHub Actions:
#    - Tests
#    - Database backup
#    - Deploy a PRODUCTION
#    - Create release tag
#    - Notifica en Slack
```

### Ejemplo 3: Ejecutar Deploy Manual

```bash
# En GitHub:
# 1. Actions tab
# 2. Select workflow (deploy-advanced.yml)
# 3. "Run workflow" button
# 4. Seleccionar environment (staging o production)
# 5. Execute

# Útil para:
# - Deploy sin hacer push
# - Testing del workflow
# - Deployments planeados
```

## 🐛 Debugging Workflows

### Ver logs en GitHub

```
1. Actions tab
2. Click en el workflow
3. Click en el job
4. Ver output de cada step
```

### Ver en qué falló

```yaml
# Busca en los logs el paso que falló
# Ejemplo: "Run Deploy via SSH" ← falló aquí

# Causa probable:
# - SSH key incorrecta
# - Servidor no alcanzable
# - Credenciales incorrectas
```

### Rerun un workflow

```
1. Actions tab
2. Click en el workflow fallido
3. "Re-run all jobs" o "Re-run failed jobs"
```

## 🔒 Seguridad

### Secrets Seguros

- ✅ Nunca commits secrets
- ✅ Usar secrets en GitHub, no env vars
- ✅ Cambiar secrets periódicamente
- ✅ SSH keys con permiso restringido

### IP Whitelist (Opcional)

```bash
# En authorized_keys del servidor
from="140.82.112.0/24,143.55.64.0/22" ssh-rsa AAAA...
```

### Proteger main branch

```
1. Settings → Branches
2. Add rule: main
3. Require status checks to pass
4. Require pull request reviews
```

## ⚙️ Personalización

### Cambiar qué triggerean los workflows

```yaml
on:
  # Solo tags
  push:
    tags:
      - 'v*'

  # Solo ciertas carpetas
  paths:
    - 'backend/**'
    - '!backend/tests/**'  # Excepto tests

  # Schedule (cron)
  schedule:
    - cron: '0 2 * * *'  # Todos los días a las 2 AM

  # Manual
  workflow_dispatch:
```

### Agregar más pasos

```yaml
- name: Custom Step
  run: |
    cd backend
    # Tu comando aquí
    php artisan your:command
```

### Agregar notificaciones

```yaml
- name: Discord Notification
  uses: sarisia/actions-status-discord@v1
  with:
    webhook_url: ${{ secrets.DISCORD_WEBHOOK }}
    status: ${{ job.status }}
```

## 📊 Monitoring

### Ver todas las ejecuciones

```
Actions tab → Todos los workflows
```

### Estadísticas

```
Actions tab → cada workflow → "All workflow runs"
```

### Fallos recientes

```
Actions tab → Workflows que tienen ❌
```

## 🆘 Troubleshooting Rápido

| Problema | Causa | Solución |
|----------|-------|----------|
| Workflow no se ejecuta | Branch no es main/develop | Pushear a rama correcta |
| SSH auth failed | Key incorrecta | Verificar SSH_PRIVATE_KEY secret |
| Composer not found | Path incorrecto | Verificar SSH_PROJECT_PATH |
| Deploy no ve cambios | Caché viejo | Workflow limpia caché |
| Migración falló | BD locked | Ver GITHUB_ACTIONS_SETUP.md |

## 📚 Recursos

- **Guía completa**: [GITHUB_ACTIONS_SETUP.md](./GITHUB_ACTIONS_SETUP.md)
- **Workflow simple**: [.github/workflows/deploy.yml](./.github/workflows/deploy.yml)
- **Workflow avanzado**: [.github/workflows/deploy-advanced.yml.example](./.github/workflows/deploy-advanced.yml.example)

## 🎓 Próximos Pasos

1. **Elige workflow**: Simple (deploy.yml) o Avanzado (deploy-advanced.yml)
2. **Configura secrets**: Ver GITHUB_ACTIONS_SETUP.md
3. **Prepara servidor**: Ver GITHUB_ACTIONS_SETUP.md
4. **Haz test push**: Verifica que funciona
5. **Configura alertas**: Slack/Discord si quieres notificaciones

## 🎉 ¡Listo!

Una vez configurado, tendrás:

✅ Deploy automático con git push  
✅ Tests antes de cada deploy  
✅ Verificación de salud  
✅ Rollback si falla  
✅ Notificaciones en Slack  
✅ Releases documentadas  

¡Tu CI/CD pipeline está listo! 🚀
