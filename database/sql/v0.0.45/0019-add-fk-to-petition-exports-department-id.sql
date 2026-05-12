
ALTER TABLE petition_exports
ADD CONSTRAINT fk_petition_exports_department_id
FOREIGN KEY (department_id) REFERENCES departments(id)
ON DELETE RESTRICT;
