# Mail Alert System - Estructura del Proyecto

Estructura completa y descripción de archivos del monorepo.

## 📁 Árbol de Directorios

```
mail-alert/
├── README.md                          # Documentación principal
├── PROJECT_STRUCTURE.md               # Este archivo
├── TESTING.md                         # Guía completa de testing
├── .gitignore                         # Configuración de git
│
├── backend/                           # Laravel 13 API
│   ├── app/
│   │   ├── Http/
│   │   │   └── Controllers/
│   │   │       └── MailAlertController.php      # Endpoints POST/GET
│   │   ├── Models/
│   │   │   └── MailAlert.php                    # Modelo de alertas
│   │   └── Services/
│   │       └── FCMService.php                   # Servicio FCM HTTP v1
│   │
│   ├── config/
│   │   └── services.php               # Config de FCM y secrets
│   │
│   ├── database/
│   │   ├── migrations/
│   │   │   └── 2026_01_15_000001_create_mail_alerts_table.php
│   │   └── seeders/                   # (Vacío)
│   │
│   ├── routes/
│   │   └── api.php                    # Rutas POST/GET
│   │
│   ├── storage/
│   │   ├── firebase/
│   │   │   └── service-account.json   # Credenciales Firebase
│   │   └── logs/                      # Logs de la app
│   │
│   ├── .env.example                   # Template de configuración
│   ├── composer.json                  # Dependencias PHP
│   ├── README.md                      # Docs específico backend
│   └── database.sqlite                # BD SQLite (creado en migrate)
│
├── mobile/                            # React Native Android
│   ├── src/
│   │   ├── screens/
│   │   │   ├── HomeScreen.tsx         # Lista de alertas + pull-refresh
│   │   │   └── AlertScreen.tsx        # Fullscreen emergency alert
│   │   │
│   │   ├── services/
│   │   │   ├── AlertApiService.ts     # Calls a backend API
│   │   │   └── FirebaseService.ts     # Manejo de FCM
│   │   │
│   │   ├── utils/
│   │   │   └── dateUtils.ts           # Formateo de fechas
│   │   │
│   │   └── config.ts                  # API_BASE_URL, API_SECRET, etc
│   │
│   ├── android/
│   │   ├── app/
│   │   │   ├── AndroidManifest.xml    # Permisos y config
│   │   │   ├── google-services.json   # Credenciales Firebase (placeholder)
│   │   │   ├── build.gradle           # Gradle build config
│   │   │   └── src/main/java/com/mailalert/
│   │   │       └── MainActivity.kt    # Canales de notificación
│   │   │
│   │   ├── gradle/                    # Scripts de gradle
│   │   ├── build.gradle               # Gradle root config
│   │   ├── settings.gradle            # Gradle settings
│   │   └── local.properties           # (Generado, en .gitignore)
│   │
│   ├── node_modules/                  # (Generado, en .gitignore)
│   ├── App.tsx                        # Componente raíz + navegación
│   ├── index.js                       # Entry point
│   ├── app.json                       # Config de app
│   │
│   ├── package.json                   # Dependencias npm
│   ├── tsconfig.json                  # TypeScript config
│   ├── babel.config.js                # Babel config
│   ├── metro.config.js                # Metro bundler config
│   ├── .eslintrc.js                   # ESLint config
│   ├── .prettierrc.js                 # Prettier config
│   ├── .env.example                   # Template de env vars
│   ├── README.md                      # Docs específico mobile
│   └── ANDROID_SETUP.md               # Setup detallado Android
│
└── .gitignore                         # Global gitignore
```

## 📄 Descripción de Archivos Clave

### Backend

#### app/Http/Controllers/MailAlertController.php
- `store(Request)`: POST /api/mail-alert
  - Valida secret
  - Guarda en BD
  - Envía a FCM
  - Retorna {success: true, alert_id}

- `index(Request)`: GET /api/alerts
  - Valida Bearer token
  - Retorna últimos 50 alertas

#### app/Services/FCMService.php
- `sendToTopic()`: Envía mensaje a topic mail-alerts
- `getAccessToken()`: JWT auth con service account
- Configura android.priority y channel_id según prioridad

#### app/Models/MailAlert.php
- Modelo para tabla mail_alerts
- Campos: rule, priority, from_address, subject, snippet, timestamp, sent_at
- Casts para timestamps

#### database/migrations/2026_01_15_000001_create_mail_alerts_table.php
- Crea tabla mail_alerts con índices en created_at y priority
- ENUM para priority (high, emergency)

#### routes/api.php
- POST /api/mail-alert → MailAlertController@store
- GET /api/alerts → MailAlertController@index

#### config/services.php
- alert.secret: ALERT_SECRET env var
- fcm.project_id: FCM_PROJECT_ID env var
- fcm.service_account_json: FCM_SERVICE_ACCOUNT_JSON env var

### Mobile

#### src/config.ts
- API_BASE_URL: Base URL del backend
- API_SECRET: Token de autenticación
- FCM_CONFIG.TOPIC: Topic de suscripción (mail-alerts)
- NOTIFICATION_CHANNELS: IDs de canales
- ALERT_PRIORITIES: Enum de prioridades

#### src/services/AlertApiService.ts
- Clase con método getAlerts()
- Hace GET a /api/alerts con Bearer token
- Retorna array de Alert objects

#### src/services/FirebaseService.ts
- initialize(): Setup FCM, subscribir a topic, permisos
- setOnEmergencyAlert(): Callback para emergencias
- setOnNormalAlert(): Callback para alertas normales
- handleForegroundMessages(): Listener para mensajes en foreground
- handleBackgroundMessage(): Listener para background

