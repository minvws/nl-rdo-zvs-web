ALTER TABLE contact_petition
    ADD COLUMN reference VARCHAR(255) NULL,
    ADD COLUMN correspondence_preference VARCHAR(50) NULL;

CREATE INDEX idx_contact_petition_reference ON contact_petition(reference) WHERE reference IS NOT NULL;
