CREATE TABLE "referenced_occurrences"
(
    "id" uuid NOT NULL PRIMARY KEY,
    "type" varchar(255) NOT NULL,
    "action" varchar(255) NOT NULL,
    "subject" varchar(255) NOT NULL,
    "created_at" TIMESTAMP(0) with time zone NOT NULL,
    "updated_at" TIMESTAMP(0) with time zone NOT NULL
);

ALTER TABLE
    "referenced_occurrences"
    owner TO "cts";
