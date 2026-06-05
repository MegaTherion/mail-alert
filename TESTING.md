# Testing Guide - Mail Alert System

Guía completa para probar el sistema completo de alertas.

## 📋 Prerequisites

- Backend Laravel en ejecución
- App React Native compilada
- Firebase configurado
- Service account JSON en backend
- google-services.json en app

## 🧪 Test Scenarios

### Escenario 1: Test POST /api/mail-alert

#### Setup

1. Asegurar que backend está corriendo:
```bash
cd backend
php artisan serve
```

2. Obtener ALERT_SECRET del `.env`:
```bash
cat .env | grep ALERT_SECRET
```

#### Test con cURL

```bash
curl -X POST http://localhost:8000/api/mail-alert \
  -H "Content-Type: application/json" \
  -d '{
    "secret": "your_secret_key_here_min_32_chars",
    "rule": "github_security",
    "priority": "high",
    "from": "security@github.com",
    "subject": "Security Alert: Unauthorized Push",
    "snippet": "Detectamos un push sin autorización al branch main",
    "timestamp": "2026-01-15T10:30:00Z"
  }'
```

#### Respuesta Esperada

```json
{
  "success": true,
  "alert_id": 1
}
```

#### Test Negativo: Secret Inválido

```bash
curl -X POST http://localhost:8000/api/mail-alert \
  -H "Content-Type: application/json" \
  -d '{
    "secret": "wrong_secret",
    ...
  }'
```

Respuesta esperada (401):
```json
{"error": "Invalid secret"}
```

### Escenario 2: Test GET /api/alerts

#### Setup

Asegurar que hay alertas en la BD:
```bash
cd backend
php artisan tinker
>>> App\Models\MailAlert::count()
```

#### Test

```bash
curl http://localhost:8000/api/alerts \
  -H "Authorization: Bearer your_secret_key_here_min_32_chars"
```

#### Respuesta Esperada

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "rule": "github_security",
      "priority": "high",
      "from_address": "security@github.com",
      "subject": "Security Alert...",
      "snippet": "Detectamos un push...",
      "timestamp": "2026-01-15T10:30:00Z",
      "sent_at": "2026-01-15T10:30:15Z",
      "created_at": "2026-01-15T10:30:15Z"
    }
  ]
}
```

#### Test Negativo: Token Inválido

```bash
curl http://localhost:8000/api/alerts \
  -H "Authorization: Bearer wrong_token"
```

Respuesta esperada (401):
```json
{"error": "Unauthorized"}
```

### Escenario 3: FCM Integration Test

#### Verificar Service Account

1. Revisar que archivo existe:
```bash
ls -la backend/storage/firebase/service-account.json
```

2. Verificar contenido:
```bash
cat backend/storage/firebase/service-account.json | jq '.client_email'
```

#### Test FCM con Backend

1. Crear alerta que dispare FCM:
```bash
curl -X POST http://localhost:8000/api/mail-alert \
  -H "Content-Type: application/json" \
  -d '{
    "secret": "your_secret_key_here_min_32_chars",
    "rule": "fcm_test",
    "priority": "emergency",
    "from": "test@example.com",
    "subject": "FCM Test Alert",
    "snippet": "Testing FCM HTTP v1 API",
    "timestamp": "2026-01-15T10:30:00Z"
  }'
```

2. Revisar logs del backend:
```bash
tail -f storage/logs/laravel.log | grep FCM
```

#### Verificar en Firebase Console

1. Ir a Firebase → Cloud Messaging
2. Ver mensaje recibido en "Messages" tab
3. Revisar delivery status

### Escenario 4: App React Native - HomeScreen

#### Setup

1. Iniciar emulador/dispositivo
2. Build y run app:
```bash
cd mobile
npm run android
```

3. Esperar a que se compile y lance

#### Test

1. **Verificar subscripción a topic**:
   - Revisar logcat:
   ```bash
   adb logcat | grep "Subscribed to topic"
   ```
   - Deberá mostrar: `Subscribed to topic: mail-alerts`

2. **Listar alertas del backend**:
   - Pantalla debe cargar lista de alertas
   - Pull to refresh debe funcionar
   - Alertas deben mostrar rule, from, subject

3. **Verificar tokens de FCM**:
   ```bash
   adb logcat | grep "FCM Token"
   ```

### Escenario 5: App React Native - Notificación Emergency

#### Setup

1. Backend con servicio account configurado
2. App subscrita a topic
3. Emulador/dispositivo listo

#### Test

1. **Crear alerta de emergencia desde backend**:
```bash
curl -X POST http://localhost:8000/api/mail-alert \
  -H "Content-Type: application/json" \
  -d '{
    "secret": "your_secret",
    "rule": "emergency_test",
    "priority": "emergency",
    "from": "admin@company.com",
    "subject": "CRITICAL: System Down",
    "snippet": "All servers are down",
    "timestamp": "2026-01-15T10:30:00Z"
  }'
```

2. **Verificar en app**:
   - AlertScreen debe aparecer fullscreen
   - Sonido de alarma debe reproducirse
   - Botón DESCARTAR debe funcionar

3. **Logs esperados**:
```bash
adb logcat | grep "Emergency alert received\|AlertScreen"
```

### Escenario 6: App React Native - Notificación Normal

#### Test

1. **Crear alerta normal**:
```bash
curl -X POST http://localhost:8000/api/mail-alert \
  -H "Content-Type: application/json" \
  -d '{
    "secret": "your_secret",
    "rule": "normal_alert",
    "priority": "high",
    "from": "notify@company.com",
    "subject": "Deployment Successful",
    "snippet": "Deployment to production completed",
    "timestamp": "2026-01-15T10:30:00Z"
  }'
