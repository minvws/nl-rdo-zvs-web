ALTER TABLE "petition_statuses"
    ADD COLUMN "order" integer NOT NULL DEFAULT 0,
    ADD COLUMN "department_id" uuid,
    ADD COLUMN "status_group" varchar(255),
    ADD COLUMN "fg_color" varchar(7),
    ADD COLUMN "bg_color" varchar(7),
    DROP COLUMN "badge",
    DROP COLUMN "default_status"
;

CREATE TABLE "petition_statuses_history_entries"
(
    "petition_id" uuid NOT NULL,
    "petition_status_id" uuid NOT NULL,
    "created_at" timestamp with time zone NOT NULL
);

ALTER TABLE
    "petition_statuses_history_entries"
    owner TO "cts";
