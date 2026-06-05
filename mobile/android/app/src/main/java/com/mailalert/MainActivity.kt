package com.mailalert

import android.app.NotificationChannel
import android.app.NotificationManager
import android.media.AudioAttributes
import android.media.RingtoneManager
import android.os.Build
import androidx.core.app.NotificationManagerCompat
import com.facebook.react.ReactActivity
import com.facebook.react.ReactActivityDelegate
import com.facebook.react.defaults.DefaultNewArchitectureEntryPoint
import com.facebook.react.defaults.DefaultReactActivityDelegate

class MainActivity : ReactActivity() {

    override fun getMainComponentName(): String = "MailAlert"

    override fun createReactActivityDelegate(): ReactActivityDelegate =
        DefaultReactActivityDelegate(
            this,
            mainComponentName,
            DefaultNewArchitectureEntryPoint.fabricEnabled,
        )

    override fun onResume() {
        super.onResume()
        createNotificationChannels()
    }

    private fun createNotificationChannels() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val notificationManager = getSystemService(NotificationManager::class.java)

            // Emergency Channel
            val emergencyChannel = NotificationChannel(
                "emergency_channel",
                "Alertas de Emergencia",
                NotificationManager.IMPORTANCE_MAX
            ).apply {
                description = "Notificaciones de emergencia con sonido de alarma"
                enableVibration(true)
                enableLights(true)
                setShowBadge(true)

                // Alarma de sonido en loop
                val alarmUri = RingtoneManager.getDefaultUri(RingtoneManager.TYPE_ALARM)
                val audioAttributes = AudioAttributes.Builder()
                    .setUsage(AudioAttributes.USAGE_ALARM)
                    .build()

                setSound(alarmUri, audioAttributes)
            }

            // Alert Channel
            val alertChannel = NotificationChannel(
                "alert_channel",
                "Alertas",
                NotificationManager.IMPORTANCE_HIGH
            ).apply {
                description = "Notificaciones de alertas normales"
                enableVibration(true)
                enableLights(true)
                setShowBadge(true)

                // Sonido custom para alertas normales
                val notificationUri = RingtoneManager.getDefaultUri(RingtoneManager.TYPE_NOTIFICATION)
                val audioAttributes = AudioAttributes.Builder()
                    .setUsage(AudioAttributes.USAGE_NOTIFICATION)
                    .build()

                setSound(notificationUri, audioAttributes)
            }

            notificationManager?.createNotificationChannel(emergencyChannel)
            notificationManager?.createNotificationChannel(alertChannel)
        }
    }
}
