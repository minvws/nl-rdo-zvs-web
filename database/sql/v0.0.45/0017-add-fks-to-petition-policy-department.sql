
ALTER TABLE petition_policy_department
ADD CONSTRAINT fk_petition_policy_department_petition_id
FOREIGN KEY (petition_id) REFERENCES petitions(id)
ON DELETE RESTRICT;

ALTER TABLE petition_policy_department
ADD CONSTRAINT fk_petition_policy_department_department_id
FOREIGN KEY (policy_department_id) REFERENCES policy_departments(id)
ON DELETE RESTRICT;
