-- Create petition_custom_dates table to normalize custom dates from JSON to relational structure
-- This table will replace the custom_dates JSONB column in the petitions table

CREATE TABLE petition_custom_dates (
    id uuid PRIMARY KEY NOT NULL DEFAULT gen_random_uuid(),
    petition_id uuid NOT NULL,
    date_label varchar(255) NOT NULL,
    date DATE,
    created_at TIMESTAMP(0) with time zone NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP(0) with time zone NOT NULL DEFAULT NOW()
);

-- Add foreign key constraint and index for performance
ALTER TABLE petition_custom_dates
    ADD CONSTRAINT petition_custom_dates_petition_id_foreign
    FOREIGN KEY (petition_id) REFERENCES petitions(id) ON DELETE CASCADE;

-- Add index on petition_id for performance
CREATE INDEX petition_custom_dates_petition_id_index ON petition_custom_dates(petition_id);

-- Add composite index for common query patterns
CREATE INDEX petition_custom_dates_petition_id_date_label_index ON petition_custom_dates(petition_id, date_label);

-- Set table owner
ALTER TABLE petition_custom_dates OWNER TO "cts";
