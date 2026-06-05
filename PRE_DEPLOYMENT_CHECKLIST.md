# Pre-Deployment Checklist

Checklist de verificación antes de llevar el sistema a producción.

## 🔒 Backend Security

- [ ] `ALERT_SECRET` tiene mínimo 32 caracteres alfanuméricos
- [ ] `ALERT_SECRET` no tiene espacios ni caracteres especiales fáciles de adivinar
- [ ] `APP_ENV=production` en `.env`
- [ ] `APP_DEBUG=false` en `.env`
- [ ] `APP_KEY` es diferente para cada environment
- [ ] Service account JSON está en ubicación protegida (no web-accessible)
- [ ] `.env` no está en control de versiones (en `.gitignore`)
- [ ] Errores detallados no se muestran al cliente (APP_DEBUG=false)
- [ ] CORS está configurado si es necesario
- [ ] Rate limiting está implementado (si aplica)
- [ ] Logs están habilitados: `LOG_CHANNEL=stack`

## 🔐 Database Security

- [ ] BD está backupeada regularmente
- [ ] Usuario de BD tiene permisos mínimos necesarios
- [ ] Conexión a BD usa credenciales fuertes
- [ ] `database.sqlite` no es web-accessible (si usas SQLite)
- [ ] Datos sensibles no están logeados
- [ ] Migrations han sido testeadas
- [ ] Indices están creados para queries frecuentes

## 📱 Mobile Security

- [ ] `google-services.json` no está en control de versiones
- [ ] `API_SECRET` en `src/config.ts` matches backend `ALERT_SECRET`
- [ ] `API_BASE_URL` apunta a dominio correcto (HTTPS en producción)
- [ ] Certificados SSL válidos para HTTPS
- [ ] No hay credenciales en código fuente
- [ ] Permisos Android son mínimos necesarios
- [ ] App no guarda secrets en AsyncStorage sin encripción
- [ ] Debug logging está deshabilitado en release builds

## 🔌 Firebase/FCM

- [ ] Service account tiene solo permisos necesarios ("Cloud Messaging Admin")
- [ ] FCM_PROJECT_ID es correcto
- [ ] FCM_SERVICE_ACCOUNT_JSON apunta a archivo válido
- [ ] Topic `mail-alerts` existe en Firebase
- [ ] Push notifications han sido testeadas end-to-end
- [ ] Emergency alerts funcionan correctamente
- [ ] Normal alerts funcionan correctamente
- [ ] Logs de FCM están disponibles en Firebase Console

## 🌐 Network & Deployment

- [ ] Backend está en servidor con acceso restringido (firewall)
- [ ] Puertos 80/443 están abiertos según necesidad
- [ ] HTTPS/SSL está configurado con certificado válido
- [ ] Dominio DNS apunta correctamente
- [ ] Rate limiting está implementado o monitoreado
- [ ] Backups están configurados (diarios mínimo)
- [ ] CDN está configurado si es necesario (para archivos estáticos)

## 📊 Performance

- [ ] Queries a BD están optimizados (indices, no N+1)
- [ ] GET /api/alerts limit es 50 (no más)
- [ ] Pagos de FCM están dentro de presupuesto
- [ ] Response times están monitoreados
- [ ] Database size está monitoreado
- [ ] Logs no crecen sin límite

## 🧪 Testing

- [ ] Todos los endpoints han sido testeados
- [ ] Tests de carga han sido ejecutados
- [ ] Smoke tests pasan en staging
- [ ] End-to-end flow funciona completo
- [ ] Manejo de errores está testeado
- [ ] Validación de input está testeada

## 📋 Documentation

- [ ] README.md está actualizado
- [ ] API documentation está completa
- [ ] Setup steps están documentados
- [ ] Troubleshooting guide está disponible
- [ ] Runbook para problemas en producción existe
- [ ] Documentación de rollback existe

## 🚨 Monitoring & Alerts

- [ ] Monitoreo de salud del backend está activo
- [ ] Alertas de errores están configuradas
- [ ] Logs están centralizados (ELK, Datadog, etc si aplica)
- [ ] Dashboards están creados
- [ ] Oncall rotación está establecida
- [ ] Postmortems process existe para incidentes

