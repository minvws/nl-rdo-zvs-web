CREATE TABLE "departments"
(
    "id" uuid NOT NULL PRIMARY KEY,
    "name" varchar(255) NOT NULL,
    "slug" varchar(255) UNIQUE NOT NULL,
    "abbreviation" varchar(255) NOT NULL,
    "created_at" TIMESTAMP(0) without time zone NULL,
    "updated_at" TIMESTAMP(0) without time zone NULL
);

ALTER TABLE "contacts" ADD COLUMN "department_id" uuid NULL;
ALTER TABLE "petition_types" ADD COLUMN "department_id" uuid NULL;
ALTER TABLE "petitions" ADD COLUMN "department_id" uuid NULL;
ALTER TABLE "phases" ADD COLUMN "department_id" uuid NULL;
ALTER TABLE "term_adjustments" ADD COLUMN "department_id" uuid NULL;

ALTER TABLE
    "departments" owner TO "cts";
