import { useState } from 'react';
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  RefreshControl,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { Transport, TransportStatus, User } from '../types';

const statuses: { value: TransportStatus | ''; label: string }[] = [
  { value: '', label: 'Të gjitha' },
  { value: 'PLANNED', label: 'Planifikuar' },
  { value: 'IN_PROGRESS', label: 'Në proces' },
  { value: 'COMPLETED', label: 'Përfunduar' },
  { value: 'CANCELLED', label: 'Anuluar' },
];

export function statusLabel(status: TransportStatus): string {
  return statuses.find((item) => item.value === status)?.label ?? status;
}

export function HomeScreen({
  user,
  transports,
  loading,
  error,
  onRefresh,
  onOpen,
  onCreate,
  onLogout,
  onFilter,
}: {
  user: User;
  transports: Transport[];
  loading: boolean;
  error: string;
  onRefresh: () => void;
  onOpen: (id: number) => void;
  onCreate: () => void;
  onLogout: () => void;
  onFilter: (search: string, status: string) => void;
}) {
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState<TransportStatus | ''>('');

  return (
    <View style={styles.screen}>
      <View style={styles.header}>
        <View>
          <Text style={styles.eyebrow}>PANELI I TRANSPORTEVE</Text>
          <Text style={styles.title}>Përshëndetje, {user.full_name.split(' ')[0]}</Text>
          <Text style={styles.subtitle}>{transports.length} transport(e) në listë</Text>
        </View>
        <Pressable accessibilityLabel="Dil nga llogaria" onPress={onLogout} style={styles.logout}>
          <Text style={styles.logoutText}>Dil</Text>
        </Pressable>
      </View>
      <View style={styles.searchRow}>
        <TextInput
          onChangeText={setSearch}
          onSubmitEditing={() => onFilter(search.trim(), status)}
          placeholder="Kërko klient, adresë, shofer..."
          placeholderTextColor="#929eac"
          returnKeyType="search"
          style={styles.search}
          value={search}
        />
        <Pressable onPress={() => onFilter(search.trim(), status)} style={styles.searchButton}>
          <Text style={styles.searchButtonText}>Kërko</Text>
        </Pressable>
      </View>
      <FlatList
        contentContainerStyle={styles.listContent}
        data={transports}
        keyExtractor={(item) => String(item.id)}
        ListHeaderComponent={
          <>
            <View style={styles.filterList}>
              {statuses.map((item) => (
                <Pressable
                  key={item.value || 'all'}
                  onPress={() => {
                    setStatus(item.value);
                    onFilter(search.trim(), item.value);
                  }}
                  style={[styles.filterChip, status === item.value && styles.filterChipActive]}
                >
                  <Text style={[styles.filterText, status === item.value && styles.filterTextActive]}>
                    {item.label}
                  </Text>
                </Pressable>
              ))}
            </View>
            {!!error && <Text style={styles.error}>{error}</Text>}
            {loading && transports.length === 0 && (
              <View style={styles.empty}>
                <ActivityIndicator color="#078b81" />
                <Text style={styles.emptyText}>Po ngarkohen transportet...</Text>
              </View>
            )}
          </>
        }
        ListEmptyComponent={
          !loading ? (
            <View style={styles.empty}>
              <Text style={styles.emptyIcon}>↗</Text>
              <Text style={styles.emptyTitle}>Nuk ka transporte</Text>
              <Text style={styles.emptyText}>Ndrysho filtrat ose shto transport të ri.</Text>
            </View>
          ) : undefined
        }
        refreshControl={<RefreshControl refreshing={loading && transports.length > 0} onRefresh={onRefresh} tintColor="#078b81" />}
        renderItem={({ item }) => <TransportCard item={item} onPress={() => onOpen(item.id)} />}
      />
      <Pressable accessibilityRole="button" onPress={onCreate} style={styles.addButton}>
        <Text style={styles.addIcon}>＋</Text>
        <Text style={styles.addText}>Transport i ri</Text>
      </Pressable>
    </View>
  );
}

function TransportCard({ item, onPress }: { item: Transport; onPress: () => void }) {
  return (
    <Pressable onPress={onPress} style={({ pressed }) => [styles.card, pressed && styles.cardPressed]}>
      <View style={styles.cardTop}>
        <Text style={styles.customer} numberOfLines={1}>{item.customer_name}</Text>
        <StatusBadge status={item.status} />
      </View>
      <View style={styles.route}>
        <View style={styles.routeMarks}>
          <View style={styles.dot} />
          <View style={styles.routeLine} />
          <View style={[styles.dot, styles.destinationDot]} />
        </View>
        <View style={styles.routeText}>
          <Text numberOfLines={1} style={styles.address}>{item.pickup_address}</Text>
          <Text numberOfLines={1} style={styles.address}>{item.destination_address}</Text>
        </View>
      </View>
      <View style={styles.cardBottom}>
        <Text style={styles.meta}>{formatDate(item.scheduled_date)}</Text>
        <Text style={styles.meta}>{item.driver_name ?? 'Pa shofer'}</Text>
      </View>
    </Pressable>
  );
}

