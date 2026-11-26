import * as SQLite from 'expo-sqlite';
import { Transport, TransportInput, TransportResources, TransportStatus } from './types';

type QueuedTransportStatus = {
  transport_id: number;
  status: 'IN_PROGRESS' | 'COMPLETED';
};

type QueuedState = 'pending' | 'failed';
type QueuedActionBase = { id: number; state: QueuedState; error: string | null };
export type QueuedAction = QueuedActionBase & (
  | { type: 'create_transport'; payload: TransportInput }
  | { type: 'driver_status'; payload: QueuedTransportStatus }
);

const database = SQLite.openDatabaseAsync('transport-offline.db');

async function getDatabase(): Promise<SQLite.SQLiteDatabase> {
  const db = await database;
  await db.execAsync(`
    PRAGMA journal_mode = WAL;
    CREATE TABLE IF NOT EXISTS cached_transports (
      user_id INTEGER NOT NULL,
      transport_id INTEGER NOT NULL,
      payload TEXT NOT NULL,
      PRIMARY KEY (user_id, transport_id)
    );
    CREATE TABLE IF NOT EXISTS cached_resources (
      user_id INTEGER PRIMARY KEY,
      payload TEXT NOT NULL
    );
    CREATE TABLE IF NOT EXISTS queued_actions (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER NOT NULL,
      action TEXT NOT NULL,
      payload TEXT NOT NULL,
      state TEXT NOT NULL DEFAULT 'pending',
      error TEXT,
      created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    );
  `);
  return db;
}

export async function cacheTransports(userId: number, transports: Transport[]): Promise<void> {
  const db = await getDatabase();
  await db.execAsync('BEGIN TRANSACTION');
  try {
    await db.runAsync('DELETE FROM cached_transports WHERE user_id = ?', userId);
    for (const transport of transports) {
      await db.runAsync(
        'INSERT OR REPLACE INTO cached_transports (user_id, transport_id, payload) VALUES (?, ?, ?)',
        userId,
        transport.id,
        JSON.stringify(transport),
      );
    }
    await db.execAsync('COMMIT');
  } catch (error) {
    await db.execAsync('ROLLBACK');
    throw error;
  }
}

export async function loadCachedTransports(userId: number): Promise<Transport[]> {
  const db = await getDatabase();
  const rows = await db.getAllAsync<{ payload: string }>(
    'SELECT payload FROM cached_transports WHERE user_id = ? ORDER BY transport_id DESC',
    userId,
  );
  const transports = rows.map(({ payload }) => {
    const parsed: unknown = JSON.parse(payload);
    if (!isTransport(parsed)) {
      throw new Error('Të dhënat lokale të transporteve nuk janë të vlefshme.');
    }
    return parsed;
  });
  return transports.sort((first, second) =>
    second.scheduled_date.localeCompare(first.scheduled_date)
  );
}

export async function cacheTransportResources(userId: number, resources: TransportResources): Promise<void> {
  const db = await getDatabase();
  await db.runAsync(
    'INSERT OR REPLACE INTO cached_resources (user_id, payload) VALUES (?, ?)',
    userId,
    JSON.stringify(resources),
  );
}

export async function loadCachedTransportResources(userId: number): Promise<TransportResources | null> {
  const db = await getDatabase();
  const row = await db.getFirstAsync<{ payload: string }>(
    'SELECT payload FROM cached_resources WHERE user_id = ?',
    userId,
  );
  if (!row) return null;
  const parsed: unknown = JSON.parse(row.payload);
  if (!isTransportResources(parsed)) {
    throw new Error('Të dhënat lokale të formularit nuk janë të vlefshme.');
  }
  return parsed;
}

export async function updateCachedTransport(
  userId: number,
  transportId: number,
  status: TransportStatus,
): Promise<void> {
  const db = await getDatabase();
  const row = await db.getFirstAsync<{ payload: string }>(
    'SELECT payload FROM cached_transports WHERE user_id = ? AND transport_id = ?',
    userId,
    transportId,
  );
  if (!row) return;
  const parsed: unknown = JSON.parse(row.payload);
  if (!isTransport(parsed)) {
    throw new Error('Të dhënat lokale të transportit nuk janë të vlefshme.');
  }
  await db.runAsync(
    'UPDATE cached_transports SET payload = ? WHERE user_id = ? AND transport_id = ?',
    JSON.stringify({ ...parsed, status }),
    userId,
    transportId,
  );
}

export async function queueTransport(userId: number, payload: TransportInput): Promise<void> {
  await enqueue(userId, 'create_transport', payload);
}

export async function queueDriverStatus(
  userId: number,
  payload: QueuedTransportStatus,
): Promise<void> {
  await enqueue(userId, 'driver_status', payload);
}

