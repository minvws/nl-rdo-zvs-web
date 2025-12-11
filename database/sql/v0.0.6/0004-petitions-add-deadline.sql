ALTER TABLE
  "petitions"
ADD
  COLUMN "deadline_at" DATE NULL;

UPDATE "petitions"
SET "deadline_at" = NOW();

ALTER TABLE
    "petitions"
    ALTER
        COLUMN "deadline_at" SET NOT NULL;
