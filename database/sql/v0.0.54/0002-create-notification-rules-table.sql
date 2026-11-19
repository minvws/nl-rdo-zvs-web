CREATE TABLE "notification_rules" (
    "id" uuid NOT NULL PRIMARY KEY,
    "department_id" uuid NOT NULL,
    "anchor" varchar(32) NOT NULL,
    "event_type" varchar(64) NOT NULL,
    "offset_days" smallint NOT NULL CHECK ("offset_days" BETWEEN -365 AND 365),
    "recipient" varchar(32) NOT NULL DEFAULT 'primary_assignee',
    "active" boolean NOT NULL DEFAULT true,
    "active_from" date NOT NULL,
    "created_at" TIMESTAMP(0) with time zone NOT NULL,
    "updated_at" TIMESTAMP(0) with time zone NOT NULL
);

ALTER TABLE "notification_rules" OWNER TO "cts";

ALTER TABLE "notification_rules"
    ADD CONSTRAINT fk_notification_rules_department_id
    FOREIGN KEY (department_id) REFERENCES departments (id)
    ON DELETE RESTRICT;

CREATE INDEX "notification_rules_department_id_index" ON "notification_rules" (department_id);
