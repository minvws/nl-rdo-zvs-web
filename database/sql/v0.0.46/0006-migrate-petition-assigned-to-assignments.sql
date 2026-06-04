INSERT INTO "petition_assignments" ("id", "petition_id", "user_id", "assignment_role", "created_at", "updated_at")
SELECT
    gen_random_uuid(),
    "id",
    "assigned_to",
    1,
    NOW(),
    NOW()
FROM "petitions"
WHERE "assigned_to" IS NOT NULL;

ALTER TABLE petitions
    RENAME COLUMN assigned_to TO legacy_assigned_to;