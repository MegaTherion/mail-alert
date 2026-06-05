# GitHub Actions Deploy Setup

Guía completa para configurar el deploy automático en GitHub Actions.

## 📋 Prerequisites

- Repositorio en GitHub
- Servidor con acceso SSH
- SSH key para autenticación
- PHP 8.3+ en el servidor
- Composer instalado en el servidor
- Git configurado en el servidor

## 🔑 1. Generar SSH Key para Deploy

### En tu máquina local

Elige UNO de estos (ED25519 es más moderno y rápido):

**Opción A: ED25519** (Recomendado)
```bash
ssh-keygen -t ed25519 -f deploy_key -N ""
# Más rápido, más seguro, clave más corta
```

**Opción B: RSA** (Compatible)
```bash
ssh-keygen -t rsa -b 4096 -f deploy_key -N ""
# Más compatible, especialmente con sistemas antiguos
```

Esto crea:
- `deploy_key` (privada) - Para GitHub secret
- `deploy_key.pub` (pública) - Para servidor

Ver [SSH_CONFIGURATION.md](./SSH_CONFIGURATION.md) para más detalles sobre tipos de clave.

### En el servidor

```bash
# Como usuario deploy (recomendado)
ssh user@tu-servidor.com

# Agregar la clave pública al servidor
cat >> ~/.ssh/authorized_keys << 'EOF'
# Pega el contenido de deploy_key.pub aquí
EOF

# Permisos correctos
chmod 700 ~/.ssh
chmod 600 ~/.ssh/authorized_keys

# Verificar que funciona
ssh -i ~/deploy_key user@tu-servidor.com "echo 'SSH OK'"
```

## 🔐 2. Configurar GitHub Secrets

En tu repositorio GitHub: **Settings → Secrets and variables → Actions**

Crear los siguientes secrets:

### `SSH_PRIVATE_KEY`

Contenido del archivo `deploy_key` (privada):

```bash
cat deploy_key
# Copiar el contenido completo incluyendo -----BEGIN PRIVATE KEY-----
```

Pegar en GitHub como **SSH_PRIVATE_KEY**

### `SSH_HOST`

La dirección IP o dominio del servidor:
```
ejemplo.com
o
192.168.1.100
```

### `SSH_USER`

Usuario con el que conectarse:
```
deploy
o
ubuntu
o
ec2-user
```

### `SSH_PROJECT_PATH`

Ruta donde está el proyecto en el servidor:
```
/var/www/mail-alert
o
/home/deploy/mail-alert
o
/srv/mail-alert
```

### `SSH_PORT` (Opcional)

Puerto SSH del servidor (si no es el 22 por defecto):
```
2222
o
2200
o
cualquier_puerto_customizado
```

**Importante**: Solo agregar este secret si tu servidor usa un puerto SSH diferente al 22. Si usas puerto 22, NO es necesario agregar este secret (el workflow usa 22 por defecto).

### `ALERT_SECRET`

El mismo valor de `ALERT_SECRET` en `.env`:
```
tu_clave_secreta_de_32_caracteres
```

## ✅ 3. Verificar Configuración en GitHub

1. Ve a tu repositorio
2. **Settings → Secrets and variables → Actions**
3. Deberías ver:
   - ✅ SSH_PRIVATE_KEY
   - ✅ SSH_HOST
   - ✅ SSH_USER
   - ✅ SSH_PROJECT_PATH
   - ✅ ALERT_SECRET

## 🔄 4. Preparar el Servidor

### Estructura de directorios recomendada

```bash
# En el servidor, como usuario deploy
mkdir -p /var/www/mail-alert
cd /var/www/mail-alert

# Clonar el repositorio
git clone https://github.com/tuuser/mail-alert.git .

# O si ya existe
git init
git remote add origin https://github.com/tuuser/mail-alert.git
```

### Configurar permisos

```bash
# Como root o con sudo
chown -R deploy:deploy /var/www/mail-alert
chmod -R 755 /var/www/mail-alert
chmod -R 775 /var/www/mail-alert/backend/storage
chmod -R 775 /var/www/mail-alert/backend/bootstrap/cache
```

### Crear archivo .env

```bash
cd /var/www/mail-alert/backend

# Copiar del ejemplo
cp .env.example .env

# Editar valores
nano .env
```

Valores necesarios en `.env`:

```env
APP_NAME="Mail Alert"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com

DB_CONNECTION=sqlite
DB_DATABASE=/var/www/mail-alert/backend/database.sqlite

ALERT_SECRET=tu_clave_secreta_aqui
FCM_PROJECT_ID=tu-firebase-project-id
FCM_SERVICE_ACCOUNT_JSON=/var/www/mail-alert/backend/storage/firebase/service-account.json
```

### Crear directorio para Firebase

```bash
mkdir -p /var/www/mail-alert/backend/storage/firebase
cd /var/www/mail-alert/backend/storage/firebase

# Copiar service account JSON aquí
# Puedes usar SCP o SFTP desde tu máquina:
# scp service-account.json deploy@servidor:/var/www/mail-alert/backend/storage/firebase/
```

### Instalar dependencias iniciales

```bash
cd /var/www/mail-alert/backend

# Generar clave de app
php artisan key:generate --force

# Instalar dependencias
composer install --no-dev --prefer-dist

# Ejecutar migraciones
php artisan migrate --force

# Crear enlaces simbólicos (si usas storage público)
php artisan storage:link || true

# Permisos finales
chmod -R 775 storage bootstrap/cache
```

### Configurar servidor web (Nginx/Apache)

**Nginx:**

```nginx
server {
    listen 80;
    server_name tu-dominio.com;

    root /var/www/mail-alert/backend/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

**Apache:**

```apache
<VirtualHost *:80>
    ServerName tu-dominio.com
    DocumentRoot /var/www/mail-alert/backend/public

    <Directory /var/www/mail-alert/backend>
        AllowOverride All
        Require all granted
    </Directory>

    <FilesMatch \.php$>
        SetHandler "proxy:unix:/run/php/php8.3-fpm.sock|fcgi://localhost"
    </FilesMatch>
</VirtualHost>
```

## 🚀 5. Probar el Workflow

### Trigger manual

1. Ve a tu repositorio en GitHub
2. **Actions** tab
3. Selecciona **"Deploy Backend to Production"**
4. Click **"Run workflow"**

### Trigger automático

Simplemente haz un push a main branch en la carpeta backend:

```bash
git add backend/
git commit -m "feat: update backend"
git push origin main
```

## 📊 Monitorear el Deploy

### En GitHub

1. Ve a **Actions** tab
2. Selecciona el workflow más reciente
3. Haz click en el job para ver logs detallados

### En el servidor

```bash
# Ver logs de Laravel
ssh user@servidor
tail -f /var/www/mail-alert/backend/storage/logs/laravel.log

# Ver si hay errores recientes
grep -i error /var/www/mail-alert/backend/storage/logs/laravel.log | tail -20

# Verificar que app está corriendo
curl http://localhost:8000/api/alerts \
  -H "Authorization: Bearer tu_secret"
```

## ✨ Qué Hace el Workflow

### Fase 1: Test

```
✓ Checkout de código
✓ Setup PHP 8.3
✓ Cache de composer
✓ Instalar dependencias
✓ Verificar sintaxis PHP
✓ Ejecutar tests
```

### Fase 2: Deploy (solo en main)

```
✓ Checkout de código
✓ Setup SSH
✓ Conectarse al servidor
  ├─ Backup de .env
  ├─ Git pull del código
  ├─ Composer install
  ├─ Migraciones de BD
  ├─ Cache clear y generación
  ├─ Permisos de storage
  └─ Restart de queue workers (si existen)
✓ Verificar deployment
✓ Cleanup
```

### Fase 3: Notify

```
✓ Notificar success o failure
```

## 🔧 Configuración Avanzada

### Desplegar solo cambios en backend

El workflow incluye `paths` para solo ejecutarse cuando cambia backend:

```yaml
on:
  push:
    branches:
      - main
    paths:
      - 'backend/**'
      - '.github/workflows/deploy.yml'
```

Para cambiar esto, edita `.github/workflows/deploy.yml`

### Variables de entorno adicionales

Si necesitas variables adicionales, agrégalas como secretos:

```yaml
env:
  CUSTOM_VAR: ${{ secrets.CUSTOM_VAR }}
```

### Ejecutar comandos custom

Agregar antes de "Cleanup SSH":

```yaml
- name: Custom Deploy Tasks
  run: |
    ssh -i ~/.ssh/id_rsa ${{ secrets.SSH_USER }}@${{ secrets.SSH_HOST }} << 'EOF'
    cd ${{ secrets.SSH_PROJECT_PATH }}/backend
    
    # Tu comando aquí
    php artisan your:command
    
    EOF
