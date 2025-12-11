UPDATE "case_requests"
SET "created_at" = NOW()
WHERE "created_at" IS NULL;

UPDATE "case_requests"
SET "updated_at" = NOW()
WHERE "updated_at" IS NULL;

ALTER TABLE
  "case_requests"
ALTER
  COLUMN "created_at" SET NOT NULL,
ALTER
  COLUMN "updated_at" SET NOT NULL;

ALTER TABLE
  "case_requests"
ADD
  COLUMN "case_request_type_id" varchar(255) NOT NULL,
ADD
  COLUMN "number" varchar(255) UNIQUE NOT NULL,
ADD
  COLUMN "name" varchar(255) NOT NULL,
ADD
  COLUMN "description" TEXT NULL,
ADD
  COLUMN "date_of_entry" TIMESTAMP(0) with time zone NOT NULL;

