# GitHub Secrets - Sincronización Oficial

Guía de sincronización entre la documentación y el workflow de GitHub Actions.

## 📋 Secrets Requeridos (Sincronizados)

Estos son los secretos que **DEBES** configurar en GitHub:

### Settings → Secrets and variables → Actions

```
Name: SSH_PRIVATE_KEY
Value: [Contenido completo de tu deploy_key privado]
Descripción: Private SSH key for deployment (-----BEGIN PRIVATE KEY----- ... -----END PRIVATE KEY-----)

Name: SSH_HOST
Value: [Tu servidor] (e.g., tu-dominio.com o 192.168.1.100)
Descripción: Hostname or IP of your cPanel server

Name: SSH_USER
Value: [Tu usuario cPanel] (e.g., deploy, cpanel_user, etc.)
Descripción: SSH username for authentication

Name: SSH_PROJECT_PATH
Value: [Ruta del proyecto] (e.g., /home/username/public_html o /var/www/mail-alert)
Descripción: Path where the project is located on the server (without /backend)
```

### Optional Secrets

```
Name: SSH_PORT
Value: [Puerto SSH] (e.g., 2222)
Descripción: SSH port (ONLY if not using default port 22)
Default: 22 (no es necesario agregar este secret si usas puerto 22)

Name: ALERT_SECRET
Value: [Tu clave secreta de 32+ caracteres]
Descripción: Secret key for API authentication (sync with your .env)
Note: Este secret es para referencia/documentación, no se usa en el workflow actual
```

## ✅ Checklist de Configuración

```
GitHub Secrets Required:
  ☐ SSH_PRIVATE_KEY
  ☐ SSH_HOST
  ☐ SSH_USER
  ☐ SSH_PROJECT_PATH

GitHub Secrets Optional:
  ☐ SSH_PORT (solo si puerto ≠ 22)
  ☐ ALERT_SECRET (para referencia)
```

## 🔍 Cómo Generar SSH_PRIVATE_KEY

### 1. Generar la clave

**Opción A: ED25519** (Recomendado - más rápido y seguro)
```bash
ssh-keygen -t ed25519 -f deploy_key -N ""
```

**Opción B: RSA** (Compatible)
```bash
ssh-keygen -t rsa -b 4096 -f deploy_key -N ""
```

### 2. Obtener el contenido

```bash
cat deploy_key
# Copiar TODO el contenido (incluyendo -----BEGIN PRIVATE KEY----- y -----END PRIVATE KEY-----)
```

### 3. Agregar a GitHub

1. Ve a tu repo en GitHub
2. **Settings** → **Secrets and variables** → **Actions**
3. Click **"New repository secret"**
4. Name: `SSH_PRIVATE_KEY`
5. Value: [Pega el contenido completo del archivo deploy_key]
6. Click **"Add secret"**

---

## 🚀 Workflow Deploy Flow

El workflow (`.github/workflows/deploy.yml`) ejecuta estos pasos:

```yaml
1. Checkout code
2. Setup SSH
   ├─ Crear ~/.ssh/deploy_key con SSH_PRIVATE_KEY
   ├─ Detectar puerto SSH (default 22)
   ├─ Agregar host a known_hosts
   └─ Crear SSH config con host "deploy-server"
3. Deploy via SSH (ejecuta comandos en el servidor)
   ├─ cd {{ secrets.SSH_PROJECT_PATH }}/backend
   ├─ git fetch origin main
   ├─ git checkout origin/main
   ├─ composer install --no-dev
   ├─ ea-php83 artisan config:clear
   ├─ ea-php83 artisan migrate --force
   ├─ ea-php83 artisan config:cache
   ├─ ea-php83 artisan route:cache
   └─ chmod -R 775 storage bootstrap/cache
4. Cleanup SSH
   └─ Remover archivos temporales
```

---

## 🔧 Tabla de Correspondencia