```

## 🐛 Troubleshooting

### Error: "Permission denied (publickey)"

**Causa**: SSH key no configurada correctamente

**Solución**:
```bash
# Verificar en el servidor
cat ~/.ssh/authorized_keys | grep "SSH_PRIVATE_KEY"

# Verificar permisos
ls -la ~/.ssh/authorized_keys
# Debe ser: -rw------- (600)
```

### Error: "Composer: command not found"

**Causa**: Composer no instalado en el servidor

**Solución**:
```bash
# En el servidor
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### Error: "Database locked" o "migration failed"

**Causa**: Transacciones anteriores bloqueadas

**Solución**:
```bash
# En el servidor, para SQLite
rm database.sqlite-*
rm database.sqlite-journal

# O verificar permisos
chmod 775 storage/
```

### Deploy se ejecuta pero no ve cambios

**Causa**: Caché de aplicación

**Solución**:
```bash
# Agregar al workflow:
# El workflow ya lo hace con:
# php artisan config:cache
# php artisan route:cache
```

## 🔒 Seguridad

### Buenas prácticas

1. **SSH Key**: Generar una key nueva solo para deploy
2. **Usuario**: Usar un usuario específico `deploy` con permisos limitados
3. **Secrets**: Nunca commitear archivos con secrets
4. **IP Whitelist**: Opcional, whitelist de IPs de GitHub Actions

### Whitelist IP de GitHub

```bash
# Agregar a ~/.ssh/authorized_keys
# Antes de la SSH key:
from="140.82.112.0/24,143.55.64.0/22" ssh-rsa AAAA...

# Esto restringe a IPs de GitHub Actions
```

## 📈 Mejoras Futuras

### Agregar notificaciones

**Slack notification:**
```yaml
- name: Notify Slack
  if: always()
  uses: slackapi/slack-github-action@v1
  with:
    webhook-url: ${{ secrets.SLACK_WEBHOOK }}
```

### Agregar stage/production separados

```yaml
on:
  push:
    branches:
      - develop  # Deploy a staging
      - main     # Deploy a production
```

### Agregar health checks

```yaml
- name: Health Check
  run: |
    sleep 5
    curl -f http://tu-dominio.com/health || exit 1
```

### Agregar rollback automático

```yaml
- name: Automatic Rollback
  if: failure()
  run: |
    ssh -i ~/.ssh/id_rsa ${{ secrets.SSH_USER }}@${{ secrets.SSH_HOST }} << 'EOF'
    cd ${{ secrets.SSH_PROJECT_PATH }}/backend
    git reset --hard HEAD~1
    composer install --no-dev
    php artisan migrate:refresh --force
    EOF
```

## 📋 Checklist Final

- [ ] SSH key generada
- [ ] SSH key agregada a servidor
- [ ] Secrets configurados en GitHub
- [ ] Directorio del proyecto en servidor
- [ ] .env configurado en servidor
- [ ] service-account.json en servidor
- [ ] Composer instalado en servidor
- [ ] Servidor web configurado (Nginx/Apache)
- [ ] Permisos configurados en servidor
- [ ] Migraciones iniciales ejecutadas
- [ ] Workflow testeado manualmente
- [ ] Verificado en logs del servidor

## 🚀 Primero Deploy Manual

Antes de confiar en el workflow, hacer deploy manual:

```bash
# 1. SSH al servidor
ssh deploy@tu-servidor.com

# 2. Actualizar código
cd /var/www/mail-alert
git pull origin main

# 3. Deploy manual
cd backend
composer install --no-dev
php artisan migrate --force
php artisan config:cache

# 4. Verificar
curl http://localhost:8000/api/alerts \
  -H "Authorization: Bearer tu_secret"
```

Una vez que funciona manualmente, el workflow automático funcionará igual.

## 📞 Support

Si tienes problemas:

1. Revisar logs en GitHub Actions
2. Revisar logs en servidor: `tail -f storage/logs/laravel.log`
3. Verificar SSH manualmente: `ssh -v user@servidor`
4. Revisar secrets en GitHub: **Settings → Secrets**

## 🎉 ¡Listo!

Tu workflow de GitHub Actions está configurado para:

✅ Testear código automáticamente  
✅ Deployar a production con SSH  
✅ Ejecutar migraciones  
✅ Limpiar caché  
✅ Verificar que todo funciona  
✅ Notificar en caso de error  

Ahora, cada push a `main` en la carpeta `backend` ejecutará automáticamente el deploy. 🚀
