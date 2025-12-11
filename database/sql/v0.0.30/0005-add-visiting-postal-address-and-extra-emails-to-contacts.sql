-- Add visiting and postal address fields, and extra email addresses to contacts table
ALTER TABLE contacts
    ADD COLUMN visiting_address_street VARCHAR(255) NULL,
    ADD COLUMN visiting_address_house_number VARCHAR(20) NULL,
    ADD COLUMN visiting_address_postal_code VARCHAR(20) NULL,
    ADD COLUMN visiting_address_city VARCHAR(255) NULL,
    ADD COLUMN postal_address_street VARCHAR(255) NULL,
    ADD COLUMN postal_address_house_number VARCHAR(20) NULL,
    ADD COLUMN postal_address_postal_code VARCHAR(20) NULL,
    ADD COLUMN postal_address_city VARCHAR(255) NULL,
    ADD COLUMN email_address_2 VARCHAR(255) NULL,
    ADD COLUMN email_address_3 VARCHAR(255) NULL;