async function enqueue(
  userId: number,
  action: QueuedAction['type'],
  payload: QueuedAction['payload'],
): Promise<void> {
  const db = await getDatabase();
  await db.runAsync(
    'INSERT INTO queued_actions (user_id, action, payload) VALUES (?, ?, ?)',
    userId,
    action,
    JSON.stringify(payload),
  );
}

export async function loadQueuedActions(userId: number): Promise<QueuedAction[]> {
  const db = await getDatabase();
  const rows = await db.getAllAsync<{
    id: number;
    action: string;
    payload: string;
    state: string;
    error: string | null;
  }>(
    'SELECT id, action, payload, state, error FROM queued_actions WHERE user_id = ? ORDER BY id',
    userId,
  );
  return rows.map((row) => {
    const payload: unknown = JSON.parse(row.payload);
    if (!isRecord(payload) || (row.state !== 'pending' && row.state !== 'failed')) {
      throw new Error('Radha lokale për sinkronizim nuk është e vlefshme.');
    }
    const base: QueuedActionBase = {
      id: row.id,
      state: row.state,
      error: row.error,
    };
    if (row.action === 'create_transport' && isTransportInput(payload)) {
      return { ...base, type: row.action, payload };
    }
    if (row.action === 'driver_status' && isQueuedTransportStatus(payload)) {
      return { ...base, type: row.action, payload };
    }
    throw new Error('Radha lokale për sinkronizim nuk është e vlefshme.');
  });
}

export async function markQueuedActionFailed(id: number, error: string): Promise<void> {
  const db = await getDatabase();
  await db.runAsync(
    'UPDATE queued_actions SET state = ?, error = ? WHERE id = ?',
    'failed',
    error,
    id,
  );
}

export async function retryQueuedAction(id: number): Promise<void> {
  const db = await getDatabase();
  await db.runAsync(
    'UPDATE queued_actions SET state = ?, error = NULL WHERE id = ?',
    'pending',
    id,
  );
}

export async function removeQueuedAction(id: number): Promise<void> {
  const db = await getDatabase();
  await db.runAsync('DELETE FROM queued_actions WHERE id = ?', id);
}

export async function clearUserOfflineData(userId: number): Promise<void> {
  const db = await getDatabase();
  await db.runAsync('DELETE FROM cached_transports WHERE user_id = ?', userId);
  await db.runAsync('DELETE FROM cached_resources WHERE user_id = ?', userId);
  await db.runAsync('DELETE FROM queued_actions WHERE user_id = ?', userId);
}

export async function clearCachedTransports(userId: number): Promise<void> {
  const db = await getDatabase();
  await db.runAsync('DELETE FROM cached_transports WHERE user_id = ?', userId);
  await db.runAsync('DELETE FROM cached_resources WHERE user_id = ?', userId);
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value);
}

function isTransport(value: unknown): value is Transport {
  if (!isRecord(value)) return false;
  return typeof value.id === 'number'
    && typeof value.customer_id === 'number'
    && typeof value.customer_name === 'string'
    && (value.driver_id === null || typeof value.driver_id === 'number')
    && (value.vehicle_id === null || typeof value.vehicle_id === 'number')
    && typeof value.pickup_address === 'string'
    && typeof value.destination_address === 'string'
    && typeof value.cargo_description === 'string'
    && typeof value.scheduled_date === 'string'
    && typeof value.scheduled_end === 'string'
    && ['PLANNED', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED'].includes(String(value.status))
    && typeof value.created_by_name === 'string';
}

function isTransportInput(value: unknown): value is TransportInput {
  if (!isRecord(value)) return false;
  return Number.isInteger(value.customer_id)
    && (value.driver_id === null || Number.isInteger(value.driver_id))
    && (value.vehicle_id === null || Number.isInteger(value.vehicle_id))
    && typeof value.pickup_address === 'string'
    && typeof value.destination_address === 'string'
    && typeof value.cargo_description === 'string'
    && typeof value.scheduled_date === 'string'
    && typeof value.scheduled_end === 'string'
    && ['PLANNED', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED'].includes(String(value.status));
}

function isTransportResources(value: unknown): value is TransportResources {
  if (!isRecord(value)) return false;
  return ['customers', 'drivers', 'vehicles'].every((key) => {
    const items = value[key];
    return Array.isArray(items) && items.every((item) => isRecord(item)
      && Number.isInteger(item.id)
      && typeof item.label === 'string');
  });
}

function isQueuedTransportStatus(value: unknown): value is QueuedTransportStatus {
  if (!isRecord(value)) return false;
  return Number.isInteger(value.transport_id)
    && (value.status === 'IN_PROGRESS' || value.status === 'COMPLETED');
}
