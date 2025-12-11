-- A cleanup file for the Zaakvolgsysteem Test and Staging environments
-- Version: Mon 27 Jan 2024 10:24
-- DB version: v0.0.23

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
    ('0192573b-1475-7052-8b34-c314d33e0bcf', 'In behandeling', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00', 'warning', 't'),
    ('0192573b-1477-7100-949f-50be988ce068', 'Afgehandeld', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00', 'success', 'f'),
    ('0192573b-148f-7075-b0f2-76c268c0d61a', 'Afgerond', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00', 'info', 'f');

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
    ('0192573b-14b2-7144-88d2-f290b95a4a63', 'Beroep niet tijdig beslissen ', 'BNT', 'end_required', 14, 'Startdatum', 'Einddatum', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00', dep_uuid_c1),
    ('0192573b-14b2-7144-88d2-f290b95a4a64', 'Opschorting ', 'Opgeschort', 'end_undefined', 14, 'Startdatum', 'Einddatum', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00', dep_uuid_c4),
    ('0192573b-14b2-7144-88d2-f290b95a4a65', 'Ingebrekestelling ', 'IGS', 'system_defined', 14, 'Startdatum', 'Einddatum', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00', dep_uuid_c4),
    ('0192573b-14b2-7144-88d2-f290b95a4a66', 'Beroep niet tijdig beslissen ', 'BNT', 'end_required', 14, 'Startdatum', 'Einddatum', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00', dep_uuid_c4),
    ('0192573b-14b2-7144-88d2-f290b95a4a67', 'Opschorting ', 'Opgeschort', 'end_undefined', 14, 'Startdatum', 'Einddatum', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00', dep_uuid_pdo),
    ('0192573b-14b2-7144-88d2-f290b95a4a68', 'Ingebrekestelling ', 'IGS', 'system_defined', 14, 'Startdatum', 'Einddatum', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00', dep_uuid_pdo),
    ('0192573b-14b2-7144-88d2-f290b95a4a69', 'Beroep niet tijdig beslissen ', 'BNT', 'end_required', 14, 'Startdatum', 'Einddatum', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00', dep_uuid_pdo);

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

    TRUNCATE  "petition_type_custom_dates_labels";
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

    TRUNCATE "custom_petition_properties";
    INSERT INTO "custom_petition_properties" ("id", "petition_type_id", "name", "type", "ordering", "created_at", "updated_at") VALUES
    ('0192e2f1-0923-61d1-ab62-9180408e49a5', '0192573b-1496-70c1-abf7-793da7ffdb80','Uitkomst', 'name', 1, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0923-61d2-0e5d-f8a5f0b845bf', '0192573b-1496-70c1-abf7-793da7ffdb80','Uitkomst doorgeven', 'title', 2, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0923-61d3-6240-c62d3d72b5e3', '0192573b-1496-70c1-abf7-793da7ffdb80','Uitspraak', 'subtitle', 3, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0923-61d4-055b-f93cbd54c0e2', '0192573b-1496-70c1-abf7-793da7ffdb80','Gegrond', 'option', 4, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0923-61d5-d682-783e7fc86ae8', '0192573b-1496-70c1-abf7-793da7ffdb80','Ongegrond', 'option', 5, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0923-61d6-7177-c53e66a88fcd', '0192573b-1496-70c1-abf7-793da7ffdb80','Intrekking', 'option', 6, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0923-61d7-e75e-826345f31d99', '0192573b-1496-70c1-abf7-793da7ffdb80','Niet-ontvankelijk', 'option', 7, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0923-61d8-97c5-2f17b3ced18a', '0192573b-1496-70c1-abf7-793da7ffdb80','Kennelijk niet-ontvankelijk', 'option', 8, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0923-61d9-7e80-019df78c438f', '0192573b-1496-70c1-abf7-793da7ffdb81', 'Uitkomst', 'name', 1, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0923-61da-b9a9-f46c966981b7', '0192573b-1496-70c1-abf7-793da7ffdb81', 'Uitkomst doorgeven', 'title', 2, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0923-61db-8bd1-d3644481ea7c', '0192573b-1496-70c1-abf7-793da7ffdb81', 'Uitspraak', 'subtitle', 3, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0923-61dc-f0ab-e37bd1a37b44', '0192573b-1496-70c1-abf7-793da7ffdb81', 'Gegrond', 'option', 4, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0923-61dd-3310-1fd2c20a3e12', '0192573b-1496-70c1-abf7-793da7ffdb81', 'Ongegrond', 'option', 5, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0923-61de-bbfc-ae52bb5963f5', '0192573b-1496-70c1-abf7-793da7ffdb81', 'Intrekking', 'option', 6, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0923-61df-26c4-667d56d02862', '0192573b-1496-70c1-abf7-793da7ffdb81', 'Niet-ontvankelijk', 'option', 7, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0923-61e0-b64e-0ea729f301d7', '0192573b-1496-70c1-abf7-793da7ffdb81', 'Kennelijk niet-ontvankelijk', 'option', 8, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0923-61e1-06bd-471efd9581ac', '0192573b-1496-70c1-abf7-793da7ffdb88', 'Uitkomst', 'name', 1, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0923-61e2-8e02-bb667e9345cc', '0192573b-1496-70c1-abf7-793da7ffdb88', 'Uitkomst doorgeven', 'title', 2, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0923-61e3-452c-be5ed5bca832', '0192573b-1496-70c1-abf7-793da7ffdb88', 'Uitspraak', 'subtitle', 3, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0923-61e4-050f-b601e2db6def', '0192573b-1496-70c1-abf7-793da7ffdb88', 'Gegrond', 'option', 4, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0923-61e5-a035-76f2c2ee304a', '0192573b-1496-70c1-abf7-793da7ffdb88', 'Ongegrond', 'option', 5, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0923-61e6-656e-fb50db91a551', '0192573b-1496-70c1-abf7-793da7ffdb88', 'Intrekking', 'option', 6, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0923-61e7-9040-dad3f3f281d6', '0192573b-1496-70c1-abf7-793da7ffdb88', 'Niet-ontvankelijk', 'option', 7, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0925-68e0-3f66-7966c1450815', '0192573b-1496-70c1-abf7-793da7ffdb88', 'Kennelijk niet-ontvankelijk', 'option', 8, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0925-68e1-50d6-2f444232691d', '0192573b-1496-70c1-abf7-793da7ffdb89', 'Uitkomst', 'name', 1, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0925-68e2-e5b9-5e3f3e88fc94', '0192573b-1496-70c1-abf7-793da7ffdb89', 'Uitkomst doorgeven', 'title', 2, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0925-68e3-981d-cb6fd3dab45a', '0192573b-1496-70c1-abf7-793da7ffdb89', 'Uitspraak', 'subtitle', 3, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0925-68e4-2ef9-ee5d4b738b4c', '0192573b-1496-70c1-abf7-793da7ffdb89', 'Gegrond', 'option', 4, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0925-68e5-e188-5305842649ee', '0192573b-1496-70c1-abf7-793da7ffdb89', 'Ongegrond', 'option', 5, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0925-68e6-eb89-ae511aa293d3', '0192573b-1496-70c1-abf7-793da7ffdb89', 'Intrekking', 'option', 6, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0925-68e7-44ab-43c0fe3a4549', '0192573b-1496-70c1-abf7-793da7ffdb89', 'Niet-ontvankelijk', 'option', 7, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0925-68e8-b437-4cfce4b4ee5e', '0192573b-1496-70c1-abf7-793da7ffdb89', 'Kennelijk niet-ontvankelijk', 'option', 8, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0925-68e9-6c9a-dfc54e1c48ca', '0192573b-1496-70c1-abf7-793da7ffdb90', 'Uitkomst', 'name', -3, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0925-68ea-9aa8-e1ae65a9e51b', '0192573b-1496-70c1-abf7-793da7ffdb90', 'Binnen/buiten termijn doorgeven', 'title', -3, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0925-68eb-5808-5d6280082c71', '0192573b-1496-70c1-abf7-793da7ffdb90', 'Binnen/buiten termijn', 'subtitle', -2, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0925-68ec-1f53-b872d9bade9f', '0192573b-1496-70c1-abf7-793da7ffdb90', 'Binnen wettelijke termijn', 'option', -2, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0925-68ed-8d2c-f0bf03cff8b0', '0192573b-1496-70c1-abf7-793da7ffdb90', 'Binnen afgesproken termijn', 'option', -1, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0925-68ee-1e5e-512b615b5060', '0192573b-1496-70c1-abf7-793da7ffdb90', 'Buiten wettelijke/afgesproken termijn', 'option', 0, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0925-68ef-d7a1-29906f4ff439', '0192573b-1496-70c1-abf7-793da7ffdb90', 'Uitkomst doorgeven', 'title', 2, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0925-68f0-4411-37fdf5fc5925', '0192573b-1496-70c1-abf7-793da7ffdb90', 'Dictum', 'subtitle', 3, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0925-68f1-aa27-4366cd972a3f', '0192573b-1496-70c1-abf7-793da7ffdb90', 'Gegrond', 'option', 4, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0925-68f2-b09d-255c58088dff', '0192573b-1496-70c1-abf7-793da7ffdb90', 'Kennelijk gegrond', 'option', 5, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0925-68f3-a5a3-dd7f4c135426', '0192573b-1496-70c1-abf7-793da7ffdb90', 'Ongegrond', 'option', 6, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0925-68f4-db5e-609eeef7198a', '0192573b-1496-70c1-abf7-793da7ffdb90', 'Kennelijk ongegrond', 'option', 7, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0925-68f5-5385-4921212789aa', '0192573b-1496-70c1-abf7-793da7ffdb90', 'Niet-ontvankelijk', 'option', 8, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0925-68f6-68d5-f15fdc1dbce6', '0192573b-1496-70c1-abf7-793da7ffdb90', 'Kennelijk niet-ontvankelijk', 'option', 9, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0925-68f7-25f3-f58442483f00', '0192573b-1496-70c1-abf7-793da7ffdb90', 'Doorzending', 'subtitle', 10, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0925-68f8-ec39-5f3c3eda02c3', '0192573b-1496-70c1-abf7-793da7ffdb90', 'Doorzending', 'option', 11, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0925-68f9-fa40-3c5fe20bc3b5', '0192573b-1496-70c1-abf7-793da7ffdb90', 'Intrekking bezwaar', 'subtitle', 12, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0927-6ff0-08eb-9e697339cd66', '0192573b-1496-70c1-abf7-793da7ffdb90', 'Herziening – herstel bezwaar', 'option', 13, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0927-6ff1-a3e1-2273f192daf6', '0192573b-1496-70c1-abf7-793da7ffdb90', 'Herziening – herstel primair besluit', 'option', 14, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0927-6ff2-e8f7-db661c696101', '0192573b-1496-70c1-abf7-793da7ffdb90', 'Informeel', 'option', 15, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0927-6ff3-63d5-cbb60b5a9fa0', '0192573b-1496-70c1-abf7-793da7ffdb90', 'Overig', 'option', 16, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0927-6ff4-dedb-aedb2cd7abaf', '0192573b-1496-70c1-abf7-793da7ffdb90', 'Zwaarte doorgeven', 'title', 17, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0927-6ff5-1252-e44c10aa05e1', '0192573b-1496-70c1-abf7-793da7ffdb90', 'Zwaarte', 'subtitle', 18, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0927-6ff6-1114-6adf42ba533d', '0192573b-1496-70c1-abf7-793da7ffdb90', 'A', 'option', 19, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0927-6ff7-4951-4292e90a8b30', '0192573b-1496-70c1-abf7-793da7ffdb90', 'B', 'option', 20, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0927-6ff8-3f06-ce7a9032fd7a', '0192573b-1496-70c1-abf7-793da7ffdb90', 'C', 'option', 21, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0927-6ff9-74f3-49ca282b6e04', '0192573b-1496-70c1-abf7-793da7ffdb90', 'D', 'option', 22, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0927-6ffa-7f83-b10c3d5790c3', '0192573b-1496-70c1-abf7-793da7ffdb90', 'E', 'option', 23, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0927-6ffb-748b-dd040ecf6933', '0192e2f0-e34f-70c9-befd-23b76d432776', 'Eigenschappen', 'name', 1, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0927-6ffc-9d8a-244345f69a66', '0192e2f0-e34f-70c9-befd-23b76d432776', 'Datumafspraak doorgeven', 'title', 2, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0927-6ffd-b2ae-4faebabbd698', '0192e2f0-e34f-70c9-befd-23b76d432776', 'Datumafspraak', 'subtitle', 3, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0927-6ffe-4583-b185a0c02803', '0192e2f0-e34f-70c9-befd-23b76d432776', 'In overleg met verzoeker', 'option', 4, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0927-6fff-7f9c-4487d3c5ee7c', '0192e2f0-e34f-70c9-befd-23b76d432776', 'Termijnaanpassing doorgeven', 'title', 5, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0928-6000-594d-cf287c36844d', '0192e2f0-e34f-70c9-befd-23b76d432776', 'Termijnaanpassing', 'subtitle', 6, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0928-6001-8e0d-6841301898d0', '0192e2f0-e34f-70c9-befd-23b76d432776', 'Verdaging', 'option', 7, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0928-6002-1660-8f2b90b02342', '0192e2f0-e34f-70c9-befd-23b76d432776', 'Uitkomst doorgeven', 'title', 8, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0928-6003-2e31-f55a4a1c2708', '0192e2f0-e34f-70c9-befd-23b76d432776', 'Zaak afdoen met besluit', 'subtitle', 9, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0928-6004-4d0f-6358a2d713e8', '0192e2f0-e34f-70c9-befd-23b76d432776', 'Een besluit', 'option', 10, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0928-6005-885a-a402a2d3d375', '0192e2f0-e34f-70c9-befd-23b76d432776', 'Deelbesluiten', 'option', 11, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0928-6006-e5f1-974f537d67e8', '0192e2f0-e34f-70c9-befd-23b76d432776', 'Afwijsbesluit', 'option', 12, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0928-6007-4ef3-1298a0bb7ca9', '0192e2f0-e34f-70c9-befd-23b76d432776', 'Buiten behandeling stellen', 'option', 13, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0928-6008-f3ec-fc41660d93b9', '0192e2f0-e34f-70c9-befd-23b76d432776', 'Zaak afdoen zonder besluit', 'subtitle', 14, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0928-6009-7430-92956084d9d6', '0192e2f0-e34f-70c9-befd-23b76d432776', 'Verzoek ingetrokken', 'option', 15, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0928-600a-456a-1e83c105a8f7', '0192e2f0-e34f-70c9-befd-23b76d432776', 'Verzoek doorverwezen', 'option', 16, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0928-600b-a590-ce5344418c82', '0192e2f0-e34f-70c9-befd-23b76d432776', 'Verzoek betrof bij nader inzien burgervraag', 'option', 17, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0928-600c-2148-b5cfba450598', '0192e2f0-e34f-70c9-befd-23b76d432776', 'Verzoek betrof reeds openbare informatie', 'option', 18, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0928-600d-74be-dbcea3eca0aa', '0192e2f0-e34f-70c9-befd-23b76d432776', 'Anders', 'option', 19, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0928-600e-e3d5-b85592bf22ae', '0192e2f0-e34f-70c9-befd-23b76d432776', 'Zwaarte doorgeven', 'title', 20, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0928-600f-37d0-a245979af45e', '0192e2f0-e34f-70c9-befd-23b76d432776', 'Zwaarte', 'subtitle', 21, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0928-6010-b936-4b8111e598c1', '0192e2f0-e34f-70c9-befd-23b76d432776', 'A', 'option', 22, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0928-6011-4f8f-2850588b51ba', '0192e2f0-e34f-70c9-befd-23b76d432776', 'B', 'option', 23, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0928-6012-5a6b-4b1159c34812', '0192e2f0-e34f-70c9-befd-23b76d432776', 'C', 'option', 24, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0928-6013-8203-acdd76c32ef5', '0192e2f0-e34f-70c9-befd-23b76d432776', 'D', 'option', 25, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0928-6014-6d14-e5b14d5ddb04', '0192e2f0-e34f-70c9-befd-23b76d432776', 'E', 'option', 26, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0928-6015-9ffc-43a199ac1e53', '0192e2f1-0918-720c-8797-a33d3c63aa46', 'Eigenschappen', 'name', 1, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0928-6016-efdd-344996e58358', '0192e2f1-0918-720c-8797-a33d3c63aa46', 'Datumafspraak doorgeven', 'title', 2, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0928-6017-e3ca-5029eb144def', '0192e2f1-0918-720c-8797-a33d3c63aa46', 'Datumafspraak', 'subtitle', 3, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0928-6018-9eeb-b0ea3f9fd86e', '0192e2f1-0918-720c-8797-a33d3c63aa46', 'In overleg met verzoeker', 'option', 4, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0928-6019-5d7f-e6d8926ec906', '0192e2f1-0918-720c-8797-a33d3c63aa46', 'Termijnaanpassing doorgeven', 'title', 5, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0928-601a-657c-9b2d4326b738', '0192e2f1-0918-720c-8797-a33d3c63aa46', 'Termijnaanpassing', 'subtitle', 6, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0928-601b-85b8-1f382b8e34c5', '0192e2f1-0918-720c-8797-a33d3c63aa46', 'Verdaging', 'option', 7, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0928-601c-8045-321aea67b8f9', '0192e2f1-0918-720c-8797-a33d3c63aa46', 'Uitkomst doorgeven', 'title', 8, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0928-601d-3478-691f0abb7cf5', '0192e2f1-0918-720c-8797-a33d3c63aa46', 'Zaak afdoen met besluit', 'subtitle', 9, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0928-601e-858c-730d616bf4e1', '0192e2f1-0918-720c-8797-a33d3c63aa46', 'Een besluit', 'option', 10, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0928-601f-7684-0a78d07f5ab3', '0192e2f1-0918-720c-8797-a33d3c63aa46', 'Deelbesluiten', 'option', 11, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0928-6020-1b82-fac7116a6535', '0192e2f1-0918-720c-8797-a33d3c63aa46', 'Afwijsbesluit', 'option', 12, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0928-6021-810f-c41b8b27ba99', '0192e2f1-0918-720c-8797-a33d3c63aa46', 'Buiten behandeling stellen', 'option', 13, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0928-6022-f278-4821a902a47a', '0192e2f1-0918-720c-8797-a33d3c63aa46', 'Zaak afdoen zonder besluit', 'subtitle', 14, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0928-6023-3bc5-110f71d263d4', '0192e2f1-0918-720c-8797-a33d3c63aa46', 'Verzoek ingetrokken', 'option', 15, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0928-6024-30eb-31a9f040e9ed', '0192e2f1-0918-720c-8797-a33d3c63aa46', 'Verzoek doorverwezen', 'option', 16, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0928-6025-df75-2a34fcfb3955', '0192e2f1-0918-720c-8797-a33d3c63aa46', 'Verzoek betrof bij nader inzien burgervraag', 'option', 17, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0928-6026-ca33-38d0bc858cb3', '0192e2f1-0918-720c-8797-a33d3c63aa46', 'Verzoek betrof reeds openbare informatie', 'option', 18, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0928-6027-3880-bd0bc8a73fa6', '0192e2f1-0918-720c-8797-a33d3c63aa46', 'Anders', 'option', 19, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0928-6028-5391-43886697f0af', '0192e2f1-0918-720c-8797-a33d3c63aa46', 'Zwaarte doorgeven', 'title', 20, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-0928-6029-c522-e0281f0c2d68', '0192e2f1-0918-720c-8797-a33d3c63aa46', 'Zwaarte', 'subtitle', 21, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-092a-6700-349e-20f08ab6ebb3', '0192e2f1-0918-720c-8797-a33d3c63aa46', 'A', 'option', 22, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-092a-6701-50db-eadf0bbfba71', '0192e2f1-0918-720c-8797-a33d3c63aa46', 'B', 'option', 23, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-092a-6702-f363-3f2baa76e575', '0192e2f1-0918-720c-8797-a33d3c63aa46', 'C', 'option', 24, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-092a-6703-ce28-f1f7068aa188', '0192e2f1-0918-720c-8797-a33d3c63aa46', 'D', 'option', 25, '2024-12-02 14:00:00', '2024-12-02 14:00:00'),
    ('0192e2f1-092a-6704-349b-566a99b457e5', '0192e2f1-0918-720c-8797-a33d3c63aa46', 'E', 'option', 26, '2024-12-02 14:00:00', '2024-12-02 14:00:00');

END $$
