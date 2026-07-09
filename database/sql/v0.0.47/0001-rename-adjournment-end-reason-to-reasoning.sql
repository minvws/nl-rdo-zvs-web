ALTER TABLE petition_events
    RENAME COLUMN adjournment_end_reason TO reasoning;

ALTER TABLE petition_events
    ALTER COLUMN reasoning TYPE TEXT;

