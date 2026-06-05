# SSH Configuration Guide

Guía de configuración para GitHub Actions con soporte para puerto SSH customizado y múltiples tipos de claves.

## 🔑 Tipos de Claves Soportadas

El workflow `deploy.yml` soporta ambos tipos de claves SSH modernas:

### 1. **RSA** (Más común, compatible)
```bash
# Generar
ssh-keygen -t rsa -b 4096 -f deploy_key -N ""

# Características:
# - Ampliamente soportado
# - Más lento que ED25519
# - Compatible con sistemas antiguos
# - Recomendado: Min 4096 bits
```

### 2. **ED25519** (Moderno, más rápido)
```bash
# Generar
ssh-keygen -t ed25519 -f deploy_key -N ""

# Características:
# - Más rápido que RSA
# - Más seguro
# - Clave más corta
# - Recomendado para nuevos servidores
```

**Recomendación**: Usa **ED25519** si tu servidor lo soporta. Si no, usa **RSA 4096**.

---

## 🔌 Configurar Puerto SSH Customizado

El workflow detecta automáticamente el puerto SSH. No necesita puerto en el hostname.

### En GitHub Secrets

Agregar secret adicional (opcional):

```
SSH_PORT = 2222
(o cualquier puerto que uses)
```

Si **NO** agregas `SSH_PORT`, el workflow usa el **puerto 22 por defecto**.

### Configuración en el Workflow

El workflow automáticamente:

1. Lee `SSH_PORT` del secret (si existe)
2. Usa puerto 22 si no está configurado
3. Configura SSH para usar ese puerto

```yaml
# En deploy.yml (ya está implementado)
SSH_PORT="${{ secrets.SSH_PORT }}"
SSH_PORT=${SSH_PORT:-22}  # Default 22 si vacío
```

---

## 📋 Secrets Requeridos

### Obligatorios (siempre)
```
SSH_PRIVATE_KEY    ← Contenido de deploy_key
SSH_HOST           ← servidor.com
SSH_USER           ← deploy
SSH_PROJECT_PATH   ← /var/www/mail-alert
ALERT_SECRET       ← Tu clave de app
```

### Opcionales (solo si usas puerto no-estándar)
```
SSH_PORT           ← 2222 (si puerto ≠ 22)
```

---

## 🚀 Setup Completo

### 1. Generar SSH Key (Elige UNA)

**Opción A: ED25519 (Recomendado)**
```bash
ssh-keyscan -H servidor.com >> ~/.ssh/known_hosts
ssh-keyscan -p 2222 -H servidor.com >> ~/.ssh/known_hosts  # Si puerto ≠ 22

ssh-keygen -t ed25519 -f deploy_key -N ""
# Genera: deploy_key (privada) y deploy_key.pub (pública)
```

**Opción B: RSA**
```bash
ssh-keyscan -H servidor.com >> ~/.ssh/known_hosts
ssh-keyscan -p 2222 -H servidor.com >> ~/.ssh/known_hosts  # Si puerto ≠ 22

ssh-keygen -t rsa -b 4096 -f deploy_key -N ""
# Genera: deploy_key (privada) y deploy_key.pub (pública)
```

### 2. Agregar Clave Pública al Servidor

```bash
# Opción A: Automática (más fácil)
ssh-copy-id -i deploy_key.pub -p 2222 user@servidor
# Sin puerto si usas el 22

# Opción B: Manual
ssh -p 2222 user@servidor
cat >> ~/.ssh/authorized_keys << 'EOF'
# Pega aquí el contenido de deploy_key.pub
EOF
chmod 600 ~/.ssh/authorized_keys
```

### 3. Configurar Secrets en GitHub

**Mínimo (puerto 22)**:
```
Settings → Secrets → New secret

SSH_PRIVATE_KEY = [Contenido de deploy_key]
SSH_HOST = servidor.com
SSH_USER = deploy
SSH_PROJECT_PATH = /var/www/mail-alert
ALERT_SECRET = tu_clave_aqui
```

**Con puerto customizado**:
```
[Todos los anteriores, más:]

SSH_PORT = 2222
```

### 4. Verificar Conexión (Antes de Usar en Workflow)

```bash
# Prueba local (desde tu máquina)
ssh -i deploy_key -p 2222 user@servidor "echo OK"

# Debería responder: OK
```

### 5. Deploy

```bash
git add backend/
git commit -m "feature: update"
git push origin main

# ✅ Workflow se ejecuta automáticamente
```

