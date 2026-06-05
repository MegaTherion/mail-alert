import React, { useEffect, useState } from 'react';
import { NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { Platform, View, StyleSheet } from 'react-native';
import HomeScreen from './src/screens/HomeScreen';
import AlertScreen from './src/screens/AlertScreen';
import FirebaseService from './src/services/FirebaseService';

export type RootStackParamList = {
  Home: undefined;
  Alert: {
    subject: string;
    snippet: string;
    from: string;
    rule: string;
  };
};

const Stack = createNativeStackNavigator<RootStackParamList>();

const App: React.FC = () => {
  const [isEmergencyAlertVisible, setIsEmergencyAlertVisible] = useState(false);
  const [emergencyAlertData, setEmergencyAlertData] = useState<any>(null);

  useEffect(() => {
    // Inicializar Firebase
    FirebaseService.initialize();

    // Configurar callback para alertas de emergencia
    FirebaseService.setOnEmergencyAlert((data) => {
      console.log('Emergency alert received:', data);
      setEmergencyAlertData(data);
      setIsEmergencyAlertVisible(true);
    });

    // Configurar callback para alertas normales
    FirebaseService.setOnNormalAlert((data) => {
      console.log('Normal alert received:', data);
      // Aquí se podría mostrar una notificación local
      // o simplemente refrescar la lista de alertas
    });
  }, []);

  const handleDismissAlert = () => {
    setIsEmergencyAlertVisible(false);
    setEmergencyAlertData(null);
  };

  return (
    <NavigationContainer>
      <Stack.Navigator
        screenOptions={{
          headerShown: false,
          cardStyle: { backgroundColor: '#f5f5f5' },
        }}
      >
        <Stack.Screen
          name="Home"
          component={HomeScreen}
          options={{
            title: 'Alertas de Correo',
          }}
        />
      </Stack.Navigator>

      {/* Overlay de alerta de emergencia */}
      {isEmergencyAlertVisible && emergencyAlertData && (
        <View style={styles.alertOverlay}>
          <AlertScreen
            subject={emergencyAlertData.subject || 'Alerta de Emergencia'}
            snippet={emergencyAlertData.snippet || ''}
            from={emergencyAlertData.from || 'Desconocido'}
            rule={emergencyAlertData.rule || 'emergency'}
            onDismiss={handleDismissAlert}
          />
        </View>
      )}
    </NavigationContainer>
  );
};

const styles = StyleSheet.create({
  alertOverlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(0, 0, 0, 0.7)',
    justifyContent: 'center',
    alignItems: 'center',
    zIndex: 1000,
  },
});

export default App;
