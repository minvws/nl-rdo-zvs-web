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

    TRUNCATE "petition_statuses";

    INSERT INTO "petition_statuses" ("id", "department_id", "order", "status_group", "bg_color", "status", "created_at", "updated_at") VALUES
    ('1eff35f9-507e-6521-3dc1-930f34831144', dep_uuid_team_a,  1, 'pending',  '#FFDEEB', 'Zaak in behandeling genomen', NOW(), NOW()),
    ('1eff35f9-507e-6522-eb47-3dc48dc2240b', dep_uuid_team_a,  2, 'pending',  '#FCC2D7', 'Bevestiging verstuurd', NOW(), NOW()),
    ('1eff35f9-507e-6523-d16f-ed1cba40f4d9', dep_uuid_team_a,  3, 'pending',  '#FAA2C1', 'Verzoek beoordeeld', NOW(), NOW()),
    ('1eff35f9-507e-6524-76e1-024af1bca210', dep_uuid_team_a,  4, 'pending',  '#F8F0FC', 'Startgesprek gepland', NOW(), NOW()),
    ('1eff35f9-507e-6525-6d1b-83df99255f85', dep_uuid_team_a,  5, 'pending',  '#F3D9FA', 'Startgesprek gehouden', NOW(), NOW()),
    ('1eff35f9-507e-6526-1d2f-0d480075895e', dep_uuid_team_a,  6, 'pending',  '#EEBEFA', 'Preciseringsgesprek gehouden', NOW(), NOW()),
    ('1eff35f9-507e-6527-de65-d8bbc3212469', dep_uuid_team_a,  7, 'pending',  '#FFE8CC', 'Documenten ontvangen', NOW(), NOW()),
    ('1eff35f9-5080-6c30-13cd-483081e5e768', dep_uuid_team_a,  8, 'pending',  '#FFD8A8', 'Documenten beoordeeld', NOW(), NOW()),
    ('1eff35f9-5080-6c31-9520-dee662542d7e', dep_uuid_team_a,  9, 'pending',  '#FFE8CC', 'Zienswijzen verstuurd', NOW(), NOW()),
    ('1eff35f9-5080-6c32-a2d2-06fadcdd7abe', dep_uuid_team_a, 10, 'pending',  '#FFD8A8', 'Nota en besluit opgesteld', NOW(), NOW()),
    ('1eff35f9-5080-6c33-97c2-f7971f3431bb', dep_uuid_team_a, 11, 'pending',  '#D0EBFF', 'Review afgerond', NOW(), NOW()),
    ('1eff35f9-5080-6c34-6c60-e375dc87694a', dep_uuid_team_a, 12, 'pending',  '#A5D8FF', 'Besluit ter parafering', NOW(), NOW()),
    ('1eff35f9-5080-6c35-5ac6-10dedda83d9a', dep_uuid_team_a, 13, 'finished', '#FFF3BF', 'Besluit verzonden', NOW(), NOW()),
    ('1eff35f9-5080-6c36-b35d-a10859c52702', dep_uuid_team_a, 14, 'finished', '#FFEC99', 'Feitelijke verstrekking', NOW(), NOW()),
    ('1eff35f9-5080-6c37-861a-e1ddc852896c', dep_uuid_team_a, 15, 'closed',   '#B2F2BB', 'Besluit gepubliceerd', NOW(), NOW()),
    ('1eff35e8-15dd-63a5-258e-e707ce314d64', dep_uuid_team_b,  1, 'pending',  '#FFDEEB', 'Substatus', NOW(), NOW()),
    ('1eff35e8-15dd-63aa-9bfd-80263ee27a15', dep_uuid_team_c,  1, 'pending',  '#FFDEEB', 'Substatus', NOW(), NOW()),
    ('1eff35e8-15dd-63af-3f43-2d386b1f0334', dep_uuid_wjz_bb,  1, 'pending',  '#FFDEEB', 'Substatus', NOW(), NOW());

    UPDATE "petitions"
    SET petition_status_id = ps.id
    FROM petition_statuses ps
    WHERE petitions.department_id = ps.department_id;

END $$

