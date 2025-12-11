CREATE TABLE "active_phase_rollback_occurences"
(
    "id" varchar(255) NOT NULL PRIMARY KEY,
    "deadline_at" DATE NOT NULL,
    "comment" TEXT NOT NULL,
    "created_at" TIMESTAMP(0) with time zone NOT NULL,
    "updated_at" TIMESTAMP(0) with time zone NOT NULL
);

ALTER TABLE
    "active_phase_rollback_occurences"
    owner TO "cts";
