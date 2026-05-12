
ALTER TABLE petitions
ADD CONSTRAINT fk_petitions_petition_status_id
FOREIGN KEY (petition_status_id) REFERENCES petition_statuses(id)
ON DELETE RESTRICT;
