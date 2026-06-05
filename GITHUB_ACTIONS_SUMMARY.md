# GitHub Actions Setup - Complete Summary

## ✅ Lo Que Se Creó

### 📦 Archivos GitHub Actions

1. **`.github/workflows/deploy.yml`** (Recomendado)
   - Deploy simple y directo
   - 1 servidor (production)
   - Test → Deploy → Verify
   - Perfecto para comenzar

2. **`.github/workflows/deploy-advanced.yml.example`**
   - Deploy avanzado
   - Staging + Production
   - Notificaciones Slack
   - Rollback automático
   - Database backups
   - Release tags

### 📚 Documentación de Setup

1. **`GITHUB_ACTIONS_SETUP.md`** (Guía completa)
   - Paso a paso
   - Generación SSH keys
   - Configuración de secrets
   - Preparación del servidor
   - Troubleshooting

2. **`WORKFLOWS_GUIDE.md`** (Comparación workflows)
   - Diferencias entre workflows
   - Cuándo usar cada uno
   - Ejemplos de uso
   - Customización

3. **`CI_CD_CHEATSHEET.md`** (Referencia rápida)
   - Quick setup
   - Common issues
   - Commands
   - Secrets checklist

## 🎯 Deploy Flow (Simple)

```
git push origin main (cambios en backend/)
    ↓
[GitHub Actions triggered]
    ↓
1️⃣  Tests
    • Setup PHP 8.3
    • Install composer
    • Run tests
    ↓
2️⃣  Deploy (solo si tests pasaron)
    • SSH to server
    • Git pull main
    • Composer install
    • Migrations
    • Cache clear
    • Permissions fix
    ↓
3️⃣  Verify
    • Health check
    • Log verification
    ↓
✅  Complete
```

## 🔑 Quick Setup (10 minutos)

### 1. Generate SSH Key
```bash
ssh-keygen -t rsa -b 4096 -f deploy_key -N ""
```

### 2. Add to Server
```bash
ssh-copy-id -i deploy_key.pub user@servidor
```

### 3. Configure GitHub Secrets
```
Settings → Secrets → New secret

SSH_PRIVATE_KEY = [contents of deploy_key]
SSH_HOST = servidor.com
SSH_USER = deploy
SSH_PROJECT_PATH = /var/www/mail-alert
ALERT_SECRET = tu_clave_aqui
```

### 4. Prepare Server
```bash
cd /var/www/mail-alert/backend
cp .env.example .env
# Edit .env with your values
php artisan key:generate --force
composer install
php artisan migrate
```

### 5. Push to Main
```bash
git add backend/
git commit -m "feature: your change"
git push origin main
# ✅ Workflow runs automatically
```

## 📊 Comparison: Simple vs Advanced

| Feature | deploy.yml | deploy-advanced.yml |
|---------|-----------|-------------------|
| Servers | 1 (Prod) | 2 (Staging + Prod) |
| Branches | main | develop + main |
| Slack notifications | ❌ | ✅ |
| Automatic rollback | ❌ | ✅ |
| Database backup | ❌ | ✅ |
| Release tags | ❌ | ✅ |
| Setup complexity | Low | Medium |

**Recommendation**: Start with `deploy.yml`, upgrade to advanced later if needed.

## 📋 Secrets Required

### For deploy.yml (5 total)
```
1. SSH_PRIVATE_KEY    ← deploy_key content
2. SSH_HOST           ← servidor.com
3. SSH_USER           ← deploy username
4. SSH_PROJECT_PATH   ← /var/www/mail-alert
5. ALERT_SECRET       ← Your app secret
```

### For deploy-advanced.yml (10 total)
```
Same as above, plus:

6. STAGING_SSH_PRIVATE_KEY
7. STAGING_SSH_HOST
8. STAGING_SSH_USER
9. STAGING_SSH_PROJECT_PATH
10. SLACK_WEBHOOK     ← Optional, for notifications
```

## 🚀 What Happens on Deploy

### Phase 1: Tests
```bash
1. Checkout code
2. Setup PHP 8.3
3. Cache composer packages
4. Install dependencies
5. Run PHP syntax check
6. Execute tests
```

### Phase 2: Deploy
```bash
1. Setup SSH connection
2. Connect to server
3. Backup .env file
4. Git pull latest code
5. Composer install --no-dev
6. Run migrations --force
7. Clear and cache config
8. Clear and cache routes
9. Clear and cache views
10. Fix storage permissions
11. Restart PHP-FPM (if sudo)
```

### Phase 3: Verify
```bash
1. Wait for app
2. Test API endpoint
3. Check logs for errors
4. Cleanup SSH keys
```

## 🐛 Troubleshooting Quick Reference

| Issue | Cause | Fix |
|-------|-------|-----|
| Workflow not running | Branch not main | Push to main branch |
| SSH auth fails | Wrong key | Check SSH_PRIVATE_KEY secret |
| Composer not found | Server not prepared | SSH to server, install composer |
| Migration fails | DB locked | Check DB file permissions |
| App not responding | Still loading | Workflow waits 5 seconds |
| Tests fail | Code issue | Fix code and retry |

## 📱 Manual Workflow Execution

```
1. GitHub → Actions tab
2. Select "Deploy Backend to Production"
3. Click "Run workflow"
4. Wait for execution
```

Useful for:
- Testing deployment without code push
- Scheduled deployments
- Hotfixes

## 🔄 Rollback Procedure

### Automatic (advanced workflow)
```
If deploy fails → Automatically rollback
```

