import { useState } from 'react';
import {
  FlatList,
  Modal,
  Pressable,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { colors } from '../theme';
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
  label: { color: '#c3c9c7', fontSize: 13, fontWeight: '700', marginBottom: 7, marginTop: 16 },
  select: {
    alignItems: 'center',
    backgroundColor: '#09151e',
    borderColor: '#ffffff20',
    borderRadius: 10,
    borderWidth: 1,
    flexDirection: 'row',
    justifyContent: 'space-between',
    minHeight: 50,
    paddingHorizontal: 14,
  },
  value: { color: colors.text, fontSize: 15 },
  placeholder: { color: '#77868b', fontSize: 15 },
  chevron: { color: colors.amberLight, fontSize: 22 },
  overlay: { backgroundColor: '#03090fc4', flex: 1, justifyContent: 'flex-end' },
  sheet: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderTopWidth: 1,
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    maxHeight: '78%',
    paddingBottom: 28,
    paddingHorizontal: 20,
    paddingTop: 12,
  },
  handle: { alignSelf: 'center', backgroundColor: '#53616a', borderRadius: 4, height: 5, marginBottom: 16, width: 42 },
  title: { color: colors.text, fontSize: 18, fontWeight: '800', marginBottom: 10 },
  empty: { color: colors.textMuted, paddingVertical: 20 },
  option: {
    alignItems: 'center',
    borderBottomColor: colors.border,
    borderBottomWidth: 1,
    flexDirection: 'row',
    justifyContent: 'space-between',
    minHeight: 50,
    paddingHorizontal: 8,
  },
  optionSelected: { backgroundColor: `${colors.amber}12` },
  optionText: { color: colors.text, fontSize: 15 },
  check: { color: colors.amberLight, fontSize: 18, fontWeight: '800' },
  cancel: { alignItems: 'center', backgroundColor: '#ffffff0d', borderRadius: 10, marginTop: 14, padding: 14 },
  cancelText: { color: '#c0c9c7', fontWeight: '700' },
});
