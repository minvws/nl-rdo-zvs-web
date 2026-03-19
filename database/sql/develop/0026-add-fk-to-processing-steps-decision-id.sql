
ALTER TABLE processing_steps
ADD CONSTRAINT fk_processing_steps_decision_id
FOREIGN KEY (decision_id) REFERENCES decisions(id)
ON DELETE RESTRICT;
