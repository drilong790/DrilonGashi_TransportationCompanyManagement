import {
  Alert,
  ActivityIndicator,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import * as Linking from 'expo-linking';
import { Transport } from '../types';
import { colors } from '../theme';
import { formatDate, StatusBadge } from './HomeScreen';

export function DetailScreen({
  transport,
  loading,
  error,
  onBack,
  onEdit,
  onRefresh,
  isDriver,
  onDriverStatusChange,
}: {
  transport: Transport;
  loading: boolean;
  error: string;
  onBack: () => void;
  onEdit: () => void;
  onRefresh: () => void;
  isDriver: boolean;
  onDriverStatusChange: (id: number, status: 'IN_PROGRESS' | 'COMPLETED') => Promise<boolean | void>;
}) {
  const openNavigation = async () => {
    const origin = encodeURIComponent(transport.pickup_address);
    const destination = encodeURIComponent(transport.destination_address);
    const url = Platform.OS === 'ios'
      ? `https://maps.apple.com/?saddr=${origin}&daddr=${destination}`
      : `https://www.google.com/maps/dir/?api=1&origin=${origin}&destination=${destination}`;
    try {
      await Linking.openURL(url);
    } catch {
      Alert.alert('Harta nuk u hap', 'Kontrollo që pajisja të ketë një aplikacion harte të disponueshëm.');
    }
  };

  const updateDriverStatus = async (status: 'IN_PROGRESS' | 'COMPLETED') => {
    try {
      const queued = await onDriverStatusChange(transport.id, status);
      if (queued) {
        Alert.alert(
          'Statusi u radhit',
          'Ndryshimi do të dërgohet kur pajisja të lidhet me serverin.',
        );
      }
    } catch (statusError) {
      Alert.alert('Statusi nuk u ndryshua', statusError instanceof Error ? statusError.message : 'Provo përsëri.');
    }
  };

  return (
    <View style={styles.screen}>
      <View style={styles.header}>
        <Pressable onPress={onBack} style={styles.back}><Text style={styles.backText}>‹</Text></Pressable>
        <Text style={styles.headerTitle}>Detajet e transportit</Text>
        <Pressable onPress={onRefresh} style={styles.refresh}><Text style={styles.refreshText}>Rifresko</Text></Pressable>
      </View>
      <ScrollView contentContainerStyle={styles.content}>
        <View style={styles.titleRow}>
          <View>
            <Text style={styles.id}>TRANSPORTI #{transport.id}</Text>
            <Text style={styles.title}>{transport.customer_name}</Text>
          </View>
          <StatusBadge status={transport.status} />
        </View>
        {!!error && <Text style={styles.error}>{error}</Text>}
        {loading && <ActivityIndicator color={colors.amber} style={styles.loader} />}
        <View style={styles.card}>
          <Text style={styles.sectionTitle}>Itinerari</Text>
          <InfoRow label="Nisja" value={transport.pickup_address} />
          <InfoRow label="Destinacioni" value={transport.destination_address} />
          <InfoRow label="Ngarkesa" value={transport.cargo_description} />
          <Pressable onPress={() => void openNavigation()} style={styles.mapButton}>
            <Text style={styles.mapButtonText}>Hap navigimin në hartë</Text>
            <Text style={styles.mapArrow}>↗</Text>
          </Pressable>
        </View>
        <View style={styles.card}>
          <Text style={styles.sectionTitle}>Orari</Text>
          <InfoRow label="Fillimi" value={formatDate(transport.scheduled_date)} />
          <InfoRow label="Përfundimi" value={formatDate(transport.scheduled_end)} />
        </View>
        <View style={styles.card}>
          <Text style={styles.sectionTitle}>Caktimet</Text>
          <InfoRow label="Shoferi" value={transport.driver_name ?? 'Pa caktuar'} />
          <InfoRow label="Automjeti" value={transport.vehicle_plate ?? 'Pa caktuar'} />
          <InfoRow label="Regjistruar nga" value={transport.created_by_name} />
        </View>
      </ScrollView>
      {isDriver ? (
        transport.status === 'PLANNED' || transport.status === 'IN_PROGRESS' ? (
          <Pressable
            onPress={() => void updateDriverStatus(transport.status === 'PLANNED' ? 'IN_PROGRESS' : 'COMPLETED')}
            style={styles.editButton}
          >
            <Text style={styles.editText}>
              {transport.status === 'PLANNED' ? 'Nis transportin' : 'Përfundo transportin'}
            </Text>
          </Pressable>
        ) : null
      ) : (
        <Pressable onPress={onEdit} style={styles.editButton}>
          <Text style={styles.editText}>Ndrysho transportin</Text>
        </Pressable>
      )}
    </View>
  );
}

function InfoRow({ label, value }: { label: string; value: string }) {
  return (
    <View style={styles.row}>
      <Text style={styles.label}>{label}</Text>
      <Text style={styles.value}>{value}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1 },
  header: {
    alignItems: 'center',
    backgroundColor: colors.background,
    borderBottomColor: colors.border,
    borderBottomWidth: 1,
    flexDirection: 'row',
    justifyContent: 'space-between',
    minHeight: 58,
    paddingHorizontal: 14,
  },
  back: { alignItems: 'center', height: 40, justifyContent: 'center', width: 36 },
  backText: { color: colors.amberLight, fontSize: 34, lineHeight: 38 },
  headerTitle: { color: colors.text, fontSize: 15, fontWeight: '800' },
  refresh: { padding: 7 },
  refreshText: { color: colors.amberLight, fontSize: 12, fontWeight: '700' },
  content: { padding: 18, paddingBottom: 100 },
  titleRow: { alignItems: 'flex-start', flexDirection: 'row', justifyContent: 'space-between', marginBottom: 18 },
  id: { color: '#d3a25c', fontSize: 10, fontWeight: '900', letterSpacing: 1.2 },
  title: { color: colors.text, fontSize: 21, fontWeight: '800', marginTop: 5 },
  card: { backgroundColor: colors.surface, borderColor: colors.border, borderRadius: 14, borderWidth: 1, marginTop: 12, padding: 16 },
  sectionTitle: { color: colors.amberLight, fontSize: 14, fontWeight: '800', marginBottom: 5 },
  mapButton: { alignItems: 'center', backgroundColor: `${colors.amber}12`, borderColor: `${colors.amber}35`, borderRadius: 10, borderWidth: 1, flexDirection: 'row', justifyContent: 'space-between', marginTop: 13, paddingHorizontal: 13, paddingVertical: 11 },
  mapButtonText: { color: colors.amberLight, fontSize: 12, fontWeight: '700' },
  mapArrow: { color: colors.amberLight, fontSize: 18 },
  row: { borderBottomColor: colors.border, borderBottomWidth: 1, paddingVertical: 11 },
  label: { color: colors.textMuted, fontSize: 11, marginBottom: 4 },
  value: { color: '#d9dfdc', fontSize: 14, lineHeight: 20 },
  error: { backgroundColor: '#652e2a36', borderColor: '#9c54504f', borderRadius: 10, borderWidth: 1, color: colors.danger, fontSize: 12, padding: 10 },
  loader: { marginVertical: 8 },
  editButton: { alignItems: 'center', backgroundColor: colors.amber, borderRadius: 12, bottom: 16, left: 18, padding: 16, position: 'absolute', right: 18 },
  editText: { color: '#17212a', fontSize: 14, fontWeight: '800' },
});
