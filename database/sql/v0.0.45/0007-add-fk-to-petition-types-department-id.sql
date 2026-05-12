
ALTER TABLE petition_types
ADD CONSTRAINT fk_petition_types_department_id
FOREIGN KEY (department_id) REFERENCES departments(id)
ON DELETE RESTRICT;
