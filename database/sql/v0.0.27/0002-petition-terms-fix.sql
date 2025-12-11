UPDATE petition_terms SET duration_in_days=0 WHERE duration_in_days IS NULL;
UPDATE petition_terms SET penalty_amount_in_euros=0 WHERE penalty_amount_in_euros IS NULL;

ALTER TABLE petition_terms
    ALTER COLUMN duration_in_days SET NOT NULL;
ALTER TABLE petition_terms
    ALTER COLUMN penalty_amount_in_euros SET NOT NULL;
ALTER TABLE petition_terms
    ALTER COLUMN penalty_amount_in_euros SET DEFAULT 0;
