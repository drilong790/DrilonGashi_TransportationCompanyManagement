import { StatusBar } from 'expo-status-bar';
import { useCallback, useEffect, useRef, useState } from 'react';
import {
  ActivityIndicator,
  SafeAreaView,
  StyleSheet,
  Text,
} from 'react-native';
import { router, useFocusEffect } from 'expo-router';
import {
  ApiError,
  apiBaseUrl,
  clearToken,
  currentUser,
  loadTransports,
  signIn,
  signOut,
  storedToken,
} from '../api';
import { HomeScreen } from '../screens/HomeScreen';
import { LoginScreen } from '../screens/LoginScreen';
import { Transport, User } from '../types';

export default function App() {
  const [token, setToken] = useState<string | null>(null);
  const [user, setUser] = useState<User | null>(null);
  const [transports, setTransports] = useState<Transport[]>([]);
  const [filters, setFilters] = useState<Record<string, string>>({});
  const filtersRef = useRef<Record<string, string>>({});
  const [loading, setLoading] = useState(true);
  const [initializing, setInitializing] = useState(true);
  const [error, setError] = useState('');
  const [startupError, setStartupError] = useState('');

  const loadList = useCallback(async (activeToken: string, nextFilters: Record<string, string> = {}) => {
    setLoading(true);
    setError('');
    filtersRef.current = nextFilters;
    setFilters(nextFilters);
    try {
      setTransports(await loadTransports(activeToken, nextFilters));
    } catch (requestError) {
      setError(messageFor(requestError));
      if (requestError instanceof ApiError && requestError.status === 401) {
        await clearToken();
        setToken(null);
        setUser(null);
      }
    } finally {
      setLoading(false);
    }
  }, []);

  const restoreSession = useCallback(async () => {
    try {
      const savedToken = await storedToken();
      if (!savedToken) {
        setToken(null);
        setUser(null);
        setStartupError('');
        return;
      }
      const current = await currentUser(savedToken);
      setToken(savedToken);
      setUser(current);
    } catch (requestError) {
      if (requestError instanceof ApiError && requestError.status === 401) {
        await clearToken();
        setToken(null);
        setUser(null);
      } else {
        setStartupError(messageFor(requestError));
      }
    } finally {
      setInitializing(false);
    }
  }, []);

  useEffect(() => {
    void Promise.resolve().then(restoreSession);
  }, [restoreSession]);

  useFocusEffect(
    useCallback(() => {
      if (token) {
        void Promise.resolve().then(() => loadList(token, filtersRef.current));
      }
    }, [token, loadList]),
  );

  const handleLogin = async (email: string, password: string) => {
    const result = await signIn(email, password);
    setToken(result.token);
    setUser(result.user);
    filtersRef.current = {};
    setFilters({});
  };

  const handleLogout = async () => {
    if (token) {
      try {
        await signOut(token);
      } catch {
        await clearToken();
      }
    }
    setToken(null);
    setUser(null);
    setTransports([]);
    filtersRef.current = {};
    setFilters({});
  };

  if (initializing) {
    return <LoadingScreen message="Po lidhemi me sistemin..." />;
  }

  if (!token || !user) {
    if (startupError) {
      return (
        <LoadingScreen
          message={startupError}
          action="Provo përsëri"
          onAction={() => {
            setInitializing(true);
            setStartupError('');
            void restoreSession();
          }}
        />
      );
    }
    return (
      <>
        <StatusBar style="dark" />
        <LoginScreen onLogin={handleLogin} />
      </>
    );
  }

  return (
    <SafeAreaView style={styles.safeArea}>
      <StatusBar style="dark" />
      <HomeScreen
        user={user}
        transports={transports}
        loading={loading}
        error={error}
        onRefresh={() => loadList(token, filters)}
        onOpen={(id) => router.push(`/transport/${id}`)}
        onCreate={() => router.push('/transport/form')}
        onLogout={handleLogout}
        onFilter={(search, status) => {
          const next = {
            ...(search ? { search } : {}),
            ...(status ? { status } : {}),
          };
          void loadList(token, next);
        }}
      />
    </SafeAreaView>
  );
}

function LoadingScreen({
  message,
  action,
  onAction,
}: {
  message: string;
  action?: string;
  onAction?: () => void;
}) {
  return (
    <SafeAreaView style={[styles.safeArea, styles.center]}>
      <StatusBar style="dark" />
      <ActivityIndicator color="#078b81" size="large" />
      <Text style={styles.loadingText}>{message}</Text>
      {action && (
        <Text onPress={onAction} style={styles.retry}>
          {action}
        </Text>
      )}
      {!apiBaseUrl && <Text style={styles.configHint}>API: mobile/.env</Text>}
    </SafeAreaView>
  );
}

function messageFor(error: unknown): string {
  return error instanceof Error ? error.message : 'Ndodhi një gabim i papritur.';
}

const styles = StyleSheet.create({
  safeArea: { backgroundColor: '#f3f6f9', flex: 1 },
  center: { alignItems: 'center', justifyContent: 'center', padding: 28 },
  loadingText: { color: '#304258', fontSize: 15, marginTop: 16, textAlign: 'center' },
  retry: { color: '#078b81', fontSize: 15, fontWeight: '800', marginTop: 18, padding: 10 },
  configHint: { color: '#8492a2', fontSize: 12, marginTop: 18 },
});
