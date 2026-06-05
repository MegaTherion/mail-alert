// Firebase Cloud Messaging Configuration
export const FCM_CONFIG = {
  // Topic to subscribe to for mail alerts
  TOPIC: 'mail-alerts',
};

// API Configuration
export const API_BASE_URL = 'http://10.0.2.2:8000'; // Android emulator localhost
// Para dispositivo físico, cambiar a: http://192.168.1.X:8000

export const API_SECRET = 'your_secret_key_here_min_32_chars';

// Notification Channels
export const NOTIFICATION_CHANNELS = {
  EMERGENCY: 'emergency_channel',
  ALERT: 'alert_channel',
};

// Alert Priorities
export const ALERT_PRIORITIES = {
  HIGH: 'high',
  EMERGENCY: 'emergency',
};
