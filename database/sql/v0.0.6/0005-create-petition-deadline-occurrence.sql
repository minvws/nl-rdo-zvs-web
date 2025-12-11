CREATE TABLE "deadline_occurrences"
(
    "id" varchar(255) NOT NULL PRIMARY KEY,
    "previous_deadline" TIMESTAMP(0) with time zone NOT NULL,
    "current_deadline" TIMESTAMP(0) with time zone NOT NULL,
    "reason_for_change" varchar(255) NOT NULL,
    "explanation" text,
    "created_at" TIMESTAMP(0) with time zone NOT NULL,
    "updated_at" TIMESTAMP(0) with time zone NOT NULL
);

ALTER TABLE
    "occurrences"
    owner TO "cts";
