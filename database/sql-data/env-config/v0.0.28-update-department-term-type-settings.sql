DO $$

DECLARE

    dep_uuid_team_c UUID;
    dep_uuid_wjz_bb UUID;

BEGIN

    SELECT "id" FROM "departments" WHERE "slug"='team-c' INTO dep_uuid_team_c;
    SELECT "id" FROM "departments" WHERE "slug"='wjz-bb' INTO dep_uuid_wjz_bb;

    UPDATE "department_term_type_settings"
        SET "default_value"=42
        WHERE
            "department_id" IN (dep_uuid_team_c,dep_uuid_wjz_bb)
            AND "term_type"='second'
            AND "field"='duration_in_days';

END $$
