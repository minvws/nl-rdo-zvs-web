DO $$

DECLARE

    dep_uuid_team_c UUID;
    dep_uuid_team_a UUID;
    dep_uuid_team_b UUID;
    dep_uuid_wjz_bb UUID;

BEGIN

    SELECT "id" FROM "departments" WHERE "slug"='team-a' INTO dep_uuid_team_a;
    SELECT "id" FROM "departments" WHERE "slug"='team-b' INTO dep_uuid_team_b;
    SELECT "id" FROM "departments" WHERE "slug"='team-c' INTO dep_uuid_team_c;
    SELECT "id" FROM "departments" WHERE "slug"='wjz-bb' INTO dep_uuid_wjz_bb;

    UPDATE "departments" SET "hide_column_defaults"='zaaksoort,categorie' WHERE "id"=dep_uuid_team_a;
    UPDATE "departments" SET "hide_column_defaults"='zaaksoort,categorie' WHERE "id"=dep_uuid_team_b;
    UPDATE "departments" SET "hide_column_defaults"='' WHERE "id"=dep_uuid_team_c;
    UPDATE "departments" SET "hide_column_defaults"='' WHERE "id"=dep_uuid_wjz_bb;

END $$

