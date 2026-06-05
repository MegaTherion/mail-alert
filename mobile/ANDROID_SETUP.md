# Android Setup Guide - Mail Alert App

Guía completa para configurar el proyecto React Native en Android.

## 📋 Requisitos Previos

- Android SDK API Level 24+
- Android Studio instalado
- Node.js 18+
- npm o yarn
- Java Development Kit (JDK) 11+

## 🔧 Configuración del Ambiente

### 1. Instalar Android SDK

```bash
# Si usas Android Studio, aparecerá la opción de instalar SDK
# O manualmente:
# - Descargar SDK desde: https://developer.android.com/sdk
# - Agregar a PATH
```

### 2. Configurar Variables de Entorno

**Windows (PowerShell):**
```powershell
$env:ANDROID_HOME = "C:\Users\<tu-usuario>\AppData\Local\Android\Sdk"
$env:ANDROID_SDK_ROOT = "C:\Users\<tu-usuario>\AppData\Local\Android\Sdk"
```

**macOS/Linux:**
```bash
export ANDROID_HOME=$HOME/Library/Android/Sdk
export PATH=$PATH:$ANDROID_HOME/emulator
export PATH=$PATH:$ANDROID_HOME/platform-tools
```

### 3. Crear Virtual Device (Emulador)

```bash
# Listar dispositivos disponibles
emulator -list-avds

# Crear nuevo dispositivo
avdmanager create avd -n "Pixel4" -d "pixel_4" -k "android-30"

# Iniciar emulador
emulator -avd Pixel4
```

## 📦 Dependencias del Proyecto

### Instalar dependencias

```bash
cd mobile
npm install
```

### Gradle Build

```bash
cd android
./gradlew build
cd ..
```

## 🔐 Firebase Setup

### 1. Crear Proyecto Firebase

1. Ir a [Firebase Console](https://console.firebase.google.com)
2. Crear nuevo proyecto
3. Habilitare Cloud Messaging

### 2. Registrar App Android

1. En Firebase Console → Proyecto → Configuración → Apps
2. Click "+ Agregar app Android"
3. Llenar datos:
   - **Package name**: `com.mailalert`
   - **SHA-1**: (Opcional para desarrollo)
4. Descargar `google-services.json`

### 3. Copiar Archivo

```bash
cp /path/to/google-services.json mobile/android/app/
```

## 🚀 Build y Run

### Build APK

```bash
cd mobile
npm run android

# O manualmente:
cd android
./gradlew assembleDebug
cd ..
```

### Instalar en Emulador/Dispositivo

```bash
adb install -r android/app/build/outputs/apk/debug/app-debug.apk
```

### Run en Emulador

```bash
npm run android
```

### Run en Dispositivo Físico

1. Conectar dispositivo con USB
2. Habilitar USB Debugging en Settings → Developer Options
3. Ejecutar:

```bash
adb devices  # Verificar que aparece
npm run android
```

## 🔌 Permisos Android

Los siguientes permisos están configurados en `AndroidManifest.xml`:

```xml
<uses-permission android:name="android.permission.INTERNET" />
<uses-permission android:name="android.permission.POST_NOTIFICATIONS" />
<uses-permission android:name="android.permission.SCHEDULE_EXACT_ALARM" />
```

Para Android 13+, el permiso `POST_NOTIFICATIONS` se solicita en runtime.

## 🔔 Configuración de Canales de Notificación

Los canales se crean en `MainActivity.kt`:

- **emergency_channel**: Alarma de sonido
- **alert_channel**: Sonido de notificación

```kotlin
private fun createNotificationChannels() {
    if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
        val notificationManager = getSystemService(NotificationManager::class.java)
        
        val emergencyChannel = NotificationChannel(
            "emergency_channel",
            "Alertas de Emergencia",
            NotificationManager.IMPORTANCE_MAX
        )
        
        notificationManager?.createNotificationChannel(emergencyChannel)
    }
}
```

## 🧪 Testing y Debugging

### Logs en Tiempo Real

```bash
adb logcat | grep "mail-alert\|Firebase"
```

### Instalar en modo Debug

```bash
npm run android -- --variant=debug
```

### Detener Servidor Metro

```bash
npm start
```

### Limpiar Build

```bash
cd android
./gradlew clean
cd ..
```

## 🐛 Troubleshooting

### Error: "Android SDK not found"

```bash
# Verificar ANDROID_HOME
echo $ANDROID_HOME

# O establecerlo
export ANDROID_HOME=/path/to/android/sdk
```

### Error: "Gradle build failed"

```bash
cd android
./gradlew clean build
cd ..
```

### Error: "gradle-wrapper.jar not found"

```bash
cd android
./gradlew wrapper --gradle-version 8.0
cd ..
```

### Emulador lento

- Habilitar KVM (Linux)
- Usar hardware acceleration (Windows/Mac)
- Aumentar RAM asignada en emulador

### Notificaciones no se reciben

1. Verificar `google-services.json` está en `android/app/`
2. Revisar Firebase Console → Cloud Messaging
3. Comprobar permisos de notificaciones en Settings
4. Reiniciar emulador y app

### Hot Reload no funciona

```bash
# Presionar 'R' dos veces en terminal Metro
# O:
adb reverse tcp:8081 tcp:8081
```

## 📱 Configuración de Conectividad

### Emulador → Servidor Local

Para conectar desde emulador a servidor en localhost:

```typescript
// src/config.ts
export const API_BASE_URL = 'http://10.0.2.2:8000';
```

`10.0.2.2` es la IP especial del emulador para alcanzar host machine.

### Dispositivo Físico → Servidor Local

1. Averiguar IP local:

```bash
# Windows
ipconfig

# macOS/Linux
ifconfig | grep inet
```

2. Usar IP en config:

```typescript
export const API_BASE_URL = 'http://192.168.1.100:8000';
```

3. Asegurar que backend escucha en `0.0.0.0`:

```bash
php artisan serve --host=0.0.0.0
```

## 🔒 Network Security

Para desarrollo en emulador con HTTP (no HTTPS):

`AndroidManifest.xml` ya tiene:
```xml
android:usesCleartextTraffic="true"
```

⚠️ **No usar en producción**

## 📊 Build Variants

```bash
# Debug
npm run android -- --variant=debug

# Release
npm run android -- --variant=release
```

## 🎯 ProGuard/R8 (Release Builds)

Configurado en `android/app/build.gradle`:

```gradle
release {
    minifyEnabled true
    proguardFiles getDefaultProguardFile('proguard-android-optimize.txt')
}
```

## 📦 APK Size

Reducir tamaño:

```gradle
android {
    splits {
        abi {
            enable true
            reset()
            include 'arm64-v8a'
        }
    }
}
```

## 🔗 Referencias

- [React Native Android Setup](https://reactnative.dev/docs/environment-setup)
- [Firebase Android Setup](https://firebase.google.com/docs/android/setup)
- [Android Developers](https://developer.android.com/)
- [Gradle Documentation](https://gradle.org/releases/)
