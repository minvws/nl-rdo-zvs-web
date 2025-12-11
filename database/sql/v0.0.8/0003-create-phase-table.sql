CREATE TABLE "phases" 
(
    "id" varchar(255) NOT NULL PRIMARY KEY,
    "name" varchar(255) NOT NULL,
    "status_label" varchar(255) NOT NULL,
    "type" varchar(255) NOT NULL,
    "period_in_days" integer NOT NULL,
    "start_date_label" varchar(255) NOT NULL,
    "has_end_date" boolean default false,
    "end_date_label" varchar(255) NULL,
    "created_at" TIMESTAMP(0) with time zone NOT NULL,
    "updated_at" TIMESTAMP(0) with time zone NOT NULL
);

/** the pivot table for the many-to-many relationship */
CREATE TABLE "petition_type_phase"
(
    "phase_id" varchar(255) NOT NULL,
    "petition_type_id" varchar(255) NOT NULL
);

ALTER TABLE
    "phases"
    owner TO "cts";

ALTER TABLE
    "petition_type_phase"
    owner TO "cts";