### Manual
```bash
ssh user@servidor
cd /var/www/mail-alert/backend
git reset --hard HEAD~1
composer install --no-dev
php artisan config:cache
# Done
```

## 📊 Monitoring & Logs

### View Workflow Status
```
GitHub → Actions tab → Workflows
```

### View Workflow Logs
```
Click workflow → Click job → View output
Scroll down to see detailed logs
```

### View Server Logs
```bash
ssh user@servidor
tail -f /var/www/mail-alert/backend/storage/logs/laravel.log
```

### Check Deployment Status
```bash
ssh user@servidor
cd /var/www/mail-alert/backend
curl http://localhost/api/alerts \
  -H "Authorization: Bearer YOUR_SECRET"
```

## ⏱️ Typical Deployment Time

| Phase | Time |
|-------|------|
| Checkout + Setup | ~20s |
| Composer install | ~30s |
| Tests | ~60s |
| Deploy SSH | ~30s |
| Migrations | ~20s |
| Cleanup | ~10s |
| **Total** | **~170s (~3 min)** |

## 🎯 When It Runs

### Automatically
```yaml
- Push to main branch
- Only if backend/ files changed
- Only if all tests pass
- Only if secrets are configured
```

### Manually
```yaml
- Actions tab → Run workflow button
- No push required
- Useful for re-deployments
```

## 🔐 Security Features

- ✅ SSH keys never exposed
- ✅ Secrets encrypted on GitHub
- ✅ No credentials in code
- ✅ No credentials in logs
- ✅ SSH key deleted after use
- ✅ APP_DEBUG=false in production

## 📈 Advanced Features (deploy-advanced.yml)

### Slack Notifications
```
Deploy success → 🎉 Message in Slack
Deploy failure → ❌ Message in Slack
```

### Database Backups
```
Before migration:
→ database.sqlite.backup.TIMESTAMP
```

### Release Tags
```
On success:
→ v2024.01.15-143000 (auto-generated)
→ Published in GitHub Releases
```

### Concurrent Deployments
```
Only one deploy at a time
Prevents conflicts
```

## 🎓 Documentation Files

| Document | Purpose |
|----------|---------|
| `GITHUB_ACTIONS_SETUP.md` | Complete setup guide |
| `WORKFLOWS_GUIDE.md` | Workflow comparison |
| `CI_CD_CHEATSHEET.md` | Quick reference |
| `.github/workflows/deploy.yml` | Simple workflow |
| `.github/workflows/deploy-advanced.yml.example` | Advanced workflow |

## ✅ Pre-Flight Checklist

- [ ] SSH key generated locally
- [ ] SSH key added to server
- [ ] 5+ secrets configured in GitHub
- [ ] Server directory prepared
- [ ] .env file on server
- [ ] service-account.json on server
- [ ] First migration manual test done
- [ ] GitHub Actions enabled on repo
- [ ] deploy.yml file in place
- [ ] Read GITHUB_ACTIONS_SETUP.md

## 🚀 First Deployment Steps

1. **Generate SSH key** (5 min)
   ```bash
   ssh-keyscan -H servidor.com >> ~/.ssh/known_hosts
   ssh-keygen -t rsa -b 4096 -f deploy_key -N ""
   ```

2. **Add secrets to GitHub** (3 min)
   - SSH_PRIVATE_KEY
   - SSH_HOST
   - SSH_USER
   - SSH_PROJECT_PATH
   - ALERT_SECRET

3. **Prepare server** (5 min)
   ```bash
   ssh user@servidor
   cd /var/www/mail-alert
   # Setup directories, .env, permissions
   ```

4. **Test manually first** (10 min)
   ```bash
   # Deploy manually to ensure it works
   # Then workflow will do the same automatically
   ```

5. **Push to main** (1 min)
   ```bash
   git push origin main
   # Watch it deploy in Actions tab
   ```

## 🎉 Success Indicators

You know it's working when:

- ✅ Workflow shows ✓ on main branch
- ✅ You see "Deployment successful" message
- ✅ API endpoint responds with latest code
- ✅ Server logs show migrations completed
- ✅ Each push to main triggers deployment

## 🔗 Quick Links

- **Start here**: [GITHUB_ACTIONS_SETUP.md](./GITHUB_ACTIONS_SETUP.md)
- **Compare workflows**: [WORKFLOWS_GUIDE.md](./WORKFLOWS_GUIDE.md)
- **Quick reference**: [CI_CD_CHEATSHEET.md](./CI_CD_CHEATSHEET.md)
- **Deployment checklist**: [PRE_DEPLOYMENT_CHECKLIST.md](./PRE_DEPLOYMENT_CHECKLIST.md)

## 💬 Help

**If something doesn't work:**

1. Check GitHub Actions logs (click red ❌)
2. SSH to server and verify code pulled
3. Check server storage/logs/laravel.log
4. Review GITHUB_ACTIONS_SETUP.md "Troubleshooting" section
5. Run test deployment manually

## 🎊 Final Status

```
✅ GitHub Actions workflow created
✅ Deploy.yml ready to use
✅ Documentation complete
✅ Setup guide provided
✅ Cheatsheet available
✅ Troubleshooting guide included

Ready for: Immediate CI/CD deployment! 🚀
```

---

**Next Step**: Follow [GITHUB_ACTIONS_SETUP.md](./GITHUB_ACTIONS_SETUP.md) to configure your environment.

**Time to first deployment**: ~20 minutes

**Time saved annually with automation**: ∞ (literally never manually deploy again!)