export function StatusBadge({ status }: { status: TransportStatus }) {
  const color = status === 'COMPLETED'
    ? '#27845a'
    : status === 'CANCELLED'
      ? '#a64b4b'
      : status === 'IN_PROGRESS'
        ? '#a66b19'
        : '#356aa0';
  return (
    <View style={[styles.badge, { backgroundColor: `${color}18` }]}>
      <Text style={[styles.badgeText, { color }]}>{statusLabel(status)}</Text>
    </View>
  );
}

export function formatDate(value: string): string {
  const date = new Date(value.replace(' ', 'T'));
  if (Number.isNaN(date.getTime())) return value;
  return `${date.toLocaleDateString('sq-AL', { day: '2-digit', month: 'short', year: 'numeric' })} · ${date.toLocaleTimeString('sq-AL', { hour: '2-digit', minute: '2-digit' })}`;
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
    paddingHorizontal: 20,
    paddingTop: 16,
    paddingBottom: 14,
  },
  eyebrow: { color: '#078b81', fontSize: 10, fontWeight: '900', letterSpacing: 1.4 },
  title: { color: '#15263b', fontSize: 23, fontWeight: '800', marginTop: 5 },
  subtitle: { color: '#8491a0', fontSize: 12, marginTop: 4 },
  logout: { backgroundColor: '#f0f4f7', borderRadius: 12, paddingHorizontal: 13, paddingVertical: 9 },
  logoutText: { color: '#42556b', fontSize: 13, fontWeight: '700' },
  searchRow: { flexDirection: 'row', gap: 8, paddingHorizontal: 16, paddingTop: 14 },
  search: {
    backgroundColor: '#fff',
    borderColor: '#e1e7ed',
    borderRadius: 12,
    borderWidth: 1,
    color: '#24364b',
    flex: 1,
    fontSize: 14,
    minHeight: 46,
    paddingHorizontal: 13,
  },
  searchButton: { alignItems: 'center', backgroundColor: '#152f46', borderRadius: 12, justifyContent: 'center', paddingHorizontal: 14 },
  searchButtonText: { color: '#fff', fontSize: 13, fontWeight: '700' },
  listContent: { paddingBottom: 100, paddingHorizontal: 16, paddingTop: 8 },
  filterList: { flexDirection: 'row', flexWrap: 'wrap', gap: 7, paddingVertical: 8 },
  filterChip: { backgroundColor: '#e9eef3', borderRadius: 20, paddingHorizontal: 11, paddingVertical: 7 },
  filterChipActive: { backgroundColor: '#d8efec' },
  filterText: { color: '#647387', fontSize: 11, fontWeight: '700' },
  filterTextActive: { color: '#06786f' },
  card: {
    backgroundColor: '#fff',
    borderColor: '#e7edf2',
    borderRadius: 16,
    borderWidth: 1,
    marginTop: 10,
    padding: 14,
  },
  cardPressed: { opacity: 0.8 },
  cardTop: { alignItems: 'center', flexDirection: 'row', justifyContent: 'space-between' },
  customer: { color: '#1c3046', flex: 1, fontSize: 15, fontWeight: '800', marginRight: 8 },
  badge: { borderRadius: 20, paddingHorizontal: 9, paddingVertical: 5 },
  badgeText: { fontSize: 10, fontWeight: '800' },
  route: { flexDirection: 'row', marginTop: 13 },
  routeMarks: { alignItems: 'center', paddingHorizontal: 3, paddingTop: 4 },
  dot: { backgroundColor: '#078b81', borderRadius: 5, height: 8, width: 8 },
  destinationDot: { backgroundColor: '#f1a24a' },
  routeLine: { backgroundColor: '#d8e1e8', height: 16, width: 1, marginVertical: 2 },
  routeText: { flex: 1, gap: 13, marginLeft: 9 },
  address: { color: '#4d5f73', fontSize: 13 },
  cardBottom: { borderTopColor: '#eef2f5', borderTopWidth: 1, flexDirection: 'row', justifyContent: 'space-between', marginTop: 13, paddingTop: 10 },
  meta: { color: '#8a97a6', fontSize: 11 },
  empty: { alignItems: 'center', paddingHorizontal: 26, paddingVertical: 34 },
  emptyIcon: { color: '#93c8c1', fontSize: 36, fontWeight: '800' },
  emptyTitle: { color: '#34485e', fontSize: 16, fontWeight: '800', marginTop: 10 },
  emptyText: { color: '#8794a2', fontSize: 13, marginTop: 6, textAlign: 'center' },
  error: { backgroundColor: '#fceeee', borderRadius: 10, color: '#a73b3b', fontSize: 12, lineHeight: 18, marginTop: 8, padding: 10 },
  addButton: {
    alignItems: 'center',
    backgroundColor: '#078b81',
    borderRadius: 16,
    bottom: 18,
    elevation: 6,
    flexDirection: 'row',
    justifyContent: 'center',
    left: 20,
    minHeight: 54,
    position: 'absolute',
    right: 20,
    shadowColor: '#064e49',
    shadowOffset: { width: 0, height: 5 },
    shadowOpacity: 0.2,
    shadowRadius: 9,
  },
  addIcon: { color: '#fff', fontSize: 22, fontWeight: '700', marginRight: 7 },
  addText: { color: '#fff', fontSize: 14, fontWeight: '800' },
});