---

## 🔄 Cómo Funciona el Workflow Ahora

### SSH Setup Mejorado

```yaml
# 1. Detecta puerto (default 22)
SSH_PORT=${{ secrets.SSH_PORT }}
SSH_PORT=${SSH_PORT:-22}

# 2. Agrega host a known_hosts (con puerto correcto)
ssh-keyscan -p $SSH_PORT -H ${{ secrets.SSH_HOST }}

# 3. Guarda la clave privada
echo "${{ secrets.SSH_PRIVATE_KEY }}" > ~/.ssh/deploy_key

# 4. Crea SSH config para conexiones limpias
Host deploy-server
  HostName ${{ secrets.SSH_HOST }}
  Port ${{ secrets.SSH_PORT || 22 }}
  User ${{ secrets.SSH_USER }}
  IdentityFile ~/.ssh/deploy_key
  StrictHostKeyChecking accept-new
  ConnectTimeout 10

# 5. Conecta usando SSH config
ssh deploy-server  # Listo, SSH usa toda la config
```

### Ventajas

- ✅ **Automático**: Detecta el puerto automáticamente
- ✅ **Flexible**: Soporta RSA y ED25519
- ✅ **Seguro**: StrictHostKeyChecking accept-new
- ✅ **Limpio**: SSH config en lugar de flags
- ✅ **Robusto**: Fallback a puerto 22 si no existe secret

---

## 🧪 Casos de Uso

### Caso 1: Puerto 22, RSA

**Secrets**:
```
SSH_PRIVATE_KEY = [RSA key]
SSH_HOST = servidor.com
SSH_USER = deploy
SSH_PROJECT_PATH = /var/www/mail-alert
ALERT_SECRET = secret
# SSH_PORT: NO configurar (usa default 22)
```

**Setup**:
```bash
ssh-keygen -t rsa -b 4096 -f deploy_key -N ""
ssh-copy-id -i deploy_key.pub deploy@servidor.com
```

### Caso 2: Puerto 2222, ED25519

**Secrets**:
```
SSH_PRIVATE_KEY = [ED25519 key]
SSH_HOST = servidor.com
SSH_USER = deploy
SSH_PROJECT_PATH = /var/www/mail-alert
ALERT_SECRET = secret
SSH_PORT = 2222
```

**Setup**:
```bash
ssh-keygen -t ed25519 -f deploy_key -N ""
ssh-copy-id -i deploy_key.pub -p 2222 deploy@servidor.com
```

### Caso 3: IP con Puerto Customizado, RSA

**Secrets**:
```
SSH_PRIVATE_KEY = [RSA key]
SSH_HOST = 192.168.1.100
SSH_USER = deploy
SSH_PROJECT_PATH = /var/www/mail-alert
ALERT_SECRET = secret
SSH_PORT = 2222
```

**Setup**:
```bash
ssh-keyscan -p 2222 -H 192.168.1.100 >> ~/.ssh/known_hosts
ssh-keygen -t rsa -b 4096 -f deploy_key -N ""
ssh-copy-id -i deploy_key.pub -p 2222 deploy@192.168.1.100
```

---

## 🐛 Troubleshooting

### Error: "Connection refused"

**Causa**: Puerto incorrecto o servidor no accesible

**Solución**:
```bash
# Verificar puerto SSH en servidor
ssh -p 2222 user@servidor "echo OK"

# Si funciona, asegurar que SSH_PORT en GitHub está correcto
# Si no funciona, verificar puerto SSH del servidor
sudo ss -tlnp | grep ssh  # En servidor
```

### Error: "Permission denied (publickey)"

**Causa**: Clave pública no agregada correctamente

**Solución**:
```bash
# En servidor, verificar clave
cat ~/.ssh/authorized_keys | grep "ED25519\|ssh-rsa"

# Si no está, agregar manualmente
cat deploy_key.pub >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys

# Verificar localmente
ssh -i deploy_key -p 2222 user@servidor "echo OK"
```

### Error: "Host key verification failed"

**Causa**: Host no está en known_hosts

**Solución**: El workflow lo maneja automáticamente con `StrictHostKeyChecking=accept-new`, pero si hay problema:

```bash
# Agregar manualmente
ssh-keyscan -p 2222 -H servidor.com >> ~/.ssh/known_hosts

# Verificar
ssh -i deploy_key -p 2222 user@servidor "echo OK"
```

