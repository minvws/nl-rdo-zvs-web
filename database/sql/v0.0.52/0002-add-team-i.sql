DO $$

DECLARE

    dep_uuid_team_a UUID;
    dep_uuid_team_i UUID := 'b4f908f6-8569-46ec-a680-2b58a8f80675';
    petition_type_uuid_team_i UUID := 'c5b3d55c-b5e7-4986-a171-7347258a0de5';

BEGIN

    -- Team A is the source for the column defaults and term type settings
    SELECT "id"
    FROM "departments"
    WHERE "slug" = 'team-a'
    INTO dep_uuid_team_a;

    -- Skip when team A does not exist or team I already exists
    IF dep_uuid_team_a IS NULL OR EXISTS (
        SELECT 1
        FROM "departments"
        WHERE "slug" = 'team-i'
    ) THEN
        RETURN;
    END IF;

    INSERT INTO "departments" ("id", "name", "slug", "config_key", "abbreviation", "hide_column_defaults", "created_at", "updated_at")
    SELECT dep_uuid_team_i, 'Team I Woo-Informatieverzoeken', 'team-i', 'team-i', 'I', "hide_column_defaults", NOW(), NOW()
    FROM "departments"
    WHERE "id" = dep_uuid_team_a;

    INSERT INTO "petition_types" ("id", "name", "type", "department_id", "particularity_label", "active", "created_at", "updated_at") VALUES
    (petition_type_uuid_team_i, 'Informatieverzoek', 'woo_verzoek', dep_uuid_team_i, NULL, true, NOW(), NOW());

    INSERT INTO "petition_statuses" ("id", "petition_type_id", "order", "status_group", "bg_color", "status", "created_at", "updated_at") VALUES
    ('eb2a5ef7-8218-48cc-a4b3-e9f0b0714492', petition_type_uuid_team_i, 200, 'pending', '#FFDEEB', 'Beantwoorden', NOW(), NOW()),
    ('99bb6400-4c9f-48bd-a6d6-b9cc223c4b45', petition_type_uuid_team_i, 201, 'pending', '#FCC2D7', 'Doorsturen', NOW(), NOW()),
    ('897d7e29-a630-46a7-a653-57ac4b6b06cb', petition_type_uuid_team_i, 202, 'pending', '#FAA2C1', 'Doorverwijzen', NOW(), NOW()),
    ('788272ce-830d-4fec-a3ac-dbac67371658', petition_type_uuid_team_i, 400, 'closed', '#B2F2BB', 'Afgehandeld', NOW(), NOW());

    INSERT INTO "department_term_type_settings" ("id", "department_id", "term_type", "field", "active", "default_value", "title")
    SELECT gen_random_uuid(), dep_uuid_team_i, "term_type", "field", "active", "default_value", "title"
    FROM "department_term_type_settings"
    WHERE "department_id" = dep_uuid_team_a;

    INSERT INTO "custom_petition_properties" ("id", "petition_type_id", "name", "type", "ordering", "grouping", "created_at", "updated_at")
    SELECT gen_random_uuid(), petition_type_uuid_team_i, "property"."name", "property"."type", "property"."ordering", "property"."grouping", NOW(), NOW()
    FROM (VALUES
        ('Eigenschappen', 'name', 1, NULL),
        ('Eigenschappen doorgeven', 'title', 2, NULL),

        ('Doel verzoek', 'subtitle', 3, NULL),
        ('Documenten opvragen', 'option', 4, 1),
        ('(Hulp-) vraag stellen', 'option', 5, 1),
        ('Informatie opvragen', 'option', 6, 1),
        ('Informeren', 'option', 7, 1),
        ('In gebreke stellen', 'option', 8, 1),
        ('Intrekken', 'option', 9, 1),
        ('Klacht indienen', 'option', 10, 1),
        ('Reageren', 'option', 11, 1),
        ('Zienswijze opvragen', 'option', 12, 1),
        ('Anders', 'option', 13, 1),

        ('Complexiteit', 'subtitle', 14, NULL),
        ('Eerstelijns', 'option', 15, 2),
        ('Standaard', 'option', 16, 2),
        ('Complex', 'option', 17, 2),

        ('Betrokken organisaties', 'subtitle', 18, NULL),
        ('Directielijst', 'option', 19, NULL),
        ('Opties departementenlijst', 'option', 20, NULL),
        ('Lagere overheden', 'option', 21, NULL),
        ('Anders', 'option', 22, NULL),

        ('Bestemd voor', 'subtitle', 23, NULL),
        ('Directielijst', 'option', 24, 3),
        ('Opties departementenlijst', 'option', 25, 3),
        ('Lagere overheden', 'option', 26, 3),
        ('Anders', 'option', 27, 3)
    ) AS "property" ("name", "type", "ordering", "grouping");

END $$;
