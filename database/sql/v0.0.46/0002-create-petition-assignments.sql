CREATE TABLE "petition_assignments" (
    "id" uuid NOT NULL PRIMARY KEY,
    "petition_id" uuid NOT NULL,
    "user_id" uuid NOT NULL,
    "assignment_role" integer NOT NULL,
    "created_at" TIMESTAMP(0) with time zone NOT NULL,
    "updated_at" TIMESTAMP(0) with time zone NOT NULL
);

ALTER TABLE "petition_assignments"
    OWNER TO "cts";

ALTER TABLE "petition_assignments"
    ADD CONSTRAINT "fk_petition_assignments_petition_id"
    FOREIGN KEY ("petition_id") REFERENCES "petitions" ("id") ON DELETE CASCADE;

ALTER TABLE "petition_assignments"
    ADD CONSTRAINT "fk_petition_assignments_user_id"
    FOREIGN KEY ("user_id") REFERENCES "users" ("id") ON DELETE RESTRICT;

ALTER TABLE "petition_assignments"
    ADD CONSTRAINT "uq_petition_assignments_petition_user"
    UNIQUE ("petition_id", "user_id");