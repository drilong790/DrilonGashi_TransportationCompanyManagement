import { useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import * as LocalAuthentication from 'expo-local-authentication';
import {
  clearTransportReminders,
  requestReminderPermission,
  scheduleTransportReminders,
} from '../notifications';
import { preferenceEnabled, setPreference } from '../preferences';
import { loadCachedTransports, QueuedAction } from '../offline';
import { colors } from '../theme';
import { Transport } from '../types';

export function SettingsScreen({
  userId,
  transports,
  queuedActions,
  onBack,
  onBiometricChanged,
  onRetryQueuedAction,
  onRemoveQueuedAction,
}: {
  userId: number;
  transports: Transport[];
  queuedActions: QueuedAction[];
  onBack: () => void;
  onBiometricChanged: (enabled: boolean) => void;
  onRetryQueuedAction: (id: number) => void;
  onRemoveQueuedAction: (id: number) => void;
}) {
  const [remindersEnabled, setRemindersEnabled] = useState(false);
  const [biometricEnabled, setBiometricEnabled] = useState(false);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    let active = true;
    Promise.all([
      preferenceEnabled('reminders', userId),
      preferenceEnabled('biometric', userId),
    ])
      .then(([reminders, biometric]) => {
        if (!active) return;
        setRemindersEnabled(reminders);
        setBiometricEnabled(biometric);
      })
      .catch((loadError: unknown) => {
        if (active) setError(loadError instanceof Error ? loadError.message : 'Cilësimet nuk u ngarkuan.');
      })
      .finally(() => {
        if (active) setLoading(false);
      });
    return () => {
      active = false;
    };
  }, [userId]);

  const toggleReminders = async () => {
    setError('');
    setSaving(true);
    try {
      if (remindersEnabled) {
        await clearTransportReminders();
        await setPreference('reminders', userId, false);
        setRemindersEnabled(false);
        return;
      }
      if (!(await requestReminderPermission())) {
        setError('Lejo njoftimet në cilësimet e pajisjes për të marrë kujtesa.');
        return;
      }
      const cachedTransports = await loadCachedTransports(userId);
      await scheduleTransportReminders(cachedTransports.length > 0 ? cachedTransports : transports);
      await setPreference('reminders', userId, true);
      setRemindersEnabled(true);
    } catch (toggleError) {
      setError(toggleError instanceof Error ? toggleError.message : 'Njoftimet nuk u përditësuan.');
    } finally {
      setSaving(false);
    }
  };

  const toggleBiometrics = async () => {
    setError('');
    setSaving(true);
    try {
      if (biometricEnabled) {
        await setPreference('biometric', userId, false);
        setBiometricEnabled(false);
        onBiometricChanged(false);
        return;
      }
      if (!(await LocalAuthentication.hasHardwareAsync()) || !(await LocalAuthentication.isEnrolledAsync())) {
        setError('Pajisja nuk ka biometrikë të konfiguruar. Aktivizo Face ID ose gjurmën e gishtit te cilësimet e pajisjes.');
        return;
      }
      const result = await LocalAuthentication.authenticateAsync({
        promptMessage: 'Aktivizo kyçjen biometrike',
        cancelLabel: 'Anulo',
      });
      if (!result.success) {
        setError('Verifikimi biometrik u anulua ose nuk u krye.');
        return;
      }
      await setPreference('biometric', userId, true);
      setBiometricEnabled(true);
      onBiometricChanged(true);
    } catch (toggleError) {
      setError(toggleError instanceof Error ? toggleError.message : 'Kyçja biometrike nuk u përditësua.');
    } finally {
      setSaving(false);
    }
  };

  const confirmRemoveQueuedAction = (id: number) => {
    Alert.alert(
      'Fshi ndryshimin?',
      'Ky veprim nuk mund të zhbëhet dhe ndryshimi nuk do të dërgohet në server.',
      [
        { text: 'Anulo', style: 'cancel' },
        { text: 'Fshi', style: 'destructive', onPress: () => onRemoveQueuedAction(id) },
      ],
    );
  };

  return (
    <View style={styles.screen}>
      <View style={styles.header}>
        <Pressable accessibilityRole="button" onPress={onBack} style={styles.back}>
          <Text style={styles.backText}>‹</Text>
        </Pressable>
        <Text style={styles.title}>Cilësimet</Text>
        <View style={styles.headerSpace} />
      </View>
      {loading ? (
        <View style={styles.loading}>
          <ActivityIndicator color={colors.amber} />
        </View>
      ) : (
        <ScrollView contentContainerStyle={styles.content}>
          <Text style={styles.eyebrow}>PERSONALIZIMI</Text>
          <Text style={styles.heading}>Siguria dhe kujtesat</Text>
          <Text style={styles.intro}>Zgjidh si të të lajmërojë aplikacioni dhe si ta mbrosh kur nuk e përdor.</Text>
          {!!error && <Text style={styles.error}>{error}</Text>}
          <SettingRow
            title="Kujtesa për transportet"
            description="Njoftim lokal një orë para nisjes së transportit."
            enabled={remindersEnabled}
            disabled={saving}
            onPress={() => void toggleReminders()}
          />
          <SettingRow
            title="Kyçje me Face ID ose biometrikë"
            description="Kërko verifikim kur kthehesh në aplikacion pas daljes në sfond."
            enabled={biometricEnabled}
            disabled={saving}
            onPress={() => void toggleBiometrics()}
          />
          {queuedActions.length > 0 && (
            <View style={styles.outbox}>
              <Text style={styles.outboxTitle}>Ndryshime për sinkronizim</Text>
              {queuedActions.map((action) => (
                <View key={action.id} style={styles.outboxRow}>
                  <View style={styles.outboxCopy}>
                    <Text style={styles.rowTitle}>
                      {action.type === 'create_transport' ? 'Transport i ri' : 'Ndryshim statusi'}
                    </Text>
                    <Text style={styles.rowDescription}>
                      {action.state === 'failed'
                        ? action.error ?? 'Sinkronizimi dështoi.'
                        : 'Në pritje të lidhjes me serverin.'}
                    </Text>
                  </View>
                  {action.state === 'failed' && (
                    <Pressable onPress={() => onRetryQueuedAction(action.id)} style={styles.outboxAction}>
                      <Text style={styles.outboxActionText}>Provo</Text>
                    </Pressable>
                  )}
                  <Pressable onPress={() => confirmRemoveQueuedAction(action.id)} style={styles.outboxAction}>
                    <Text style={styles.outboxRemoveText}>Fshi</Text>
                  </Pressable>
                </View>
              ))}
            </View>
          )}
          {saving && <ActivityIndicator color={colors.amber} style={styles.saving} />}
          <Text style={styles.note}>
            Kujtesat krijohen në këtë pajisje dhe përditësohen kur rifreskohen transportet.
          </Text>
        </ScrollView>
      )}
    </View>
  );
}

