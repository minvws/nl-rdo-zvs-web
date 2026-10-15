DO $$

DECLARE

    petition_type_uuid_team_b UUID;
    new_order INTEGER;

BEGIN

    -- Find the Woo-verzoek petition type of team B
    SELECT "petition_types"."id"
    FROM "petition_types"
    JOIN "departments" ON "departments"."id" = "petition_types"."department_id"
    WHERE "departments"."slug" = 'team-b'
      AND "petition_types"."type" = 'woo_verzoek'
    INTO petition_type_uuid_team_b;

    -- Skip when team B does not exist or already has the status
    IF petition_type_uuid_team_b IS NULL OR EXISTS (
        SELECT 1
        FROM "petition_statuses"
        WHERE "petition_type_id" = petition_type_uuid_team_b
          AND "status" = 'In afwachting van FV'
    ) THEN
        RETURN;
    END IF;

    -- Place the status right after the last finished status
    SELECT MAX("order") + 1
    FROM "petition_statuses"
    WHERE "petition_type_id" = petition_type_uuid_team_b
      AND "status_group" = 'finished'
    INTO new_order;

    IF new_order IS NULL THEN
        RETURN;
    END IF;

    -- Move the following statuses one place down
    UPDATE "petition_statuses"
    SET "order" = "order" + 1, "updated_at" = NOW()
    WHERE "petition_type_id" = petition_type_uuid_team_b
      AND "order" >= new_order;

    -- Add the status with the same group and color as team A
    INSERT INTO "petition_statuses" ("id", "petition_type_id", "order", "status_group", "bg_color", "status", "created_at", "updated_at") VALUES
    ('98f5407a-1aeb-42c3-8818-d66b759a8fdc', petition_type_uuid_team_b, new_order, 'finished', '#FFEC99', 'In afwachting van FV', NOW(), NOW());

END $$;
