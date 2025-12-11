CREATE TABLE "active_phase_occurrences"
(
    "id" varchar(255) NOT NULL PRIMARY KEY,
    "name" varchar(255) NOT NULL,
    "action" varchar(255) NOT NULL,
    "duration_in_days" integer NOT NULL default 0,
    "start_date" DATE NOT NULL,
    "end_date" DATE NULL,
    "start_date_label" varchar(255) NOT NULL,
    "end_date_label" varchar(255),
    "comment" text NOT NULL,
    "created_at" TIMESTAMP(0) with time zone NOT NULL,
    "updated_at" TIMESTAMP(0) with time zone NOT NULL
);

ALTER TABLE
    "active_phase_occurrences"
    owner TO "cts";