## 📱 Mobile App Publishing

- [ ] App está signada con clave de producción
- [ ] Version code/name están actualizados
- [ ] Icono y assets están finales
- [ ] Google Play Store requirements están cumplidos
- [ ] Privacy policy está linkada
- [ ] App ha sido testeada en múltiples dispositivos

## 🔄 CI/CD Pipeline

- [ ] Tests corren en cada commit
- [ ] Build automático está configurado
- [ ] Deployments son automáticos o semi-automáticos
- [ ] Rollback process está definido
- [ ] Deployment logs están disponibles
- [ ] Staging environment matches producción

## 📞 Operational

- [ ] Runbook para common issues existe
- [ ] Escalation procedures están definidas
- [ ] SLA está definido
- [ ] Support process está establecido
- [ ] Incident response plan existe
- [ ] Communication channels están configurados

## 🔍 Compliance & Legal

- [ ] Privacy policy menciona FCM
- [ ] Terms of service están actualizados
- [ ] GDPR compliance (si aplica)
- [ ] Data retention policies están establecidas
- [ ] Data deletion procedures están definidas
- [ ] Auditing logs están disponibles

## 🎯 Final Review

- [ ] Code review completada
- [ ] Security review completada
- [ ] Performance review completada
- [ ] Architecture review completada
- [ ] All known issues documentados
- [ ] Deployment date fijado
- [ ] Rollback plan existe
- [ ] Team está en sincronía

## ✅ Pre-Flight

Antes de hacer deploy:

```bash
# Backend
cd backend
composer install --no-dev  # Solo dependencias de producción
php artisan config:cache   # Cache config
php artisan route:cache    # Cache rutas
php artisan view:cache     # Cache vistas
php artisan optimize       # Optimizaciones

# Verificar
php artisan key:generate --force  # NO - si ya existe
php artisan migrate          # Ejecutar en BD de producción

# Tests
php artisan test             # Todos los tests deben pasar

# Logs
tail -f storage/logs/laravel.log  # Monitorear
```

## 🚀 Deployment Steps

1. **Backup**
   ```bash
   # Backup BD
   # Backup .env
   # Backup code
   ```

2. **Pull code**
   ```bash
   git pull origin main
   ```

3. **Install dependencies**
   ```bash
   composer install --no-dev
   npm ci  # Usar lock file
   ```

4. **Database**
   ```bash
   php artisan migrate --force
   ```

5. **Cache**
   ```bash
   php artisan config:cache
   php artisan route:cache
   ```

6. **Health check**
   ```bash
   curl -H "Authorization: Bearer $ALERT_SECRET" \
     https://your-domain.com/api/alerts
   ```

7. **Monitor logs**
   ```bash
   tail -f storage/logs/laravel.log
   ```

## 🆘 Rollback Plan

Si algo falla:

```bash
# Revertir código
git reset --hard HEAD~1
git push -f origin main

# Restaurar BD de backup
# Reiniciar servicios

# Verificar
curl -H "Authorization: Bearer $ALERT_SECRET" \
  https://your-domain.com/api/alerts
```

## 📞 Post-Deployment

- [ ] Verificar que sistema funciona
- [ ] Crear alert de prueba
- [ ] Verificar que notificación llega a app
- [ ] Monitorear logs por 1 hora
- [ ] Team está available para hotfixes
- [ ] Comunicar a usuarios si aplica

## 🎉 Sign Off

Checklist completado por: _________________ Fecha: _________

Autorizado por: _________________ Fecha: _________

---

## Notas Rápidas

Si algo falla durante deployment:

1. **No paniquear** - Tienes un rollback plan
2. **Revisar logs** - `tail -f storage/logs/laravel.log`
3. **Chequear configs** - Variables de entorno correctas
4. **Test manual** - Curl a /api/alerts
5. **Comunicar** - Al team y afectados
6. **Documentar** - Para postmortem después

## Recursos

- [QUICKSTART.md](./QUICKSTART.md) - Setup rápido
- [README.md](./README.md) - Documentación general
- [TESTING.md](./TESTING.md) - Testing guide
- [backend/README.md](./backend/README.md) - Backend docs
- [mobile/README.md](./mobile/README.md) - Mobile docs

¡Good luck! 🚀
