
ALTER TABLE petitions
ADD CONSTRAINT fk_petitions_petition_type_id
FOREIGN KEY (petition_type_id) REFERENCES petition_types(id)
ON DELETE RESTRICT;
