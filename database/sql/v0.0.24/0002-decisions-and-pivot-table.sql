CREATE TABLE "decisions"
(
    "id" uuid NOT NULL PRIMARY KEY,
    "name" varchar(255) NOT NULL,
    "date" date,
    "department_id" uuid NOT NULL,
    "created_at" TIMESTAMP(0) with time zone NOT NULL,
    "updated_at" TIMESTAMP(0) with time zone NOT NULL
);

CREATE TABLE "decision_petition"
(
    "decision_id" uuid NOT NULL,
    "petition_id" uuid NOT NULL
);

ALTER TABLE
    "decisions"
    owner TO "cts";

ALTER TABLE
    "decision_petition"
    owner TO "cts";