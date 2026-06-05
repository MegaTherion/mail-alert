# Deploy.yml - Mejoras Implementadas

Resumen de las mejoras realizadas al archivo `deploy.yml` para mayor flexibilidad y seguridad.

## ✨ Mejoras Realizadas

### 1. **Puerto SSH Parametrizable** 🔌

**Antes**: Puerto hardcodeado a 22
```bash
ssh-keyscan -H ${{ secrets.SSH_HOST }}
ssh -i ~/.ssh/id_rsa user@host
```

**Ahora**: Puerto dinámico con fallback a 22
```bash
SSH_PORT="${{ secrets.SSH_PORT }}"
SSH_PORT=${SSH_PORT:-22}

ssh-keyscan -p $SSH_PORT -H ${{ secrets.SSH_HOST }}
ssh deploy-server  # Usa SSH config con puerto correcto
```

**Cómo usar**:
- **Puerto 22**: NO agregar secret SSH_PORT (usa default)
- **Otro puerto**: Agregar secret `SSH_PORT = 2222`

---

### 2. **Soporte para Múltiples Tipos de Clave** 🔑

**Antes**: Solo RSA, hardcodeado a `~/.ssh/id_rsa`
```bash
echo "${{ secrets.SSH_PRIVATE_KEY }}" > ~/.ssh/id_rsa
chmod 600 ~/.ssh/id_rsa
ssh -i ~/.ssh/id_rsa user@host
```

**Ahora**: Soporta RSA y ED25519 automáticamente
```bash
echo "${{ secrets.SSH_PRIVATE_KEY }}" > ~/.ssh/deploy_key
chmod 600 ~/.ssh/deploy_key

# SSH config maneja la clave automáticamente
# Funciona con RSA o ED25519 indistintamente
```

**Cómo usar**:
```bash
# Opción A: ED25519 (Recomendado - más rápido)
ssh-keygen -t ed25519 -f deploy_key -N ""

# Opción B: RSA (Compatible - más amplio)
ssh-keygen -t rsa -b 4096 -f deploy_key -N ""

# Ambos funcionan igual con el workflow
```

---

### 3. **SSH Config Limpio** 🏗️

**Antes**: Flags de línea de comandos
```bash
ssh -i ~/.ssh/id_rsa -p 2222 user@host "command"
```

**Ahora**: Archivo SSH config centralizado
```bash
Host deploy-server
  HostName ${{ secrets.SSH_HOST }}
  Port ${{ secrets.SSH_PORT || 22 }}
  User ${{ secrets.SSH_USER }}
  IdentityFile ~/.ssh/deploy_key
  StrictHostKeyChecking accept-new
  ConnectTimeout 10

# Uso simple:
ssh deploy-server "command"
```

**Ventajas**:
- ✅ Más limpio y legible
- ✅ Configuración centralizada
- ✅ Fácil de depurar
- ✅ Mejor manejo de timeouts

---

### 4. **Host Key Verification Automática** 🔒

**Antes**: Manual o riesgoso
```bash
ssh-keyscan -H host >> ~/.ssh/known_hosts
```

**Ahora**: Automática con aceptación segura
```bash
ssh-keyscan -p $SSH_PORT -H ${{ secrets.SSH_HOST }} 
  >> ~/.ssh/known_hosts

StrictHostKeyChecking=accept-new  # En SSH config
ConnectTimeout=10                  # Con timeout
```

---

### 5. **Cleanup Mejorado** 🧹

**Antes**: Solo limpiaba id_rsa
```bash
rm -f ~/.ssh/id_rsa
```

**Ahora**: Limpia todos los archivos SSH temporales
```bash
rm -f ~/.ssh/deploy_key
rm -f ~/.ssh/config
rm -f ~/.ssh/known_hosts
```

---

## 🔄 Flujo de Ejecución Mejorado

```
1. Setup SSH
   ├─ Detectar puerto SSH (secret o default 22)
   ├─ Agregar host a known_hosts con puerto correcto
   ├─ Guardar clave privada (RSA o ED25519)
   ├─ Crear SSH config con toda la configuración
   └─ Set 600 permisos

2. Deploy via SSH
   └─ ssh deploy-server  (usa todo desde config)
      ├─ Determina hostname, port, user, key automáticamente
      ├─ Conecta con timeout de 10s
      └─ Ejecuta comandos de deploy

3. Verify
   └─ ssh deploy-server  (conexión igual)

4. Cleanup
   └─ Borra deploy_key, config, known_hosts
```

---

## 📋 Configuración Requerida

### Obligatorios (5 siempre)
```
SSH_PRIVATE_KEY     ← deploy_key file (RSA o ED25519)
SSH_HOST            ← servidor.com o IP
SSH_USER            ← deploy o ubuntu
SSH_PROJECT_PATH    ← /var/www/mail-alert
ALERT_SECRET        ← Tu secret de app
```

### Opcional (solo si puerto ≠ 22)
```
SSH_PORT            ← 2222 (o tu puerto)
```

