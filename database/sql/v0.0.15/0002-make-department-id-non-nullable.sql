-- A file for removing orphan department items. Which are department items that have not yet been set with a department id.
-- Version: Tue 29 Oct 08:51
-- DB version: develop

ALTER TABLE "contacts"
    ALTER COLUMN "department_id" SET NOT NULL;

ALTER TABLE "petition_types"
    ALTER COLUMN "department_id" SET NOT NULL;

ALTER TABLE "petitions"
    ALTER COLUMN "department_id" SET NOT NULL;

ALTER TABLE "phases"
    ALTER COLUMN "department_id" SET NOT NULL;

ALTER TABLE "term_adjustments"
    ALTER COLUMN "department_id" SET NOT NULL;
