CREATE TABLE "decision_processing_steps" (
    "id" uuid NOT NULL PRIMARY KEY,
    "name" varchar(255) NOT NULL,
    "decision_id" uuid NOT NULL,
    "deadline_at" DATE NOT NULL,
    "status" varchar(20) NOT NULL,
    "assigned_to" uuid,
    "created_at" TIMESTAMP(0) with time zone NOT NULL,
    "updated_at" TIMESTAMP(0) with time zone NOT NULL
);

ALTER TABLE "decision_processing_steps"
 OWNER TO "cts";