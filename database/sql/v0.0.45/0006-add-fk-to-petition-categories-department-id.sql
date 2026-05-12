
ALTER TABLE petition_categories
ADD CONSTRAINT fk_petition_categories_department_id
FOREIGN KEY (department_id) REFERENCES departments(id)
ON DELETE RESTRICT;
