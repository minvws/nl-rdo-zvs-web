
ALTER TABLE decision_petition
ADD CONSTRAINT fk_decision_petition_decision_id
FOREIGN KEY (decision_id) REFERENCES decisions(id)
ON DELETE RESTRICT;

ALTER TABLE decision_petition
ADD CONSTRAINT fk_decision_petition_petition_id
FOREIGN KEY (petition_id) REFERENCES petitions(id)
ON DELETE RESTRICT;
