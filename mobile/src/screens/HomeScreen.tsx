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
import { colors } from '../theme';
import { DashboardSummary, Transport, TransportStatus, User } from '../types';

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
  summary,
  loading,
  error,
  offline,
  queuedCount,
  onRefresh,
  onOpen,
  onCreate,
  onLogout,
  onFilter,
  onCalendar,
  onSettings,
  onSync,
}: {
  user: User;
  transports: Transport[];
  summary: DashboardSummary | null;
  loading: boolean;
  error: string;
  offline: boolean;
  queuedCount: number;
  onRefresh: () => void;
  onOpen: (id: number) => void;
  onCreate: () => void;
  onLogout: () => void;
  onFilter: (search: string, status: string) => void;
  onCalendar: () => void;
  onSettings: () => void;
  onSync: () => void;
}) {
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState<TransportStatus | ''>('');

  return (
    <View style={styles.screen}>
      <View style={styles.header}>
        <View>
          <Text style={styles.eyebrow}>
            {user.role === 'DRIVER' ? 'PANELI I SHOFERIT' : 'MENAXHIMI I TRANSPORTEVE'}
          </Text>
          <Text style={styles.title}>Përshëndetje, {user.full_name.split(' ')[0]}</Text>
          <Text style={styles.subtitle}>{transports.length} transport(e) në listë</Text>
        </View>
        <Pressable accessibilityLabel="Dil nga llogaria" onPress={onLogout} style={styles.logout}>
          <Text style={styles.logoutText}>Dil</Text>
        </Pressable>
      </View>
      <FlatList
        contentContainerStyle={styles.listContent}
        data={transports}
        keyExtractor={(item) => String(item.id)}
        ListHeaderComponent={
          <>
            <View style={styles.summaryGrid}>
              <SummaryCard label="Sot" value={summary?.today ?? 0} />
              <SummaryCard label="Planifikuar" value={summary?.planned ?? 0} />
              <SummaryCard label="Në proces" value={summary?.in_progress ?? 0} />
              <SummaryCard label="Përfunduar" value={summary?.completed ?? 0} />
            </View>
            <View style={styles.shortcutRow}>
              <Pressable onPress={onCalendar} style={styles.shortcut}>
                <Text style={styles.shortcutIcon}>▦</Text>
                <Text style={styles.shortcutText}>Kalendari</Text>
                <Text style={styles.shortcutArrow}>›</Text>
              </Pressable>
              <Pressable onPress={onSettings} style={styles.shortcut}>
                <Text style={styles.shortcutIcon}>⚙</Text>
                <Text style={styles.shortcutText}>Cilësimet</Text>
                <Text style={styles.shortcutArrow}>›</Text>
              </Pressable>
            </View>
            {offline && (
              <View style={styles.offlineNotice}>
                <Text style={styles.offlineText}>Pa lidhje — po shfaqen të dhënat e ruajtura.</Text>
              </View>
            )}
            {queuedCount > 0 && (
              <Pressable onPress={onSync} style={styles.queueNotice}>
                <View style={styles.queueCopy}>
                  <Text style={styles.queueTitle}>{queuedCount} ndryshim(e) presin sinkronizimin</Text>
                  <Text style={styles.queueSubtitle}>Prek për t’i dërguar kur je online.</Text>
                </View>
                <Text style={styles.queueAction}>Sinkronizo</Text>
              </Pressable>
            )}
            {user.role !== 'DRIVER' && (
              <>
                <View style={styles.searchRow}>
                  <TextInput
                    onChangeText={setSearch}
                    onSubmitEditing={() => onFilter(search.trim(), status)}
                    placeholder="Kërko klient, adresë, shofer..."
                    placeholderTextColor="#77868b"
                    returnKeyType="search"
                    style={styles.search}
                    value={search}
                  />
                  <Pressable onPress={() => onFilter(search.trim(), status)} style={styles.searchButton}>
                    <Text style={styles.searchButtonText}>Kërko</Text>
                  </Pressable>
                </View>
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
              </>
            )}
            {!!error && <Text style={styles.error}>{error}</Text>}
            {loading && transports.length === 0 && (
              <View style={styles.empty}>
                <ActivityIndicator color={colors.amber} />
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
              <Text style={styles.emptyText}>
                {user.role === 'DRIVER'
                  ? 'Transportet e caktuara për ty do të shfaqen këtu.'
                  : 'Ndrysho filtrat ose shto transport të ri.'}
              </Text>
            </View>
          ) : undefined
        }
        refreshControl={<RefreshControl refreshing={loading && transports.length > 0} onRefresh={onRefresh} tintColor={colors.amber} />}
        renderItem={({ item }) => <TransportCard item={item} onPress={() => onOpen(item.id)} />}
      />
      {user.role !== 'DRIVER' && (
        <Pressable accessibilityRole="button" onPress={onCreate} style={styles.addButton}>
          <Text style={styles.addIcon}>＋</Text>
          <Text style={styles.addText}>Transport i ri</Text>
        </Pressable>
      )}
    </View>
  );
}

function SummaryCard({ label, value }: { label: string; value: number }) {
  return (
    <View style={styles.summaryCard}>
      <Text style={styles.summaryValue}>{value}</Text>
      <Text style={styles.summaryLabel}>{label}</Text>
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
    ? colors.success
    : status === 'CANCELLED'
      ? colors.danger
      : status === 'IN_PROGRESS'
        ? '#a8e0d6'
        : colors.amberLight;
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
    backgroundColor: colors.background,
    borderBottomColor: colors.border,
    borderBottomWidth: 1,
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingHorizontal: 20,
    paddingTop: 12,
    paddingBottom: 16,
  },
  eyebrow: { color: '#d3a25c', fontSize: 9, fontWeight: '800', letterSpacing: 1.5 },
  title: { color: colors.text, fontSize: 23, fontWeight: '700', letterSpacing: -0.5, marginTop: 6 },
  subtitle: { color: colors.textMuted, fontSize: 12, marginTop: 5 },
  logout: { backgroundColor: '#ffffff0d', borderColor: colors.border, borderRadius: 10, borderWidth: 1, paddingHorizontal: 13, paddingVertical: 9 },
  logoutText: { color: colors.amberLight, fontSize: 13, fontWeight: '700' },
  searchRow: { flexDirection: 'row', gap: 8, paddingHorizontal: 16, paddingTop: 14 },
  search: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: 10,
    borderWidth: 1,
    color: colors.text,
    flex: 1,
    fontSize: 14,
    minHeight: 46,
    paddingHorizontal: 13,
  },
  searchButton: { alignItems: 'center', backgroundColor: colors.amber, borderRadius: 10, justifyContent: 'center', paddingHorizontal: 15 },
  searchButtonText: { color: '#17212a', fontSize: 13, fontWeight: '800' },
  listContent: { paddingBottom: 100, paddingHorizontal: 16, paddingTop: 12 },
  summaryGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 9, paddingBottom: 11 },
  summaryCard: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: 12,
    borderWidth: 1,
    flexBasis: '48%',
    flexGrow: 1,
    minHeight: 72,
    paddingHorizontal: 13,
    paddingVertical: 11,
  },
  summaryValue: { color: colors.amberLight, fontSize: 21, fontWeight: '800' },
  summaryLabel: { color: colors.textMuted, fontSize: 11, marginTop: 3 },
  shortcutRow: { flexDirection: 'row', gap: 9, marginBottom: 10 },
  shortcut: {
    alignItems: 'center',
    backgroundColor: colors.surfaceRaised,
    borderColor: colors.border,
    borderRadius: 11,
    borderWidth: 1,
    flex: 1,
    flexDirection: 'row',
    minHeight: 45,
    paddingHorizontal: 11,
  },
  shortcutIcon: { color: colors.amberLight, fontSize: 17, marginRight: 8 },
  shortcutText: { color: colors.text, flex: 1, fontSize: 12, fontWeight: '700' },
  shortcutArrow: { color: colors.textMuted, fontSize: 20 },
  offlineNotice: { backgroundColor: '#f5ad4f12', borderColor: '#f5ad4f35', borderRadius: 10, borderWidth: 1, marginBottom: 9, padding: 11 },
  offlineText: { color: colors.amberLight, fontSize: 12 },
  queueNotice: { alignItems: 'center', backgroundColor: colors.surface, borderColor: '#f5ad4f35', borderRadius: 11, borderWidth: 1, flexDirection: 'row', gap: 10, marginBottom: 8, padding: 12 },
  queueCopy: { flex: 1 },
  queueTitle: { color: colors.text, fontSize: 12, fontWeight: '700' },
  queueSubtitle: { color: colors.textMuted, fontSize: 11, marginTop: 3 },
  queueAction: { color: colors.amberLight, fontSize: 11, fontWeight: '800' },
  filterList: { flexDirection: 'row', flexWrap: 'wrap', gap: 7, paddingVertical: 8 },
  filterChip: { backgroundColor: colors.surfaceRaised, borderColor: colors.border, borderRadius: 20, borderWidth: 1, paddingHorizontal: 11, paddingVertical: 7 },
  filterChipActive: { backgroundColor: colors.amber, borderColor: colors.amber },
  filterText: { color: '#a4afb3', fontSize: 11, fontWeight: '700' },
  filterTextActive: { color: '#17212a' },
  card: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: 14,
    borderWidth: 1,
    marginTop: 10,
    padding: 14,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.13,
    shadowRadius: 12,
  },
  cardPressed: { opacity: 0.8 },
  cardTop: { alignItems: 'center', flexDirection: 'row', justifyContent: 'space-between' },
  customer: { color: colors.text, flex: 1, fontSize: 15, fontWeight: '700', marginRight: 8 },
  badge: { borderRadius: 20, paddingHorizontal: 9, paddingVertical: 5 },
  badgeText: { fontSize: 10, fontWeight: '800' },
  route: { flexDirection: 'row', marginTop: 13 },
  routeMarks: { alignItems: 'center', paddingHorizontal: 3, paddingTop: 4 },
  dot: { backgroundColor: colors.amber, borderRadius: 5, height: 8, width: 8 },
  destinationDot: { backgroundColor: colors.amberLight },
  routeLine: { backgroundColor: '#6a563c', height: 16, width: 1, marginVertical: 2 },
  routeText: { flex: 1, gap: 13, marginLeft: 9 },
  address: { color: '#c0c9c7', fontSize: 13 },
  cardBottom: { borderTopColor: colors.border, borderTopWidth: 1, flexDirection: 'row', justifyContent: 'space-between', marginTop: 13, paddingTop: 10 },
  meta: { color: colors.textMuted, fontSize: 11 },
  empty: { alignItems: 'center', paddingHorizontal: 26, paddingVertical: 34 },
  emptyIcon: { color: colors.amber, fontSize: 36, fontWeight: '800' },
  emptyTitle: { color: colors.text, fontSize: 16, fontWeight: '800', marginTop: 10 },
  emptyText: { color: colors.textMuted, fontSize: 13, marginTop: 6, textAlign: 'center' },
  error: { backgroundColor: '#652e2a36', borderColor: '#9c54504f', borderRadius: 10, borderWidth: 1, color: colors.danger, fontSize: 12, lineHeight: 18, marginTop: 8, padding: 10 },
  addButton: {
    alignItems: 'center',
    backgroundColor: colors.amber,
    borderRadius: 13,
    bottom: 18,
    elevation: 6,
    flexDirection: 'row',
    justifyContent: 'center',
    left: 20,
    minHeight: 54,
    position: 'absolute',
    right: 20,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 5 },
    shadowOpacity: 0.32,
    shadowRadius: 12,
  },
  addIcon: { color: '#17212a', fontSize: 22, fontWeight: '700', marginRight: 7 },
  addText: { color: '#17212a', fontSize: 14, fontWeight: '800' },
});
