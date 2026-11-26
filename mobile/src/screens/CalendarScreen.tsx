import { useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { ApiError, loadTransports } from '../api';
import { loadCachedTransports } from '../offline';
import { colors } from '../theme';
import { Transport } from '../types';
import { StatusBadge } from './HomeScreen';

function localDate(date: Date): string {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function mondayOf(date: Date): Date {
  const monday = new Date(date.getFullYear(), date.getMonth(), date.getDate());
  monday.setDate(monday.getDate() - ((monday.getDay() + 6) % 7));
  return monday;
}

export function CalendarScreen({
  token,
  userId,
  onBack,
  onOpen,
}: {
  token: string;
  userId: number;
  onBack: () => void;
  onOpen: (id: number) => void;
}) {
  const [week, setWeek] = useState(() => mondayOf(new Date()));
  const [selectedDate, setSelectedDate] = useState(() => localDate(new Date()));
  const [transports, setTransports] = useState<Transport[]>([]);
  const [loadedWeek, setLoadedWeek] = useState('');
  const [offline, setOffline] = useState(false);
  const [requestError, setRequestError] = useState<{ week: string; message: string } | null>(null);
  const currentWeek = localDate(week);
  const loading = loadedWeek !== currentWeek;
  const error = requestError?.week === currentWeek ? requestError.message : '';
  const days = useMemo(
    () => Array.from({ length: 7 }, (_, index) => {
      const date = new Date(week);
      date.setDate(week.getDate() + index);
      return date;
    }),
    [week],
  );

  useEffect(() => {
    let active = true;
    const from = localDate(week);
    const end = new Date(week);
    end.setDate(week.getDate() + 6);
    loadTransports(token, { date_from: from, date_to: localDate(end) })
      .then((loaded) => {
        if (!active) return;
        setTransports(loaded);
        setOffline(false);
        setRequestError(null);
      })
      .catch(async (requestError: unknown) => {
        if (!active) return;
        try {
          if (requestError instanceof ApiError && requestError.status === 0) {
            const cached = await loadCachedTransports(userId);
            if (!active) return;
            setTransports(cached.filter((transport) => {
              const date = transport.scheduled_date.slice(0, 10);
              return date >= from && date <= localDate(end);
            }));
            setOffline(true);
          } else {
            setRequestError({
              week: from,
              message: requestError instanceof Error ? requestError.message : 'Kalendari nuk u ngarkua.',
            });
          }
        } catch (cacheError) {
          setRequestError({
            week: from,
            message: cacheError instanceof Error ? cacheError.message : 'Të dhënat e kalendarit nuk u lexuan.',
          });
        }
      })
      .finally(() => {
        if (active) setLoadedWeek(from);
      });
    return () => {
      active = false;
    };
  }, [token, userId, week]);

  const visibleTransports = loading || error ? [] : transports;
  const selectedTransports = visibleTransports.filter(
    (transport) => transport.scheduled_date.slice(0, 10) === selectedDate,
  );
  const rangeLabel = `${days[0].toLocaleDateString('sq-AL', { day: 'numeric', month: 'short' })} – ${days[6].toLocaleDateString('sq-AL', { day: 'numeric', month: 'short', year: 'numeric' })}`;

  const moveWeek = (amount: number) => {
    setWeek((current) => {
      const next = new Date(current);
      next.setDate(current.getDate() + amount * 7);
      return next;
    });
    setSelectedDate((current) => {
      const next = new Date(`${current}T12:00:00`);
      next.setDate(next.getDate() + amount * 7);
      return localDate(next);
    });
  };

  return (
    <View style={styles.screen}>
      <View style={styles.header}>
        <Pressable accessibilityRole="button" onPress={onBack} style={styles.back}>
          <Text style={styles.backText}>‹</Text>
        </Pressable>
        <View style={styles.headerCopy}>
          <Text style={styles.eyebrow}>PLANIFIKIMI</Text>
          <Text style={styles.title}>Kalendari</Text>
        </View>
        <Pressable
          accessibilityRole="button"
          onPress={() => {
            const today = new Date();
            setWeek(mondayOf(today));
            setSelectedDate(localDate(today));
          }}
          style={styles.todayButton}
        >
          <Text style={styles.todayText}>Sot</Text>
        </Pressable>
      </View>
      <View style={styles.weekToolbar}>
        <Pressable accessibilityLabel="Java e kaluar" onPress={() => moveWeek(-1)} style={styles.arrow}>
          <Text style={styles.arrowText}>‹</Text>
        </Pressable>
        <Text style={styles.range}>{rangeLabel}</Text>
        <Pressable accessibilityLabel="Java tjetër" onPress={() => moveWeek(1)} style={styles.arrow}>
          <Text style={styles.arrowText}>›</Text>
        </Pressable>
      </View>
      {offline && <Text style={styles.offline}>Pa lidhje — shfaqen takimet e ruajtura.</Text>}
      <ScrollView horizontal contentContainerStyle={styles.dayStrip} showsHorizontalScrollIndicator={false}>
        {days.map((date) => {
          const key = localDate(date);
          const count = visibleTransports.filter((transport) => transport.scheduled_date.slice(0, 10) === key).length;
          const selected = key === selectedDate;
          return (
            <Pressable
              key={key}
              onPress={() => setSelectedDate(key)}
              style={[styles.day, selected && styles.daySelected]}
            >
              <Text style={[styles.dayName, selected && styles.dayTextSelected]}>
                {date.toLocaleDateString('sq-AL', { weekday: 'short' })}
              </Text>
              <Text style={[styles.dayNumber, selected && styles.dayTextSelected]}>{date.getDate()}</Text>
              <View style={[styles.dayCount, selected && styles.dayCountSelected]}>
                <Text style={[styles.dayCountText, selected && styles.dayTextSelected]}>{count}</Text>
              </View>
            </Pressable>
          );
        })}
      </ScrollView>
      <ScrollView contentContainerStyle={styles.content}>
        <View style={styles.sectionHeading}>
          <Text style={styles.sectionTitle}>
            {new Date(`${selectedDate}T12:00:00`).toLocaleDateString('sq-AL', {
              weekday: 'long',
              day: 'numeric',
              month: 'long',
            })}
          </Text>
          <Text style={styles.resultCount}>{selectedTransports.length} transport(e)</Text>
        </View>
        {loading && <ActivityIndicator color={colors.amber} style={styles.loading} />}
        {!!error && <Text style={styles.error}>{error}</Text>}
        {!loading && !error && selectedTransports.length === 0 && (
          <View style={styles.empty}>
            <Text style={styles.emptyIcon}>◷</Text>
            <Text style={styles.emptyTitle}>Asnjë transport për këtë ditë</Text>
            <Text style={styles.emptyText}>Zgjidh një ditë tjetër ose kontrollo javën pasuese.</Text>
          </View>
        )}
        {selectedTransports.map((transport) => (
          <Pressable key={transport.id} onPress={() => onOpen(transport.id)} style={styles.transport}>
            <View style={styles.transportTop}>
              <Text style={styles.time}>
                {new Date(transport.scheduled_date.replace(' ', 'T')).toLocaleTimeString('sq-AL', {
                  hour: '2-digit',
                  minute: '2-digit',
                })}
              </Text>
              <StatusBadge status={transport.status} />
            </View>
            <Text style={styles.customer}>{transport.customer_name}</Text>
            <Text style={styles.route} numberOfLines={2}>
              {transport.pickup_address}  →  {transport.destination_address}
            </Text>
            <Text style={styles.driver}>{transport.driver_name ?? 'Pa shofer të caktuar'}</Text>
          </Pressable>
        ))}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  screen: { backgroundColor: colors.background, flex: 1 },
  header: { alignItems: 'center', borderBottomColor: colors.border, borderBottomWidth: 1, flexDirection: 'row', minHeight: 65, paddingHorizontal: 14 },
  back: { alignItems: 'center', height: 40, justifyContent: 'center', width: 36 },
  backText: { color: colors.amberLight, fontSize: 34, lineHeight: 38 },
  headerCopy: { flex: 1 },
  eyebrow: { color: '#d3a25c', fontSize: 9, fontWeight: '800', letterSpacing: 1.2 },
  title: { color: colors.text, fontSize: 18, fontWeight: '800', marginTop: 2 },
  todayButton: { backgroundColor: `${colors.amber}18`, borderColor: `${colors.amber}40`, borderRadius: 9, borderWidth: 1, paddingHorizontal: 12, paddingVertical: 8 },
  todayText: { color: colors.amberLight, fontSize: 12, fontWeight: '800' },
  weekToolbar: { alignItems: 'center', flexDirection: 'row', justifyContent: 'space-between', paddingHorizontal: 15, paddingVertical: 10 },
  arrow: { alignItems: 'center', backgroundColor: colors.surface, borderColor: colors.border, borderRadius: 10, borderWidth: 1, height: 36, justifyContent: 'center', width: 38 },
  arrowText: { color: colors.amberLight, fontSize: 22, lineHeight: 24 },
  range: { color: colors.text, fontSize: 14, fontWeight: '700' },
  offline: { color: colors.amberLight, fontSize: 11, paddingHorizontal: 18, paddingBottom: 8 },
  dayStrip: { gap: 7, paddingHorizontal: 15, paddingBottom: 14 },
  day: { alignItems: 'center', backgroundColor: colors.surface, borderColor: colors.border, borderRadius: 12, borderWidth: 1, minWidth: 47, paddingHorizontal: 7, paddingVertical: 9 },
  daySelected: { backgroundColor: colors.amber, borderColor: colors.amber },
  dayName: { color: colors.textMuted, fontSize: 10, fontWeight: '600', textTransform: 'capitalize' },
  dayNumber: { color: colors.text, fontSize: 16, fontWeight: '800', marginTop: 4 },
  dayTextSelected: { color: '#17212a' },
  dayCount: { alignItems: 'center', backgroundColor: '#ffffff12', borderRadius: 8, minWidth: 19, marginTop: 5, paddingHorizontal: 4, paddingVertical: 2 },
  dayCountSelected: { backgroundColor: '#17212a20' },
  dayCountText: { color: colors.textMuted, fontSize: 9, fontWeight: '700' },
  content: { paddingBottom: 30, paddingHorizontal: 16 },
  sectionHeading: { alignItems: 'center', flexDirection: 'row', justifyContent: 'space-between', marginBottom: 8, marginTop: 4 },
  sectionTitle: { color: colors.text, fontSize: 15, fontWeight: '800', textTransform: 'capitalize' },
  resultCount: { color: colors.textMuted, fontSize: 11 },
  loading: { marginTop: 30 },
  error: { color: colors.danger, fontSize: 13, marginTop: 12 },
  empty: { alignItems: 'center', paddingHorizontal: 20, paddingVertical: 46 },
  emptyIcon: { color: colors.amber, fontSize: 36 },
  emptyTitle: { color: colors.text, fontSize: 15, fontWeight: '800', marginTop: 12 },
  emptyText: { color: colors.textMuted, fontSize: 12, lineHeight: 18, marginTop: 6, textAlign: 'center' },
  transport: { backgroundColor: colors.surface, borderColor: colors.border, borderLeftColor: colors.amber, borderLeftWidth: 3, borderRadius: 13, borderWidth: 1, marginTop: 10, padding: 14 },
  transportTop: { alignItems: 'center', flexDirection: 'row', justifyContent: 'space-between' },
  time: { color: colors.amberLight, fontSize: 13, fontVariant: ['tabular-nums'], fontWeight: '800' },
  customer: { color: colors.text, fontSize: 15, fontWeight: '800', marginTop: 10 },
  route: { color: '#c0c9c7', fontSize: 12, lineHeight: 18, marginTop: 5 },
  driver: { color: colors.textMuted, fontSize: 11, marginTop: 9 },
});
