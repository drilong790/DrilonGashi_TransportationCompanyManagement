import { useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { loadResources } from '../api';
import { SelectField } from '../components/SelectField';
import { Transport, TransportInput, TransportResources, TransportStatus } from '../types';

const statusOptions: { id: number; label: string }[] = [
  { id: 1, label: 'Planifikuar' },
  { id: 2, label: 'Në proces' },
  { id: 3, label: 'Përfunduar' },
  { id: 4, label: 'Anuluar' },
];
const statuses: TransportStatus[] = ['PLANNED', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED'];

type Values = {
  customer_id: number | null;
  driver_id: number | null;
  vehicle_id: number | null;
  pickup_address: string;
  destination_address: string;
  cargo_description: string;
  scheduled_date: string;
  scheduled_end: string;
  status: TransportStatus;
};

function blankValues(): Values {
  const now = new Date();
  now.setMinutes(0, 0, 0);
  const end = new Date(now.getTime() + 60 * 60 * 1000);
  return {
    customer_id: null,
    driver_id: null,
    vehicle_id: null,
    pickup_address: '',
    destination_address: '',
    cargo_description: '',
    scheduled_date: toLocalInput(now),
    scheduled_end: toLocalInput(end),
    status: 'PLANNED',
  };
}

function valuesFromTransport(transport: Transport | null): Values {
  if (!transport) return blankValues();
  return {
    customer_id: transport.customer_id,
    driver_id: transport.driver_id,
    vehicle_id: transport.vehicle_id,
    pickup_address: transport.pickup_address,
    destination_address: transport.destination_address,
    cargo_description: transport.cargo_description,
    scheduled_date: fromDatabaseDate(transport.scheduled_date),
    scheduled_end: fromDatabaseDate(transport.scheduled_end),
    status: transport.status,
  };
}

function toLocalInput(value: Date): string {
  const pad = (part: number) => String(part).padStart(2, '0');
  return `${value.getFullYear()}-${pad(value.getMonth() + 1)}-${pad(value.getDate())}T${pad(value.getHours())}:${pad(value.getMinutes())}`;
}

function fromDatabaseDate(value: string): string {
  return value.replace(' ', 'T').slice(0, 16);
}

export function TransportFormScreen({
  token,
  transport,
  onBack,
  onSave,
}: {
  token: string;
  transport: Transport | null;
  onBack: () => void;
  onSave: (values: TransportInput) => Promise<void>;
}) {
  const [values, setValues] = useState<Values>(() => valuesFromTransport(transport));
  const [resources, setResources] = useState<TransportResources | null>(null);
  const [loadingResources, setLoadingResources] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    let active = true;
    loadResources(token)
      .then((loaded) => {
        if (active) setResources(loaded);
      })
      .catch((requestError: unknown) => {
        if (active) setError(requestError instanceof Error ? requestError.message : 'Të dhënat nuk u ngarkuan.');
      })
      .finally(() => {
        if (active) setLoadingResources(false);
      });
    return () => {
      active = false;
    };
  }, [token, transport]);

  const update = <K extends keyof Values>(field: K, value: Values[K]) => {
    setValues((current) => ({ ...current, [field]: value }));
  };

  const submit = async () => {
    setError('');
    if (
      values.customer_id === null
      || !values.pickup_address.trim()
      || !values.destination_address.trim()
      || !values.cargo_description.trim()
      || !/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/.test(values.scheduled_date)
      || !/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/.test(values.scheduled_end)
    ) {
      setError('Plotëso fushat e kërkuara me vlera të vlefshme.');
      return;
    }
    setSaving(true);
    try {
      await onSave({
        ...values,
        customer_id: values.customer_id,
        scheduled_date: values.scheduled_date,
        scheduled_end: values.scheduled_end,
      });
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'Transporti nuk u ruajt.');
    } finally {
      setSaving(false);
    }
  };

  const statusValue = statuses.indexOf(values.status) + 1;
  const driverOptions = [...(resources?.drivers ?? [])];
  if (
    transport?.driver_id !== null
    && transport?.driver_id !== undefined
    && !driverOptions.some((option) => option.id === transport.driver_id)
  ) {
    driverOptions.push({
      id: transport.driver_id,
      label: `${transport.driver_name ?? 'Shoferi'} (joaktiv)`,
    });
  }
  const vehicleOptions = [...(resources?.vehicles ?? [])];
  if (
    transport?.vehicle_id !== null
    && transport?.vehicle_id !== undefined
    && !vehicleOptions.some((option) => option.id === transport.vehicle_id)
  ) {
    vehicleOptions.push({
      id: transport.vehicle_id,
      label: `${transport.vehicle_plate ?? 'Automjeti'} (joaktiv)`,
    });
  }
  const input = (
    field: 'pickup_address' | 'destination_address' | 'cargo_description',
    label: string,
    placeholder: string,
    multiline = false,
  ) => (
    <>
      <Text style={styles.label}>{label}</Text>
      <TextInput
        multiline={multiline}
        onChangeText={(text) => update(field, text)}
        placeholder={placeholder}
        placeholderTextColor="#9aa7b6"
        style={[styles.input, multiline && styles.multiline]}
        textAlignVertical={multiline ? 'top' : 'center'}
        value={values[field]}
      />
    </>
  );

  return (
    <View style={styles.screen}>
      <View style={styles.header}>
        <Pressable onPress={onBack} style={styles.back}><Text style={styles.backText}>‹</Text></Pressable>
        <Text style={styles.title}>{transport ? 'Ndrysho transportin' : 'Transport i ri'}</Text>
        <View style={styles.headerSpace} />
      </View>
      {loadingResources ? (
        <View style={styles.loading}><ActivityIndicator color="#078b81" /><Text style={styles.help}>Po ngarkohen klientët dhe caktimet...</Text></View>
      ) : (
        <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
          {resources && (
            <>
              <Text style={styles.section}>Informacioni kryesor</Text>
              <SelectField
                label="Klienti *"
                value={values.customer_id}
                options={resources.customers}
                onChange={(value) => update('customer_id', value)}
              />
              <SelectField
                label="Shoferi"
                value={values.driver_id}
                options={driverOptions}
                onChange={(value) => update('driver_id', value)}
                optional
              />
              <SelectField
                label="Automjeti"
                value={values.vehicle_id}
                options={vehicleOptions}
                onChange={(value) => update('vehicle_id', value)}
                optional
              />
              {input('pickup_address', 'Adresa e nisjes *', 'Shkruaj adresën e nisjes')}
              {input('destination_address', 'Adresa e destinacionit *', 'Shkruaj destinacionin')}
              {input('cargo_description', 'Përshkrimi i ngarkesës *', 'Përshkruaj ngarkesën', true)}
              <Text style={styles.section}>Orari dhe statusi</Text>
              <Text style={styles.label}>Data dhe ora e fillimit * (YYYY-MM-DDTHH:mm)</Text>
              <TextInput
                autoCapitalize="none"
                onChangeText={(text) => update('scheduled_date', text)}
                placeholder="2026-11-18T10:00"
                placeholderTextColor="#9aa7b6"
                style={styles.input}
                value={values.scheduled_date}
              />
              <Text style={styles.label}>Data dhe ora e përfundimit * (YYYY-MM-DDTHH:mm)</Text>
              <TextInput
                autoCapitalize="none"
                onChangeText={(text) => update('scheduled_end', text)}
                placeholder="2026-11-18T11:00"
                placeholderTextColor="#9aa7b6"
                style={styles.input}
                value={values.scheduled_end}
              />
              <SelectField
                label="Statusi"
                value={statusValue}
                options={statusOptions}
                onChange={(value) => {
                  if (value !== null) update('status', statuses[value - 1]);
                }}
              />
            </>
          )}
          {!!error && <Text style={styles.error}>{error}</Text>}
          <Pressable disabled={saving || loadingResources || !resources} onPress={() => void submit()} style={[styles.save, (saving || !resources) && styles.disabled]}>
            {saving ? <ActivityIndicator color="#fff" /> : <Text style={styles.saveText}>Ruaj transportin</Text>}
          </Pressable>
        </ScrollView>
      )}
      {!loadingResources && error && !resources && <Text style={styles.errorOnly}>{error}</Text>}
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
    minHeight: 58,
    paddingHorizontal: 14,
  },
  back: { alignItems: 'center', height: 40, justifyContent: 'center', width: 36 },
  backText: { color: '#18334c', fontSize: 34, lineHeight: 38 },
  title: { color: '#23384e', flex: 1, fontSize: 15, fontWeight: '800', textAlign: 'center' },
  headerSpace: { width: 36 },
  content: { padding: 18, paddingBottom: 35 },
  section: { color: '#20364d', fontSize: 15, fontWeight: '800', marginTop: 16 },
  label: { color: '#304258', fontSize: 13, fontWeight: '700', marginBottom: 7, marginTop: 16 },
  input: {
    backgroundColor: '#fff',
    borderColor: '#dce4ec',
    borderRadius: 12,
    borderWidth: 1,
    color: '#15263b',
    fontSize: 14,
    minHeight: 50,
    paddingHorizontal: 14,
  },
  multiline: { minHeight: 90, paddingTop: 12 },
  loading: { alignItems: 'center', flex: 1, justifyContent: 'center' },
  help: { color: '#718196', fontSize: 13, marginTop: 12 },
  error: { backgroundColor: '#fceeee', borderRadius: 10, color: '#a73b3b', fontSize: 12, lineHeight: 18, marginTop: 16, padding: 10 },
  errorOnly: { color: '#a73b3b', fontSize: 13, marginHorizontal: 18, marginTop: 12 },
  save: { alignItems: 'center', backgroundColor: '#078b81', borderRadius: 13, justifyContent: 'center', marginTop: 22, minHeight: 52 },
  disabled: { opacity: 0.6 },
  saveText: { color: '#fff', fontSize: 14, fontWeight: '800' },
});
