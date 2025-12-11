TRUNCATE "petition_number";

ALTER TABLE "petition_number"
    DROP CONSTRAINT petition_number_pkey;

ALTER TABLE "petition_number"
    DROP COLUMN "name",
    ADD COLUMN "year" INT,
    ADD COLUMN "department_id" UUID;
