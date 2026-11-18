import { router, useLocalSearchParams } from 'expo-router';
import { useEffect, useState } from 'react';
import { ActivityIndicator, Alert, Pressable, SafeAreaView, StyleSheet, Text } from 'react-native';
import { ApiError, clearToken, loadTransport, saveTransport, storedToken } from '../../api';
import { TransportFormScreen } from '../../screens/TransportFormScreen';
import { Transport, TransportInput } from '../../types';

export default function TransportFormRoute() {
  const { id } = useLocalSearchParams<{ id?: string }>();
  const [token, setToken] = useState<string | null>(null);
  const [transport, setTransport] = useState<Transport | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    let active = true;
    const initialize = async () => {
      try {
        const savedToken = await storedToken();
        if (!savedToken) {
          router.replace('/');
          return;
        }
        const existing = id ? await loadTransport(savedToken, Number(id)) : null;
        if (active) {
          setToken(savedToken);
          setTransport(existing);
        }
      } catch (requestError) {
        if (requestError instanceof ApiError && requestError.status === 401) {
          await clearToken();
          router.replace('/');
        } else if (active) {
          setError(requestError instanceof Error ? requestError.message : 'Transporti nuk u ngarkua.');
        }
      } finally {
        if (active) setLoading(false);
      }
    };
    void initialize();
    return () => {
      active = false;
    };
  }, [id]);

  const submit = async (values: TransportInput) => {
    if (!token) throw new Error('Sesioni përfundoi. Hyr përsëri.');
    const result = await saveTransport(token, transport?.id ?? null, values);
    if (result.warnings.length > 0) {
      Alert.alert('Transporti u ruajt', result.warnings.join('\n'));
    }
    router.replace({ pathname: '/transport/[id]', params: { id: String(result.transport.id) } });
  };

  if (loading) {
    return (
      <SafeAreaView style={styles.center}>
        <ActivityIndicator color="#078b81" size="large" />
      </SafeAreaView>
    );
  }
  if (error || !token) {
    return (
      <SafeAreaView style={styles.center}>
        <Text style={styles.error}>{error || 'Sesioni nuk është aktiv.'}</Text>
        <Pressable onPress={() => router.back()}><Text style={styles.back}>Kthehu</Text></Pressable>
      </SafeAreaView>
    );
  }
  return (
    <TransportFormScreen
      token={token}
      transport={transport}
      onBack={() => router.back()}
      onSave={submit}
    />
  );
}

const styles = StyleSheet.create({
  center: { alignItems: 'center', backgroundColor: '#f3f6f9', flex: 1, justifyContent: 'center', padding: 24 },
  error: { color: '#a73b3b', fontSize: 14, textAlign: 'center' },
  back: { color: '#078b81', fontSize: 15, fontWeight: '800', marginTop: 16, padding: 12 },
});
