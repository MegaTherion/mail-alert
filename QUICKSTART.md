# Mail Alert System - Quick Start Guide

Guía rápida paso a paso para tener el sistema funcionando en 30 minutos.

## ✅ Prerequisites

- [ ] PHP 8.3+
- [ ] Node.js 18+
- [ ] Android SDK instalado
- [ ] Android Studio (opcional pero recomendado)
- [ ] Firebase proyecto creado
- [ ] Service account JSON descargado
- [ ] google-services.json descargado

## 🚀 Backend Setup (5 minutos)

### 1. Instalar dependencias

```bash
cd backend
composer install
```

### 2. Configurar variables de entorno

```bash
cp .env.example .env
php artisan key:generate
```

Editar `.env`:
```env
ALERT_SECRET=mi_clave_secreta_de_32_caracteres_minimo_1234567890
FCM_PROJECT_ID=mi-firebase-project-id
FCM_SERVICE_ACCOUNT_JSON=storage/firebase/service-account.json
```

### 3. Crear directorio y copiar service account

```bash
mkdir -p storage/firebase
# Copiar tu service account JSON aquí
cp /path/to/service-account.json storage/firebase/service-account.json
```

### 4. Crear BD y ejecutar migraciones

```bash
touch database.sqlite
php artisan migrate
```

### 5. Iniciar servidor

```bash
php artisan serve
```

✅ Backend está en http://localhost:8000

## 📱 Mobile Setup (10 minutos)

### 1. Instalar dependencias

```bash
cd ../mobile
npm install
```

### 2. Configurar Firebase

```bash
# Copiar google-services.json al directorio correcto
cp /path/to/google-services.json android/app/google-services.json
```

### 3. Configurar API

Editar `src/config.ts`:

```typescript
// Para emulador (por defecto):
export const API_BASE_URL = 'http://10.0.2.2:8000';
export const API_SECRET = 'mi_clave_secreta_de_32_caracteres_minimo_1234567890';
```

Si usas dispositivo físico, cambiar `API_BASE_URL` a `http://tu_ip_local:8000`

### 4. Build Gradle

```bash
cd android
./gradlew build
cd ..
```

## ▶️ Running the App (5 minutos)

### Terminal 1: Backend

```bash
cd backend
php artisan serve
```

Esperar: `Server running on http://localhost:8000`

### Terminal 2: Metro (Metro bundler)

```bash
cd mobile
npm start
```

Esperar: `Metro waiting on exp://...`

### Terminal 3: Emulador/Dispositivo

```bash
# En otra terminal, en el directorio mobile
npm run android
```

Esperar a que compile y se lance la app.

## ✨ Testing the System

### Test 1: Crear alerta normal

```bash
curl -X POST http://localhost:8000/api/mail-alert \
  -H "Content-Type: application/json" \
  -d '{
    "secret": "mi_clave_secreta_de_32_caracteres_minimo_1234567890",
    "rule": "test_normal",
    "priority": "high",
    "from": "test@example.com",
    "subject": "Test Alert",
    "snippet": "This is a test alert",
    "timestamp": "2026-01-15T10:30:00Z"
  }'
```

**Esperado**: En la app, debe aparecer en HomeScreen

### Test 2: Crear alerta de emergencia

```bash
curl -X POST http://localhost:8000/api/mail-alert \
  -H "Content-Type: application/json" \
  -d '{
    "secret": "mi_clave_secreta_de_32_caracteres_minimo_1234567890",
    "rule": "test_emergency",
    "priority": "emergency",
    "from": "security@example.com",
    "subject": "CRITICAL ALERT",
    "snippet": "This is an emergency alert",
    "timestamp": "2026-01-15T10:30:00Z"
  }'
```

**Esperado**: 
- AlertScreen aparece fullscreen en la app
- Sonido de alarma suena (si el archivo existe)
- Botón DESCARTAR detiene la alarma

### Test 3: Listar alertas

```bash
curl http://localhost:8000/api/alerts \
  -H "Authorization: Bearer mi_clave_secreta_de_32_caracteres_minimo_1234567890"
```

**Esperado**: JSON array con últimas 50 alertas

## 🔍 Verificaciones Rápidas

### Backend está corriendo

```bash
curl http://localhost:8000/api/alerts \
  -H "Authorization: Bearer test"
```

Debe retornar error 401 (unauthorized)

### App está recibiendo FCM

```bash
adb logcat | grep "Subscribed to topic\|FCM Token"
```

Debe mostrar: `Subscribed to topic: mail-alerts`

### Base de datos tiene datos

```bash
cd backend
php artisan tinker
>>> App\Models\MailAlert::count()
```

### Logs en tiempo real

```bash
# Backend
tail -f backend/storage/logs/laravel.log | grep -i fcm

# Mobile
adb logcat | grep -i "mail-alert\|firebase"
```

## 🎯 Next Steps

Una vez todo está funcionando:

1. Ver [TESTING.md](./TESTING.md) para tests más exhaustivos
2. Ver [mobile/ANDROID_SETUP.md](./mobile/ANDROID_SETUP.md) para setup avanzado de Android
3. Customizar alertas según tus necesidades
4. Integrar con tu sistema de alertas

## ⚡ Troubleshooting Rápido

| Problema | Solución |
|----------|----------|
| "command not found: composer" | Instalar Composer globalmente |
| "ALERT_SECRET mismatch" | Verificar que .env y src/config.ts son iguales |
| "FCM notification not sent" | Verificar service-account.json existe y es válido |
| "App can't connect to backend" | Si emulador: usa `10.0.2.2`, si dispositivo: usa IP local |
| "Metro bundler hangs" | `npm start --reset-cache` |
| "Gradle build fails" | `cd android && ./gradlew clean && cd ..` |
| "AlertScreen no aparece" | Revisar logs: `adb logcat | grep AlertScreen` |
| "Alarma no se reproduce" | Crear/agregar `mobile/assets/alarm.mp3` |

## 📋 Checklist de Setup Completo

- [ ] Backend composer install ✓
- [ ] Backend .env configurado ✓
- [ ] Backend migrations ejecutadas ✓
- [ ] Backend php artisan serve corriendo ✓
- [ ] Mobile npm install ✓
- [ ] google-services.json en lugar correcto ✓
- [ ] Mobile src/config.ts configurado ✓
- [ ] Mobile npm run android compilado y corriendo ✓
- [ ] Test POST /api/mail-alert funciona ✓
- [ ] Test GET /api/alerts funciona ✓
- [ ] App recibe notificaciones ✓
- [ ] AlertScreen aparece para emergencias ✓

## 📞 Help

Si algo no funciona:

1. Revisar logs: `adb logcat | grep -i error`
2. Revisar logs backend: `cat backend/storage/logs/laravel.log | tail -50`
3. Ver [TESTING.md](./TESTING.md) para debugging detallado
4. Ver [PROJECT_STRUCTURE.md](./PROJECT_STRUCTURE.md) para entender arquitectura

## 🎉 Success!

Si llegaste aquí sin errores:

```
✅ Backend API en http://localhost:8000
✅ App React Native corriendo en emulador
✅ FCM notificaciones funcionando
✅ Sistema completo operativo
```

Ahora puedes:
- Crear alertas desde cualquier fuente
- Recibirlas en tiempo real en la app
- Alertas de emergencia con interfaz fullscreen
- Alertas normales con notificaciones

¡Felicidades! 🎊