function SettingRow({
  title,
  description,
  enabled,
  disabled,
  onPress,
}: {
  title: string;
  description: string;
  enabled: boolean;
  disabled: boolean;
  onPress: () => void;
}) {
  return (
    <Pressable
      accessibilityRole="switch"
      accessibilityState={{ checked: enabled, disabled }}
      disabled={disabled}
      onPress={onPress}
      style={({ pressed }) => [styles.row, pressed && styles.rowPressed]}
    >
      <View style={styles.rowCopy}>
        <Text style={styles.rowTitle}>{title}</Text>
        <Text style={styles.rowDescription}>{description}</Text>
      </View>
      <View style={[styles.switch, enabled && styles.switchEnabled]}>
        <View style={[styles.switchThumb, enabled && styles.switchThumbEnabled]} />
      </View>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  screen: { backgroundColor: colors.background, flex: 1 },
  header: {
    alignItems: 'center',
    borderBottomColor: colors.border,
    borderBottomWidth: 1,
    flexDirection: 'row',
    minHeight: 58,
    paddingHorizontal: 14,
  },
  back: { alignItems: 'center', height: 40, justifyContent: 'center', width: 36 },
  backText: { color: colors.amberLight, fontSize: 34, lineHeight: 38 },
  title: { color: colors.text, flex: 1, fontSize: 15, fontWeight: '800', textAlign: 'center' },
  headerSpace: { width: 36 },
  content: { padding: 18, paddingBottom: 36 },
  eyebrow: { color: '#d3a25c', fontSize: 10, fontWeight: '800', letterSpacing: 1.3, marginTop: 8 },
  heading: { color: colors.text, fontSize: 23, fontWeight: '800', marginTop: 7 },
  intro: { color: colors.textMuted, fontSize: 13, lineHeight: 20, marginBottom: 22, marginTop: 8 },
  row: {
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: 14,
    borderWidth: 1,
    flexDirection: 'row',
    gap: 14,
    marginBottom: 12,
    padding: 16,
  },
  rowPressed: { opacity: 0.8 },
  rowCopy: { flex: 1 },
  rowTitle: { color: colors.text, fontSize: 14, fontWeight: '700' },
  rowDescription: { color: colors.textMuted, fontSize: 12, lineHeight: 18, marginTop: 5 },
  switch: { backgroundColor: '#ffffff1a', borderRadius: 12, height: 24, justifyContent: 'center', padding: 2, width: 42 },
  switchEnabled: { backgroundColor: colors.amber },
  switchThumb: { backgroundColor: '#d6d8d4', borderRadius: 9, height: 18, width: 18 },
  switchThumbEnabled: { alignSelf: 'flex-end', backgroundColor: '#17212a' },
  error: { backgroundColor: '#652e2a36', borderColor: '#9c54504f', borderRadius: 10, borderWidth: 1, color: colors.danger, fontSize: 12, lineHeight: 18, marginBottom: 14, padding: 10 },
  loading: { alignItems: 'center', flex: 1, justifyContent: 'center' },
  saving: { marginTop: 10 },
  outbox: { backgroundColor: colors.surface, borderColor: colors.border, borderRadius: 14, borderWidth: 1, marginTop: 8, padding: 14 },
  outboxTitle: { color: colors.amberLight, fontSize: 14, fontWeight: '800', marginBottom: 8 },
  outboxRow: { alignItems: 'center', borderTopColor: colors.border, borderTopWidth: 1, flexDirection: 'row', gap: 8, paddingVertical: 10 },
  outboxCopy: { flex: 1 },
  outboxAction: { paddingHorizontal: 5, paddingVertical: 8 },
  outboxActionText: { color: colors.amberLight, fontSize: 11, fontWeight: '800' },
  outboxRemoveText: { color: colors.danger, fontSize: 11, fontWeight: '700' },
  note: { color: '#74838a', fontSize: 12, lineHeight: 18, marginTop: 14 },
});
