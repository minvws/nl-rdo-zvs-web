ALTER TABLE petition_events
ADD COLUMN penalties JSONB DEFAULT NULL;

UPDATE petition_events
SET penalties = extra_data->'penalties'
WHERE extra_data IS NOT NULL AND extra_data ? 'penalties';

ALTER TABLE petition_events
DROP COLUMN extra_data;
