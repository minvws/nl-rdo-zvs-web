ALTER TABLE petition_statuses_history_entries
    ADD COLUMN id uuid PRIMARY KEY NOT NULL DEFAULT gen_random_uuid() ;