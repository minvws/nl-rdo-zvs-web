CREATE TABLE "active_phase_delete_occurences"
(
    "id" uuid NOT NULL PRIMARY KEY,
    "name" varchar(255) NOT NULL,
    "deadline_at" DATE NOT NULL,
    "start_date" DATE NOT NULL,
    "end_date" DATE NOT NULL,
    "comment" TEXT NULL,
    "created_at" TIMESTAMP(0) with time zone NOT NULL,
    "updated_at" TIMESTAMP(0) with time zone NOT NULL
);

ALTER TABLE
    "active_phase_delete_occurences"
    owner TO "cts";
