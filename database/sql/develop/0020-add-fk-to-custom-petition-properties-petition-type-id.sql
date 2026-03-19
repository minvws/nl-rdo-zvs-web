
ALTER TABLE custom_petition_properties
ADD CONSTRAINT fk_custom_petition_properties_petition_type_id
FOREIGN KEY (petition_type_id) REFERENCES petition_types(id)
ON DELETE RESTRICT;
