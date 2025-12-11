ALTER TABLE petition_statuses_history_entries
    ADD COLUMN date DATE;

UPDATE petition_statuses_history_entries
    SET date = (created_at AT TIME ZONE 'Europe/Amsterdam')::date;