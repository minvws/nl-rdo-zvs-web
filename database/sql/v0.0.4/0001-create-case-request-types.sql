CREATE TABLE "requesting_parties"
(
    "id" varchar(255) NOT NULL,
    "name" varchar(255) NOT NULL,
    "email" varchar(255) NOT NULL,
    "created_at" TIMESTAMP(0) with time zone NOT NULL,
    "updated_at" TIMESTAMP(0) with time zone NOT NULL
);

ALTER TABLE
    "requesting_parties"
    owner TO "cts";

ALTER TABLE
    "requesting_parties"
    ADD
        PRIMARY KEY ("id");

-- Deleting all records as we're adding a not null column and database version still 0.0.x (non-production)
TRUNCATE TABLE "case_requests";

ALTER TABLE
    "case_requests"
    ADD
        COLUMN "requesting_party_id" varchar(255) NOT NULL;
