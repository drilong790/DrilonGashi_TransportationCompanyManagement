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
            ? <ActivityIndicator color="#fff" />
            : <Text style={styles.buttonText}>Hyr në aplikacion</Text>}
        </Pressable>
      </View>
      <Text style={styles.footer}>Qasje e sigurt për ekipin e transportit</Text>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, justifyContent: 'center', paddingHorizontal: 24 },
  brand: { alignItems: 'center', marginBottom: 28 },
  mark: {
    alignItems: 'center',
    backgroundColor: '#078b81',
    borderRadius: 18,
    height: 56,
    justifyContent: 'center',
    marginBottom: 12,
    width: 56,
  },
  markText: { color: '#fff', fontSize: 28, fontWeight: '900' },
  brandName: { color: '#15263b', fontSize: 18, fontWeight: '900', letterSpacing: 2 },
  brandCaption: { color: '#7b8998', fontSize: 13, marginTop: 4 },
  card: {
    backgroundColor: '#fff',
    borderColor: '#e5ebf0',
    borderRadius: 22,
    borderWidth: 1,
    elevation: 3,
    padding: 22,
    shadowColor: '#17314c',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.06,
    shadowRadius: 18,
  },
  title: { color: '#15263b', fontSize: 24, fontWeight: '800' },
  subtitle: { color: '#738195', fontSize: 14, lineHeight: 21, marginBottom: 18, marginTop: 7 },
  label: { color: '#304258', fontSize: 13, fontWeight: '700', marginBottom: 7, marginTop: 12 },
  input: {
    backgroundColor: '#fbfcfd',
    borderColor: '#dce4ec',
    borderRadius: 12,
    borderWidth: 1,
    color: '#15263b',
    fontSize: 15,
    minHeight: 50,
    paddingHorizontal: 14,
  },
  button: {
    alignItems: 'center',
    backgroundColor: '#078b81',
    borderRadius: 12,
    justifyContent: 'center',
    marginTop: 20,
    minHeight: 50,
  },
  buttonDisabled: { backgroundColor: '#92b8b4' },
  buttonPressed: { opacity: 0.85 },
  buttonText: { color: '#fff', fontSize: 15, fontWeight: '800' },
  error: { color: '#b83434', fontSize: 13, lineHeight: 19, marginTop: 12 },
  configError: { color: '#a76a15', fontSize: 13, lineHeight: 19, marginTop: 12 },
  footer: { color: '#91a0b0', fontSize: 12, marginTop: 22, textAlign: 'center' },
});
