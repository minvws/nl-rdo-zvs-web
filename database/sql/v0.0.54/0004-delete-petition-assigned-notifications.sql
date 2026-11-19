-- The PetitionAssigned notification was saved on every assignment while the bell was still
-- hidden behind the NOTIFICATIONS_ENABLED flag. The notification is gone and the flag is
-- removed, so delete those rows to keep users from seeing a pile of old unread notifications.
DELETE FROM notifications
WHERE type = 'petition-assigned';
