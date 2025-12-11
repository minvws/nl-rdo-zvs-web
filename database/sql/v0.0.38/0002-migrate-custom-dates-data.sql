-- Migrate existing custom_dates data from JSON column to petition_custom_dates table
-- This script extracts data from petitions.custom_dates JSONB and inserts it into the new normalized table

DO $$
DECLARE
    petition_record RECORD;
    custom_date_item JSONB;
    date_value DATE;
    date_label_value TEXT;
BEGIN
    -- Iterate through all petitions that have non-empty custom_dates
    FOR petition_record IN
        SELECT id, custom_dates
        FROM petitions
        WHERE custom_dates IS NOT NULL
        AND custom_dates != '[]'::JSONB
        AND jsonb_array_length(custom_dates) > 0
    LOOP
        -- Iterate through each custom date in the JSON array
        FOR custom_date_item IN
            SELECT jsonb_array_elements(petition_record.custom_dates)
        LOOP
            -- Extract the date and label from the JSON object
            date_value := (custom_date_item->>'date')::DATE;
            date_label_value := custom_date_item->>'date_label';

            -- Only insert if date is not null
            IF date_value IS NOT NULL THEN
                INSERT INTO petition_custom_dates (petition_id, date_label, date, created_at, updated_at)
                VALUES (
                    petition_record.id,
                    date_label_value,
                    date_value,
                    NOW(),
                    NOW()
                );
            END IF;
        END LOOP;
    END LOOP;

    RAISE NOTICE 'Custom dates migration completed successfully';
END $$;
