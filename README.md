# Mail Alert System - Monorepo

Sistema de alertas por correo electrónico con notificaciones push en tiempo real. Incluye un backend Laravel API y una app React Native Android.

## 📋 Arquitectura

```
mail-alert/
├── backend/          # Laravel 13 API
├── mobile/           # React Native Android app
├── README.md         # Este archivo
└── .gitignore
```

## 🔄 Flujo Completo

1. **Fuente de Alertas** (externa) → POST `/api/mail-alert` con secret
2. **Backend Laravel** → Valida secret → Guarda en DB → Envía FCM
3. **Firebase FCM** → Rutea notificación por topic `mail-alerts`
4. **App React Native** → Recibe push → Lanza AlertScreen (emergency) o notificación normal (high)
5. **Usuario** → Ve alertas en HomeScreen o responde a notificación emergente

### Prioridades

- **emergency**: fullScreenIntent + sonido alarma en loop + canal MAX importance
- **high**: notificación normal + sonido custom + canal HIGH importance

## 🚀 Setup en Orden

### Prerequisitos

- PHP 8.3+
- Node.js 18+
- Android SDK (para React Native)
- Firebase proyecto creado
- Service account JSON de Firebase

### Backend Setup

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

Configurar `.env`:
```env
ALERT_SECRET=tu_secret_aqui
FCM_PROJECT_ID=tu-firebase-project-id
FCM_SERVICE_ACCOUNT_JSON=/path/to/service-account.json
```

Iniciar servidor:
```bash
php artisan serve
```

### Mobile Setup

```bash
cd mobile
npm install
# Copiar google-services.json a mobile/android/app/
cp google-services.json android/app/

# Configurar src/config.ts
# Editar API_BASE_URL y API_SECRET

# Build y run en emulador
npm run android
```

## 📚 API Endpoints

### POST /api/mail-alert
Crear alerta (sin autenticación, solo validación de secret)

```bash
curl -X POST http://localhost:8000/api/mail-alert \
  -H "Content-Type: application/json" \
  -d '{
    "secret": "tu_secret_aqui",
    "rule": "github_alert",
    "priority": "emergency",
    "from": "noreply@github.com",
    "subject": "Security Alert: Push to main",
    "snippet": "Unauthorized push detected",
    "timestamp": "2026-01-15T10:30:00Z"
  }'
```

Respuesta exitosa:
```json
{"success": true, "alert_id": 1}
```

### GET /api/alerts
Listar últimos 50 alertas (Bearer token required)

```bash
curl http://localhost:8000/api/alerts \
  -H "Authorization: Bearer tu_secret_aqui"
```

Respuesta:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "rule": "github_alert",
      "priority": "emergency",
      "from_address": "noreply@github.com",
      "subject": "Security Alert",
      "snippet": "...",
      "timestamp": "2026-01-15T10:30:00Z",
      "sent_at": null,
      "created_at": "2026-01-15T10:32:00Z"
    }
  ]
}
```

## 🔐 Seguridad

- **Secret validation**: POST /api/mail-alert solo acepta con secret válido
- **Bearer token**: GET /api/alerts usa Bearer token en Authorization header
- **FCM Service Account**: Almacenado en server, nunca expuesto al cliente
- **CORS**: Configurado solo para dominio frontend si aplica

## 📱 Instalación Google Services

1. Crear Firebase proyecto en console.firebase.google.com
2. Agregar app Android
3. Descargar `google-services.json`
4. Copiarlo a `mobile/android/app/google-services.json`
5. Crear service account JSON en Firebase → Project Settings → Service Accounts
6. Copiarlo a `backend/storage/firebase/service-account.json` (o path configurado)

## 🧪 Testing

### Backend
```bash
cd backend
php artisan test
```

### Mobile
```bash
cd mobile
npm test
```

## 📦 Dependencias Principales

### Backend
- Laravel 13
- guzzlehttp/guzzle (FCM HTTP requests)
- firebase/php-jwt (si es necesario)

### Mobile
- @react-native-firebase/messaging
- @react-native-firebase/app
- axios (API calls)
- react-native-sound (alarma)

## 🐛 Troubleshooting

**FCM no envía notificaciones:**
- Verificar service account tiene permisos "Cloud Messaging" en Firebase
- Revisar logs en Firebase Console
- Comprobar topic subscription en app React Native

**AlertScreen no se lanza:**
- Android 12+: verificar permisos SCHEDULE_EXACT_ALARM
- Revisar logs: `adb logcat | grep AlertScreen`

**API_SECRET no funciona en mobile:**
- Verificar que env matches backend ALERT_SECRET
- Chequear Android Network Security Config si es HTTPS

## 📝 Notas de Implementación

- FCM HTTP v1 API en lugar de legacy
- No usa Sanctum, solo validación de secret en string
- Canales de notificación Android definidos en MainActivity
- AlertScreen usa fullScreenIntent para emergency priority
- Pull to refresh en HomeScreen

## 🔗 Links

- [Laravel 13 Docs](https://laravel.com/docs/13)
- [React Native Docs](https://reactnative.dev)
- [Firebase Cloud Messaging](https://firebase.google.com/docs/cloud-messaging)
- [@react-native-firebase/messaging](https://rnfirebase.io/messaging/usage)
