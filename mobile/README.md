# Mail Alert App - React Native Android

App Android con React Native que recibe y muestra notificaciones push de alertas de correo electrónico vía Firebase Cloud Messaging.

## 📋 Requisitos

- Node.js 18+
- Android SDK (API Level 24+)
- Android Studio
- Firebase proyecto con credenciales
- google-services.json descargado de Firebase Console

## 🚀 Setup Rápido

### 1. Instalar dependencias

```bash
npm install
```

### 2. Configurar Firebase

1. Ir a [Firebase Console](https://console.firebase.google.com)
2. Crear o seleccionar proyecto
3. Agregar app Android
4. Descargar `google-services.json`
5. Copiar a `android/app/google-services.json`

```bash
cp /path/to/google-services.json android/app/
```

### 3. Configurar API

Editar `src/config.ts`:

```typescript
export const API_BASE_URL = 'http://10.0.2.2:8000'; // Para emulador
// o para dispositivo físico:
// export const API_BASE_URL = 'http://192.168.1.X:8000';

export const API_SECRET = 'tu_secret_del_backend';
```

### 4. Setup de Android

```bash
# Descargar dependencias de Gradle
cd android && ./gradlew build && cd ..
```

### 5. Build y Run

```bash
# En emulador
npm run android

# En dispositivo físico (conectado)
npm run android -- --deviceId=<device-id>
```

## 📱 Estructura del Proyecto

```
mobile/
├── src/
│   ├── screens/
│   │   ├── HomeScreen.tsx        # Lista de alertas recientes
│   │   └── AlertScreen.tsx       # Pantalla de alerta emergencia
│   ├── services/
│   │   ├── AlertApiService.ts    # API calls al backend
│   │   └── FirebaseService.ts    # Manejo de FCM
│   └── config.ts                 # Configuración de API y FCM
├── android/
│   ├── app/
│   │   ├── AndroidManifest.xml
│   │   ├── google-services.json
│   │   └── src/main/java/com/mailalert/MainActivity.kt
│   └── gradle/
├── App.tsx                        # Componente raíz con navegación
├── index.js                       # Entry point
└── package.json
```

## 🔔 Notificaciones Push

### Cómo Funcionan

1. **App inicia** → Se suscribe al topic `mail-alerts`
2. **Backend envía FCM** → Con data de prioridad
3. **App recibe notificación**:
   - Si `priority=emergency`: Lanza fullScreenIntent + alarma sonora
   - Si `priority=high`: Notificación normal + sonido custom

### Canales de Notificación

Configurados en `MainActivity.kt`:

- **emergency_channel**: Importance MAX, sonido de alarma, vibración
- **alert_channel**: Importance HIGH, sonido de notificación

### Tema de Suscripción

```typescript
// En FirebaseService.ts
await messaging().subscribeToTopic('mail-alerts');
```

## 📄 Pantallas

### HomeScreen

- Lista de últimos 50 alertas
- Pull to refresh
- Mostrar rule, from, subject, tiempo relativo
- Código de color por prioridad (rojo=emergency, naranja=high)

### AlertScreen

- Fullscreen modal para emergencias
- Título rojo "⚠ EMERGENCIA"
- Subject y snippet del alerta
- Botón DESCARTAR que detiene la alarma
- Animación de entrada con spring

## 🔐 Seguridad

- **API_SECRET**: Almacenado en `src/config.ts`
- Bearer token en header `Authorization: Bearer {API_SECRET}`
- No almacena tokens en AsyncStorage
- HTTPS recomendado en producción

## 🎵 Audio

### Alarma de Emergencia

Para que la alarma funcione:

1. Agregar archivo de sonido a `assets/alarm.mp3`
2. Usar con `react-native-sound`

```typescript
const sound = new Sound(
  require('../../assets/alarm.mp3'),
  undefined,
  (error) => {
    if (!error) {
      sound.setNumberOfLoops(-1); // Loop infinito
      sound.play();
    }
  }
);
```

## 🧪 Testing

### Simular notificación en emulador

1. Usar Firebase Console → Cloud Messaging
2. O usar `adb shell` para enviar notificación:

```bash
adb shell am start -a "com.google.firebase.MESSAGING_EVENT" \
  -n "com.mailalert/.MainActivity" \
  --es "rule" "test" \
  --es "priority" "emergency"
```

### Revisar logs

```bash
adb logcat | grep "Firebase\|AlertScreen"
```

## 🐛 Troubleshooting

### FCM Token no se obtiene

```bash
# Verificar que google-services.json está en lugar correcto
ls -la android/app/google-services.json

# Revisar logs
adb logcat | grep "FirebaseMessaging\|FCM"
```

### AlertScreen no se muestra

- Verificar que app está suscrita al topic en logs
- Revisar `FirebaseService.setOnEmergencyAlert()` está configurado
- Comprobar que prioridad en backend es "emergency"

### Sonido de alarma no se reproduce

- Comprobar que archivo `assets/alarm.mp3` existe
- Revisar permisos de audio
- Test en dispositivo físico (algunos emuladores tienen limitaciones)

### API_SECRET no funciona

- Verificar que `config.ts` tiene valor correcto
- Sincronizar con backend `ALERT_SECRET` en `.env`
- Revisar endpoint en `API_BASE_URL`

### Conexión a localhost desde emulador

- Android emulador: usar `10.0.2.2` en lugar de `localhost`
- Dispositivo físico: usar IP local (ej. `192.168.1.100`)
- HTTPS: necesita certificado válido

## 📦 Dependencias Principales

- `@react-native-firebase/app`: ^21.0.0
- `@react-native-firebase/messaging`: ^21.0.0
- `@react-navigation/native`: ^6.1.0
- `@react-navigation/native-stack`: ^6.9.0
- `axios`: ^1.7.0
- `react-native-sound`: ^0.11.2

## 🔗 Referencias

- [Firebase Cloud Messaging Android](https://firebase.google.com/docs/cloud-messaging/android/client)
- [React Native Firebase](https://rnfirebase.io/)
- [@react-native-firebase/messaging](https://rnfirebase.io/messaging/usage)
- [React Navigation](https://reactnavigation.org/)
- [react-native-sound](https://github.com/zmxv/react-native-sound)

## 💡 Notas

- App requiere permisos de internet y notificaciones
- Android 13+: solicita permiso POST_NOTIFICATIONS en runtime
- Fullscreen intent requiere permiso `SCHEDULE_EXACT_ALARM` (Android 12+)
- El servicio de Firebase corre en background
