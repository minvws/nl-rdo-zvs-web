ALTER TABLE petition_terms
    ADD COLUMN description VARCHAR(255) NULL,
    ADD COLUMN legal_term_applied BOOLEAN DEFAULT FALSE NOT NULL;