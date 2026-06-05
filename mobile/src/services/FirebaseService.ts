import messaging from '@react-native-firebase/messaging';
import { Platform, PermissionsAndroid } from 'react-native';
import { FCM_CONFIG, ALERT_PRIORITIES } from '../config';

export interface RemoteMessage {
  messageId?: string;
  sentTime?: number;
  from?: string;
  to?: string;
  ttl?: number;
  messageType?: string;
  data: Record<string, any>;
  notification?: {
    title?: string;
    body?: string;
  };
}

class FirebaseService {
  private onEmergencyAlert: ((data: any) => void) | null = null;
  private onNormalAlert: ((data: any) => void) | null = null;

  async initialize(): Promise<void> {
    try {
      // Request notification permission (iOS)
      if (Platform.OS === 'ios') {
        const authStatus = await messaging().requestPermission();
        const enabled =
          authStatus === messaging.AuthorizationStatus.AUTHORIZED ||
          authStatus === messaging.AuthorizationStatus.PROVISIONAL;

        if (!enabled) {
          console.log('Notification permission not granted');
        }
      }

      // Request permission (Android 13+)
      if (Platform.OS === 'android' && Platform.Version >= 33) {
        const permission = await PermissionsAndroid.request(
          PermissionsAndroid.PERMISSIONS.POST_NOTIFICATIONS,
        );
        if (permission !== PermissionsAndroid.RESULTS.GRANTED) {
          console.log('Notification permission denied on Android 13+');
        }
      }

      // Get and log FCM token
      const token = await messaging().getToken();
      console.log('FCM Token:', token);

      // Subscribe to mail-alerts topic
      await messaging().subscribeToTopic(FCM_CONFIG.TOPIC);
      console.log(`Subscribed to topic: ${FCM_CONFIG.TOPIC}`);

      // Handle foreground messages
      this.handleForegroundMessages();

      // Handle background messages
      messaging().onNotificationOpenedApp(this.handleBackgroundMessage);

      // Handle initial notification
      const initialNotification = await messaging().getInitialNotification();
      if (initialNotification) {
        this.handleBackgroundMessage(initialNotification);
      }

      console.log('Firebase messaging initialized');
    } catch (error) {
      console.error('Error initializing Firebase:', error);
    }
  }

  private handleForegroundMessages(): void {
    messaging().onMessage(async (remoteMessage: RemoteMessage) => {
      console.log('Foreground message received:', remoteMessage);

      const priority = remoteMessage.data?.priority || 'high';

      if (priority === ALERT_PRIORITIES.EMERGENCY) {
        if (this.onEmergencyAlert) {
          this.onEmergencyAlert(remoteMessage.data);
        }
      } else {
        if (this.onNormalAlert) {
          this.onNormalAlert(remoteMessage.data);
        }
      }
    });
  }

  private handleBackgroundMessage = (remoteMessage: RemoteMessage): void => {
    console.log('Background/Notification opened:', remoteMessage);
    const priority = remoteMessage.data?.priority || 'high';

    if (priority === ALERT_PRIORITIES.EMERGENCY) {
      if (this.onEmergencyAlert) {
        this.onEmergencyAlert(remoteMessage.data);
      }
    } else {
      if (this.onNormalAlert) {
        this.onNormalAlert(remoteMessage.data);
      }
    }
  };

  setOnEmergencyAlert(callback: (data: any) => void): void {
    this.onEmergencyAlert = callback;
  }

  setOnNormalAlert(callback: (data: any) => void): void {
    this.onNormalAlert = callback;
  }

  async unsubscribeFromTopic(): Promise<void> {
    try {
      await messaging().unsubscribeFromTopic(FCM_CONFIG.TOPIC);
      console.log(`Unsubscribed from topic: ${FCM_CONFIG.TOPIC}`);
    } catch (error) {
      console.error('Error unsubscribing from topic:', error);
    }
  }
}

export default new FirebaseService();
