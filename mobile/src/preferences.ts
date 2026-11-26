import * as SecureStore from 'expo-secure-store';

function key(name: 'biometric' | 'reminders', userId: number): string {
  return `transport-${name}-${userId}`;
}

export async function preferenceEnabled(
  name: 'biometric' | 'reminders',
  userId: number,
): Promise<boolean> {
  return (await SecureStore.getItemAsync(key(name, userId))) === 'true';
}

export async function setPreference(
  name: 'biometric' | 'reminders',
  userId: number,
  enabled: boolean,
): Promise<void> {
  const preferenceKey = key(name, userId);
  if (enabled) {
    await SecureStore.setItemAsync(preferenceKey, 'true');
  } else {
    await SecureStore.deleteItemAsync(preferenceKey);
  }
}