```

2. **Verificar en app**:
   - Notificación debe aparecer en system tray
   - Sonido personalizado debe reproducirse
   - Al hacer tap, debe abrir app en HomeScreen

## 🔄 Full Flow Test

### Paso a Paso Completo

1. **Resetear datos**:
```bash
# Backend: eliminar alertas
cd backend
php artisan tinker
>>> App\Models\MailAlert::truncate()
```

2. **Iniciar servicios**:
```bash
# Terminal 1: Backend
cd backend && php artisan serve

# Terminal 2: Metro (React Native)
cd mobile && npm start

# Terminal 3: Emulador
emulator -avd Pixel4
```

3. **Build y run app**:
```bash
cd mobile && npm run android
```

4. **Ejecutar tests secuencialmente**:
   - POST /api/mail-alert (high) → Verificar en HomeScreen
   - POST /api/mail-alert (emergency) → Verificar AlertScreen
   - GET /api/alerts → Verificar lista completa

5. **Verificar logs**:
```bash
adb logcat | grep "mail-alert\|Firebase\|FCM\|AlertScreen"
```

## 📊 Performance Test

### Test de Carga: Múltiples Alertas

```bash
# Script para crear 50 alertas
for i in {1..50}; do
  curl -X POST http://localhost:8000/api/mail-alert \
    -H "Content-Type: application/json" \
    -d "{
      \"secret\": \"your_secret\",
      \"rule\": \"load_test_$i\",
      \"priority\": \"$([ $((i % 2)) -eq 0 ] && echo 'high' || echo 'emergency')\",
      \"from\": \"test$i@example.com\",
      \"subject\": \"Test Alert $i\",
      \"snippet\": \"This is test alert number $i\",
      \"timestamp\": \"$(date -u +%Y-%m-%dT%H:%M:%SZ)\"
    }"
  echo "Created alert $i"
  sleep 0.5
done
```

Luego:
1. Revisar que GET /api/alerts devuelve últimos 50 (máximo configurado)
2. Verificar performance en app (debe scrollear smoothly)
3. Revisar memoria en logcat

## 🔍 Network Debugging

### Interceptar requests

**Android emulator:**
```bash
adb shell settings put global http_proxy 127.0.0.1:8888
```

Usar Charles Proxy o Fiddler para inspeccionar tráfico.

### Verificar conectividad

```bash
# Test desde emulador a host
adb shell ping 10.0.2.2

# Test desde dispositivo a host
adb shell ping <your-host-ip>
```

## 🐛 Common Issues y Soluciones

### Issue: "Invalid secret" en todos los requests

**Solución:**
- Verificar que ALERT_SECRET en backend .env tiene 32+ caracteres
- Sincronizar con src/config.ts en mobile
- Reiniciar servidor backend

### Issue: FCM no envía notificaciones

**Solución:**
1. Verificar service account JSON:
```bash
cat backend/storage/firebase/service-account.json | jq .
```

2. Verificar permisos en Firebase Console
3. Revisar Cloud Logging en Firebase
4. Revisar logs backend:
```bash
tail -f backend/storage/logs/laravel.log
```

### Issue: AlertScreen no aparece

**Solución:**
1. Verificar que prioridad es "emergency":
```bash
# En request POST
"priority": "emergency"
```

2. Verificar logs:
```bash
adb logcat | grep "onEmergencyAlert"
```

3. Verificar subscripción:
```bash
adb logcat | grep "subscribeToTopic"
```

### Issue: HomeScreen no carga alertas

**Solución:**
1. Verificar conectividad:
```bash
adb shell ping 10.0.2.2 -c 1
```

2. Verificar API_BASE_URL en src/config.ts
3. Verificar que API_SECRET es correcto
4. Revisar logs:
```bash
adb logcat | grep "AlertApiService\|getAlerts"
```

### Issue: Sonido no se reproduce

**Solución:**
1. Verificar archivo assets/alarm.mp3 existe
2. Verificar permisos de audio
3. Revisar en dispositivo físico (emulador limitado)
4. Revisar canal de notificación en MainActivity

## 📝 Test Report Template

```
Test Report - Mail Alert System
Date: YYYY-MM-DD
Tester: Name

## Results

### Backend API
- [ ] POST /api/mail-alert (valid secret)
- [ ] POST /api/mail-alert (invalid secret)
- [ ] GET /api/alerts (valid token)
- [ ] GET /api/alerts (invalid token)
- [ ] FCM notification sent

### App React Native
- [ ] App starts and subscribes to topic
- [ ] HomeScreen loads alerts
- [ ] Pull to refresh works
- [ ] AlertScreen shows for emergency
- [ ] Alarm sound plays
- [ ] Dismiss button works

### Issues Found
1. ...
2. ...

### Notes
...
```

## 🔗 Useful Commands

```bash
# Backend: Clear cache
cd backend && php artisan cache:clear

# Backend: Run migrations
cd backend && php artisan migrate:refresh

# Mobile: Clear cache
cd mobile && npm cache clean --force

# Mobile: Rebuild gradle
cd mobile && cd android && ./gradlew clean && cd ../..

# Logs: Follow all
adb logcat -c && adb logcat | grep -i "mail-alert\|firebase"
```
