
ALTER TABLE petition_exports
ADD CONSTRAINT fk_petition_exports_petition_category_id
FOREIGN KEY (petition_category_id) REFERENCES petition_categories(id)
ON DELETE RESTRICT;
