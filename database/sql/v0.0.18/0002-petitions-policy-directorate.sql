CREATE TABLE "policy_departments"
(
    "id" uuid NOT NULL PRIMARY KEY,
    "name" varchar(255) NOT NULL,
    "created_at" TIMESTAMP(0) without time zone NULL,
    "updated_at" TIMESTAMP(0) without time zone NULL
);

ALTER TABLE
    "policy_departments" owner TO "cts";

ALTER TABLE
    "petitions"
    ADD
        COLUMN "policy_department_id" uuid NULL;