| Secret | Workflow | Documentación | Requerido |
|--------|----------|---------------|-----------|
| SSH_PRIVATE_KEY | `${{ secrets.SSH_PRIVATE_KEY }}` | GITHUB_ACTIONS_SETUP.md | ✅ SÍ |
| SSH_HOST | `${{ secrets.SSH_HOST }}` | GITHUB_ACTIONS_SETUP.md | ✅ SÍ |
| SSH_USER | `${{ secrets.SSH_USER }}` | GITHUB_ACTIONS_SETUP.md | ✅ SÍ |
| SSH_PROJECT_PATH | `${{ secrets.SSH_PROJECT_PATH }}` | GITHUB_ACTIONS_SETUP.md | ✅ SÍ |
| SSH_PORT | `${{ secrets.SSH_PORT }}` | GITHUB_ACTIONS_SETUP.md | ❌ Opcional |
| ALERT_SECRET | (no se usa) | GITHUB_ACTIONS_SETUP.md | 📝 Referencia |

---

## 📝 Valores de Ejemplo

```
SSH_PRIVATE_KEY:
-----BEGIN OPENSSH PRIVATE KEY-----
b3BlbnNzaC1rZXktdjEAAAAABG5vbmUtbm9uZS1ub25lAAAAAAAAAEcAAAAhAAAAC2VkMjU=
...
-----END OPENSSH PRIVATE KEY-----

SSH_HOST:
ejemplo.com

SSH_USER:
cpanel_user

SSH_PROJECT_PATH:
/home/cpanel_user/public_html

SSH_PORT:
2222

ALERT_SECRET:
your-secret-key-here-min-32-characters
```

---

## ✨ Verificación

### En GitHub

1. Ve a tu repo
2. **Settings** → **Secrets and variables** → **Actions**
3. Verifica que ves:
   - ✅ SSH_PRIVATE_KEY
   - ✅ SSH_HOST
   - ✅ SSH_USER
   - ✅ SSH_PROJECT_PATH

### En la Terminal (verificar SSH_PRIVATE_KEY)

```bash
# Verificar formato
head -1 deploy_key
# Debe ser: -----BEGIN OPENSSH PRIVATE KEY----- o -----BEGIN RSA PRIVATE KEY-----

# Verificar que es válido
ssh-keygen -y -f deploy_key > deploy_key.pub
# Debe mostrar la clave pública
```

---

## 🐛 Troubleshooting

### Error: "SSH_PRIVATE_KEY not found"
- Verificar que el secret se llama `SSH_PRIVATE_KEY` (exactamente)
- Verificar que está en **Settings → Secrets**
- No en Environment variables

### Error: "Permission denied (publickey)"
- SSH_PRIVATE_KEY está mal
- deploy_key.pub no está en authorized_keys del servidor
- Permisos de authorized_keys incorrecto (debe ser 600)

### Error: "SSH: Could not resolve hostname"
- SSH_HOST es incorrecto
- Verificar IP o dominio en GitHub secret

### Error: "ssh: command not found"
- No es un error de setup, algo más está mal
- Verificar logs en GitHub Actions

---

## 📚 Documentación Relacionada

- **[GITHUB_ACTIONS_SETUP.md](./GITHUB_ACTIONS_SETUP.md)** - Guía completa de setup
- **[SSH_CONFIGURATION.md](./SSH_CONFIGURATION.md)** - Tipos de clave SSH
- **[.github/workflows/deploy.yml](./.github/workflows/deploy.yml)** - Workflow actual

---

## ✅ Estado de Sincronización

**Última actualización**: 2026-01-15

- ✅ Workflow actualizado con secrets correctos
- ✅ Documentación GITHUB_ACTIONS_SETUP.md sincronizada
- ✅ Nombres de secrets consistentes (SSH_PRIVATE_KEY)
- ✅ Flujo de deploy documentado
- ✅ Ejemplos de valores proporcionados

---

## 🎯 Próximo Paso

Una vez configurados los secrets en GitHub, simplemente:

```bash
git add backend/
git commit -m "Deploy to production"
git push origin main
```

El workflow se ejecutará automáticamente. ✨

