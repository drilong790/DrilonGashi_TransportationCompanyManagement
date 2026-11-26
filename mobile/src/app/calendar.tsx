import { useEffect, useState } from 'react';
import { ActivityIndicator, SafeAreaView, StyleSheet, Text } from 'react-native';
import { router } from 'expo-router';
import { storedToken, storedUser } from '../api';
import { CalendarScreen } from '../screens/CalendarScreen';
import { colors } from '../theme';
import { User } from '../types';

export default function CalendarRoute() {
  const [token, setToken] = useState<string | null>(null);
  const [user, setUser] = useState<User | null>(null);
  const [error, setError] = useState('');

  useEffect(() => {
    let active = true;
    Promise.all([storedToken(), storedUser()])
      .then(([savedToken, savedUser]) => {
        if (!active) return;
        if (!savedToken || !savedUser) {
          router.replace('/');
          return;
        }
        setToken(savedToken);
        setUser(savedUser);
      })
      .catch((loadError: unknown) => {
        if (active) setError(loadError instanceof Error ? loadError.message : 'Sesioni nuk u ngarkua.');
      });
    return () => {
      active = false;
    };
  }, []);

  if (error) {
    return (
      <SafeAreaView style={styles.center}>
        <Text style={styles.error}>{error}</Text>
      </SafeAreaView>
    );
  }
  if (!token || !user) {
    return (
      <SafeAreaView style={styles.center}>
        <ActivityIndicator color={colors.amber} />
      </SafeAreaView>
    );
  }
  return (
    <CalendarScreen
      token={token}
      userId={user.id}
      onBack={() => router.back()}
      onOpen={(id) => router.push(`/transport/${id}`)}
    />
  );
}

const styles = StyleSheet.create({
  center: { alignItems: 'center', backgroundColor: colors.background, flex: 1, justifyContent: 'center', padding: 24 },
  error: { color: colors.danger, fontSize: 14, textAlign: 'center' },
});
