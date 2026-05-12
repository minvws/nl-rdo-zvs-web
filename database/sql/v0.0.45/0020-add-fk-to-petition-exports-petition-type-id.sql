
ALTER TABLE petition_exports
ADD CONSTRAINT fk_petition_exports_petition_type_id
FOREIGN KEY (petition_type_id) REFERENCES petition_types(id)
ON DELETE RESTRICT;
