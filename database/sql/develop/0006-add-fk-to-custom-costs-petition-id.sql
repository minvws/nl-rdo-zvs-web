
ALTER TABLE custom_costs
ADD CONSTRAINT fk_custom_costs_petition_id
FOREIGN KEY (petition_id) REFERENCES petitions(id)
ON DELETE RESTRICT;
