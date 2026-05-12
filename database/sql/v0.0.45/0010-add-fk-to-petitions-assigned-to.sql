
ALTER TABLE petitions
ADD CONSTRAINT fk_petitions_assigned_to
FOREIGN KEY (assigned_to) REFERENCES users(id)
ON DELETE RESTRICT;