#### src/screens/HomeScreen.tsx
- FlatList con últimas alertas
- Pull to refresh con RefreshControl
- Colores por prioridad (rojo=emergency, naranja=high)
- Tiempo relativo (5m atrás, 2h atrás, etc)

#### src/screens/AlertScreen.tsx
- Fullscreen alert para emergencias
- Título rojo "⚠ EMERGENCIA"
- Reproductor de alarma en loop (react-native-sound)
- Botón DESCARTAR que para la alarma
- Animación de entrada (spring)

#### App.tsx
- NavigationContainer + Stack Navigator
- Overlay que muestra AlertScreen cuando llega emergency alert
- Maneja callbacks de Firebase

#### android/app/src/main/java/com/mailalert/MainActivity.kt
- createNotificationChannels(): Crea canales Android
- emergency_channel: IMPORTANCE_MAX + alarma
- alert_channel: IMPORTANCE_HIGH + notificación normal

#### android/app/AndroidManifest.xml
- Permisos: INTERNET, POST_NOTIFICATIONS, SCHEDULE_EXACT_ALARM
- Firebase MessagingService configurado
- usesCleartextTraffic=true para desarrollo

## 🔧 Configuración Necesaria

### Backend .env

```env
APP_KEY=base64:...
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
DB_DATABASE=database.sqlite

ALERT_SECRET=tu_clave_secreta_de_32_caracteres_minimo
FCM_PROJECT_ID=tu-firebase-project-id
FCM_SERVICE_ACCOUNT_JSON=storage/firebase/service-account.json
```

### Mobile src/config.ts

```typescript
export const API_BASE_URL = 'http://10.0.2.2:8000';  // Emulador
// o 'http://192.168.1.X:8000' para dispositivo
export const API_SECRET = 'tu_clave_secreta_igual_al_backend';
```

### Mobile android/app/google-services.json

Descargar de Firebase Console y reemplazar placeholder.

## 🚀 Flujo de Ejecución

### Startup

1. Backend: `php artisan serve`
2. Mobile: `npm run android` (en emulador)

### Notificación Recibida

```
Backend recibe POST /api/mail-alert
    ↓
Valida secret
    ↓
Guarda en mail_alerts table
    ↓
Envía a FCM con Firebase HTTP v1 API
    ↓
FCM rutea a topic "mail-alerts"
    ↓
App React Native recibe notificación
    ↓
Si priority=emergency:
    → Lanza AlertScreen + alarma
Si priority=high:
    → Notificación normal en system tray
```

## 📊 Base de Datos - Tabla mail_alerts

```sql
CREATE TABLE mail_alerts (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  rule VARCHAR(255) NOT NULL,
  priority ENUM('high', 'emergency') NOT NULL,
  from_address VARCHAR(255) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  snippet LONGTEXT NOT NULL,
  timestamp DATETIME NOT NULL,
  sent_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_created_at (created_at),
  INDEX idx_priority (priority)
);
```

### Ejemplos de Datos

```json
{
  "id": 1,
  "rule": "github_security_alert",
  "priority": "emergency",
  "from_address": "security@github.com",
  "subject": "Security Alert: Unauthorized Push to Main",
  "snippet": "Detectamos un push sin autorización al branch main de tu repositorio",
  "timestamp": "2026-01-15T10:30:00Z",
  "sent_at": "2026-01-15T10:30:15Z",
  "created_at": "2026-01-15T10:30:15Z",
  "updated_at": "2026-01-15T10:30:15Z"
}
```

## 🔐 Autenticación y Seguridad

### POST /api/mail-alert

- Requiere campo `secret` en JSON body
- Sin validación de CORS (abierto)
- Validación servidor-side del secret contra env var

### GET /api/alerts

- Requiere header: `Authorization: Bearer {ALERT_SECRET}`
- Validación servidor-side del token

### Firebase Service Account

- Archivo JSON en `backend/storage/firebase/`
- Private key para generar JWT
- Acceso a Google OAuth2 API
- Nunca enviado a cliente

## 📦 Dependencias Principales

### Backend
- laravel/framework: ^13.0
- guzzlehttp/guzzle: ^7.8 (FCM HTTP calls)
- firebase/php-jwt: (para JWT si es necesario)

### Mobile
- react-native: 0.76.0
- @react-native-firebase/messaging: ^21.0.0
- @react-navigation/native: ^6.1.0
- axios: ^1.7.0
- react-native-sound: ^0.11.2

## 🧪 Testing

Ver [TESTING.md](./TESTING.md) para guía completa de testing con:
- Test de cada endpoint
- Test de notificaciones FCM
- Test de UI en app mobile
- Full flow test
- Performance test
- Troubleshooting

## 📚 Documentación

- [README.md](./README.md) - Overview general y setup
- [backend/README.md](./backend/README.md) - Backend específico
- [mobile/README.md](./mobile/README.md) - Mobile específico
- [mobile/ANDROID_SETUP.md](./mobile/ANDROID_SETUP.md) - Setup Android detallado
- [TESTING.md](./TESTING.md) - Testing completo
- [PROJECT_STRUCTURE.md](./PROJECT_STRUCTURE.md) - Este archivo

## 🎯 Próximos Pasos

1. Reemplazar placeholders en google-services.json
2. Descargar service account JSON de Firebase
3. Configurar .env en backend
4. Configurar src/config.ts en mobile
5. Ejecutar migrations en backend
6. Build y run en emulador/dispositivo
7. Seguir guía en TESTING.md

## 🔗 Referencias Externas

- [Laravel 13 Docs](https://laravel.com/docs/13)
- [React Native Docs](https://reactnative.dev/docs/getting-started)
- [Firebase Cloud Messaging](https://firebase.google.com/docs/cloud-messaging)
- [@react-native-firebase](https://rnfirebase.io/)
- [React Navigation](https://reactnavigation.org/docs/getting-started)
