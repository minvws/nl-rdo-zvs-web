TRUNCATE "petition_statuses";

INSERT INTO "petition_statuses" ("id", "department_id", "status", "order", "status_group", "fg_color", "bg_color", "created_at", "updated_at")
SELECT
    gen_random_uuid(),
    d.id,'status', 1,  'pending', '#000000', '#FFFFFF',
    now(), now()
FROM "departments" d
WHERE NOT EXISTS (
          SELECT 1
          FROM petition_statuses ps
          WHERE ps.department_id = d.id);

UPDATE "petitions"
SET petition_status_id = ps.id
FROM petition_statuses ps
WHERE petitions.department_id = ps.department_id;
