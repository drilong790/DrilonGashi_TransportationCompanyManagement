import { router, useFocusEffect, useLocalSearchParams } from 'expo-router';
import { useCallback, useState } from 'react';
import { ActivityIndicator, Pressable, SafeAreaView, StyleSheet, Text } from 'react-native';
import { ApiError, clearToken, loadTransport, storedToken } from '../../api';
import { DetailScreen } from '../../screens/DetailScreen';
import { Transport } from '../../types';

export default function TransportDetailRoute() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const [transport, setTransport] = useState<Transport | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const fetchTransport = useCallback(async () => {
    const transportId = Number(id);
    if (!Number.isSafeInteger(transportId) || transportId < 1) {
      throw new Error('ID e transportit nuk është e vlefshme.');
    }
    const token = await storedToken();
    if (!token) {
      router.replace('/');
      throw new Error('Sesioni përfundoi.');
    }
    return loadTransport(token, transportId);
  }, [id]);

  useFocusEffect(
    useCallback(() => {
    let active = true;
    void Promise.resolve()
      .then(fetchTransport)
      .then((loaded) => {
        if (active) setTransport(loaded);
      })
      .catch(async (requestError: unknown) => {
        if (!active) return;
        if (requestError instanceof ApiError && requestError.status === 401) {
          await clearToken();
          router.replace('/');
        } else if (active) {
          setError(requestError instanceof Error ? requestError.message : 'Transporti nuk u ngarkua.');
        }
      })
      .finally(() => {
        if (active) setLoading(false);
      });
    return () => {
      active = false;
    };
    }, [fetchTransport]),
  );

  const refresh = async () => {
    setLoading(true);
    setError('');
    try {
      setTransport(await fetchTransport());
    } catch (requestError) {
      if (requestError instanceof ApiError && requestError.status === 401) {
        await clearToken();
        router.replace('/');
      } else {
        setError(requestError instanceof Error ? requestError.message : 'Transporti nuk u ngarkua.');
      }
    } finally {
      setLoading(false);
    }
  };

  if (!transport) {
    return (
      <SafeAreaView style={styles.center}>
        {loading ? <ActivityIndicator color="#078b81" size="large" /> : <Text style={styles.error}>{error}</Text>}
        {!loading && <Pressable onPress={() => router.back()}><Text style={styles.back}>Kthehu</Text></Pressable>}
      </SafeAreaView>
    );
  }

  return (
    <DetailScreen
      transport={transport}
      loading={loading}
      error={error}
      onBack={() => router.back()}
      onEdit={() => router.push({ pathname: '/transport/form', params: { id: String(transport.id) } })}
      onRefresh={() => void refresh()}
    />
  );
}

const styles = StyleSheet.create({
  center: { alignItems: 'center', backgroundColor: '#f3f6f9', flex: 1, justifyContent: 'center', padding: 24 },
  error: { color: '#a73b3b', fontSize: 14, textAlign: 'center' },
  back: { color: '#078b81', fontSize: 15, fontWeight: '800', marginTop: 16, padding: 12 },
});
