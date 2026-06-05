# Mail Alert Backend - Laravel 13 API

API REST para gestionar alertas de correo y enviar notificaciones push vía Firebase Cloud Messaging (FCM).

## 📋 Requisitos

- PHP 8.3+
- Composer
- SQLite (o MySQL/PostgreSQL)
- Firebase proyecto con credenciales Service Account

## 🚀 Setup Rápido

### 1. Instalar dependencias
```bash
composer install
```

### 2. Configurar .env
```bash
cp .env.example .env
php artisan key:generate
```

Completar valores en `.env`:
```env
# Database (MySQL)
DB_HOST=127.0.0.1
DB_DATABASE=mail_alert
DB_USERNAME=mail_alert_user
DB_PASSWORD=your_secure_password_here

# Firebase
ALERT_SECRET=tu_clave_secreta_minimo_32_caracteres
FCM_PROJECT_ID=tu-firebase-project-id
FCM_SERVICE_ACCOUNT_JSON=storage/firebase/service-account.json
```

📖 **Para MySQL setup completo, ver [MYSQL_SETUP.md](../MYSQL_SETUP.md)**

### 3. Crear base de datos y ejecutar migraciones

**Opción A: MySQL** (Recomendado)
```bash
# Crear BD y usuario
mysql -u root -p
CREATE DATABASE mail_alert CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'mail_alert_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON mail_alert.* TO 'mail_alert_user'@'localhost';
FLUSH PRIVILEGES;

# Ejecutar migraciones
php artisan migrate
```

**Opción B: SQLite** (Desarrollo local)
```bash
touch database.sqlite
php artisan migrate
```

📖 **Ver [MYSQL_SETUP.md](../MYSQL_SETUP.md) para guía completa de MySQL**

### 4. Obtener Firebase Service Account

1. Ir a Firebase Console → Project Settings → Service Accounts
2. Click "Generate New Private Key"
3. Descargar JSON
4. Copiar a `storage/firebase/service-account.json`
5. Crear directorio si no existe: `mkdir -p storage/firebase`

### 5. Iniciar servidor
```bash
php artisan serve
```

El API estará disponible en `http://localhost:8000/api`

## 📡 Endpoints

### POST /api/mail-alert
Crear nueva alerta (sin autenticación, solo validación de secret)

**Request:**
```json
{
  "secret": "tu_secret_aqui",
  "rule": "github_security_alert",
  "priority": "emergency",
  "from": "noreply@github.com",
  "subject": "Security Alert: Unauthorized Push",
  "snippet": "Detectamos un push sin autorización al branch main",
  "timestamp": "2026-01-15T10:30:00Z"
}
```

**Response (201):**
```json
{
  "success": true,
  "alert_id": 1
}
```

**Errores:**
- `401`: Secret inválido
- `422`: Validación fallida (campos faltantes/inválidos)

### GET /api/alerts
Listar últimos 50 alertas (requiere Bearer token)

**Request:**
```bash
curl -H "Authorization: Bearer tu_secret_aqui" \
  http://localhost:8000/api/alerts
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "rule": "github_security_alert",
      "priority": "emergency",
      "from_address": "noreply@github.com",
      "subject": "Security Alert: Unauthorized Push",
      "snippet": "Detectamos un push sin autorización...",
      "timestamp": "2026-01-15T10:30:00Z",
      "sent_at": "2026-01-15T10:30:15Z",
      "created_at": "2026-01-15T10:30:15Z"
    }
  ]
}
```

## 🔐 Seguridad

### Validación de Secret
- `POST /api/mail-alert`: Requiere campo `secret` en el body que coincida con `ALERT_SECRET`
- No usa cookies/sessions, solo validación de string

### Autenticación de Alertas
- `GET /api/alerts`: Requiere header `Authorization: Bearer {ALERT_SECRET}`
- Token = mismo valor de `ALERT_SECRET`

### Firebase Service Account
- Almacenado en servidor, nunca expuesto al cliente
- JWT generado con clave privada del service account
- Token de acceso refresco cada hora

## 🔔 Firebase Cloud Messaging (FCM)

### HTTP v1 API
- **Endpoint**: `https://fcm.googleapis.com/v1/projects/{PROJECT_ID}/messages:send`
- **Autenticación**: OAuth 2.0 con JWT del service account
- **Payload**: Incluye notification data + Android-specific config

### Prioridades y Canales

**Emergency (priority=emergency):**
- `android.priority=high`
- `android.notification.channel_id=emergency_channel`
- En app React Native: lanza fullScreenIntent + sonido alarma en loop

**High (priority=high):**
- `android.priority=high`
- `android.notification.channel_id=alert_channel`
- En app React Native: notificación normal + sonido custom

### Estructura del Payload FCM
```json
{
  "message": {
    "topic": "mail-alerts",
    "notification": {
      "title": "Security Alert",
      "body": "Snippet del alerta"
    },
    "data": {
      "rule": "github_security_alert",
      "priority": "emergency",
      "from": "noreply@github.com",
      "subject": "Security Alert...",
      "snippet": "Detectamos un push..."
    },
    "android": {
      "priority": "high",
      "notification": {
        "channel_id": "emergency_channel"
      }
    }
  }
}
```

## 📊 Base de Datos

### Tabla: mail_alerts

```sql
CREATE TABLE mail_alerts (
  id BIGINT PRIMARY KEY,
  rule VARCHAR(255) NOT NULL,
  priority ENUM('high', 'emergency') NOT NULL,
  from_address VARCHAR(255) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  snippet LONGTEXT NOT NULL,
  timestamp DATETIME NOT NULL,
  sent_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (created_at),
  INDEX (priority)
);
```

## 🧪 Testing

### Test Manual de POST /api/mail-alert
```bash
curl -X POST http://localhost:8000/api/mail-alert \
  -H "Content-Type: application/json" \
  -d '{
    "secret": "your_secret_key_here_min_32_chars",
    "rule": "test_rule",
    "priority": "high",
    "from": "test@example.com",
    "subject": "Test Subject",
    "snippet": "Test snippet content",
    "timestamp": "2026-01-15T10:30:00Z"
  }'
```

### Test Manual de GET /api/alerts
```bash
curl http://localhost:8000/api/alerts \
  -H "Authorization: Bearer your_secret_key_here_min_32_chars"
```

### Tests Automatizados
```bash
php artisan test
```

## 🐛 Troubleshooting

### "Invalid secret"
- Verificar que `ALERT_SECRET` en `.env` coincide con el secret enviado en POST
- Mínimo 32 caracteres recomendado

### FCM: Error 401 Unauthorized
- Service account no tiene permisos "Firebase Cloud Messaging API"
- Verificar archivo service-account.json está en lugar correcto
- Comprobar `FCM_PROJECT_ID` es correcto

### FCM: Topic subscription not working
- Verificar que app React Native se suscribe a `mail-alerts` al iniciar
- Revisar Android logcat para errores de Firebase

### Database locked (SQLite)
- Cerrar otras conexiones a `database.sqlite`
- Borrar archivos de lock: `rm database.sqlite-*`

## 📦 Dependencias

- `laravel/framework`: ^13.0
- `guzzlehttp/guzzle`: ^7.8 - Cliente HTTP para FCM
- `firebase/php-jwt`: Para generar JWT de service account

## 🔗 Referencias

- [Firebase Cloud Messaging HTTP v1 API](https://firebase.google.com/docs/cloud-messaging/migrate-v1)
- [Laravel 13 Documentation](https://laravel.com/docs/13)
- [Firebase Service Account Auth](https://firebase.google.com/docs/app-check/custom-resource-tokens)
