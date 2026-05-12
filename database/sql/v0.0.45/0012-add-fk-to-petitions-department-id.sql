
ALTER TABLE petitions
ADD CONSTRAINT fk_petitions_department_id
FOREIGN KEY (department_id) REFERENCES departments(id)
ON DELETE RESTRICT;
