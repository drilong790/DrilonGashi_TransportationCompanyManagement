import { useEffect } from 'react';
import * as Notifications from 'expo-notifications';
import { router, Stack } from 'expo-router';
import { colors } from '../theme';

export default function RootLayout() {
  return (
    <>
      <NotificationRouter />
      <Stack
        screenOptions={{
          headerShown: false,
          contentStyle: { backgroundColor: colors.background },
        }}
      />
    </>
  );
}

function NotificationRouter() {
  useEffect(() => {
    const openTransport = (notification: Notifications.Notification) => {
      const url = notification.request.content.data?.url;
      if (typeof url === 'string' && /^\/transport\/\d+$/.test(url)) {
        router.push(url);
        Notifications.clearLastNotificationResponse();
      }
    };

    const lastResponse = Notifications.getLastNotificationResponse();
    if (lastResponse) openTransport(lastResponse.notification);
    const subscription = Notifications.addNotificationResponseReceivedListener((response) => {
      openTransport(response.notification);
    });
    return () => subscription.remove();
  }, []);

  return null;
}
