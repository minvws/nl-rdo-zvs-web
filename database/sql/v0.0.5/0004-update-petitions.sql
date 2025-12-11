-- Deleting all records as we're adding a not null column and database version still 0.0.x (non-production)
ALTER  TABLE case_requests RENAME TO petitions;
ALTER  TABLE case_request_types RENAME TO petition_types;

TRUNCATE TABLE "petitions";

ALTER TABLE
  "petitions"
ADD
  COLUMN "petition_status_id" varchar(255) NOT NULL;

ALTER TABLE
    "petitions"
    RENAME
        COLUMN "case_request_type_id" TO "petition_type_id";
