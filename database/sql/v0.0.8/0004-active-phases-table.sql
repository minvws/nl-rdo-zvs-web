CREATE TABLE "active_phases"
(
    "petition_id" varchar(255) NOT NULL PRIMARY KEY,
    "name" varchar(255) NOT NULL,
    "status_label" varchar(255) NOT NULL,
    "type" varchar(255) NOT NULL,
    "period_in_days" integer NOT NULL,
    "start_date_label" varchar(255) NOT NULL,
    "start_date" DATE NOT NULL,
    "end_date_label" varchar(255),
    "end_date" DATE,
    "created_at" TIMESTAMP(0) with time zone NOT NULL,
    "updated_at" TIMESTAMP(0) with time zone NOT NULL
);

ALTER TABLE
    "active_phases"
    owner TO "cts";