import { ApiError, saveTransport, updateDriverStatus } from './api';
import {
  loadQueuedActions,
  markQueuedActionFailed,
  removeQueuedAction,
} from './offline';

export async function syncQueuedActions(
  userId: number,
  token: string,
): Promise<{ synced: number; pending: number }> {
  const actions = await loadQueuedActions(userId);
  let synced = 0;
  for (const action of actions) {
    if (action.state === 'failed') break;
    try {
      if (action.type === 'create_transport') {
        await saveTransport(token, null, action.payload);
      } else {
        await updateDriverStatus(
          token,
          action.payload.transport_id,
          action.payload.status,
        );
      }
      await removeQueuedAction(action.id);
      synced += 1;
    } catch (error) {
      if (error instanceof ApiError && error.status === 0) break;
      const message = error instanceof Error ? error.message : 'Sinkronizimi dështoi.';
      await markQueuedActionFailed(action.id, message);
      break;
    }
  }
  const remaining = await loadQueuedActions(userId);
  return { synced, pending: remaining.length };
}
