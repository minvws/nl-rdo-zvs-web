CREATE TABLE petition_type_custom_dates_labels (
    internal_id serial PRIMARY KEY,
    petition_type_id uuid NOT NULL,
    date_label VARCHAR(255) NOT NULL,
    created_at TIMESTAMP(0) with time zone NOT NULL,
    updated_at TIMESTAMP(0) with time zone NOT NULL
);

ALTER TABLE petition_type_custom_dates_labels
    owner TO "cts";