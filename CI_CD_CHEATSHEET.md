# CI/CD Cheat Sheet

Referencia rápida para GitHub Actions y deploy.

## 🚀 Quick Deploy Setup (10 minutos)

### 1. Generar SSH Key
```bash
# Opción A: ED25519 (Recomendado - más rápido)
ssh-keygen -t ed25519 -f deploy_key -N ""

# Opción B: RSA (Compatible - más amplio)
ssh-keygen -t rsa -b 4096 -f deploy_key -N ""

cat deploy_key  # Copiar para GitHub secret
```

### 2. Agregar a Servidor
```bash
# Puerto 22 (default):
ssh-copy-id -i deploy_key.pub user@servidor

# Otro puerto (ej: 2222):
ssh-copy-id -i deploy_key.pub -p 2222 user@servidor

# O manualmente:
cat >> ~/.ssh/authorized_keys < deploy_key.pub
```

### 3. Configurar GitHub Secrets
```
Settings → Secrets → New secret:
  SSH_PRIVATE_KEY = (contenido de deploy_key)
  SSH_HOST = servidor.com
  SSH_USER = deploy
  SSH_PROJECT_PATH = /var/www/mail-alert
  ALERT_SECRET = tu_clave_aqui

OPCIONAL (solo si puerto ≠ 22):
  SSH_PORT = 2222
```

### 4. Push a Main
```bash
git add backend/
git commit -m "feature"
git push origin main
# ✅ Workflow se ejecuta automáticamente
```

---

## 📋 Workflow Files

| Archivo | Propósito | Complejidad |
|---------|----------|-----------|
| `.github/workflows/deploy.yml` | Deploy simple | Baja |
| `.github/workflows/deploy-advanced.yml.example` | Multi-env | Alta |

---

## 🔄 Deploy Flow

```
Push a main
    ↓
Workflow trigger
    ↓
Tests (Ubuntu)
    ↓
Deploy (SSH)
    ↓
Verify
    ↓
Done or Rollback
```

---

## 🐚 SSH Commands

### Test SSH Connection
```bash
ssh -i deploy_key user@servidor "echo OK"
```

### Manual Deploy
```bash
ssh user@servidor << 'EOF'
cd /var/www/mail-alert/backend
git pull origin main
composer install --no-dev
php artisan migrate --force
php artisan config:cache
EOF
```

### View Logs
```bash
ssh user@servidor "tail -f /var/www/mail-alert/backend/storage/logs/laravel.log"
```

---

## 🔐 Secrets Reference

### Required (deploy.yml) - 5 obligatorios
```
SSH_PRIVATE_KEY    → Full private key content (RSA o ED25519)
SSH_HOST           → servidor.com or 192.168.1.1
SSH_USER           → deploy or ubuntu
SSH_PROJECT_PATH   → /var/www/mail-alert
ALERT_SECRET       → Your app secret (for verification)
```

### Optional (solo si puerto SSH ≠ 22)
```
SSH_PORT           → 2222 (o tu puerto customizado)
SLACK_WEBHOOK      → For notifications (advanced workflow)
```

---

## ❌ Common Issues & Fixes

### "Permission denied (publickey)"
```bash
# Check on server
cat ~/.ssh/authorized_keys | wc -l
chmod 600 ~/.ssh/authorized_keys
chmod 700 ~/.ssh
```

### "Cannot find composer"
```bash
# On server
which composer
# If not found:
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### "Database locked"
```bash
# On server
rm -f database.sqlite-*
rm -f database.sqlite-journal
```

### Workflow doesn't run
```
Check:
- 1. Branch is 'main'
- 2. Changes in 'backend/' folder
- 3. Secrets configured
- 4. Actions tab is enabled
```

---

## 📊 Monitoring

### View Workflow Status
```
GitHub → Actions → All workflows
```

### View Logs
```
Actions → Workflow → Job → Step
Scroll for error details
```

### Check if SSH Works
```bash
# In workflow, add:
- name: Test SSH
  run: ssh -i ~/.ssh/id_rsa user@host "whoami"
```

---

## 🔧 Manual Workflow Run

```
1. GitHub → Actions tab
2. Select workflow
3. "Run workflow" button
4. Confirm
# Executes even without push
```

---

## 📝 Secrets Checklist

**Obligatorios** (5):
- [ ] SSH_PRIVATE_KEY (RSA o ED25519)
- [ ] SSH_HOST (servidor.com)
- [ ] SSH_USER (deploy or ubuntu)
- [ ] SSH_PROJECT_PATH (/var/www/mail-alert)
- [ ] ALERT_SECRET (from .env)

**Opcional** (solo si puerto ≠ 22):
- [ ] SSH_PORT (2222 o tu puerto)

Total: 5-6 secrets

---

## 🎯 Workflow Triggers

```yaml
# Automatic triggers:
- Push to main with changes in backend/

