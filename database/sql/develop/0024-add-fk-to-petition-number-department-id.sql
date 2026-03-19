
ALTER TABLE petition_number
ADD CONSTRAINT fk_petition_number_department_id
FOREIGN KEY (department_id) REFERENCES departments(id)
ON DELETE RESTRICT;
