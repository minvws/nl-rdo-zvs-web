CREATE TABLE "petition_policy_department"
(
    "petition_id" uuid NOT NULL,
    "policy_department_id" uuid NOT NULL
);

ALTER TABLE
    "petition_policy_department"
    owner TO "cts";

INSERT INTO "petition_policy_department" (petition_id, policy_department_id)
SELECT id, policy_department_id FROM "petitions" WHERE policy_department_id IS NOT NULL;

ALTER TABLE "petitions"
    DROP COLUMN "policy_department_id";
