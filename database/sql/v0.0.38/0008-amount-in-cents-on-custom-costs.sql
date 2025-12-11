ALTER TABLE custom_costs
    ADD COLUMN custom_cost_amount_in_cents BIGINT NOT NULL default 0;

UPDATE custom_costs
SET custom_cost_amount_in_cents = custom_cost_amount_in_euros * 100;
