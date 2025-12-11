CREATE TABLE "decision_reference_occurrences"
(
    "id" uuid NOT NULL PRIMARY KEY,
    "type" varchar(255) NOT NULL,
    "action" varchar(255) NOT NULL,
    "decision_reference" varchar(255) NOT NULL,
    "created_at" TIMESTAMP(0) with time zone NOT NULL,
    "updated_at" TIMESTAMP(0) with time zone NOT NULL
);

ALTER TABLE
    "decision_reference_occurrences"
    owner TO "cts";