# Manual trigger:
- Actions tab → Run workflow

# Alternative triggers (edit deploy.yml):
on:
  schedule:
    - cron: '0 2 * * *'  # Daily at 2 AM
  
  pull_request:
    branches: [main]
  
  push:
    tags: ['v*']  # On version tags
```

---

## 📊 Workflow Timing

| Step | Time | Notes |
|------|------|-------|
| Checkout | ~5s | |
| Setup PHP | ~15s | Cached |
| Composer | ~30s | Cached |
| Tests | ~60s | May fail |
| Deploy | ~60s | SSH to server |
| Verify | ~10s | Health check |
| **Total** | **~180s** | ~3 minutes |

---

## 🚨 Rollback Procedure

### Automatic (deploy-advanced.yml)
```
Deploy fails → Auto rollback enabled
```

### Manual
```bash
ssh user@servidor
cd /var/www/mail-alert/backend
git reset --hard HEAD~1
composer install --no-dev
php artisan config:cache
```

---

## 🔐 Security Checklist

- [ ] SSH key generated locally
- [ ] Public key only on server
- [ ] Private key only in GitHub secrets
- [ ] .env not committed
- [ ] APP_DEBUG=false in production
- [ ] Database backups enabled

---

## 📱 Notifications

### Email (GitHub Built-in)
```
Settings → Notifications
Enable "Email on failed workflows"
```

### Slack (Advanced Workflow)
```
1. Create Slack Webhook
2. Add SLACK_WEBHOOK secret
3. Deploy-advanced.yml handles rest
```

### Discord
```yaml
# Add to workflow
- uses: sarisia/actions-status-discord@v1
  with:
    webhook_url: ${{ secrets.DISCORD_WEBHOOK }}
```

---

## 🎓 Learn More

- Full guide: [GITHUB_ACTIONS_SETUP.md](./GITHUB_ACTIONS_SETUP.md)
- Workflows comparison: [WORKFLOWS_GUIDE.md](./WORKFLOWS_GUIDE.md)
- Deployment checklist: [PRE_DEPLOYMENT_CHECKLIST.md](./PRE_DEPLOYMENT_CHECKLIST.md)

---

## ⚡ Quick Reference Commands

```bash
# Generate key
ssh-keyscan SERVER >> ~/.ssh/known_hosts
ssh-keyscan -t rsa SERVER >> ~/.ssh/known_hosts

# Copy key to server
ssh-copy-id -i deploy_key.pub user@server

# Test workflow in dry-run
act -l  # list all workflows

# View GitHub Actions available
gh actions list

# Check workflow syntax
yamllint .github/workflows/deploy.yml
```

---

## 📞 Debug Workflow Locally (Optional)

### Install act
```bash
brew install act  # macOS
# or Windows/Linux: https://github.com/nektos/act
```

### Run workflow locally
```bash
cd mail-alert
act push  # Simulate push event
act -j test  # Run specific job
```

---

## ✅ Success Checklist

After setup, you should have:

- [x] SSH keys generated and configured
- [x] 5 secrets in GitHub
- [x] Server prepared with code
- [x] Workflow file in `.github/workflows/`
- [x] First manual deployment tested
- [x] Logs verified
- [x] Health check working

Then: Push to main and watch it deploy automatically! 🚀

---

## 🎯 Next Steps

1. **Today**: Setup SSH + secrets (15 min)
2. **Tomorrow**: Test first deploy (5 min)
3. **This week**: Setup notifications (10 min)
4. **Production**: Add rollback protection (5 min)

---

## 📈 Advanced: Custom Scripts

### Add pre-deploy hook
```bash
# On server in deploy hook
#!/bin/bash
echo "Backing up database..."
mysqldump -u user -p db > db.backup.sql
```

### Add post-deploy hook
```bash
# On server after deploy
php artisan optimize
supervisorctl restart laravel-worker
```

---

## 🎉 You're Ready!

Your CI/CD pipeline is configured for:
- ✅ Automated testing
- ✅ Automated deployment
- ✅ Health verification
- ✅ Error notifications
- ✅ Rollback capability

**Next push to main will deploy automatically!** 🚀
