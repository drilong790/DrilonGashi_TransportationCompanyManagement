import { StatusBar } from 'expo-status-bar';
import { useCallback, useEffect, useRef, useState } from 'react';
import * as LocalAuthentication from 'expo-local-authentication';
import {
  AppState,
  ActivityIndicator,
  BackHandler,
  Pressable,
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
  loadDashboard,
  loadTransports,
  signIn,
  signOut,
  storedToken,
  storedUser,
} from '../api';
import {
  cacheTransports,
  clearCachedTransports,
  loadCachedTransports,
  loadQueuedActions,
  removeQueuedAction,
  retryQueuedAction,
  QueuedAction,
} from '../offline';
import { clearTransportReminders, scheduleTransportReminders } from '../notifications';
import { preferenceEnabled } from '../preferences';
import { SettingsScreen } from '../screens/SettingsScreen';
import { HomeScreen } from '../screens/HomeScreen';
import { LoginScreen } from '../screens/LoginScreen';
import { syncQueuedActions } from '../sync';
import { colors } from '../theme';
import { DashboardSummary, Transport, User } from '../types';

export default function App() {
  const [token, setToken] = useState<string | null>(null);
  const [user, setUser] = useState<User | null>(null);
  const [transports, setTransports] = useState<Transport[]>([]);
  const [summary, setSummary] = useState<DashboardSummary | null>(null);
  const [queuedActions, setQueuedActions] = useState<QueuedAction[]>([]);
  const [offline, setOffline] = useState(false);
  const [filters, setFilters] = useState<Record<string, string>>({});
  const filtersRef = useRef<Record<string, string>>({});
  const [loading, setLoading] = useState(true);
  const [initializing, setInitializing] = useState(true);
  const [error, setError] = useState('');
  const [startupError, setStartupError] = useState('');
  const [showSettings, setShowSettings] = useState(false);
  const [biometricEnabled, setBiometricEnabled] = useState(false);
  const [locked, setLocked] = useState(false);
  const [lockError, setLockError] = useState('');
  const tokenRef = useRef<string | null>(null);
  const lockedRef = useRef(false);
  const biometricRef = useRef(false);

  useEffect(() => {
    tokenRef.current = token;
  }, [token]);

  useEffect(() => {
    lockedRef.current = locked;
  }, [locked]);

  useEffect(() => {
    biometricRef.current = biometricEnabled;
  }, [biometricEnabled]);

  const loadList = useCallback(async (
    activeToken: string,
    nextFilters: Record<string, string> = {},
    activeUser: User | null = user,
  ) => {
    setLoading(true);
    setError('');
    filtersRef.current = nextFilters;
    setFilters(nextFilters);
    if (!activeUser) {
      setLoading(false);
      return;
    }
    let freshTransports: Transport[] | null = null;
    try {
      await syncQueuedActions(activeUser.id, activeToken);
      const loaded = await loadTransports(activeToken, nextFilters);
      freshTransports = loaded;
      setTransports(loaded);
      if (Object.keys(nextFilters).length === 0) {
        await cacheTransports(activeUser.id, loaded);
      }
      const [dashboard, queue] = await Promise.all([
        loadDashboard(activeToken),
        loadQueuedActions(activeUser.id),
      ]);
      setSummary(dashboard);
      setQueuedActions(queue);
      setOffline(false);
      if (await preferenceEnabled('reminders', activeUser.id)) {
        const reminderTransports = Object.keys(nextFilters).length === 0
          ? loaded
          : await loadCachedTransports(activeUser.id);
        await scheduleTransportReminders(reminderTransports);
      }
    } catch (requestError) {
      if (requestError instanceof ApiError && requestError.status === 401) {
        await clearToken();
        setToken(null);
        setUser(null);
        setError(messageFor(requestError));
      } else if (requestError instanceof ApiError && requestError.status === 0) {
        try {
          const cached = await loadCachedTransports(activeUser.id);
          const available = freshTransports ?? cached;
          const queue = await loadQueuedActions(activeUser.id);
          setTransports(filterTransports(available, nextFilters));
          setSummary(summarize(cached.length > 0 || freshTransports === null ? cached : freshTransports));
          setQueuedActions(queue);
          setOffline(true);
          setError('');
        } catch (cacheError) {
          setError(messageFor(cacheError));
        }
      } else {
        setError(messageFor(requestError));
      }
    } finally {
      setLoading(false);
    }
  }, [user]);

  const restoreSession = useCallback(async () => {
    try {
      const savedToken = await storedToken();
      if (!savedToken) {
        setToken(null);
        setUser(null);
        setStartupError('');
        return;
      }
      const savedUser = await storedUser();
      let current: User;
      try {
        current = await currentUser(savedToken);
      } catch (requestError) {
        if (!(requestError instanceof ApiError && requestError.status === 0) || !savedUser) {
          throw requestError;
        }
        current = savedUser;
        setOffline(true);
      }
      setToken(savedToken);
      setUser(current);
      setQueuedActions(await loadQueuedActions(current.id));
      const enabled = await preferenceEnabled('biometric', current.id);
      setBiometricEnabled(enabled);
      if (enabled) {
        setLocked(true);
        const result = await LocalAuthentication.authenticateAsync({
          promptMessage: 'Verifiko identitetin për të vazhduar',
          cancelLabel: 'Anulo',
        });
        if (result.success) {
          setLocked(false);
        } else {
          setLockError('Verifikimi biometrik nuk u krye.');
        }
      }
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
    setStartupError('');
    setOffline(false);
    filtersRef.current = {};
    setFilters({});
    setQueuedActions(await loadQueuedActions(result.user.id));
  };

  const handleLogout = async () => {
    const activeUser = user;
    if (token) {
      try {
        await signOut(token);
      } catch (logoutError) {
        await clearToken();
        setError(messageFor(logoutError));
      }
    }
    if (activeUser) {
      await clearCachedTransports(activeUser.id);
    }
    await clearTransportReminders();
    setToken(null);
    setUser(null);
    setTransports([]);
    setSummary(null);
    setQueuedActions([]);
    setOffline(false);
    setShowSettings(false);
    setLocked(false);
    filtersRef.current = {};
    setFilters({});
  };

  const verifyBiometric = useCallback(async () => {
    if (!biometricRef.current) {
      setLocked(false);
      setLockError('');
      return;
    }
    setLockError('');
    try {
      const result = await LocalAuthentication.authenticateAsync({
        promptMessage: 'Verifiko identitetin për të vazhduar',
        cancelLabel: 'Anulo',
      });
      if (result.success) {
        setLocked(false);
      } else {
        setLockError('Verifikimi biometrik nuk u krye. Provo përsëri.');
      }
    } catch (authenticationError) {
      setLockError(messageFor(authenticationError));
    }
  }, []);

  useEffect(() => {
    const subscription = AppState.addEventListener('change', (nextState) => {
      if (nextState === 'background' && tokenRef.current && biometricRef.current) {
        lockedRef.current = true;
        setLocked(true);
      }
      if (nextState === 'active' && lockedRef.current && tokenRef.current) {
        void verifyBiometric();
      }
    });
    return () => subscription.remove();
  }, [verifyBiometric]);

  useEffect(() => {
    if (!showSettings) return undefined;
    const subscription = BackHandler.addEventListener('hardwareBackPress', () => {
      setShowSettings(false);
      return true;
    });
    return () => subscription.remove();
  }, [showSettings]);

  const handleRetryQueuedAction = async (id: number) => {
    if (!user || !token) return;
    try {
      await retryQueuedAction(id);
      await loadList(token, filtersRef.current, user);
    } catch (retryError) {
      setError(messageFor(retryError));
    }
  };

  const handleRemoveQueuedAction = async (id: number) => {
    if (!user) return;
    try {
      await removeQueuedAction(id);
      setQueuedActions(await loadQueuedActions(user.id));
    } catch (removeError) {
      setError(messageFor(removeError));
    }
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
        <StatusBar style="light" />
        <LoginScreen onLogin={handleLogin} />
      </>
    );
  }

  if (locked) {
    return (
      <SafeAreaView style={[styles.safeArea, styles.center]}>
        <StatusBar style="light" />
        <Text style={styles.lockTitle}>Llogaria është kyçur</Text>
        <Text style={styles.lockText}>Verifiko identitetin për të vazhduar.</Text>
        {!!lockError && <Text style={styles.lockError}>{lockError}</Text>}
        <Pressable onPress={() => void verifyBiometric()} style={styles.unlockButton}>
          <Text style={styles.unlockText}>Hap me biometrikë</Text>
        </Pressable>
        <Pressable onPress={() => void handleLogout()} style={styles.lockLogout}>
          <Text style={styles.lockLogoutText}>Dil nga llogaria</Text>
        </Pressable>
      </SafeAreaView>
    );
  }

  if (showSettings) {
    return (
      <SafeAreaView style={styles.safeArea}>
        <StatusBar style="light" />
        <SettingsScreen
          userId={user.id}
          transports={transports}
          queuedActions={queuedActions}
          onBack={() => setShowSettings(false)}
          onBiometricChanged={setBiometricEnabled}
          onRetryQueuedAction={(id) => void handleRetryQueuedAction(id)}
          onRemoveQueuedAction={(id) => void handleRemoveQueuedAction(id)}
        />
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.safeArea}>
      <StatusBar style="light" />
      <HomeScreen
        user={user}
        transports={transports}
        summary={summary}
        loading={loading}
        error={error}
        offline={offline}
        queuedCount={queuedActions.length}
        onRefresh={() => loadList(token, filters)}
        onOpen={(id) => router.push(`/transport/${id}`)}
        onCreate={() => router.push('/transport/form')}
        onLogout={() => void handleLogout()}
        onCalendar={() => router.push('/calendar')}
        onSettings={() => setShowSettings(true)}
        onSync={() => void loadList(token, filters)}
        onFilter={(search, status) => {
          const next = {
            ...(search ? { search } : {}),
            ...(status ? { status } : {}),
          };
          void loadList(token, next, user);
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
      <StatusBar style="light" />
      <ActivityIndicator color={colors.amber} size="large" />
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

function filterTransports(transports: Transport[], filters: Record<string, string>): Transport[] {
  const search = (filters.search ?? '').trim().toLocaleLowerCase();
  return transports.filter((transport) => {
    if (filters.status && transport.status !== filters.status) return false;
    if (!search) return true;
    return [
      transport.customer_name,
      transport.driver_name ?? '',
      transport.pickup_address,
      transport.destination_address,
      transport.cargo_description,
    ].some((value) => value.toLocaleLowerCase().includes(search));
  });
}

function summarize(transports: Transport[]): DashboardSummary {
  const today = new Date();
  const todayKey = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
  return {
    total: transports.length,
    planned: transports.filter((transport) => transport.status === 'PLANNED').length,
    in_progress: transports.filter((transport) => transport.status === 'IN_PROGRESS').length,
    completed: transports.filter((transport) => transport.status === 'COMPLETED').length,
    cancelled: transports.filter((transport) => transport.status === 'CANCELLED').length,
    today: transports.filter((transport) => (
      transport.status !== 'CANCELLED' && transport.scheduled_date.slice(0, 10) === todayKey
    )).length,
  };
}

const styles = StyleSheet.create({
  safeArea: { backgroundColor: colors.background, flex: 1 },
  center: { alignItems: 'center', justifyContent: 'center', padding: 28 },
  loadingText: { color: colors.text, fontSize: 15, marginTop: 16, textAlign: 'center' },
  retry: { color: colors.amberLight, fontSize: 15, fontWeight: '800', marginTop: 18, padding: 10 },
  configHint: { color: colors.textMuted, fontSize: 12, marginTop: 18 },
  lockTitle: { color: colors.text, fontSize: 23, fontWeight: '800' },
  lockText: { color: colors.textMuted, fontSize: 14, marginTop: 10, textAlign: 'center' },
  lockError: { color: colors.danger, fontSize: 13, marginTop: 18, textAlign: 'center' },
  unlockButton: { alignItems: 'center', backgroundColor: colors.amber, borderRadius: 12, marginTop: 24, minWidth: 210, paddingHorizontal: 20, paddingVertical: 14 },
  unlockText: { color: '#17212a', fontSize: 14, fontWeight: '800' },
  lockLogout: { marginTop: 18, padding: 10 },
  lockLogoutText: { color: colors.textMuted, fontSize: 13, fontWeight: '700' },
});
