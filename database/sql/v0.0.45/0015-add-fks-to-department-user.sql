
ALTER TABLE department_user
ADD CONSTRAINT fk_department_user_department_id
FOREIGN KEY (department_id) REFERENCES departments(id)
ON DELETE RESTRICT;

ALTER TABLE department_user
ADD CONSTRAINT fk_department_user_user_id
FOREIGN KEY (user_id) REFERENCES users(id)
ON DELETE RESTRICT;
