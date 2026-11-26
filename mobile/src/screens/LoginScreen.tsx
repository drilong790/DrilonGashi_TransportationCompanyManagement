import { useState } from 'react';
import {
  ActivityIndicator,
  KeyboardAvoidingView,
  Platform,
  Pressable,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { apiBaseUrl } from '../api';
import { colors } from '../theme';

export function LoginScreen({
  onLogin,
}: {
  onLogin: (email: string, password: string) => Promise<void>;
}) {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const submit = async () => {
    setError('');
    setLoading(true);
    try {
      await onLogin(email.trim(), password);
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'Hyrja dështoi.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <KeyboardAvoidingView
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      style={styles.container}
    >
      <View style={styles.brand}>
        <View style={styles.mark}><Text style={styles.markText}>T</Text></View>
        <Text style={styles.brandName}>TRANSPORT</Text>
        <Text style={styles.brandCaption}>Menaxhimi në lëvizje</Text>
      </View>
      <View style={styles.card}>
        <Text style={styles.title}>Mirë se u ktheve</Text>
        <Text style={styles.subtitle}>Hyr me llogarinë e administratorit ose dispeçerit.</Text>
        <Text style={styles.label}>Email</Text>
        <TextInput
          autoCapitalize="none"
          autoComplete="email"
          keyboardType="email-address"
          onChangeText={setEmail}
          placeholder="emri@kompania.com"
          placeholderTextColor="#9aa7b6"
          style={styles.input}
          value={email}
        />
        <Text style={styles.label}>Fjalëkalimi</Text>
        <TextInput
          autoCapitalize="none"
          onChangeText={setPassword}
          onSubmitEditing={submit}
          placeholder="Fjalëkalimi"
          placeholderTextColor="#9aa7b6"
          secureTextEntry
          style={styles.input}
          value={password}
        />
        {!!error && <Text style={styles.error}>{error}</Text>}
        {!apiBaseUrl && (
          <Text style={styles.configError}>
            Konfiguro adresën e API-së në mobile/.env para hyrjes.
          </Text>
        )}
        <Pressable
          accessibilityRole="button"
          disabled={loading || !apiBaseUrl || !email || !password}
          onPress={() => void submit()}
          style={({ pressed }) => [
            styles.button,
            (loading || !apiBaseUrl || !email || !password) && styles.buttonDisabled,
            pressed && styles.buttonPressed,
          ]}
        >
          {loading
            ? <ActivityIndicator color="#17212a" />
            : <Text style={styles.buttonText}>Hyr në aplikacion</Text>}
        </Pressable>
      </View>
      <Text style={styles.footer}>Qasje e sigurt për ekipin e transportit</Text>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: { backgroundColor: colors.background, flex: 1, justifyContent: 'center', paddingHorizontal: 24 },
  brand: { alignItems: 'center', marginBottom: 28 },
  mark: {
    alignItems: 'center',
    backgroundColor: `${colors.amber}18`,
    borderColor: `${colors.amber}45`,
    borderRadius: 17,
    borderWidth: 1,
    height: 56,
    justifyContent: 'center',
    marginBottom: 12,
    width: 56,
  },
  markText: { color: colors.amberLight, fontSize: 28, fontWeight: '900' },
  brandName: { color: colors.text, fontSize: 18, fontWeight: '900', letterSpacing: 2 },
  brandCaption: { color: colors.textMuted, fontSize: 13, marginTop: 4 },
  card: {
    backgroundColor: colors.surface,
    borderColor: colors.border,
    borderRadius: 20,
    borderWidth: 1,
    elevation: 3,
    padding: 22,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.22,
    shadowRadius: 18,
  },
  title: { color: colors.text, fontSize: 24, fontWeight: '800', letterSpacing: -0.5 },
  subtitle: { color: colors.textMuted, fontSize: 14, lineHeight: 21, marginBottom: 18, marginTop: 7 },
  label: { color: '#c3c9c7', fontSize: 13, fontWeight: '700', marginBottom: 7, marginTop: 12 },
  input: {
    backgroundColor: '#07121b',
    borderColor: '#ffffff22',
    borderRadius: 10,
    borderWidth: 1,
    color: colors.text,
    fontSize: 15,
    minHeight: 50,
    paddingHorizontal: 14,
  },
  button: {
    alignItems: 'center',
    backgroundColor: colors.amber,
    borderRadius: 10,
    justifyContent: 'center',
    marginTop: 20,
    minHeight: 50,
  },
  buttonDisabled: { backgroundColor: '#8b704e' },
  buttonPressed: { opacity: 0.85 },
  buttonText: { color: '#17212a', fontSize: 15, fontWeight: '800' },
  error: { color: colors.danger, fontSize: 13, lineHeight: 19, marginTop: 12 },
  configError: { color: colors.amberLight, fontSize: 13, lineHeight: 19, marginTop: 12 },
  footer: { color: '#74838a', fontSize: 12, marginTop: 22, textAlign: 'center' },
});
