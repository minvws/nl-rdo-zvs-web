ALTER TABLE departments
    ADD COLUMN config_key VARCHAR(255);

UPDATE departments
    SET config_key = slug;

ALTER TABLE departments
    ALTER COLUMN config_key SET NOT NULL;

-- Add a new column 'config_key' to the 'departments' table
-- This column will be used to store a unique configuration identifier for each department