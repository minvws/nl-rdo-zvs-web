
ALTER TABLE petition_statuses
ADD CONSTRAINT fk_petition_statuses_petition_type_id
FOREIGN KEY (petition_type_id) REFERENCES petition_types(id)
ON DELETE RESTRICT;