---

## 🚀 Ejemplos de Setup

### Caso 1: Puerto 22, ED25519 (Más común ahora)
```bash
ssh-keygen -t ed25519 -f deploy_key -N ""
ssh-copy-id -i deploy_key.pub user@servidor.com

# En GitHub, agregar 5 secrets (sin SSH_PORT)
```

### Caso 2: Puerto 2222, RSA (Configuración antigua)
```bash
ssh-keygen -t rsa -b 4096 -f deploy_key -N ""
ssh-copy-id -i deploy_key.pub -p 2222 user@servidor.com

# En GitHub, agregar 6 secrets (incluir SSH_PORT=2222)
```

### Caso 3: IP con puerto custom, ED25519 (Servidor privado)
```bash
ssh-keyscan -p 3000 -H 192.168.1.100 >> ~/.ssh/known_hosts
ssh-keygen -t ed25519 -f deploy_key -N ""
ssh-copy-id -i deploy_key.pub -p 3000 deploy@192.168.1.100

# En GitHub:
# SSH_HOST = 192.168.1.100
# SSH_PORT = 3000
# SSH_USER = deploy
# SSH_PRIVATE_KEY = [ED25519 key]
# ... etc
```

---

## ✅ Checklist de Cambios

### Para Usuarios Nuevos
- [ ] Generar clave (ED25519 o RSA)
- [ ] Agregar al servidor
- [ ] Crear secrets en GitHub (5-6 dependiendo de puerto)
- [ ] Push a main
- [ ] ✅ Deploy automático

### Para Usuarios Existentes (Migración)
- [ ] Actualizar deploy.yml del repo
- [ ] Agregar secret SSH_PORT si lo necesitan
- [ ] No necesita cambiar nada más
- [ ] Siguiente push funcionará con los cambios
- [ ] ✅ Completado

---

## 🔍 Verificación Local Antes de Usar Workflow

```bash
# Test connection (reemplaza valores)
ssh-keyscan -p 2222 -H servidor.com >> ~/.ssh/known_hosts
ssh -i deploy_key -p 2222 user@servidor.com "echo OK"

# Si responde "OK", está listo para GitHub Actions
```

---

## 🆚 Comparación: Antes vs Después

| Aspecto | Antes | Después |
|---------|-------|---------|
| Puertos SSH | Solo 22 | Cualquier puerto |
| Tipos de clave | Solo RSA | RSA o ED25519 |
| Config SSH | Flags en comando | Archivo config |
| Host verification | Manual | Automática |
| Clave hardcodeada | id_rsa | deploy_key (genérico) |
| Timeout | No | 10 segundos |
| Fallback puerto | No | Default 22 |
| Flexibilidad | Baja | Alta |

---

## 🎯 Ventajas de los Cambios

✅ **Más flexible**: Soporta cualquier puerto SSH  
✅ **Más seguro**: Mejor manejo de host keys  
✅ **Más moderno**: Soporta ED25519  
✅ **Más limpio**: SSH config centralizado  
✅ **Más robusto**: Fallback automático  
✅ **Más compatible**: Funciona con RSA y ED25519  

---

## 📖 Documentación Relacionada

- **[SSH_CONFIGURATION.md](./SSH_CONFIGURATION.md)** - Guía completa de SSH
- **[GITHUB_ACTIONS_SETUP.md](./GITHUB_ACTIONS_SETUP.md)** - Setup original (actualizado)
- **[CI_CD_CHEATSHEET.md](./CI_CD_CHEATSHEET.md)** - Quick reference

---

## 🔄 Migración (Si usabas version anterior)

Si usabas una versión anterior de deploy.yml:

1. **Actualizar**: 
   - Reemplazar `.github/workflows/deploy.yml` con la versión nueva

2. **Agregar puerto** (si lo necesitas):
   - GitHub Settings → Secrets → SSH_PORT

3. **Nada más**:
   - Los secrets existentes funcionarán igual
   - Tu próximo push usará la versión nueva

4. **Sin tiempo de inactividad**:
   - El workflow anterior sigue funcionando hasta hacer push
   - El nuevo se activa en el próximo push

---

## 📞 Soporte

**Si algo no funciona después de actualizar:**

1. Verificar SSH localmente: `ssh -i deploy_key -p PORT user@host`
2. Ver logs en GitHub Actions (click en workflow fallido)
3. Revisar [SSH_CONFIGURATION.md](./SSH_CONFIGURATION.md) sección troubleshooting
4. Confirmar que todos los secrets están correctos

---

## 🎉 Resumen

El workflow `deploy.yml` ahora es:

✅ **Más flexible** - Soporta cualquier puerto SSH  
✅ **Más seguro** - Mejor manejo de claves  
✅ **Más moderno** - ED25519 compatible  
✅ **Backward compatible** - Funciona con config anterior  
✅ **Robusto** - Fallbacks y timeouts automáticos  

¡Listo para cualquier configuración SSH! 🚀
