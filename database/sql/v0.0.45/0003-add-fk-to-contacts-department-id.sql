
ALTER TABLE contacts
ADD CONSTRAINT fk_contacts_department_id
FOREIGN KEY (department_id) REFERENCES departments(id)
ON DELETE RESTRICT;
