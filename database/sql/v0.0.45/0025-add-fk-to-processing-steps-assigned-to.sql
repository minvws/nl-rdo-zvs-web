
ALTER TABLE processing_steps
ADD CONSTRAINT fk_processing_steps_assigned_to
FOREIGN KEY (assigned_to) REFERENCES users(id)
ON DELETE RESTRICT;
