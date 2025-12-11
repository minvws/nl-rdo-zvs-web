-- A cleanup file for the Zaakvolgsysteem Test and Staging environments
-- Version: Fri 17 Jan 2024 16:47
-- DB version: v0.0.22

-- IMPORTANT! All entries and their properties are fake and generated!
-- This file does not and will never contain references to real people or actual petitions.

TRUNCATE  "active_phase_delete_occurrences";
TRUNCATE  "active_phase_occurrences";
TRUNCATE  "active_phase_rollback_occurrences";
TRUNCATE  "active_phases";
TRUNCATE  "active_phases_deadline_shifts";
TRUNCATE  "assignment_occurrences";
TRUNCATE  "attachments";
TRUNCATE  "contact_petition";
TRUNCATE  "notes";
TRUNCATE  "petitions";
TRUNCATE  "petition_exports";
TRUNCATE  "status_occurrences";
TRUNCATE  "term_adjustment_occurrences";
TRUNCATE  "timeline_items";

DO $$

DECLARE

    dep_uuid_c1 UUID;
    dep_uuid_c4 UUID;
    dep_uuid_pdo UUID;

BEGIN

    SELECT "id" FROM "departments" WHERE "name"='Cluster 1' INTO dep_uuid_c1;
    SELECT "id" FROM "departments" WHERE "name"='Cluster 4' INTO dep_uuid_c4;
    SELECT "id" FROM "departments" WHERE "name"='Programmadirectie Openbaarheid' INTO dep_uuid_pdo;

    TRUNCATE  "public_holidays";
    INSERT INTO "public_holidays" ("id", "name", "date", "created_at", "updated_at") VALUES
    ('215ff9ef-dfa2-44fc-a29a-e8f729bad9b2', 'Hemelvaartsdag', '2024-05-09', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00'),
    ('3d237b8e-e448-4661-8f1e-be60b97ef08f', 'Eerste Kerstdag', '2024-12-25', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00'),
    ('61b53806-50be-4e3f-9cde-6cc184a5c12b', 'Pinksteren', '2024-05-19', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00'),
    ('674e8b8c-65e5-4adb-b51c-573243395189', 'Bevrijdingsdag', '2024-05-05', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00'),
    ('72ba061c-e18d-4d8a-9787-c7678e1ec4af', 'Nieuwjaarsdag', '2024-01-01', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00'),
    ('8580152b-f0b1-48ff-80ea-5328af4418c0', 'Pasen', '2024-03-31', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00'),
    ('a0582aca-107c-401c-b293-739319ad5fb5', 'Goede Vrijdag', '2024-03-29', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00'),
    ('b0bbf1d2-c37b-43ea-9abb-4574fe4312eb', 'Koningsdag', '2024-04-27', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00'),
    ('fac0c608-2fc0-4af2-94a3-89db838f1b11', 'Tweede Kerstdag', '2024-12-26', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00');

    TRUNCATE  "petition_statuses";
    INSERT INTO "petition_statuses" ("id", "status", "created_at", "updated_at", "badge", "default_status") VALUES
    ('0192573b-145c-73c8-963f-e24176312090', 'Opgeschort', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00', 'danger', 'f'),
    ('0192573b-1475-7052-8b34-c314d33e0bcf', 'In behandeling', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00', 'warning', 'f'),
    ('0192573b-1477-7100-949f-50be988ce068', 'Afgehandeld', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00', 'success', 'f'),
    ('0192573b-148f-7075-b0f2-76c268c0d61a', 'Nieuw', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00', 'info', 't');

    TRUNCATE  "contacts";
    INSERT INTO "contacts" ("id", "initials", "last_name", "organisation_name", "email_address", "phone_number", "street", "house_number", "postal_code", "city", "is_journalist", "created_at", "updated_at", "department_id") VALUES
    ('0192573b-1a38-711d-be28-767e64703414', 'C.', 'de Vries', '', 'c.devries@rdobeheer.nl', '054 2542155', NULL, NULL, '1531CZ', 'Achtmaal', 'f', '2024-10-04 11:12:24+00', '2024-10-04 11:12:24+00', dep_uuid_c1),
    ('0192573b-1a3a-7220-823f-8e58efd3b515', 'S.', 'Hendriks', 'VNO-NCW', 's.hendriks@rdobeheer.nl', NULL, 'van den Eerenbeemtdreef', NULL, '9354VD', NULL, 'f', '2024-10-04 11:12:24+00', '2024-10-04 11:12:24+00', dep_uuid_c1),
    ('0192573b-1a3c-718a-9c51-7e500f199be3', 'T.', 'Lemmens', 'AGA Juristen', 't.lemmens@rdobeheer.nl', NULL, NULL, NULL, '4382JC', NULL, 'f', '2024-10-04 11:12:24+00', '2024-10-04 11:12:24+00', dep_uuid_c1),
    ('0192573b-1a3d-73df-9ba2-45212bb73d67', 'J.', 'Maes', 'NRC', 'j.maes@rdobeheer.nl', '0900 671596', 'Schipperhof', '18-71', NULL, 'Tuil', 'f', '2024-10-04 11:12:24+00', '2024-10-04 11:12:24+00', dep_uuid_c1),
    ('0192573b-1a38-711d-be28-767e64703411', 'C.', 'de Vries', '', 'c.devries@rdobeheer.nl', '054 2542155', NULL, NULL, '1531CZ', 'Achtmaal', 'f', '2024-10-04 11:12:24+00', '2024-10-04 11:12:24+00', dep_uuid_c4),
    ('0192573b-1a3a-7220-823f-8e58efd3b512', 'S.', 'Hendriks', 'VNO-NCW', 's.hendriks@rdobeheer.nl', NULL, 'van den Eerenbeemtdreef', NULL, '9354VD', NULL, 'f', '2024-10-04 11:12:24+00', '2024-10-04 11:12:24+00', dep_uuid_c4),
    ('0192573b-1a3c-718a-9c51-7e500f199be8', 'T.', 'Lemmens', 'AGA Juristen', 't.lemmens@rdobeheer.nl', NULL, NULL, NULL, '4382JC', NULL, 'f', '2024-10-04 11:12:24+00', '2024-10-04 11:12:24+00', dep_uuid_c4),
    ('0192573b-1a3d-73df-9ba2-45212bb73d64', 'J.', 'Maes', 'NRC', 'j.maes@rdobeheer.nl', '0900 671596', 'Schipperhof', '18-71', NULL, 'Tuil', 'f', '2024-10-04 11:12:24+00', '2024-10-04 11:12:24+00', dep_uuid_c4),
    ('0192573b-1a38-711d-be28-767e64703415', 'C.', 'de Vries', '', 'c.devries@rdobeheer.nl', '054 2542155', NULL, NULL, '1531CZ', 'Achtmaal', 'f', '2024-10-04 11:12:24+00', '2024-10-04 11:12:24+00', dep_uuid_pdo),
    ('0192573b-1a3a-7220-823f-8e58efd3b516', 'S.', 'Hendriks', 'VNO-NCW', 's.hendriks@rdobeheer.nl', NULL, 'van den Eerenbeemtdreef', NULL, '9354VD', NULL, 'f', '2024-10-04 11:12:24+00', '2024-10-04 11:12:24+00', dep_uuid_pdo),
    ('0192573b-1a3c-718a-9c51-7e500f199be7', 'T.', 'Lemmens', 'AGA Juristen', 't.lemmens@rdobeheer.nl', NULL, NULL, NULL, '4382JC', NULL, 'f', '2024-10-04 11:12:24+00', '2024-10-04 11:12:24+00', dep_uuid_pdo),
    ('0192573b-1a3d-73df-9ba2-45212bb73d68', 'J.', 'Maes', 'NRC', 'j.maes@rdobeheer.nl', '0900 671596', 'Schipperhof', '18-71', NULL, 'Tuil', 'f', '2024-10-04 11:12:24+00', '2024-10-04 11:12:24+00', dep_uuid_pdo);

    TRUNCATE  "petition_types";
    INSERT INTO "petition_types" ("id", "name", "created_at", "updated_at", "department_id", "type") VALUES
    ('0192573b-1496-70c1-abf7-793da7ffdb80', 'Beroepsprocedure: Hoger beroep', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00', dep_uuid_c1, 'beroep'),
    ('0192573b-1496-70c1-abf7-793da7ffdb81', 'Beroepsprocedure: Beroep niet tijdig', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00', dep_uuid_c1, 'beroep'),
    ('0192573b-1496-70c1-abf7-793da7ffdb88', 'Beroepsprocedure: Inhoudelijk beroep', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00', dep_uuid_c1, 'beroep'),
    ('0192573b-1496-70c1-abf7-793da7ffdb89', 'Beroepsprocedure: VOVO', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00', dep_uuid_c1, 'beroep'),
    ('0192573b-1496-70c1-abf7-793da7ffdb90', 'Bezwaarprocedure', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00', dep_uuid_c1, 'bezwaar'),
    ('0192e2f0-e34f-70c9-befd-23b76d432776', 'WOO-verzoek', '2024-10-31 14:18:10+00', '2024-10-31 14:18:10+00', dep_uuid_c4, 'woo_verzoek'),
    ('0192e2f1-0918-720c-8797-a33d3c63aa46', 'WOO-verzoek (covid-19)', '2024-10-31 14:18:20+00', '2024-10-31 14:18:20+00', dep_uuid_pdo, 'woo_verzoek');

    TRUNCATE  "term_adjustments";
    INSERT INTO "term_adjustments" ("id", "name", "period_in_days", "can_enter_date_manually", "created_at", "updated_at", "department_id") VALUES
    ('0192573b-14a5-7004-9d65-6eaf0835bdf1', 'Verdaging', 14, 't', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00', dep_uuid_c1),
    ('0192573b-14a9-7392-a2f5-b9c371d02c02', 'Afspraak met verzoeker', 0, 't', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00', dep_uuid_c1),
    ('0192573b-14ab-7196-b308-676b04bb1383', 'Anders, namelijk:', 0, 't', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00', dep_uuid_c1),
    ('0192573b-14a5-7004-9d65-6eaf0835bdf4', 'Verdaging', 14, 't', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00', dep_uuid_c4),
    ('0192573b-14a9-7392-a2f5-b9c371d02c05', 'Afspraak met verzoeker', 0, 't', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00', dep_uuid_c4),
    ('0192573b-14ab-7196-b308-676b04bb1386', 'Anders, namelijk:', 0, 't', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00', dep_uuid_c4),
    ('0192573b-14a5-7004-9d65-6eaf0835bdf7', 'Verdaging', 14, 't', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00', dep_uuid_pdo),
    ('0192573b-14a9-7392-a2f5-b9c371d02c08', 'Afspraak met verzoeker', 0, 't', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00', dep_uuid_pdo),
    ('0192573b-14ab-7196-b308-676b04bb1389', 'Anders, namelijk:', 0, 't', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00', dep_uuid_pdo);

    TRUNCATE  "phases";
    INSERT INTO "phases" ("id", "name", "status_label", "type", "period_in_days", "start_date_label", "end_date_label", "created_at", "updated_at", "department_id") VALUES
    ('0192573b-14b2-7144-88d2-f290b95a4a61', 'Opschorting ', 'Opgeschort', 'end_undefined', 14, 'Startdatum', 'Einddatum', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00', dep_uuid_c1),
    ('0192573b-14b2-7144-88d2-f290b95a4a62', 'Ingebrekestelling ', 'IGS', 'system_defined', 14, 'Startdatum', 'Einddatum', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00', dep_uuid_c1),
    ('0192573b-14b2-7144-88d2-f290b95a4a63', 'Beroep niet tijdig beslissen ', 'BNB', 'end_required', 14, 'Startdatum', 'Einddatum', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00', dep_uuid_c1),
    ('0192573b-14b2-7144-88d2-f290b95a4a64', 'Opschorting ', 'Opgeschort', 'end_undefined', 14, 'Startdatum', 'Einddatum', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00', dep_uuid_c4),
    ('0192573b-14b2-7144-88d2-f290b95a4a65', 'Ingebrekestelling ', 'IGS', 'system_defined', 14, 'Startdatum', 'Einddatum', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00', dep_uuid_c4),
    ('0192573b-14b2-7144-88d2-f290b95a4a66', 'Beroep niet tijdig beslissen ', 'BNB', 'end_required', 14, 'Startdatum', 'Einddatum', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00', dep_uuid_c4),
    ('0192573b-14b2-7144-88d2-f290b95a4a67', 'Opschorting ', 'Opgeschort', 'end_undefined', 14, 'Startdatum', 'Einddatum', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00', dep_uuid_pdo),
    ('0192573b-14b2-7144-88d2-f290b95a4a68', 'Ingebrekestelling ', 'IGS', 'system_defined', 14, 'Startdatum', 'Einddatum', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00', dep_uuid_pdo),
    ('0192573b-14b2-7144-88d2-f290b95a4a69', 'Beroep niet tijdig beslissen ', 'BNB', 'end_required', 14, 'Startdatum', 'Einddatum', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00', dep_uuid_pdo);

    TRUNCATE  "petition_type_phase";
    INSERT INTO "petition_type_phase" ("phase_id", "petition_type_id") VALUES
    ('0192573b-14b2-7144-88d2-f290b95a4a64', '0192e2f0-e34f-70c9-befd-23b76d432776'),
    ('0192573b-14b2-7144-88d2-f290b95a4a65', '0192e2f0-e34f-70c9-befd-23b76d432776'),
    ('0192573b-14b2-7144-88d2-f290b95a4a66', '0192e2f0-e34f-70c9-befd-23b76d432776'),
    ('0192573b-14b2-7144-88d2-f290b95a4a67', '0192e2f1-0918-720c-8797-a33d3c63aa46'),
    ('0192573b-14b2-7144-88d2-f290b95a4a68', '0192e2f1-0918-720c-8797-a33d3c63aa46'),
    ('0192573b-14b2-7144-88d2-f290b95a4a69', '0192e2f1-0918-720c-8797-a33d3c63aa46'),
    ('0192573b-14b2-7144-88d2-f290b95a4a63', '0192573b-1496-70c1-abf7-793da7ffdb80'),
    ('0192573b-14b2-7144-88d2-f290b95a4a63', '0192573b-1496-70c1-abf7-793da7ffdb81'),
    ('0192573b-14b2-7144-88d2-f290b95a4a63', '0192573b-1496-70c1-abf7-793da7ffdb87'),
    ('0192573b-14b2-7144-88d2-f290b95a4a63', '0192573b-1496-70c1-abf7-793da7ffdb88'),
    ('0192573b-14b2-7144-88d2-f290b95a4a63', '0192573b-1496-70c1-abf7-793da7ffdb89'),
    ('0192573b-14b2-7144-88d2-f290b95a4a62', '0192573b-1496-70c1-abf7-793da7ffdb80'),
    ('0192573b-14b2-7144-88d2-f290b95a4a62', '0192573b-1496-70c1-abf7-793da7ffdb81'),
    ('0192573b-14b2-7144-88d2-f290b95a4a62', '0192573b-1496-70c1-abf7-793da7ffdb87'),
    ('0192573b-14b2-7144-88d2-f290b95a4a62', '0192573b-1496-70c1-abf7-793da7ffdb88'),
    ('0192573b-14b2-7144-88d2-f290b95a4a62', '0192573b-1496-70c1-abf7-793da7ffdb89'),
    ('0192573b-14b2-7144-88d2-f290b95a4a61', '0192573b-1496-70c1-abf7-793da7ffdb80'),
    ('0192573b-14b2-7144-88d2-f290b95a4a61', '0192573b-1496-70c1-abf7-793da7ffdb81'),
    ('0192573b-14b2-7144-88d2-f290b95a4a61', '0192573b-1496-70c1-abf7-793da7ffdb87'),
    ('0192573b-14b2-7144-88d2-f290b95a4a61', '0192573b-1496-70c1-abf7-793da7ffdb88'),
    ('0192573b-14b2-7144-88d2-f290b95a4a61', '0192573b-1496-70c1-abf7-793da7ffdb89');

    TRUNCATE  "policy_departments";
    INSERT INTO "policy_departments" ("id", "name", "created_at", "updated_at") VALUES
    ('0193257c-15f7-702b-a84e-166c951445bf', 'RIVM', NOW(), NOW()),
    ('0193257c-15f8-708c-9498-0a840a8c1b13', 'CIBG', NOW(), NOW());

    TRUNCATE "custom_petition_properties";
    INSERT INTO "custom_petition_properties" ("id", "petition_type_id", "name", "type", "ordering", "created_at", "updated_at") VALUES
    ('0192573b-14b0-6e41-1521-caa195cacfd1', '0192573b-1496-70c1-abf7-793da7ffdb80', 'Uitkomst', 'name', 1, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b0-6e42-20ff-c522ba705757', '0192573b-1496-70c1-abf7-793da7ffdb80', 'Uitkomst doorgeven', 'title', 2, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b0-6e43-5db6-537ef0e67bbd', '0192573b-1496-70c1-abf7-793da7ffdb80', 'Uitspraak', 'subtitle', 2, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b0-6e44-b3cd-464d8ed57228', '0192573b-1496-70c1-abf7-793da7ffdb80', 'Ongegrond', 'option', 3, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b0-6e45-ae12-c1ca2078794b', '0192573b-1496-70c1-abf7-793da7ffdb80', 'Gegrond', 'option', 4, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b0-6e46-a7ad-4006a8b6e5dd', '0192573b-1496-70c1-abf7-793da7ffdb80', 'Toegewezen', 'option', 5, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b0-6e47-1aec-5c823ff9a871', '0192573b-1496-70c1-abf7-793da7ffdb80', 'Afgewezen', 'option', 6, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b0-6e48-44eb-1a4599f7985d', '0192573b-1496-70c1-abf7-793da7ffdb80', 'Niet ontvankelijk', 'option', 7, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b0-6e49-f477-e574186ca004', '0192573b-1496-70c1-abf7-793da7ffdb80', 'Instantie verklaart zich onbevoegd', 'option', 8, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b0-6e4a-f4c2-7767b5741cd2', '0192573b-1496-70c1-abf7-793da7ffdb81', 'Uitkomst', 'name', 1, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b0-6e4b-e89c-bd139885a3fc', '0192573b-1496-70c1-abf7-793da7ffdb81', 'Uitkomst doorgeven', 'title', 2, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b0-6e4c-1b4d-f88ffd0619b8', '0192573b-1496-70c1-abf7-793da7ffdb81', 'Uitspraak', 'subtitle', 2, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b0-6e4d-499b-3b4bf8e41886', '0192573b-1496-70c1-abf7-793da7ffdb81', 'Ongegrond', 'option', 3, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b0-6e4e-1ad0-920a60e3ac5e', '0192573b-1496-70c1-abf7-793da7ffdb81', 'Gegrond', 'option', 4, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b0-6e4f-d4c7-6b4a8938195e', '0192573b-1496-70c1-abf7-793da7ffdb81', 'Toegewezen', 'option', 5, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b0-6e50-ac50-7093af5f30bc', '0192573b-1496-70c1-abf7-793da7ffdb81', 'Afgewezen', 'option', 6, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b0-6e51-d9c5-910afb03ef06', '0192573b-1496-70c1-abf7-793da7ffdb81', 'Niet ontvankelijk', 'option', 7, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b0-6e52-8d6c-3b600f9dee2a', '0192573b-1496-70c1-abf7-793da7ffdb81', 'Instantie verklaart zich onbevoegd', 'option', 8, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b0-6e53-b939-2f9994d15e69', '0192573b-1496-70c1-abf7-793da7ffdb88', 'Uitkomst', 'name', 1, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b0-6e54-276f-cf4b92cc30e1', '0192573b-1496-70c1-abf7-793da7ffdb88', 'Uitkomst doorgeven', 'title', 2, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b0-6e55-36fe-2976f208c9c7', '0192573b-1496-70c1-abf7-793da7ffdb88', 'Uitspraak', 'subtitle', 2, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b0-6e56-ab97-a91b397f8c1b', '0192573b-1496-70c1-abf7-793da7ffdb88', 'Ongegrond', 'option', 3, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b0-6e57-8a2d-c1844e92f517', '0192573b-1496-70c1-abf7-793da7ffdb88', 'Gegrond', 'option', 4, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b0-6e58-a5ba-2e6a878a8bee', '0192573b-1496-70c1-abf7-793da7ffdb88', 'Toegewezen', 'option', 5, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b0-6e59-6e7d-615434269235', '0192573b-1496-70c1-abf7-793da7ffdb88', 'Afgewezen', 'option', 6, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-6550-9b60-2f38502804c9', '0192573b-1496-70c1-abf7-793da7ffdb88', 'Niet ontvankelijk', 'option', 7, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-6551-cf93-d05b79babb1e', '0192573b-1496-70c1-abf7-793da7ffdb88', 'Instantie verklaart zich onbevoegd', 'option', 8, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-6552-f288-e24dffc969d3', '0192573b-1496-70c1-abf7-793da7ffdb89', 'Uitkomst', 'name', 1, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-6553-a61c-c3ac25f0fef5', '0192573b-1496-70c1-abf7-793da7ffdb89', 'Uitkomst doorgeven', 'title', 2, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-6554-e325-1d678a85593a', '0192573b-1496-70c1-abf7-793da7ffdb89', 'Uitspraak', 'subtitle', 2, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-6555-a05c-65ecfe66e4ae', '0192573b-1496-70c1-abf7-793da7ffdb89', 'Ongegrond', 'option', 3, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-6556-4382-ac6aab48539e', '0192573b-1496-70c1-abf7-793da7ffdb89', 'Gegrond', 'option', 4, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-6557-09cc-654e08df81c7', '0192573b-1496-70c1-abf7-793da7ffdb89', 'Toegewezen', 'option', 5, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-6558-1948-5d03c70ba5f0', '0192573b-1496-70c1-abf7-793da7ffdb89', 'Afgewezen', 'option', 6, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-6559-fbd7-41e6e77bf5d2', '0192573b-1496-70c1-abf7-793da7ffdb89', 'Niet ontvankelijk', 'option', 7, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-655a-2ce5-ff3596d193f9', '0192573b-1496-70c1-abf7-793da7ffdb89', 'Instantie verklaart zich onbevoegd', 'option', 8, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-655b-be82-617e93c15a87', '0192573b-1496-70c1-abf7-793da7ffdb90', 'Uitkomst en zwaarte', 'name', 1, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-655c-3f50-d237fe54fea3', '0192573b-1496-70c1-abf7-793da7ffdb90', 'Uitkomst doorgeven', 'title', 2, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-655d-edad-5c1673fec54c', '0192573b-1496-70c1-abf7-793da7ffdb90', 'Beslissing op bezwaar', 'subtitle', 3, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-655e-dd06-d1c061533653', '0192573b-1496-70c1-abf7-793da7ffdb90', 'Geen / n.v.t. / leeg', 'option', 4, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-655f-b4b5-726792584e45', '0192573b-1496-70c1-abf7-793da7ffdb90', 'Dictum: Ongegrond', 'option', 5, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-6560-c09f-ecf0a5be8774', '0192573b-1496-70c1-abf7-793da7ffdb90', 'Dictum: Kennelijk ongegrond', 'option', 6, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-6561-b090-d1ffcb0c96d8', '0192573b-1496-70c1-abf7-793da7ffdb90', 'Dictum: Gegrond', 'option', 7, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-6562-8fad-ec219d790ce9', '0192573b-1496-70c1-abf7-793da7ffdb90', 'Dictum: Kennelijk gegrond', 'option', 8, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-6563-b6b8-7ae0a2d45580', '0192573b-1496-70c1-abf7-793da7ffdb90', 'Dictum: Niet-ontvankelijk', 'option', 9, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-6564-7ccd-ac0d6cb6b718', '0192573b-1496-70c1-abf7-793da7ffdb90', 'Dictum: Kennelijk niet-ontvankelijk', 'option', 10, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-6565-657f-969856b3bdad', '0192573b-1496-70c1-abf7-793da7ffdb90', 'Dictum: Deels gegrond deels ongegrond', 'option', 11, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-6566-54d5-ee2199ff9959', '0192573b-1496-70c1-abf7-793da7ffdb90', 'Intrekking: Informeel', 'option', 12, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-6567-e606-5f7b7131f58f', '0192573b-1496-70c1-abf7-793da7ffdb90', 'Intrekking: Herziening door bezwaarde', 'option', 13, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-6568-18f8-3a60c2bf019c', '0192573b-1496-70c1-abf7-793da7ffdb90', 'Intrekking: Herziening door afdeling', 'option', 14, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-6569-40a3-dbf778ab0fdf', '0192573b-1496-70c1-abf7-793da7ffdb90', 'Zwaarte doorgeven', 'title', 15, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-656a-3905-00fe5ed5c202', '0192573b-1496-70c1-abf7-793da7ffdb90', 'Zwaarte', 'subtitle', 16, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-656b-f917-db1094ee190b', '0192573b-1496-70c1-abf7-793da7ffdb90', 'Geen / n.v.t. / leeg', 'option', 17, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-656c-e473-cdf43411a29b', '0192573b-1496-70c1-abf7-793da7ffdb90', 'A', 'option', 18, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-656d-29ba-49d8ac2ab03e', '0192573b-1496-70c1-abf7-793da7ffdb90', 'B', 'option', 19, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-656e-220c-05a28496bd5d', '0192573b-1496-70c1-abf7-793da7ffdb90', 'C', 'option', 20, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-656f-e959-bae36f9ae015', '0192573b-1496-70c1-abf7-793da7ffdb90', 'D', 'option', 21, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-6570-6570-40852074fbdf', '0192573b-1496-70c1-abf7-793da7ffdb90', 'E', 'option', 22, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-6571-5e26-8775c6732188', '0192e2f0-e34f-70c9-befd-23b76d432776', 'Uitkomst', 'name', 1, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-6572-6c48-22f920253e21', '0192e2f0-e34f-70c9-befd-23b76d432776', 'Termijnaanpassing vastleggen', 'title', 2, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-6573-d139-adf0f5299ed6', '0192e2f0-e34f-70c9-befd-23b76d432776', 'Termijnaanpassing', 'subtitle', 3, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-6574-0296-2d8177cc0058', '0192e2f0-e34f-70c9-befd-23b76d432776', 'In overleg met verzoeker', 'option', 4, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-6575-65cb-405071c7c3af', '0192e2f0-e34f-70c9-befd-23b76d432776', 'Verdaging', 'option', 5, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-6576-5623-2290cb6ffa9a', '0192e2f0-e34f-70c9-befd-23b76d432776', 'Uitkomst doorgeven', 'title', 6, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-6577-aa8a-277d015a9baf', '0192e2f0-e34f-70c9-befd-23b76d432776', 'Zaak afdoen met besluiten', 'subtitle', 7, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-6578-fdff-e213c355841d', '0192e2f0-e34f-70c9-befd-23b76d432776', 'Één besluit', 'option', 8, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-6579-a46b-8b2a4fc68b80', '0192e2f0-e34f-70c9-befd-23b76d432776', 'Deelbesluiten', 'option', 9, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-657a-47ec-587de8bd5ebb', '0192e2f0-e34f-70c9-befd-23b76d432776', 'Afwijsbesluit', 'option', 10, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-657b-00fc-6f60bb9fc9fe', '0192e2f0-e34f-70c9-befd-23b76d432776', 'Buiten behandeling stellen', 'option', 11, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-657c-c027-000af225c951', '0192e2f0-e34f-70c9-befd-23b76d432776', 'Zaak afdoen zonder besluit', 'subtitle', 12, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-657d-2b7e-2a4971a9397d', '0192e2f0-e34f-70c9-befd-23b76d432776', 'Burgerbrief', 'option', 13, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b3-657e-c07b-042dcbb35410', '0192e2f0-e34f-70c9-befd-23b76d432776', 'Reeds openbare informatie', 'option', 14, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b5-6c60-8f32-05e2ff08cd19', '0192e2f0-e34f-70c9-befd-23b76d432776', 'Doorzenden', 'option', 15, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b5-6c61-3f95-4638d10864f5', '0192e2f0-e34f-70c9-befd-23b76d432776', 'Ingetrokken', 'option', 16, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b5-6c62-c8d7-92b909e3a96a', '0192e2f1-0918-720c-8797-a33d3c63aa46', 'Uitkomst', 'name', 1, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b5-6c63-25fc-2d127cc39667', '0192e2f1-0918-720c-8797-a33d3c63aa46', 'Termijnaanpassing vastleggen', 'title', 2, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b5-6c64-72d8-e72092c8d6de', '0192e2f1-0918-720c-8797-a33d3c63aa46', 'Termijnaanpassing', 'subtitle', 3, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b5-6c65-cf28-8ed982c68872', '0192e2f1-0918-720c-8797-a33d3c63aa46', 'In overleg met verzoeker', 'option', 4, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b5-6c66-b93e-39a6924eaad6', '0192e2f1-0918-720c-8797-a33d3c63aa46', 'Verdaging', 'option', 5, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b5-6c67-5001-fccbe8338c4d', '0192e2f1-0918-720c-8797-a33d3c63aa46', 'Uitkomst doorgeven', 'title', 6, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b5-6c68-eebb-d5fdeca8641b', '0192e2f1-0918-720c-8797-a33d3c63aa46', 'Zaak afdoen met besluiten', 'subtitle', 7, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b5-6c69-6e21-257f595035ee', '0192e2f1-0918-720c-8797-a33d3c63aa46', 'Één besluit', 'option', 8, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b5-6c6a-13a3-babf3e77db75', '0192e2f1-0918-720c-8797-a33d3c63aa46', 'Deelbesluiten', 'option', 9, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b5-6c6b-523f-fbf5c317aee2', '0192e2f1-0918-720c-8797-a33d3c63aa46', 'Afwijsbesluit', 'option', 10, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b5-6c6c-1a08-4e5501a4e0eb', '0192e2f1-0918-720c-8797-a33d3c63aa46', 'Buiten behandeling stellen', 'option', 11, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b5-6c6d-8ed1-ef785b465352', '0192e2f1-0918-720c-8797-a33d3c63aa46', 'Zaak afdoen zonder besluit', 'subtitle', 12, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b5-6c6e-0b79-ab2dc3598da5', '0192e2f1-0918-720c-8797-a33d3c63aa46', 'Burgerbrief', 'option', 13, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b5-6c6f-8cc4-115e1c589863', '0192e2f1-0918-720c-8797-a33d3c63aa46', 'Reeds openbare informatie', 'option', 14, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b5-6c70-8b79-a1de80806a39', '0192e2f1-0918-720c-8797-a33d3c63aa46', 'Doorzenden', 'option', 15, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192573b-14b5-6c71-3508-5cd97965589e', '0192e2f1-0918-720c-8797-a33d3c63aa46', 'Ingetrokken', 'option', 16, '2024-12-02 14:00:00', '2024-12-02 14:00:00');

    INSERT INTO "petition_type_custom_dates_labels" ("petition_type_id", "date_label", "created_at", "updated_at") VALUES
    ('0192573b-1496-70c1-abf7-793da7ffdb80', 'date_ruling', '2025-01-17 15:04:17+00', '2025-01-17 15:04:17+00'),
    ('0192573b-1496-70c1-abf7-793da7ffdb80', 'date_withdrawn', '2025-01-17 15:04:17+00', '2025-01-17 15:04:17+00'),
    ('0192573b-1496-70c1-abf7-793da7ffdb81', 'date_ruling', '2025-01-17 15:04:17+00', '2025-01-17 15:04:17+00'),
    ('0192573b-1496-70c1-abf7-793da7ffdb81', 'date_withdrawn', '2025-01-17 15:04:17+00', '2025-01-17 15:04:17+00'),
    ('0192573b-1496-70c1-abf7-793da7ffdb88', 'date_ruling', '2025-01-17 15:04:17+00', '2025-01-17 15:04:17+00'),
    ('0192573b-1496-70c1-abf7-793da7ffdb88', 'date_withdrawn', '2025-01-17 15:04:17+00', '2025-01-17 15:04:17+00'),
    ('0192573b-1496-70c1-abf7-793da7ffdb89', 'date_ruling', '2025-01-17 15:04:17+00', '2025-01-17 15:04:17+00'),
    ('0192573b-1496-70c1-abf7-793da7ffdb89', 'date_withdrawn', '2025-01-17 15:04:17+00', '2025-01-17 15:04:17+00'),
    ('0192573b-1496-70c1-abf7-793da7ffdb90', 'date_ruling', '2025-01-17 15:04:17+00', '2025-01-17 15:04:17+00'),
    ('0192573b-1496-70c1-abf7-793da7ffdb90', 'date_withdrawn', '2025-01-17 15:04:17+00', '2025-01-17 15:04:17+00'),
    ('0192e2f0-e34f-70c9-befd-23b76d432776', 'date_settlement_without_decision', '2025-01-17 15:04:31+00', '2025-01-17 15:04:31+00'),
    ('0192e2f0-e34f-70c9-befd-23b76d432776', 'date_ruling', '2025-01-17 15:04:17+00', '2025-01-17 15:04:17+00'),
    ('0192e2f0-e34f-70c9-befd-23b76d432776', 'date_appointment_with_applicant', '2025-01-17 15:04:17+00', '2025-01-17 15:04:17+00'),
    ('0192e2f1-0918-720c-8797-a33d3c63aa46', 'date_settlement_without_decision', '2025-01-17 15:04:31+00', '2025-01-17 15:04:31+00'),
    ('0192e2f1-0918-720c-8797-a33d3c63aa46', 'date_ruling', '2025-01-17 15:04:17+00', '2025-01-17 15:04:17+00'),
    ('0192e2f1-0918-720c-8797-a33d3c63aa46', 'date_appointment_with_applicant', '2025-01-17 15:04:17+00', '2025-01-17 15:04:17+00');

END $$
