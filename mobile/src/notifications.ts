import { Platform } from 'react-native';
import * as Notifications from 'expo-notifications';
import { AndroidImportance } from 'expo-notifications';
import { Transport } from './types';

const REMINDER_KIND = 'transport-start-reminder';
const REMINDER_LEAD_TIME_MS = 60 * 60 * 1000;
const MAX_SCHEDULED_REMINDERS = 50;
const REMINDER_WINDOW_MS = 14 * 24 * 60 * 60 * 1000;

Notifications.setNotificationHandler({
  handleNotification: async () => ({
    shouldPlaySound: true,
    shouldSetBadge: false,
    shouldShowBanner: true,
    shouldShowList: true,
  }),
});

export async function requestReminderPermission(): Promise<boolean> {
  if (Platform.OS === 'android') {
    await Notifications.setNotificationChannelAsync('transport-reminders', {
      name: 'Kujtesat e transporteve',
      importance: AndroidImportance.DEFAULT,
    });
  }
  const current = await Notifications.getPermissionsAsync();
  if (current.granted) return true;
  const requested = await Notifications.requestPermissionsAsync();
  return requested.granted;
}

export async function scheduleTransportReminders(transports: Transport[]): Promise<void> {
  const scheduled = await Notifications.getAllScheduledNotificationsAsync();
  for (const notification of scheduled) {
    if (notification.content.data?.kind === REMINDER_KIND) {
      await Notifications.cancelScheduledNotificationAsync(notification.identifier);
    }
  }

  const now = Date.now();
  const upcoming = transports
    .filter((transport) => transport.status === 'PLANNED')
    .map((transport) => ({
      transport,
      start: new Date(transport.scheduled_date.replace(' ', 'T')).getTime(),
    }))
    .filter(({ start }) => start - now > REMINDER_LEAD_TIME_MS && start - now < REMINDER_WINDOW_MS)
    .sort((first, second) => first.start - second.start)
    .slice(0, MAX_SCHEDULED_REMINDERS);

  for (const { transport, start } of upcoming) {
    const reminderAt = new Date(start - REMINDER_LEAD_TIME_MS);
    await Notifications.scheduleNotificationAsync({
      content: {
        title: 'Transporti fillon së shpejti',
        body: `${transport.customer_name}: nisja në ${transport.pickup_address}`,
        data: { kind: REMINDER_KIND, url: `/transport/${transport.id}` },
        sound: true,
      },
      trigger: {
        type: Notifications.SchedulableTriggerInputTypes.DATE,
        date: reminderAt,
        channelId: Platform.OS === 'android' ? 'transport-reminders' : undefined,
      },
    });
  }
}

export async function clearTransportReminders(): Promise<void> {
  const scheduled = await Notifications.getAllScheduledNotificationsAsync();
  for (const notification of scheduled) {
    if (notification.content.data?.kind === REMINDER_KIND) {
      await Notifications.cancelScheduledNotificationAsync(notification.identifier);
    }
  }
}
