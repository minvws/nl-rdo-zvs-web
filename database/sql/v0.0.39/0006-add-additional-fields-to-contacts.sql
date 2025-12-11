ALTER TABLE contacts
    ADD COLUMN secondary_email_address VARCHAR(255) COLLATE "en_US.utf8" NULL ,
    ADD COLUMN middle_name VARCHAR(64) COLLATE "en_US.utf8" NULL;