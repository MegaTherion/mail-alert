# FCM Troubleshooting - Failed to Obtain Access Token

Guía para resolver problemas de autenticación con Firebase Cloud Messaging.

## ❌ Error: "Failed to obtain FCM access token"

### Causas Posibles

1. **Service account JSON no existe o ruta incorrecta**
2. **Archivo JSON está corrupto o mal formado**
3. **Permisos insuficientes en el archivo**
4. **Credenciales de Google inválidas o expiradas**
5. **Problema de red/conexión con Google OAuth2**

---

## 🔍 Paso 1: Verificar que el Archivo Existe

### En el servidor

```bash
# Navegar al backend
cd /home/tuxnir/public_html/alertemail.tuxnir.com/mail-alert/backend

# Verificar que el archivo existe
ls -la storage/firebase/service-account.json

# Debería mostrar algo como:
# -rw-r--r-- 1 tuxnir tuxnir 2345 Jun 5 10:30 service-account.json
```

Si **NO existe**, ir al paso 5 para descargar el archivo.

### Verificar en .env

```bash
# Ver la ruta configurada
grep FCM_SERVICE_ACCOUNT_JSON .env

# Debería mostrar:
# FCM_SERVICE_ACCOUNT_JSON=storage/firebase/service-account.json
```

---

## 🔍 Paso 2: Verificar que el JSON es Válido

```bash
# Validar que el JSON es válido
php -r "json_decode(file_get_contents('storage/firebase/service-account.json'), true) || exit('JSON Inválido');" && echo "✓ JSON válido"
```

Si retorna error, el archivo está corrupto. Descargarlo de nuevo (paso 5).

---

## 🔍 Paso 3: Verificar Contenido del JSON

```bash
# Ver primeras líneas del archivo
head -20 storage/firebase/service-account.json

# Debería tener esta estructura:
# {
#   "type": "service_account",
#   "project_id": "tu-project-id",
#   "private_key_id": "...",
#   "private_key": "-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----\n",
#   "client_email": "firebase-adminsdk-...",
#   "client_id": "...",
#   "auth_uri": "https://accounts.google.com/o/oauth2/auth",
#   "token_uri": "https://oauth2.googleapis.com/token",
#   ...
# }
```

**Campos obligatorios:**
- `type` = `"service_account"`
- `project_id` = tu Firebase project ID
- `private_key` = la clave privada completa
- `client_email` = email del service account

---

## 🔍 Paso 4: Verificar Configuración en Laravel

### Ver config de FCM

```bash
# Ejecutar en tinker
php artisan tinker

>>> config('services.fcm.project_id')
# Debería mostrar: "tu-firebase-project-id"

>>> config('services.fcm.service_account_json')
# Debería mostrar: "storage/firebase/service-account.json"

>>> file_exists(config('services.fcm.service_account_json'))
# Debería mostrar: true
```

Si alguno es null o false, revisar `.env` y `config/services.php`.

---

## 🔍 Paso 5: Descargar Service Account JSON Correcto

### En Firebase Console

1. Ve a **Firebase Console** → Tu proyecto
2. **Configuración del proyecto** (icono de engranaje)
3. **Cuentas de servicio** (tab)
4. Click **"Generar nueva clave privada"**
5. Se descarga `[project-name]-firebase-adminsdk-[...].json`

### En tu servidor

```bash
# Crear directorio si no existe
mkdir -p storage/firebase

# Opción A: Copiar vía SCP desde tu máquina
# Desde tu máquina local:
scp service-account.json tuxnir@alertemail.tuxnir.com:/home/tuxnir/public_html/alertemail.tuxnir.com/mail-alert/backend/storage/firebase/

# Opción B: Crear el archivo directamente
# SSH al servidor
ssh tuxnir@alertemail.tuxnir.com

# Crear el archivo y pegar el contenido
nano storage/firebase/service-account.json
# Pegar el contenido del JSON descargado
# Ctrl+X → Y → Enter

# Verificar permisos
chmod 644 storage/firebase/service-account.json
```

---

## 🔍 Paso 6: Test de Conexión a Google OAuth2

### Test manual con curl

```bash
# Obtener access token manualmente
PRIVATE_KEY=$(cat storage/firebase/service-account.json | grep '"private_key"' | cut -d'"' -f4)
CLIENT_EMAIL=$(cat storage/firebase/service-account.json | grep '"client_email"' | cut -d'"' -f4)

echo "Private Key: $PRIVATE_KEY"
echo "Client Email: $CLIENT_EMAIL"

# Debería mostrar valores no vacíos
```

### Test desde Laravel

```bash
# En tinker
php artisan tinker

>>> $service = new \App\Services\FCMService()

# Esto debería intentar obtener el token
# Si falla, ver logs en storage/logs/laravel.log
```

---

## 🔍 Paso 7: Revisar Logs Detallados

### Ver logs de Laravel

