import { useState } from 'react';
import {
  FlatList,
  Modal,
  Pressable,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { Resource } from '../types';

type Props = {
  label: string;
  value: number | null;
  options: Resource[];
  onChange: (value: number | null) => void;
  optional?: boolean;
};

export function SelectField({ label, value, options, onChange, optional = false }: Props) {
  const [visible, setVisible] = useState(false);
  const selected = options.find((option) => option.id === value);

  return (
    <>
      <Text style={styles.label}>{label}</Text>
      <Pressable style={styles.select} onPress={() => setVisible(true)}>
        <Text style={selected ? styles.value : styles.placeholder}>
          {selected?.label ?? (optional ? 'Pa caktuar' : 'Zgjidh...')}
        </Text>
        <Text style={styles.chevron}>⌄</Text>
      </Pressable>
      <Modal visible={visible} transparent animationType="slide" onRequestClose={() => setVisible(false)}>
        <Pressable style={styles.overlay} onPress={() => setVisible(false)}>
          <Pressable style={styles.sheet} onPress={(event) => event.stopPropagation()}>
            <View style={styles.handle} />
            <Text style={styles.title}>{label}</Text>
            <FlatList
              data={optional ? [{ id: 0, label: 'Pa caktuar' }, ...options] : options}
              keyExtractor={(item) => String(item.id)}
              ListEmptyComponent={<Text style={styles.empty}>Nuk ka zgjedhje aktive.</Text>}
              renderItem={({ item }) => (
                <Pressable
                  style={[styles.option, item.id === value && styles.optionSelected]}
                  onPress={() => {
                    onChange(item.id === 0 && optional ? null : item.id);
                    setVisible(false);
                  }}
                >
                  <Text style={styles.optionText}>{item.label}</Text>
                  {item.id === value && <Text style={styles.check}>✓</Text>}
                </Pressable>
              )}
            />
            <Pressable onPress={() => setVisible(false)} style={styles.cancel}>
              <Text style={styles.cancelText}>Mbyll</Text>
            </Pressable>
          </Pressable>
        </Pressable>
      </Modal>
    </>
  );
}

const styles = StyleSheet.create({
  label: { color: '#304258', fontSize: 13, fontWeight: '700', marginBottom: 7, marginTop: 16 },
  select: {
    alignItems: 'center',
    backgroundColor: '#fff',
    borderColor: '#dce4ec',
    borderRadius: 12,
    borderWidth: 1,
    flexDirection: 'row',
    justifyContent: 'space-between',
    minHeight: 50,
    paddingHorizontal: 14,
  },
  value: { color: '#15263b', fontSize: 15 },
  placeholder: { color: '#8b98a8', fontSize: 15 },
  chevron: { color: '#62758a', fontSize: 22 },
  overlay: { backgroundColor: '#071a2b88', flex: 1, justifyContent: 'flex-end' },
  sheet: {
    backgroundColor: '#fff',
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    maxHeight: '78%',
    paddingBottom: 28,
    paddingHorizontal: 20,
    paddingTop: 12,
  },
  handle: { alignSelf: 'center', backgroundColor: '#d7e0e8', borderRadius: 4, height: 5, marginBottom: 16, width: 42 },
  title: { color: '#15263b', fontSize: 18, fontWeight: '800', marginBottom: 10 },
  empty: { color: '#718196', paddingVertical: 20 },
  option: {
    alignItems: 'center',
    borderBottomColor: '#edf1f5',
    borderBottomWidth: 1,
    flexDirection: 'row',
    justifyContent: 'space-between',
    minHeight: 50,
    paddingHorizontal: 8,
  },
  optionSelected: { backgroundColor: '#eef7f6' },
  optionText: { color: '#26394f', fontSize: 15 },
  check: { color: '#078b81', fontSize: 18, fontWeight: '800' },
  cancel: { alignItems: 'center', backgroundColor: '#eef2f6', borderRadius: 12, marginTop: 14, padding: 14 },
  cancelText: { color: '#304258', fontWeight: '700' },
});
