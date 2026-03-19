
ALTER TABLE petition_petition
ADD CONSTRAINT fk_petition_petition_parent_petition_id
FOREIGN KEY (related_petition_id) REFERENCES petitions(id)
ON DELETE RESTRICT;

ALTER TABLE petition_petition
ADD CONSTRAINT fk_petition_petition_child_petition_id
FOREIGN KEY (petition_id) REFERENCES petitions(id)
ON DELETE RESTRICT;
