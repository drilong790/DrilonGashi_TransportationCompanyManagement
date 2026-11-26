import { router, useLocalSearchParams } from 'expo-router';
import { useEffect, useState } from 'react';
import { ActivityIndicator, Alert, Pressable, SafeAreaView, StyleSheet, Text } from 'react-native';
import {
  ApiError,
  clearToken,
  loadResources,
  loadTransport,
  saveTransport,
  storedToken,
  storedUser,
} from '../../api';
import {
  cacheTransportResources,
  loadCachedTransports,
  loadCachedTransportResources,
  queueTransport,
} from '../../offline';
import { TransportFormScreen } from '../../screens/TransportFormScreen';
import { colors } from '../../theme';
import { Transport, TransportInput, TransportResources, User } from '../../types';

export default function TransportFormRoute() {
  const { id } = useLocalSearchParams<{ id?: string }>();
  const [token, setToken] = useState<string | null>(null);
  const [user, setUser] = useState<User | null>(null);
  const [transport, setTransport] = useState<Transport | null>(null);
  const [resources, setResources] = useState<TransportResources | null>(null);
  const [resourceError, setResourceError] = useState('');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    let active = true;
    const initialize = async () => {
      try {
        const savedToken = await storedToken();
        const savedUser = await storedUser();
        if (!savedToken || !savedUser) {
          router.replace('/');
          return;
        }
        const [loadedResources, existing] = await Promise.all([
          loadResourcesForUser(savedToken, savedUser.id),
          loadTransportForUser(savedToken, savedUser.id, id),
        ]);
        if (active) {
          setToken(savedToken);
          setUser(savedUser);
          setTransport(existing);
          setResources(loadedResources.resources);
          setResourceError(loadedResources.error);
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
    if (!token || !user) throw new Error('Sesioni përfundoi. Hyr përsëri.');
    try {
      const result = await saveTransport(token, transport?.id ?? null, values);
      if (result.warnings.length > 0) {
        Alert.alert('Transporti u ruajt', result.warnings.join('\n'));
      }
      router.replace({ pathname: '/transport/[id]', params: { id: String(result.transport.id) } });
    } catch (requestError) {
      if (requestError instanceof ApiError && requestError.status === 0 && !transport) {
        await queueTransport(user.id, values);
        Alert.alert(
          'Ruajtur për sinkronizim',
          'Transporti do të dërgohet automatikisht kur pajisja të lidhet me serverin.',
        );
        router.back();
        return;
      }
      throw requestError;
    }
  };

  if (loading) {
    return (
      <SafeAreaView style={styles.center}>
        <ActivityIndicator color={colors.amber} size="large" />
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
      transport={transport}
      resources={resources}
      loadingResources={loading}
      resourceError={resourceError}
      onBack={() => router.back()}
      onSave={submit}
    />
  );
}

async function loadResourcesForUser(
  token: string,
  userId: number,
): Promise<{ resources: TransportResources | null; error: string }> {
  try {
    const resources = await loadResources(token);
    await cacheTransportResources(userId, resources);
    return { resources, error: '' };
  } catch (error) {
    if (error instanceof ApiError && error.status === 0) {
      const cached = await loadCachedTransportResources(userId);
      if (cached) return { resources: cached, error: '' };
    }
    throw error;
  }
}

async function loadTransportForUser(
  token: string,
  userId: number,
  id: string | undefined,
): Promise<Transport | null> {
  if (!id) return null;
  const transportId = Number(id);
  if (!Number.isSafeInteger(transportId) || transportId < 1) {
    throw new Error('ID e transportit nuk është e vlefshme.');
  }
  try {
    return await loadTransport(token, transportId);
  } catch (error) {
    if (!(error instanceof ApiError && error.status === 0)) throw error;
    const cached = await loadCachedTransports(userId);
    const transport = cached.find((item) => item.id === transportId);
    if (!transport) throw new Error('Transporti nuk u gjet në të dhënat e ruajtura.');
    return transport;
  }
}

const styles = StyleSheet.create({
  center: { alignItems: 'center', backgroundColor: colors.background, flex: 1, justifyContent: 'center', padding: 24 },
  error: { color: colors.danger, fontSize: 14, textAlign: 'center' },
  back: { color: colors.amberLight, fontSize: 15, fontWeight: '800', marginTop: 16, padding: 12 },
});
