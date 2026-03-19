
ALTER TABLE petition_type_custom_dates_labels
ADD CONSTRAINT fk_petition_type_custom_dates_labels_petition_type_id
FOREIGN KEY (petition_type_id) REFERENCES petition_types(id)
ON DELETE RESTRICT;
