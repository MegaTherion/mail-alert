import React, { useEffect, useRef } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  Animated,
} from 'react-native';
import Sound from 'react-native-sound';

interface AlertScreenProps {
  subject: string;
  snippet: string;
  from: string;
  rule: string;
  onDismiss: () => void;
}

const AlertScreen: React.FC<AlertScreenProps> = ({
  subject,
  snippet,
  from,
  rule,
  onDismiss,
}) => {
  const scaleAnim = useRef(new Animated.Value(0.8)).current;
  const alarmSound = useRef<Sound | null>(null);

  useEffect(() => {
    // Animación de entrada
    Animated.spring(scaleAnim, {
      toValue: 1,
      useNativeDriver: true,
      speed: 8,
    }).start();

    // Cargar y reproducir sonido de alarma en loop
    Sound.setCategory('Alarm');
    const sound = new Sound(
      require('../../assets/alarm.mp3'), // Necesita archivo de sonido
      undefined,
      (error) => {
        if (error) {
          console.log('Error loading alarm sound:', error);
          return;
        }
        // Reproducir en loop
        sound.setNumberOfLoops(-1);
        sound.play((success) => {
          if (success) {
            console.log('Alarm stopped');
          }
        });
        alarmSound.current = sound;
      },
    );

    return () => {
      if (alarmSound.current) {
        alarmSound.current.stop(() => {
          alarmSound.current?.release();
        });
      }
    };
  }, [scaleAnim]);

  const handleDismiss = () => {
    if (alarmSound.current) {
      alarmSound.current.stop(() => {
        alarmSound.current?.release();
      });
    }
    onDismiss();
  };

  return (
    <View style={styles.container}>
      <Animated.View
        style={[
          styles.content,
          {
            transform: [{ scale: scaleAnim }],
          },
        ]}
      >
        <View style={styles.alertBox}>
          <View style={styles.header}>
            <Text style={styles.emergencyBadge}>⚠ EMERGENCIA</Text>
          </View>

          <View style={styles.body}>
            <Text style={styles.rule}>{rule}</Text>

            <Text style={styles.subject} numberOfLines={3}>
              {subject}
            </Text>

            <Text style={styles.snippet} numberOfLines={4}>
              {snippet}
            </Text>

            <View style={styles.sender}>
              <Text style={styles.fromLabel}>De:</Text>
              <Text style={styles.from}>{from}</Text>
            </View>
          </View>

          <View style={styles.footer}>
            <TouchableOpacity
              style={styles.dismissButton}
              onPress={handleDismiss}
            >
              <Text style={styles.dismissText}>DESCARTAR</Text>
            </TouchableOpacity>
          </View>
        </View>
      </Animated.View>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.7)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  content: {
    width: '85%',
  },
  alertBox: {
    backgroundColor: '#fff',
    borderRadius: 12,
    overflow: 'hidden',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
    elevation: 8,
  },
  header: {
    backgroundColor: '#d32f2f',
    padding: 16,
    justifyContent: 'center',
    alignItems: 'center',
  },
  emergencyBadge: {
    fontSize: 20,
    fontWeight: '700',
    color: '#fff',
    textAlign: 'center',
  },
  body: {
    padding: 20,
  },
  rule: {
    fontSize: 12,
    color: '#999',
    marginBottom: 8,
    fontWeight: '500',
  },
  subject: {
    fontSize: 18,
    fontWeight: '700',
    color: '#222',
    marginBottom: 12,
    lineHeight: 24,
  },
  snippet: {
    fontSize: 14,
    color: '#555',
    marginBottom: 16,
    lineHeight: 20,
  },
  sender: {
    flexDirection: 'row',
    marginTop: 12,
    paddingTop: 12,
    borderTopWidth: 1,
    borderTopColor: '#eee',
  },
  fromLabel: {
    fontSize: 12,
    color: '#999',
    marginRight: 4,
  },
  from: {
    fontSize: 12,
    color: '#2196F3',
    fontWeight: '600',
  },
  footer: {
    paddingHorizontal: 20,
    paddingVertical: 16,
    borderTopWidth: 1,
    borderTopColor: '#eee',
  },
  dismissButton: {
    backgroundColor: '#d32f2f',
    paddingVertical: 12,
    borderRadius: 6,
    alignItems: 'center',
  },
  dismissText: {
    color: '#fff',
    fontSize: 14,
    fontWeight: '700',
    letterSpacing: 1,
  },
});

export default AlertScreen;
