CREATE TABLE "petition_categories" (
    "id" uuid NOT NULL PRIMARY KEY,
    "department_id" uuid NOT NULL,
    "name" varchar(255) NOT NULL,
    "created_at" TIMESTAMP(0) with time zone NOT NULL,
    "updated_at" TIMESTAMP(0) with time zone NOT NULL
);

ALTER TABLE "petition_categories" OWNER TO "cts";

ALTER TABLE "petitions"
    ADD COLUMN "petition_category_id" uuid null;