DROP INDEX IF EXISTS contact_petition_petition_id_role_unique;

CREATE UNIQUE INDEX contact_petition_petition_id_contact_id_role_unique ON contact_petition(petition_id, contact_id, role);