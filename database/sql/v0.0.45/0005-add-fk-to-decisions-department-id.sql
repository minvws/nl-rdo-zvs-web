
ALTER TABLE decisions
ADD CONSTRAINT fk_decisions_department_id
FOREIGN KEY (department_id) REFERENCES departments(id)
ON DELETE RESTRICT;
