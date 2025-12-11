-- Add a notes field to contacts for unstructured information
ALTER TABLE contacts
    ADD COLUMN notes TEXT NULL;