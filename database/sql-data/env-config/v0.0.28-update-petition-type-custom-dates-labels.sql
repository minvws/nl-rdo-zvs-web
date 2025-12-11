DO $$

DECLARE

    dep_uuid_team_c UUID;
    dep_uuid_wjz_bb UUID;

    bez_uuid_team_c UUID;
    bez_uuid_wjz_bb UUID;

BEGIN

    SELECT "id" FROM "departments" WHERE "slug"='team-c' INTO dep_uuid_team_c;
    SELECT "id" FROM "departments" WHERE "slug"='wjz-bb' INTO dep_uuid_wjz_bb;

    SELECT "id" FROM "petition_types" WHERE "department_id"=dep_uuid_team_c AND "name"='Bezwaarprocedure'INTO bez_uuid_team_c;
    SELECT "id" FROM "petition_types" WHERE "department_id"=dep_uuid_wjz_bb AND "name"='Bezwaarprocedure'INTO bez_uuid_wjz_bb;


    UPDATE "petition_type_custom_dates_labels"
        SET "date_label"='date_decision_on_appeal'
        WHERE
            "petition_type_id" IN (bez_uuid_team_c,bez_uuid_wjz_bb)
            AND "date_label"='date_ruling';

END $$
