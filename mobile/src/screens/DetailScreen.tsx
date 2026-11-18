import {
  ActivityIndicator,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { Transport } from '../types';
import { formatDate, StatusBadge } from './HomeScreen';

export function DetailScreen({
  transport,
  loading,
  error,
  onBack,
  onEdit,
  onRefresh,
}: {
  transport: Transport;
  loading: boolean;
  error: string;
  onBack: () => void;
  onEdit: () => void;
  onRefresh: () => void;
}) {
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
        {loading && <ActivityIndicator color="#078b81" style={styles.loader} />}
        <View style={styles.card}>
          <Text style={styles.sectionTitle}>Itinerari</Text>
          <InfoRow label="Nisja" value={transport.pickup_address} />
          <InfoRow label="Destinacioni" value={transport.destination_address} />
          <InfoRow label="Ngarkesa" value={transport.cargo_description} />
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
      <Pressable onPress={onEdit} style={styles.editButton}>
        <Text style={styles.editText}>Ndrysho transportin</Text>
      </Pressable>
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
    backgroundColor: '#fff',
    borderBottomColor: '#e8edf2',
    borderBottomWidth: 1,
    flexDirection: 'row',
    justifyContent: 'space-between',
    minHeight: 58,
    paddingHorizontal: 14,
  },
  back: { alignItems: 'center', height: 40, justifyContent: 'center', width: 36 },
  backText: { color: '#18334c', fontSize: 34, lineHeight: 38 },
  headerTitle: { color: '#23384e', fontSize: 15, fontWeight: '800' },
  refresh: { padding: 7 },
  refreshText: { color: '#078b81', fontSize: 12, fontWeight: '700' },
  content: { padding: 18, paddingBottom: 100 },
  titleRow: { alignItems: 'center', flexDirection: 'row', justifyContent: 'space-between', marginBottom: 18 },
  id: { color: '#078b81', fontSize: 10, fontWeight: '900', letterSpacing: 1 },
  title: { color: '#15263b', fontSize: 21, fontWeight: '800', marginTop: 5 },
  card: { backgroundColor: '#fff', borderColor: '#e7edf2', borderRadius: 16, borderWidth: 1, marginTop: 12, padding: 16 },
  sectionTitle: { color: '#20364d', fontSize: 14, fontWeight: '800', marginBottom: 5 },
  row: { borderBottomColor: '#f0f3f6', borderBottomWidth: 1, paddingVertical: 11 },
  label: { color: '#8794a2', fontSize: 11, marginBottom: 4 },
  value: { color: '#30445a', fontSize: 14, lineHeight: 20 },
  error: { backgroundColor: '#fceeee', borderRadius: 10, color: '#a73b3b', fontSize: 12, padding: 10 },
  loader: { marginVertical: 8 },
  editButton: { alignItems: 'center', backgroundColor: '#078b81', borderRadius: 14, bottom: 16, left: 18, padding: 16, position: 'absolute', right: 18 },
  editText: { color: '#fff', fontSize: 14, fontWeight: '800' },
});
