CREATE TABLE "custom_costs" (
    id uuid NOT NULL PRIMARY KEY,
    petition_id uuid NOT NULL,
    custom_cost_type varchar(50) NOT NULL,
    custom_cost_amount_in_euros int NOT NULL,
    created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
    updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL
);

ALTER TABLE
    "custom_costs"
    OWNER TO "cts";