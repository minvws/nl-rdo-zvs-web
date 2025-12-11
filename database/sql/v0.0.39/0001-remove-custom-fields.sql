ALTER TABLE custom_costs
    DROP COLUMN IF EXISTS custom_cost_amount_in_euros;

ALTER TABLE petitions
    DROP COLUMN IF EXISTS custom_dates;
