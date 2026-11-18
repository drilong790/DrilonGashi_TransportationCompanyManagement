import * as SecureStore from 'expo-secure-store';
import { ApiError, Transport, TransportInput, TransportResources, User } from './types';
export { ApiError } from './types';

const TOKEN_KEY = 'transport-mobile-token';

export const apiBaseUrl = (process.env.EXPO_PUBLIC_API_URL ?? '').trim().replace(/\/+$/, '');

async function request<T>(
  action: string,
  method: 'GET' | 'POST' | 'PUT',
  token?: string,
  body?: unknown,
  query: Record<string, string> = {},
): Promise<T> {
  if (!apiBaseUrl) {
    throw new ApiError(
      'Konfiguro EXPO_PUBLIC_API_URL në mobile/.env dhe rinis Expo.',
      0,
    );
  }

  const search = new URLSearchParams({ action, ...query });
  let response: Response;
  try {
    response = await fetch(`${apiBaseUrl}?${search.toString()}`, {
      method,
      headers: {
        Accept: 'application/json',
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
        ...(body ? { 'Content-Type': 'application/json' } : {}),
      },
      ...(body ? { body: JSON.stringify(body) } : {}),
    });
  } catch {
    throw new ApiError(
      'Nuk u lidhëm me serverin. Kontrollo Wi-Fi-n dhe adresën lokale të API-së.',
      0,
    );
  }

  let payload: { data?: T; error?: string; warnings?: string[] };
  try {
    payload = await response.json();
  } catch {
    throw new ApiError('Serveri ktheu përgjigje të pavlefshme.', response.status);
  }
  if (!response.ok) {
    throw new ApiError(payload.error ?? 'Kërkesa nuk u krye.', response.status);
  }
  return payload as T;
}

export async function signIn(email: string, password: string): Promise<{ token: string; user: User }> {
  const result = await request<{ data: { token: string; user: User } }>(
    'login',
    'POST',
    undefined,
    { email, password },
  );
  await SecureStore.setItemAsync(TOKEN_KEY, result.data.token);
  return result.data;
}

export async function storedToken(): Promise<string | null> {
  return SecureStore.getItemAsync(TOKEN_KEY);
}

export async function clearToken(): Promise<void> {
  await SecureStore.deleteItemAsync(TOKEN_KEY);
}

export async function currentUser(token: string): Promise<User> {
  const result = await request<{ data: User }>('me', 'GET', token);
  return result.data;
}

export async function signOut(token: string): Promise<void> {
  try {
    await request('logout', 'POST', token, {});
  } finally {
    await clearToken();
  }
}

export async function loadResources(token: string): Promise<TransportResources> {
  const result = await request<{
    data: {
      customers: { id: number; company_name: string }[];
      drivers: { id: number; full_name: string }[];
      vehicles: { id: number; plate_number: string; vehicle_type: string }[];
    };
  }>('resources', 'GET', token);
  return {
    customers: result.data.customers.map((item) => ({ id: Number(item.id), label: item.company_name })),
    drivers: result.data.drivers.map((item) => ({ id: Number(item.id), label: item.full_name })),
    vehicles: result.data.vehicles.map((item) => ({
      id: Number(item.id),
      label: `${item.plate_number} — ${item.vehicle_type}`,
    })),
  };
}

export async function loadTransports(
  token: string,
  filters: Record<string, string>,
): Promise<Transport[]> {
  const result = await request<{ data: Transport[] }>('transports', 'GET', token, undefined, filters);
  return result.data.map(normalizeTransport);
}

export async function loadTransport(token: string, id: number): Promise<Transport> {
  const result = await request<{ data: Transport }>('transport', 'GET', token, undefined, {
    id: String(id),
  });
  return normalizeTransport(result.data);
}

export async function saveTransport(
  token: string,
  id: number | null,
  values: TransportInput,
): Promise<{ transport: Transport; warnings: string[] }> {
  const result = await request<{ data: Transport; warnings?: string[] }>(
    'transport',
    id === null ? 'POST' : 'PUT',
    token,
    values,
    id === null ? {} : { id: String(id) },
  );
  return { transport: result.data, warnings: result.warnings ?? [] };
}

function normalizeTransport(transport: Transport): Transport {
  return {
    ...transport,
    id: Number(transport.id),
    customer_id: Number(transport.customer_id),
    driver_id: transport.driver_id === null ? null : Number(transport.driver_id),
    vehicle_id: transport.vehicle_id === null ? null : Number(transport.vehicle_id),
  };
}
