CREATE TABLE contact_petition (
    id bigserial PRIMARY KEY,
    contact_id uuid NOT NULL,
    petition_id uuid NOT NULL,
    role VARCHAR(32) NOT NULL
);

ALTER TABLE contact_petition
    ADD CONSTRAINT fk_contact_petition_contact FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE;
ALTER TABLE contact_petition
    ADD CONSTRAINT fk_contact_petition_petition FOREIGN KEY (petition_id) REFERENCES petitions(id) ON DELETE CASCADE;
CREATE UNIQUE INDEX contact_petition_petition_id_role_unique ON contact_petition(petition_id, role);
CREATE INDEX contact_petition_contact_id_index ON contact_petition(contact_id);
CREATE INDEX contact_petition_petition_id_index ON contact_petition(petition_id);

ALTER TABLE contact_petition OWNER TO "cts";
