CREATE TABLE "notification_rule_deliveries" (
    "id" uuid NOT NULL PRIMARY KEY,
    "notification_rule_id" uuid NOT NULL,
    "petition_id" uuid NOT NULL,
    "anchor_date" date NOT NULL,
    "trigger_date" date NOT NULL,
    "created_at" TIMESTAMP(0) with time zone NOT NULL
);

ALTER TABLE "notification_rule_deliveries" OWNER TO "cts";

ALTER TABLE "notification_rule_deliveries"
    ADD CONSTRAINT fk_notification_rule_deliveries_notification_rule_id
    FOREIGN KEY (notification_rule_id) REFERENCES notification_rules (id)
    ON DELETE CASCADE;

ALTER TABLE "notification_rule_deliveries"
    ADD CONSTRAINT fk_notification_rule_deliveries_petition_id
    FOREIGN KEY (petition_id) REFERENCES petitions (id)
    ON DELETE CASCADE;

CREATE UNIQUE INDEX "notification_rule_deliveries_unique"
    ON "notification_rule_deliveries" (notification_rule_id, petition_id, anchor_date, trigger_date);

CREATE INDEX "notification_rule_deliveries_petition_id_index" ON "notification_rule_deliveries" (petition_id);
