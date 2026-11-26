import { router, useFocusEffect, useLocalSearchParams } from 'expo-router';
import { useCallback, useState } from 'react';
import { ActivityIndicator, Pressable, SafeAreaView, StyleSheet, Text } from 'react-native';
import { ApiError, clearToken, currentUser, loadTransport, storedToken, storedUser, updateDriverStatus } from '../../api';
import { loadCachedTransports, queueDriverStatus, updateCachedTransport } from '../../offline';
import { DetailScreen } from '../../screens/DetailScreen';
import { colors } from '../../theme';
import { Transport } from '../../types';

export default function TransportDetailRoute() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const [transport, setTransport] = useState<Transport | null>(null);
  const [userRole, setUserRole] = useState<'SUPERADMIN' | 'ADMIN' | 'DISPATCHER' | 'DRIVER' | null>(null);
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
    const savedUser = await storedUser();
    if (!savedUser) {
      router.replace('/');
      throw new Error('Profili lokal i llogarisë nuk u gjet.');
    }
    let user = savedUser;
    try {
      user = await currentUser(token);
    } catch (requestError) {
      if (!(requestError instanceof ApiError && requestError.status === 0)) throw requestError;
    }
    setUserRole(user.role);
    try {
      return await loadTransport(token, transportId);
    } catch (requestError) {
      if (!(requestError instanceof ApiError && requestError.status === 0)) throw requestError;
      const cached = await loadCachedTransports(user.id);
      const transport = cached.find((item) => item.id === transportId);
      if (!transport) throw new Error('Transporti nuk u gjet në të dhënat e ruajtura.');
      return transport;
    }
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

  const changeDriverStatus = async (
    transportId: number,
    status: 'IN_PROGRESS' | 'COMPLETED',
  ): Promise<boolean> => {
    const token = await storedToken();
    if (!token) {
      router.replace('/');
      throw new Error('Sesioni përfundoi.');
    }
    const user = await storedUser();
    if (!user) throw new Error('Profili lokal i llogarisë nuk u gjet.');
    try {
      const updated = await updateDriverStatus(token, transportId, status);
      setTransport(updated);
      await updateCachedTransport(user.id, transportId, status);
      return false;
    } catch (requestError) {
      if (!(requestError instanceof ApiError && requestError.status === 0)) throw requestError;
      await queueDriverStatus(user.id, { transport_id: transportId, status });
      await updateCachedTransport(user.id, transportId, status);
      setTransport((current) => current ? { ...current, status } : current);
      return true;
    }
  };

  if (!transport) {
    return (
      <SafeAreaView style={styles.center}>
        {loading ? <ActivityIndicator color={colors.amber} size="large" /> : <Text style={styles.error}>{error}</Text>}
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
      isDriver={userRole === 'DRIVER'}
      onDriverStatusChange={changeDriverStatus}
    />
  );
}

const styles = StyleSheet.create({
  center: { alignItems: 'center', backgroundColor: colors.background, flex: 1, justifyContent: 'center', padding: 24 },
  error: { color: colors.danger, fontSize: 14, textAlign: 'center' },
  back: { color: colors.amberLight, fontSize: 15, fontWeight: '800', marginTop: 16, padding: 12 },
});
