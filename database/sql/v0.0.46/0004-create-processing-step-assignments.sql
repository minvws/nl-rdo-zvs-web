CREATE TABLE "processing_step_assignments" (
    "id" uuid NOT NULL PRIMARY KEY,
    "processing_step_id" uuid NOT NULL,
    "user_id" uuid NOT NULL,
    "assignment_role" integer NOT NULL,
    "created_at" TIMESTAMP(0) with time zone NOT NULL,
    "updated_at" TIMESTAMP(0) with time zone NOT NULL
);

ALTER TABLE "processing_step_assignments"
    OWNER TO "cts";

ALTER TABLE "processing_step_assignments"
    ADD CONSTRAINT "fk_processing_step_assignments_processing_step_id"
    FOREIGN KEY ("processing_step_id") REFERENCES "processing_steps" ("id") ON DELETE CASCADE;

ALTER TABLE "processing_step_assignments"
    ADD CONSTRAINT "fk_processing_step_assignments_user_id"
    FOREIGN KEY ("user_id") REFERENCES "users" ("id") ON DELETE RESTRICT;

ALTER TABLE "processing_step_assignments"
    ADD CONSTRAINT "uq_processing_step_assignments_step_user"
    UNIQUE ("processing_step_id", "user_id");