### "SSH_PORT secret not recognized"

**Causa**: No agregaste el secret (normal si usas puerto 22)

**Solución**: El workflow tiene fallback a puerto 22 automático. Si usas puerto diferente, agregar secret:

```
GitHub → Settings → Secrets → New secret
Name: SSH_PORT
Value: 2222
```

---

## 📊 Comparación: RSA vs ED25519

| Aspecto | RSA | ED25519 |
|---------|-----|---------|
| Velocidad | Más lento | Más rápido |
| Seguridad | Buena (4096+) | Excelente |
| Tamaño clave | Más grande | Más pequeña |
| Compatible | Muy compatible | Moderno |
| Recomendado | Si servidor antiguo | Si servidor moderno |
| Generación | `ssh-keygen -t rsa` | `ssh-keygen -t ed25519` |

---

## ✅ Checklist

### Antes del Primer Deploy

- [ ] Elegir tipo de clave (RSA o ED25519)
- [ ] Generar clave: `ssh-keygen -t ...`
- [ ] Agregar clave pública al servidor
- [ ] Verificar localmente: `ssh -i deploy_key ...`
- [ ] Crear secrets en GitHub (5 obligatorios + 1 opcional)
- [ ] Primera prueba: `git push origin main`
- [ ] Verificar en GitHub Actions

### Si Usas Puerto No-Estándar

- [ ] Verificar puerto SSH en servidor: `ss -tlnp | grep ssh`
- [ ] Agregar secret `SSH_PORT` en GitHub
- [ ] Probar localmente con mismo puerto
- [ ] Confirmar en GitHub Actions logs

---

## 🔐 Seguridad

### Buenas Prácticas

1. **Generar sin passphrase**: `-N ""`
   - GitHub no puede ingresar passphrase
   - Alternativa: Usar una clave sin passphrase solo para CI/CD

2. **Permisos restringidos**:
   ```bash
   chmod 600 deploy_key
   chmod 700 ~/.ssh
   chmod 600 ~/.ssh/authorized_keys
   ```

3. **Clave dedicada**:
   - Generar clave específica para CI/CD
   - No reutilizar clave personal

4. **Rotación**:
   - Cambiar clave cada 6-12 meses
   - Usar diferentes claves para dev/staging/prod

5. **Monitoreo**:
   - Revisar `~/.ssh/authorized_keys` regularmente
   - Eliminar claves antiguas

---

## 📝 Ejemplo Completo

### Servidor con puerto 2222, usando ED25519

**1. En servidor**: Verificar puerto SSH
```bash
sudo ss -tlnp | grep ssh
# Output: LISTEN 0 128 0.0.0.0:2222
```

**2. En máquina local**: Generar clave
```bash
ssh-keygen -t ed25519 -f deploy_key -N ""
```

**3. En máquina local**: Agregar al servidor
```bash
ssh-copy-id -i deploy_key.pub -p 2222 deploy@servidor.com
```

**4. En máquina local**: Verificar
```bash
ssh -i deploy_key -p 2222 deploy@servidor.com "echo OK"
# Output: OK
```

**5. En GitHub**: Agregar secrets
```
SSH_PRIVATE_KEY = [cat deploy_key]
SSH_HOST = servidor.com
SSH_USER = deploy
SSH_PROJECT_PATH = /var/www/mail-alert
ALERT_SECRET = tu_secret
SSH_PORT = 2222
```

**6. Deploy**
```bash
git add backend/
git commit -m "feature: test"
git push origin main
# ✅ Workflow corre automáticamente
```

---

## 📞 Support

**Si algo no funciona:**

1. Verificar SSH localmente: `ssh -i deploy_key -p PORT user@host`
2. Revisar GitHub Actions logs (click en workflow)
3. Revisar `/var/log/auth.log` en servidor
4. Confirmar que:
   - Clave pública en `~/.ssh/authorized_keys` (servidor)
   - Secrets configurados correctamente (GitHub)
   - Puerto SSH correcto (en servidor y en secret)

---

## 🎯 Resumen

El workflow ahora:

✅ Soporta **RSA y ED25519**  
✅ Detecta **puerto SSH automáticamente**  
✅ Tiene **fallback a puerto 22**  
✅ Crea **SSH config limpio**  
✅ Maneja **host verification automáticamente**  
✅ Es **seguro y flexible**  

¡Listo para usar con cualquier configuración SSH! 🚀
