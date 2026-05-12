
ALTER TABLE custom_petition_property_petition
ADD CONSTRAINT fk_custom_petition_property_petition_property_id
FOREIGN KEY (custom_petition_property_id) REFERENCES custom_petition_properties(id)
ON DELETE RESTRICT;

ALTER TABLE custom_petition_property_petition
ADD CONSTRAINT fk_custom_petition_property_petition_petition_id
FOREIGN KEY (petition_id) REFERENCES petitions(id)
ON DELETE RESTRICT;