```bash
# Últimos 50 líneas
tail -50 storage/logs/laravel.log

# Filtrar errores de FCM
grep -i fcm storage/logs/laravel.log | tail -20

# Seguir logs en tiempo real
tail -f storage/logs/laravel.log
```

### Aumentar nivel de logging

En `.env`:
```env
LOG_LEVEL=debug
```

Luego:
```bash
ea-php83 artisan config:cache
```

Reintentar la petición curl y revisar logs.

---

## 🔍 Paso 8: Verificar Firewall/Red

### Test de conexión a Google

```bash
# Verificar que puedes alcanzar OAuth2 de Google
curl -v https://oauth2.googleapis.com/token

# Debería responder con 400/401 o similar (no error de conexión)
```

Si hay problema de conexión:
- Verificar firewall del servidor
- Verificar que puerto 443 (HTTPS) está abierto
- Contactar con proveedor de hosting

---

## ✅ Checklist de Debugging

```
Verificación:
  ☐ storage/firebase/service-account.json existe
  ☐ El archivo es readable (permisos >= 644)
  ☐ JSON es válido (no corrupto)
  ☐ JSON tiene todos los campos requeridos
  ☐ .env tiene FCM_PROJECT_ID configurado
  ☐ .env tiene FCM_SERVICE_ACCOUNT_JSON configurado
  ☐ config('services.fcm.*') retorna valores
  ☐ Puedo conectar a oauth2.googleapis.com
  ☐ Private key en JSON es válida (-----BEGIN PRIVATE KEY-----)
  ☐ Logs muestran datos específicos del error
```

---

## 🔧 Soluciones Comunes

### Error: "File not found"

```bash
# Verificar ruta exacta
pwd  # Ver directorio actual
ls -la storage/firebase/

# Si archivo no existe, descargarlo (paso 5)
```

### Error: "Invalid JSON"

```bash
# El archivo está corrupto
# Solución: Descargar nuevamente de Firebase (paso 5)
```

### Error: "Permission denied"

```bash
# Permisos incorrectos
chmod 644 storage/firebase/service-account.json
chmod 755 storage/firebase/
```

### Error: "Private key invalid"

```bash
# La clave privada en el JSON está mal
# Solución: Generar nueva clave en Firebase Console (paso 5)
```

### Error: "Connection timeout"

```bash
# Problema de red
# Soluciones:
# 1. Verificar firewall
# 2. Verificar DNS
# 3. Incrementar timeout en config
```

---

## 🔐 Verificar Credenciales Google

### En Firebase Console

1. Ve a **Configuración del proyecto** → **Cuentas de servicio**
2. Busca la cuenta: `firebase-adminsdk-[id]@[project].iam.gserviceaccount.com`
3. Verifica que tiene rol **"Editor"** o **"Firebase Admin SDK Administrator Service Account"**

Si no tiene permisos, agregar:
1. **IAM & Admin** (en Google Cloud Console)
2. Buscar la cuenta de servicio
3. Click **"Editar"**
4. Agregar rol **"Firebase Admin SDK Administrator Service Account"**

---

## 📝 Test de FCM Completo

Una vez todo está configurado:

```bash
# Test de conexión
php artisan tinker

>>> $service = new \App\Services\FCMService()

>>> $service->sendToTopic(
      topic: 'mail-alerts',
      title: 'Test Alert',
      body: 'This is a test notification',
      data: ['test' => 'true'],
      priority: 'high'
    )

# Debería retornar: true

# Si retorna false, revisar logs:
>>> tail storage/logs/laravel.log
```

---

## 🚀 Test via API

Una vez que todo funciona:

```bash
curl -X POST https://alertemail.tuxnir.com/api/mail-alert \
  -H "Content-Type: application/json" \
  -d '{
    "secret": "tu_ALERT_SECRET",
    "rule": "Test Rule",
    "priority": "high",
    "from": "test@example.com",
    "subject": "Test Subject",
    "snippet": "Test snippet",
    "timestamp": "2026-06-06T01:53:00Z"
  }'
```

Debería retornar:
```json
{
  "success": true,
  "alert_id": 1
}
```

---

## 📞 Si Sigue Sin Funcionar

1. Recolectar información:
   ```bash
   # En tu servidor
   php artisan config:cache
   tail -100 storage/logs/laravel.log > fcm_error.log
   
   # Compartir:
   # - La línea del error en laravel.log
   # - Output de: php artisan tinker → config('services.fcm')
   # - Output de: file_exists(...)
   ```

2. Revisar:
   - ¿Firebase project ID es correcto?
   - ¿Service account JSON es del proyecto correcto?
   - ¿Hay espacios o caracteres especiales en la ruta?

3. Contactar soporte Firebase si las credenciales están inválidas

---

## 🎉 Checklist Final

Una vez que funciona:

- ☐ FCM notification aparece en el teléfono
- ☐ `sent_at` se actualiza en la BD
- ☐ No hay errores en logs
- ☐ El topic "mail-alerts" recibe mensajes

¡Listo! 🚀
