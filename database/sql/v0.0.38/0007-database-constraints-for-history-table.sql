-- remove orphaned history entries
DELETE FROM petition_statuses_history_entries
WHERE petition_status_id NOT IN (
    SELECT id FROM petition_statuses
);

ALTER TABLE petition_statuses_history_entries
    ALTER COLUMN petition_id SET NOT NULL,
    ALTER COLUMN petition_status_id SET NOT NULL,
    ALTER COLUMN created_at SET NOT NULL;

ALTER TABLE petition_statuses_history_entries
    ADD CONSTRAINT petition_statuses_history_entries_petition_id_fk FOREIGN KEY (petition_id)
        REFERENCES petitions(id) ON DELETE CASCADE;

ALTER TABLE petition_statuses_history_entries
    ADD CONSTRAINT petition_statuses_history_entries_petition_status_id_fk FOREIGN KEY (petition_status_id)
        REFERENCES petition_statuses(id) ON DELETE RESTRICT;