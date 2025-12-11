-- A cleanup file for the Zaakvolgsysteem Test and Staging environments
-- Version: Mon 3 Feb 2024 10:58
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

-- Vanaf april heten de clusters anders:
-- Cluster 4 : Team A Woo verzoeken regulier
-- PDO       : Team B Woo verzoeken Corona
-- Cluster 1 : Team C Bezwaar en Beroep Woo     voor alle B&B die te maken hebben met Woo verzoeken
-- Cluster 1 : WJZ Afdeling Bezwaar en Beroep   voor alle B&B exclusief de Woo

UPDATE "departments" SET "name"='Team A Woo verzoeken regulier', "slug"='team-a', "abbreviation"='A' WHERE "name"='Cluster 4';
UPDATE "departments" SET "name"='Team B Woo verzoeken Corona', "slug"='team-b', "abbreviation"='B' WHERE "name"='Programmadirectie Openbaarheid';
UPDATE "departments" SET "name"='Team C Bezwaar en Beroep Woo', "slug"='team-c', "abbreviation"='C' WHERE "name"='Cluster 1';

INSERT INTO "departments" ("id", "name", "slug", "abbreviation", "created_at", "updated_at") VALUES
('1efe221b-8a2a-6641-103a-234043445eba', 'WJZ Afdeling Bezwaar en Beroep', 'wjz-bb', 'WJZ', NOW(), NOW());

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

    TRUNCATE  "public_holidays";
    INSERT INTO "public_holidays" ("id", "name", "date", "created_at", "updated_at") VALUES
    ('1efe21c0-0e03-64f1-eb5b-66a8fa7d76c2', 'Hemelvaartsdag', '2024-05-09', NOW(), NOW()),
    ('1efe21c0-0e05-6c00-c984-7c65a85cdf92', 'Eerste Kerstdag', '2024-12-25', NOW(), NOW()),
    ('1efe21c0-0e05-6c01-d55d-86be8a242df7', 'Pinksteren', '2024-05-19', NOW(), NOW()),
    ('1efe21c0-0e05-6c02-c302-f396e1b2d9c4', 'Bevrijdingsdag', '2024-05-05', NOW(), NOW()),
    ('1efe21c0-0e05-6c03-79ff-3af1c6a96984', 'Nieuwjaarsdag', '2024-01-01', NOW(), NOW()),
    ('1efe21c0-0e05-6c04-01a2-082738f7bd29', 'Pasen', '2024-03-31', NOW(), NOW()),
    ('1efe21c0-0e05-6c05-eb1b-91a5638cdc97', 'Goede Vrijdag', '2024-03-29', NOW(), NOW()),
    ('1efe21c0-0e05-6c06-0bab-4a292add0da3', 'Koningsdag', '2024-04-27', NOW(), NOW()),
    ('1efe21c0-0e05-6c07-dc78-18e5ce354837', 'Tweede Kerstdag', '2024-12-26', NOW(), NOW());

    TRUNCATE  "petition_statuses";
    INSERT INTO "petition_statuses" ("id", "status", "badge", "default_status", "created_at", "updated_at") VALUES
    ('1efe21c0-0e05-6c08-b613-92d4d4636a66', 'In behandeling', 'warning', 't', NOW(), NOW()),
    ('1efe21c0-0e05-6c09-7c50-4a26311f4509', 'Afgehandeld', 'success', 'f', NOW(), NOW()),
    ('1efe21c0-0e05-6c0a-7930-bc42c639a165', 'Afgerond', 'info', 'f', NOW(), NOW());

    TRUNCATE  "contacts";
    INSERT INTO "contacts" ("id", "initials", "last_name", "organisation_name", "email_address", "phone_number", "street", "house_number", "postal_code", "city", "is_journalist", "department_id", "created_at", "updated_at") VALUES
    ('1efe21c0-0e05-6c0b-0c5d-725158db1231', 'C.', 'de Vries', '', 'c.devries@rdobeheer.nl', '054 2542155', NULL, NULL, '1531CZ', 'Achtmaal', 'f', dep_uuid_team_a, NOW(), NOW()),
    ('1efe21c0-0e05-6c0c-b545-c8d9243083e4', 'S.', 'Hendriks', 'VNO-NCW', 's.hendriks@rdobeheer.nl', NULL, 'van den Eerenbeemtdreef', NULL, '9354VD', NULL, 'f', dep_uuid_team_a, NOW(), NOW()),
    ('1efe21c0-0e05-6c0d-eaee-3a4d2606373e', 'T.', 'Lemmens', 'AGA Juristen', 't.lemmens@rdobeheer.nl', NULL, NULL, NULL, '4382JC', NULL, 'f', dep_uuid_team_a, NOW(), NOW()),
    ('1efe21c0-0e05-6c0e-4ac3-493920496b4f', 'J.', 'Maes', 'NRC', 'j.maes@rdobeheer.nl', '0900 671596', 'Schipperhof', '18-71', NULL, 'Tuil', 'f', dep_uuid_team_a, NOW(), NOW()),
    ('1efe21c0-0e05-6c0f-f805-1b81db71016d', 'C.', 'de Vries', '', 'c.devries@rdobeheer.nl', '054 2542155', NULL, NULL, '1531CZ', 'Achtmaal', 'f', dep_uuid_team_b, NOW(), NOW()),
    ('1efe21c0-0e05-6c10-00d6-171616bfc28f', 'S.', 'Hendriks', 'VNO-NCW', 's.hendriks@rdobeheer.nl', NULL, 'van den Eerenbeemtdreef', NULL, '9354VD', NULL, 'f', dep_uuid_team_b, NOW(), NOW()),
    ('1efe21c0-0e05-6c11-bd24-3327d2c34ff4', 'T.', 'Lemmens', 'AGA Juristen', 't.lemmens@rdobeheer.nl', NULL, NULL, NULL, '4382JC', NULL, 'f', dep_uuid_team_b, NOW(), NOW()),
    ('1efe21c0-0e05-6c12-5822-ee9fe31a56e7', 'J.', 'Maes', 'NRC', 'j.maes@rdobeheer.nl', '0900 671596', 'Schipperhof', '18-71', NULL, 'Tuil', 'f', dep_uuid_team_b, NOW(), NOW()),
    ('1efe21c0-0e05-6c13-e01a-f30c185a95a2', 'C.', 'de Vries', '', 'c.devries@rdobeheer.nl', '054 2542155', NULL, NULL, '1531CZ', 'Achtmaal', 'f', dep_uuid_team_c, NOW(), NOW()),
    ('1efe21c0-0e05-6c14-5501-f9141773d506', 'S.', 'Hendriks', 'VNO-NCW', 's.hendriks@rdobeheer.nl', NULL, 'van den Eerenbeemtdreef', NULL, '9354VD', NULL, 'f', dep_uuid_team_c, NOW(), NOW()),
    ('1efe21c0-0e05-6c15-3ed7-e4e42e5ac120', 'T.', 'Lemmens', 'AGA Juristen', 't.lemmens@rdobeheer.nl', NULL, NULL, NULL, '4382JC', NULL, 'f', dep_uuid_team_c, NOW(), NOW()),
    ('1efe21c0-0e05-6c16-6969-318f49d6aad8', 'J.', 'Maes', 'NRC', 'j.maes@rdobeheer.nl', '0900 671596', 'Schipperhof', '18-71', NULL, 'Tuil', 'f', dep_uuid_team_c, NOW(), NOW()),
    ('1efe21c0-0e05-6c17-4511-4fa317021e06', 'C.', 'de Vries', '', 'c.devries@rdobeheer.nl', '054 2542155', NULL, NULL, '1531CZ', 'Achtmaal', 'f', dep_uuid_wjz_bb, NOW(), NOW()),
    ('1efe21c0-0e05-6c18-8f42-c93ba8422269', 'S.', 'Hendriks', 'VNO-NCW', 's.hendriks@rdobeheer.nl', NULL, 'van den Eerenbeemtdreef', NULL, '9354VD', NULL, 'f', dep_uuid_wjz_bb, NOW(), NOW()),
    ('1efe21c0-0e05-6c19-213a-9980f3820e97', 'T.', 'Lemmens', 'AGA Juristen', 't.lemmens@rdobeheer.nl', NULL, NULL, NULL, '4382JC', NULL, 'f', dep_uuid_wjz_bb, NOW(), NOW()),
    ('1efe21c0-0e05-6c1a-3837-8aba5c7695e0', 'J.', 'Maes', 'NRC', 'j.maes@rdobeheer.nl', '0900 671596', 'Schipperhof', '18-71', NULL, 'Tuil', 'f', dep_uuid_wjz_bb, NOW(), NOW());

    TRUNCATE  "petition_types";
    INSERT INTO "petition_types" ("id", "name", "type", "department_id", "created_at", "updated_at") VALUES
    ('1efe21c0-0e05-6c1b-73a0-c2053da2aea7', 'WOO-verzoek', 'woo_verzoek', dep_uuid_team_a, NOW(), NOW()),
    ('1efe21c0-0e05-6c1c-174d-9dbcbabed1fd', 'WOO-verzoek (covid-19)', 'woo_verzoek', dep_uuid_team_b, NOW(), NOW()),
    ('1efe21c0-0e05-6c1d-a4f9-403891683d1a', 'Beroepsprocedure: Hoger beroep', 'beroep', dep_uuid_team_c, NOW(), NOW()),
    ('1efe21c0-0e05-6c1e-385e-e65fc6e4b3b5', 'Beroepsprocedure: Beroep niet tijdig', 'beroep', dep_uuid_team_c, NOW(), NOW()),
    ('1efe21c0-0e05-6c1f-540b-17ffa7f9494b', 'Beroepsprocedure: Inhoudelijk beroep', 'beroep', dep_uuid_team_c, NOW(), NOW()),
    ('1efe21c0-0e05-6c20-cfaf-2a6884778e05', 'Beroepsprocedure: VOVO', 'beroep', dep_uuid_team_c, NOW(), NOW()),
    ('1efe21c0-0e05-6c21-740e-5b0e14d07757', 'Bezwaarprocedure', 'bezwaar', dep_uuid_team_c, NOW(), NOW()),
    ('1efe21c0-0e05-6c22-0d63-53f9b2e8bca4', 'Beroepsprocedure: Hoger beroep', 'beroep', dep_uuid_wjz_bb, NOW(), NOW()),
    ('1efe21c0-0e05-6c23-d703-956dfba84fc9', 'Beroepsprocedure: Beroep niet tijdig', 'beroep', dep_uuid_wjz_bb, NOW(), NOW()),
    ('1efe21c0-0e05-6c24-a96a-16604abee71d', 'Beroepsprocedure: Inhoudelijk beroep', 'beroep', dep_uuid_wjz_bb, NOW(), NOW()),
    ('1efe21c0-0e05-6c25-49bc-30e3c3f69b94', 'Beroepsprocedure: VOVO', 'beroep', dep_uuid_wjz_bb, NOW(), NOW()),
    ('1efe21c0-0e05-6c26-3b9f-f69e7ef42ec5', 'Bezwaarprocedure', 'bezwaar', dep_uuid_wjz_bb, NOW(), NOW());

    TRUNCATE  "term_adjustments";
    INSERT INTO "term_adjustments" ("id", "name", "period_in_days", "can_enter_date_manually", "department_id", "created_at", "updated_at") VALUES
    ('1efe21c0-0e05-6c27-5596-62b7c536fe5f', 'Verdaging', 14, 't', dep_uuid_team_a, NOW(), NOW()),
    ('1efe21c0-0e05-6c28-5215-74144d78edd5', 'Afspraak met verzoeker', 0, 't', dep_uuid_team_a, NOW(), NOW()),
    ('1efe21c0-0e05-6c29-55c5-eaa127105537', 'Anders, namelijk:', 0, 't', dep_uuid_team_a, NOW(), NOW()),
    ('1efe21c0-0e08-6310-958e-cd648c24665e', 'Verdaging', 14, 't', dep_uuid_team_b, NOW(), NOW()),
    ('1efe21c0-0e08-6311-7592-ea03e8ac5b9c', 'Afspraak met verzoeker', 0, 't', dep_uuid_team_b, NOW(), NOW()),
    ('1efe21c0-0e08-6312-3b5b-f2763872d001', 'Anders, namelijk:', 0, 't', dep_uuid_team_b, NOW(), NOW()),
    ('1efe21c0-0e08-6313-0956-3613eb09c85e', 'Verdaging', 14, 't', dep_uuid_team_c, NOW(), NOW()),
    ('1efe21c0-0e08-6314-3f39-4d39b7b92403', 'Afspraak met verzoeker', 0, 't', dep_uuid_team_c, NOW(), NOW()),
    ('1efe21c0-0e08-6315-68cf-0df3be2b8f0d', 'Anders, namelijk:', 0, 't', dep_uuid_team_c, NOW(), NOW()),
    ('1efe21c0-0e08-6316-0a26-cac90cabcc26', 'Verdaging', 14, 't', dep_uuid_wjz_bb, NOW(), NOW()),
    ('1efe21c0-0e08-6317-0981-f23e681f27ba', 'Afspraak met verzoeker', 0, 't', dep_uuid_wjz_bb, NOW(), NOW()),
    ('1efe21c0-0e08-6318-652b-84e7f9808208', 'Anders, namelijk:', 0, 't', dep_uuid_wjz_bb, NOW(), NOW());

    TRUNCATE  "phases";
    INSERT INTO "phases" ("id", "name", "status_label", "type", "period_in_days", "start_date_label", "end_date_label", "department_id", "created_at", "updated_at") VALUES
    ('1efe21c0-0e08-6319-8991-72da2c4d5c74', 'Opschorting ', 'Opgeschort', 'end_undefined', 0, 'Startdatum', 'Einddatum', dep_uuid_team_a, NOW(), NOW()),
    ('1efe21c0-0e08-631a-4239-f2e9856a48e5', 'Ingebrekestelling ', 'IGS', 'system_defined', 14, 'Startdatum', 'Einddatum', dep_uuid_team_a, NOW(), NOW()),
    ('1efe21c0-0e08-631b-6185-d624eccaf57b', 'Beroep niet tijdig beslissen ', 'BNT', 'system_defined', 14, 'Startdatum', 'Einddatum', dep_uuid_team_a, NOW(), NOW()),
    ('1efe21c0-0e08-631c-6c61-2782a91be5a6', 'Opschorting ', 'Opgeschort', 'end_undefined', 0, 'Startdatum', 'Einddatum', dep_uuid_team_b, NOW(), NOW()),
    ('1efe21c0-0e08-631d-124b-28c49ab2aec8', 'Ingebrekestelling ', 'IGS', 'system_defined', 14, 'Startdatum', 'Einddatum', dep_uuid_team_b, NOW(), NOW()),
    ('1efe21c0-0e08-631e-0740-f13d3299065a', 'Beroep niet tijdig beslissen ', 'BNT', 'system_defined', 14, 'Startdatum', 'Einddatum', dep_uuid_team_b, NOW(), NOW()),
    ('1efe21c0-0e08-631f-cda3-fd8f9e1944a2', 'Opschorting ', 'Opgeschort', 'end_undefined', 0, 'Startdatum', 'Einddatum', dep_uuid_team_c, NOW(), NOW()),
    ('1efe21c0-0e08-6320-6823-6f24b924e79b', 'Ingebrekestelling ', 'IGS', 'system_defined', 14, 'Startdatum', 'Einddatum', dep_uuid_team_c, NOW(), NOW()),
    ('1efe21c0-0e08-6321-c6f7-289dcf2cbb3a', 'Beroep niet tijdig beslissen ', 'BNT', 'system_defined', 14, 'Startdatum', 'Einddatum', dep_uuid_team_c, NOW(), NOW()),
    ('1efe21c0-0e08-6322-7568-9f3ab124d15e', 'Opschorting ', 'Opgeschort', 'end_undefined', 0, 'Startdatum', 'Einddatum', dep_uuid_wjz_bb, NOW(), NOW()),
    ('1efe21c0-0e08-6323-b3d8-5ff35280145a', 'Ingebrekestelling ', 'IGS', 'system_defined', 14, 'Startdatum', 'Einddatum', dep_uuid_wjz_bb, NOW(), NOW()),
    ('1efe21c0-0e08-6324-5d29-ea3f31672d6b', 'Beroep niet tijdig beslissen ', 'BNT', 'system_defined', 14, 'Startdatum', 'Einddatum', dep_uuid_wjz_bb, NOW(), NOW());

    TRUNCATE  "petition_type_phase";
    INSERT INTO "petition_type_phase" ("phase_id", "petition_type_id") VALUES
    ('1efe21c0-0e08-6319-8991-72da2c4d5c74', '1efe21c0-0e05-6c1b-73a0-c2053da2aea7'),
    ('1efe21c0-0e08-631a-4239-f2e9856a48e5', '1efe21c0-0e05-6c1b-73a0-c2053da2aea7'),
    ('1efe21c0-0e08-631b-6185-d624eccaf57b', '1efe21c0-0e05-6c1b-73a0-c2053da2aea7'),
    ('1efe21c0-0e08-631c-6c61-2782a91be5a6', '1efe21c0-0e05-6c1c-174d-9dbcbabed1fd'),
    ('1efe21c0-0e08-631d-124b-28c49ab2aec8', '1efe21c0-0e05-6c1c-174d-9dbcbabed1fd'),
    ('1efe21c0-0e08-631e-0740-f13d3299065a', '1efe21c0-0e05-6c1c-174d-9dbcbabed1fd'),
    ('1efe21c0-0e08-631f-cda3-fd8f9e1944a2', '1efe21c0-0e05-6c1d-a4f9-403891683d1a'),
    ('1efe21c0-0e08-631f-cda3-fd8f9e1944a2', '1efe21c0-0e05-6c1e-385e-e65fc6e4b3b5'),
    ('1efe21c0-0e08-631f-cda3-fd8f9e1944a2', '1efe21c0-0e05-6c1f-540b-17ffa7f9494b'),
    ('1efe21c0-0e08-631f-cda3-fd8f9e1944a2', '1efe21c0-0e05-6c20-cfaf-2a6884778e05'),
    ('1efe21c0-0e08-631f-cda3-fd8f9e1944a2', '1efe21c0-0e05-6c21-740e-5b0e14d07757'),
    ('1efe21c0-0e08-6320-6823-6f24b924e79b', '1efe21c0-0e05-6c1d-a4f9-403891683d1a'),
    ('1efe21c0-0e08-6320-6823-6f24b924e79b', '1efe21c0-0e05-6c1e-385e-e65fc6e4b3b5'),
    ('1efe21c0-0e08-6320-6823-6f24b924e79b', '1efe21c0-0e05-6c1f-540b-17ffa7f9494b'),
    ('1efe21c0-0e08-6320-6823-6f24b924e79b', '1efe21c0-0e05-6c20-cfaf-2a6884778e05'),
    ('1efe21c0-0e08-6320-6823-6f24b924e79b', '1efe21c0-0e05-6c21-740e-5b0e14d07757'),
    ('1efe21c0-0e08-6321-c6f7-289dcf2cbb3a', '1efe21c0-0e05-6c1d-a4f9-403891683d1a'),
    ('1efe21c0-0e08-6321-c6f7-289dcf2cbb3a', '1efe21c0-0e05-6c1e-385e-e65fc6e4b3b5'),
    ('1efe21c0-0e08-6321-c6f7-289dcf2cbb3a', '1efe21c0-0e05-6c1f-540b-17ffa7f9494b'),
    ('1efe21c0-0e08-6321-c6f7-289dcf2cbb3a', '1efe21c0-0e05-6c20-cfaf-2a6884778e05'),
    ('1efe21c0-0e08-6321-c6f7-289dcf2cbb3a', '1efe21c0-0e05-6c21-740e-5b0e14d07757'),
    ('1efe21c0-0e08-6322-7568-9f3ab124d15e', '1efe21c0-0e05-6c22-0d63-53f9b2e8bca4'),
    ('1efe21c0-0e08-6322-7568-9f3ab124d15e', '1efe21c0-0e05-6c23-d703-956dfba84fc9'),
    ('1efe21c0-0e08-6322-7568-9f3ab124d15e', '1efe21c0-0e05-6c24-a96a-16604abee71d'),
    ('1efe21c0-0e08-6322-7568-9f3ab124d15e', '1efe21c0-0e05-6c25-49bc-30e3c3f69b94'),
    ('1efe21c0-0e08-6322-7568-9f3ab124d15e', '1efe21c0-0e05-6c26-3b9f-f69e7ef42ec5'),
    ('1efe21c0-0e08-6323-b3d8-5ff35280145a', '1efe21c0-0e05-6c22-0d63-53f9b2e8bca4'),
    ('1efe21c0-0e08-6323-b3d8-5ff35280145a', '1efe21c0-0e05-6c23-d703-956dfba84fc9'),
    ('1efe21c0-0e08-6323-b3d8-5ff35280145a', '1efe21c0-0e05-6c24-a96a-16604abee71d'),
    ('1efe21c0-0e08-6323-b3d8-5ff35280145a', '1efe21c0-0e05-6c25-49bc-30e3c3f69b94'),
    ('1efe21c0-0e08-6323-b3d8-5ff35280145a', '1efe21c0-0e05-6c26-3b9f-f69e7ef42ec5'),
    ('1efe21c0-0e08-6324-5d29-ea3f31672d6b', '1efe21c0-0e05-6c22-0d63-53f9b2e8bca4'),
    ('1efe21c0-0e08-6324-5d29-ea3f31672d6b', '1efe21c0-0e05-6c23-d703-956dfba84fc9'),
    ('1efe21c0-0e08-6324-5d29-ea3f31672d6b', '1efe21c0-0e05-6c24-a96a-16604abee71d'),
    ('1efe21c0-0e08-6324-5d29-ea3f31672d6b', '1efe21c0-0e05-6c25-49bc-30e3c3f69b94'),
    ('1efe21c0-0e08-6324-5d29-ea3f31672d6b', '1efe21c0-0e05-6c26-3b9f-f69e7ef42ec5');

    TRUNCATE  "policy_departments";
    INSERT INTO "policy_departments" ("id", "name", "created_at", "updated_at") VALUES
    ('1efe21c0-0e08-633a-edd3-25a912ec0f7f', 'RIVM', NOW(), NOW()),
    ('1efe21c0-0e08-633b-97a1-8122b03fe47a', 'CIBG', NOW(), NOW());

    TRUNCATE  "petition_type_custom_dates_labels";
    INSERT INTO "petition_type_custom_dates_labels" ("petition_type_id", "date_label", "created_at", "updated_at") VALUES
    ('1efe21c0-0e05-6c1b-73a0-c2053da2aea7', 'date_ruling', NOW(), NOW()),
    ('1efe21c0-0e05-6c1b-73a0-c2053da2aea7', 'date_settlement_without_decision', NOW(), NOW()),
    ('1efe21c0-0e05-6c1b-73a0-c2053da2aea7', 'date_appointment_with_applicant', NOW(), NOW()),
    ('1efe21c0-0e05-6c1c-174d-9dbcbabed1fd', 'date_ruling', NOW(), NOW()),
    ('1efe21c0-0e05-6c1c-174d-9dbcbabed1fd', 'date_settlement_without_decision', NOW(), NOW()),
    ('1efe21c0-0e05-6c1c-174d-9dbcbabed1fd', 'date_appointment_with_applicant', NOW(), NOW()),
    ('1efe21c0-0e05-6c1d-a4f9-403891683d1a', 'date_ruling', NOW(), NOW()),
    ('1efe21c0-0e05-6c1d-a4f9-403891683d1a', 'date_withdrawn', NOW(), NOW()),
    ('1efe21c0-0e05-6c1e-385e-e65fc6e4b3b5', 'date_ruling', NOW(), NOW()),
    ('1efe21c0-0e05-6c1e-385e-e65fc6e4b3b5', 'date_withdrawn', NOW(), NOW()),
    ('1efe21c0-0e05-6c1f-540b-17ffa7f9494b', 'date_ruling', NOW(), NOW()),
    ('1efe21c0-0e05-6c1f-540b-17ffa7f9494b', 'date_withdrawn', NOW(), NOW()),
    ('1efe21c0-0e05-6c20-cfaf-2a6884778e05', 'date_ruling', NOW(), NOW()),
    ('1efe21c0-0e05-6c20-cfaf-2a6884778e05', 'date_withdrawn', NOW(), NOW()),
    ('1efe21c0-0e05-6c21-740e-5b0e14d07757', 'date_ruling', NOW(), NOW()),
    ('1efe21c0-0e05-6c21-740e-5b0e14d07757', 'date_withdrawn', NOW(), NOW()),
    ('1efe21c0-0e05-6c22-0d63-53f9b2e8bca4', 'date_ruling', NOW(), NOW()),
    ('1efe21c0-0e05-6c22-0d63-53f9b2e8bca4', 'date_withdrawn', NOW(), NOW()),
    ('1efe21c0-0e05-6c23-d703-956dfba84fc9', 'date_ruling', NOW(), NOW()),
    ('1efe21c0-0e05-6c23-d703-956dfba84fc9', 'date_withdrawn', NOW(), NOW()),
    ('1efe21c0-0e05-6c24-a96a-16604abee71d', 'date_ruling', NOW(), NOW()),
    ('1efe21c0-0e05-6c24-a96a-16604abee71d', 'date_withdrawn', NOW(), NOW()),
    ('1efe21c0-0e05-6c25-49bc-30e3c3f69b94', 'date_ruling', NOW(), NOW()),
    ('1efe21c0-0e05-6c25-49bc-30e3c3f69b94', 'date_withdrawn', NOW(), NOW()),
    ('1efe21c0-0e05-6c26-3b9f-f69e7ef42ec5', 'date_ruling', NOW(), NOW()),
    ('1efe21c0-0e05-6c26-3b9f-f69e7ef42ec5', 'date_withdrawn', NOW(), NOW());

    TRUNCATE "custom_petition_properties";
    INSERT INTO "custom_petition_properties" ("id", "petition_type_id", "name", "type", "ordering", "created_at", "updated_at") VALUES
    ('1efe21c0-0e0d-6151-c357-9787b9fb0227', '1efe21c0-0e05-6c1b-73a0-c2053da2aea7', 'Eigenschappen', 'name', 1, NOW(), NOW()),
    ('1efe21c0-0e0d-6152-f565-3de02b62015b', '1efe21c0-0e05-6c1b-73a0-c2053da2aea7', 'Datumafspraak doorgeven', 'title', 2, NOW(), NOW()),
    ('1efe21c0-0e0d-6153-701e-9898690f33f5', '1efe21c0-0e05-6c1b-73a0-c2053da2aea7', 'Datumafspraak', 'subtitle', 3, NOW(), NOW()),
    ('1efe21c0-0e0d-6154-5f03-9ddbab979ef0', '1efe21c0-0e05-6c1b-73a0-c2053da2aea7', 'In overleg met verzoeker', 'option', 4, NOW(), NOW()),
    ('1efe21c0-0e0d-6155-c5ee-d8e2a589caf8', '1efe21c0-0e05-6c1b-73a0-c2053da2aea7', 'Termijnaanpassing doorgeven', 'title', 5, NOW(), NOW()),
    ('1efe21c0-0e0d-6156-cf68-de1c0b6b9527', '1efe21c0-0e05-6c1b-73a0-c2053da2aea7', 'Termijnaanpassing', 'subtitle', 6, NOW(), NOW()),
    ('1efe21c0-0e0d-6157-61b5-014ae4758b88', '1efe21c0-0e05-6c1b-73a0-c2053da2aea7', 'Verdaging', 'option', 7, NOW(), NOW()),
    ('1efe21c0-0e0d-6158-1f47-a5d9bd7a127e', '1efe21c0-0e05-6c1b-73a0-c2053da2aea7', 'Uitkomst doorgeven', 'title', 8, NOW(), NOW()),
    ('1efe21c0-0e0d-6159-49c6-e6f6ea5a7da2', '1efe21c0-0e05-6c1b-73a0-c2053da2aea7', 'Zaak afdoen met besluit', 'subtitle', 9, NOW(), NOW()),
    ('1efe21c0-0e0d-615a-3c4b-31532db7438b', '1efe21c0-0e05-6c1b-73a0-c2053da2aea7', 'Een besluit', 'option', 10, NOW(), NOW()),
    ('1efe21c0-0e0d-615b-be24-99964bdf4981', '1efe21c0-0e05-6c1b-73a0-c2053da2aea7', 'Deelbesluiten', 'option', 11, NOW(), NOW()),
    ('1efe21c0-0e0d-615c-f645-4474b95d4ce0', '1efe21c0-0e05-6c1b-73a0-c2053da2aea7', 'Afwijsbesluit', 'option', 12, NOW(), NOW()),
    ('1efe21c0-0e0d-615d-097e-e968f51c3f7e', '1efe21c0-0e05-6c1b-73a0-c2053da2aea7', 'Buiten behandeling stellen', 'option', 13, NOW(), NOW()),
    ('1efe21c0-0e0d-615e-6754-8aac4dc19bf6', '1efe21c0-0e05-6c1b-73a0-c2053da2aea7', 'Zaak afdoen zonder besluit', 'subtitle', 14, NOW(), NOW()),
    ('1efe21c0-0e0d-615f-330b-bd817014e25d', '1efe21c0-0e05-6c1b-73a0-c2053da2aea7', 'Verzoek ingetrokken', 'option', 15, NOW(), NOW()),
    ('1efe21c0-0e0d-6160-8325-18d933ffbb13', '1efe21c0-0e05-6c1b-73a0-c2053da2aea7', 'Verzoek doorverwezen', 'option', 16, NOW(), NOW()),
    ('1efe21c0-0e0d-6161-0947-bef100c3fe67', '1efe21c0-0e05-6c1b-73a0-c2053da2aea7', 'Verzoek betrof bij nader inzien burgervraag', 'option', 17, NOW(), NOW()),
    ('1efe21c0-0e0d-6162-8a0d-c01a6b4cf9c1', '1efe21c0-0e05-6c1b-73a0-c2053da2aea7', 'Verzoek betrof reeds openbare informatie', 'option', 18, NOW(), NOW()),
    ('1efe21c0-0e0d-6163-fc13-dde83a14df80', '1efe21c0-0e05-6c1b-73a0-c2053da2aea7', 'Anders', 'option', 19, NOW(), NOW()),
    ('1efe21c0-0e0d-6164-686d-3d655705833b', '1efe21c0-0e05-6c1b-73a0-c2053da2aea7', 'Zwaarte doorgeven', 'title', 20, NOW(), NOW()),
    ('1efe21c0-0e0d-6165-3cd2-b89993614389', '1efe21c0-0e05-6c1b-73a0-c2053da2aea7', 'Zwaarte', 'subtitle', 21, NOW(), NOW()),
    ('1efe21c0-0e0d-6166-d1e2-a91a31c955fa', '1efe21c0-0e05-6c1b-73a0-c2053da2aea7', 'A', 'option', 22, NOW(), NOW()),
    ('1efe21c0-0e0d-6167-1e9b-814c13d67950', '1efe21c0-0e05-6c1b-73a0-c2053da2aea7', 'B', 'option', 23, NOW(), NOW()),
    ('1efe21c0-0e0d-6168-f19d-dc8db3a8199a', '1efe21c0-0e05-6c1b-73a0-c2053da2aea7', 'C', 'option', 24, NOW(), NOW()),
    ('1efe21c0-0e0d-6169-6cc2-87c3a4eeefb4', '1efe21c0-0e05-6c1b-73a0-c2053da2aea7', 'D', 'option', 25, NOW(), NOW()),
    ('1efe21c0-0e0f-6840-1455-eadaaacafce8', '1efe21c0-0e05-6c1b-73a0-c2053da2aea7', 'E', 'option', 26, NOW(), NOW()),
    ('1efe21c0-0e0f-6841-c7ea-63766ae032ec', '1efe21c0-0e05-6c1c-174d-9dbcbabed1fd', 'Eigenschappen', 'name', 1, NOW(), NOW()),
    ('1efe21c0-0e0f-6842-0fca-a1c958907aa4', '1efe21c0-0e05-6c1c-174d-9dbcbabed1fd', 'Datumafspraak doorgeven', 'title', 2, NOW(), NOW()),
    ('1efe21c0-0e0f-6843-814a-4cfea31bca68', '1efe21c0-0e05-6c1c-174d-9dbcbabed1fd', 'Datumafspraak', 'subtitle', 3, NOW(), NOW()),
    ('1efe21c0-0e0f-6844-6f8a-358ee6324b6d', '1efe21c0-0e05-6c1c-174d-9dbcbabed1fd', 'In overleg met verzoeker', 'option', 4, NOW(), NOW()),
    ('1efe21c0-0e0f-6845-5796-0b4d4134f9a9', '1efe21c0-0e05-6c1c-174d-9dbcbabed1fd', 'Termijnaanpassing doorgeven', 'title', 5, NOW(), NOW()),
    ('1efe21c0-0e0f-6846-b6c7-ea2284171b22', '1efe21c0-0e05-6c1c-174d-9dbcbabed1fd', 'Termijnaanpassing', 'subtitle', 6, NOW(), NOW()),
    ('1efe21c0-0e0f-6847-0d67-adf738b5b0b1', '1efe21c0-0e05-6c1c-174d-9dbcbabed1fd', 'Verdaging', 'option', 7, NOW(), NOW()),
    ('1efe21c0-0e0f-6848-a57f-043b420c393a', '1efe21c0-0e05-6c1c-174d-9dbcbabed1fd', 'Uitkomst doorgeven', 'title', 8, NOW(), NOW()),
    ('1efe21c0-0e0f-6849-7a39-d36da3c48876', '1efe21c0-0e05-6c1c-174d-9dbcbabed1fd', 'Zaak afdoen met besluit', 'subtitle', 9, NOW(), NOW()),
    ('1efe21c0-0e0f-684a-bcb5-bafd73d02cff', '1efe21c0-0e05-6c1c-174d-9dbcbabed1fd', 'Een besluit', 'option', 10, NOW(), NOW()),
    ('1efe21c0-0e0f-684b-ae69-e1ab207e1def', '1efe21c0-0e05-6c1c-174d-9dbcbabed1fd', 'Deelbesluiten', 'option', 11, NOW(), NOW()),
    ('1efe21c0-0e0f-684c-94a2-31fdde40c299', '1efe21c0-0e05-6c1c-174d-9dbcbabed1fd', 'Afwijsbesluit', 'option', 12, NOW(), NOW()),
    ('1efe21c0-0e0f-684d-4760-e207a7e2863a', '1efe21c0-0e05-6c1c-174d-9dbcbabed1fd', 'Buiten behandeling stellen', 'option', 13, NOW(), NOW()),
    ('1efe21c0-0e0f-684e-ef5e-0f8182b72462', '1efe21c0-0e05-6c1c-174d-9dbcbabed1fd', 'Zaak afdoen zonder besluit', 'subtitle', 14, NOW(), NOW()),
    ('1efe21c0-0e0f-684f-ca40-803a44248554', '1efe21c0-0e05-6c1c-174d-9dbcbabed1fd', 'Verzoek ingetrokken', 'option', 15, NOW(), NOW()),
    ('1efe21c0-0e0f-6850-6ce0-b23ad1bc32e6', '1efe21c0-0e05-6c1c-174d-9dbcbabed1fd', 'Verzoek doorverwezen', 'option', 16, NOW(), NOW()),
    ('1efe21c0-0e0f-6851-6e00-5bfceb9188dd', '1efe21c0-0e05-6c1c-174d-9dbcbabed1fd', 'Verzoek betrof bij nader inzien burgervraag', 'option', 17, NOW(), NOW()),
    ('1efe21c0-0e0f-6852-5e26-979af055398e', '1efe21c0-0e05-6c1c-174d-9dbcbabed1fd', 'Verzoek betrof reeds openbare informatie', 'option', 18, NOW(), NOW()),
    ('1efe21c0-0e0f-6853-80f7-1be00006f724', '1efe21c0-0e05-6c1c-174d-9dbcbabed1fd', 'Anders', 'option', 19, NOW(), NOW()),
    ('1efe21c0-0e0f-6854-b600-f460afc15960', '1efe21c0-0e05-6c1c-174d-9dbcbabed1fd', 'Zwaarte doorgeven', 'title', 20, NOW(), NOW()),
    ('1efe21c0-0e0f-6855-c2ca-4a0a4adef404', '1efe21c0-0e05-6c1c-174d-9dbcbabed1fd', 'Zwaarte', 'subtitle', 21, NOW(), NOW()),
    ('1efe21c0-0e0f-6856-e510-0ba72162bfc3', '1efe21c0-0e05-6c1c-174d-9dbcbabed1fd', 'A', 'option', 22, NOW(), NOW()),
    ('1efe21c0-0e0f-6857-4d30-be658f5bb241', '1efe21c0-0e05-6c1c-174d-9dbcbabed1fd', 'B', 'option', 23, NOW(), NOW()),
    ('1efe21c0-0e0f-6858-74c2-a63c22355b99', '1efe21c0-0e05-6c1c-174d-9dbcbabed1fd', 'C', 'option', 24, NOW(), NOW()),
    ('1efe21c0-0e0f-6859-8465-0473e5cdd94a', '1efe21c0-0e05-6c1c-174d-9dbcbabed1fd', 'D', 'option', 25, NOW(), NOW()),
    ('1efe21c0-0e0f-685a-efd0-05ba1f4ee7d5', '1efe21c0-0e05-6c1c-174d-9dbcbabed1fd', 'E', 'option', 26, NOW(), NOW()),
    ('1efe21c0-0e0a-6a25-2d21-9d0adfc588e0', '1efe21c0-0e05-6c1d-a4f9-403891683d1a','Uitkomst', 'name', 1, NOW(), NOW()),
    ('1efe21c0-0e0a-6a26-59e0-ff697c6d53ac', '1efe21c0-0e05-6c1d-a4f9-403891683d1a','Uitkomst doorgeven', 'title', 2, NOW(), NOW()),
    ('1efe21c0-0e0a-6a27-c516-098a01d3ff19', '1efe21c0-0e05-6c1d-a4f9-403891683d1a','Uitspraak', 'subtitle', 3, NOW(), NOW()),
    ('1efe21c0-0e0a-6a28-008f-17202d5cbef9', '1efe21c0-0e05-6c1d-a4f9-403891683d1a','Gegrond', 'option', 4, NOW(), NOW()),
    ('1efe21c0-0e0a-6a29-87fb-0bb57bdfed48', '1efe21c0-0e05-6c1d-a4f9-403891683d1a','Ongegrond', 'option', 5, NOW(), NOW()),
    ('1efe21c0-0e0a-6a2a-1529-72fe69597eff', '1efe21c0-0e05-6c1d-a4f9-403891683d1a','Intrekking', 'option', 6, NOW(), NOW()),
    ('1efe21c0-0e0a-6a2b-b188-5c752b553994', '1efe21c0-0e05-6c1d-a4f9-403891683d1a','Niet-ontvankelijk', 'option', 7, NOW(), NOW()),
    ('1efe21c0-0e0a-6a2c-cf55-d1ad3a31cd9f', '1efe21c0-0e05-6c1d-a4f9-403891683d1a','Kennelijk niet-ontvankelijk', 'option', 8, NOW(), NOW()),
    ('1efe21c0-0e0a-6a2d-9ba4-93734385dd11', '1efe21c0-0e05-6c1e-385e-e65fc6e4b3b5', 'Uitkomst', 'name', 1, NOW(), NOW()),
    ('1efe21c0-0e0a-6a2e-2ad8-2922d7e2dcba', '1efe21c0-0e05-6c1e-385e-e65fc6e4b3b5', 'Uitkomst doorgeven', 'title', 2, NOW(), NOW()),
    ('1efe21c0-0e0a-6a2f-8163-80f2495c176d', '1efe21c0-0e05-6c1e-385e-e65fc6e4b3b5', 'Uitspraak', 'subtitle', 3, NOW(), NOW()),
    ('1efe21c0-0e0a-6a30-500e-b3d1a00f215f', '1efe21c0-0e05-6c1e-385e-e65fc6e4b3b5', 'Gegrond', 'option', 4, NOW(), NOW()),
    ('1efe21c0-0e0a-6a31-612c-56feae9c22c8', '1efe21c0-0e05-6c1e-385e-e65fc6e4b3b5', 'Ongegrond', 'option', 5, NOW(), NOW()),
    ('1efe21c0-0e0a-6a32-9cdf-0177d06f7403', '1efe21c0-0e05-6c1e-385e-e65fc6e4b3b5', 'Intrekking', 'option', 6, NOW(), NOW()),
    ('1efe21c0-0e0a-6a33-5194-8be84bafd2d8', '1efe21c0-0e05-6c1e-385e-e65fc6e4b3b5', 'Niet-ontvankelijk', 'option', 7, NOW(), NOW()),
    ('1efe21c0-0e0a-6a34-047e-70caa0f2002f', '1efe21c0-0e05-6c1e-385e-e65fc6e4b3b5', 'Kennelijk niet-ontvankelijk', 'option', 8, NOW(), NOW()),
    ('1efe21c0-0e0a-6a35-6973-262ede12325b', '1efe21c0-0e05-6c1f-540b-17ffa7f9494b', 'Uitkomst', 'name', 1, NOW(), NOW()),
    ('1efe21c0-0e0a-6a36-ecdd-80aaf47c5e34', '1efe21c0-0e05-6c1f-540b-17ffa7f9494b', 'Uitkomst doorgeven', 'title', 2, NOW(), NOW()),
    ('1efe21c0-0e0a-6a37-cc2c-dbb3a884b4c1', '1efe21c0-0e05-6c1f-540b-17ffa7f9494b', 'Uitspraak', 'subtitle', 3, NOW(), NOW()),
    ('1efe21c0-0e0a-6a38-26e8-920cc64ef20b', '1efe21c0-0e05-6c1f-540b-17ffa7f9494b', 'Gegrond', 'option', 4, NOW(), NOW()),
    ('1efe21c0-0e0a-6a39-ef06-c9032d495a80', '1efe21c0-0e05-6c1f-540b-17ffa7f9494b', 'Ongegrond', 'option', 5, NOW(), NOW()),
    ('1efe21c0-0e0a-6a3a-4a91-d601a8e04dbe', '1efe21c0-0e05-6c1f-540b-17ffa7f9494b', 'Intrekking', 'option', 6, NOW(), NOW()),
    ('1efe21c0-0e0a-6a3b-71a1-53db4051670e', '1efe21c0-0e05-6c1f-540b-17ffa7f9494b', 'Niet-ontvankelijk', 'option', 7, NOW(), NOW()),
    ('1efe21c0-0e0a-6a3c-5b98-2183de7641cd', '1efe21c0-0e05-6c1f-540b-17ffa7f9494b', 'Kennelijk niet-ontvankelijk', 'option', 8, NOW(), NOW()),
    ('1efe21c0-0e0a-6a3d-ed9b-f86ee3d3e9c2', '1efe21c0-0e05-6c20-cfaf-2a6884778e05', 'Uitkomst', 'name', 1, NOW(), NOW()),
    ('1efe21c0-0e0a-6a3e-1815-5fdcf1af1aa7', '1efe21c0-0e05-6c20-cfaf-2a6884778e05', 'Uitkomst doorgeven', 'title', 2, NOW(), NOW()),
    ('1efe21c0-0e0a-6a3f-9735-72dd6a1c0629', '1efe21c0-0e05-6c20-cfaf-2a6884778e05', 'Uitspraak', 'subtitle', 3, NOW(), NOW()),
    ('1efe21c0-0e0d-6130-4f1d-ee7f4232dcb9', '1efe21c0-0e05-6c20-cfaf-2a6884778e05', 'Gegrond', 'option', 4, NOW(), NOW()),
    ('1efe21c0-0e0d-6131-de38-c2b32949c4bc', '1efe21c0-0e05-6c20-cfaf-2a6884778e05', 'Ongegrond', 'option', 5, NOW(), NOW()),
    ('1efe21c0-0e0d-6132-22d2-314c4b56b88a', '1efe21c0-0e05-6c20-cfaf-2a6884778e05', 'Intrekking', 'option', 6, NOW(), NOW()),
    ('1efe21c0-0e0d-6133-4ec7-0d69bc52ac1e', '1efe21c0-0e05-6c20-cfaf-2a6884778e05', 'Niet-ontvankelijk', 'option', 7, NOW(), NOW()),
    ('1efe21c0-0e0d-6134-e6eb-66993a89044e', '1efe21c0-0e05-6c20-cfaf-2a6884778e05', 'Kennelijk niet-ontvankelijk', 'option', 8, NOW(), NOW()),
    ('1efe21c0-0e0d-6135-0b0c-ee61289a9585', '1efe21c0-0e05-6c21-740e-5b0e14d07757', 'Uitkomst', 'name', 1, NOW(), NOW()),
    ('1efe21c0-0e0d-6136-a8c1-d99e03ccd47b', '1efe21c0-0e05-6c21-740e-5b0e14d07757', 'Binnen/buiten termijn doorgeven', 'title', 2, NOW(), NOW()),
    ('1efe21c0-0e0d-6137-5a3a-1b9e49d38916', '1efe21c0-0e05-6c21-740e-5b0e14d07757', 'Binnen/buiten termijn', 'subtitle', 3, NOW(), NOW()),
    ('1efe21c0-0e0d-6138-460f-3ae4dd536143', '1efe21c0-0e05-6c21-740e-5b0e14d07757', 'Binnen wettelijke termijn', 'option', 4, NOW(), NOW()),
    ('1efe21c0-0e0d-6139-f371-030954538e5c', '1efe21c0-0e05-6c21-740e-5b0e14d07757', 'Binnen afgesproken termijn', 'option', 5, NOW(), NOW()),
    ('1efe21c0-0e0d-613a-b6df-0d0a739cda60', '1efe21c0-0e05-6c21-740e-5b0e14d07757', 'Buiten wettelijke/afgesproken termijn', 'option', 6, NOW(), NOW()),
    ('1efe21c0-0e0d-613b-28a2-70755697160d', '1efe21c0-0e05-6c21-740e-5b0e14d07757', 'Uitkomst doorgeven', 'title', 7, NOW(), NOW()),
    ('1efe21c0-0e0d-613c-6b34-53dfed2cc548', '1efe21c0-0e05-6c21-740e-5b0e14d07757', 'Dictum', 'subtitle', 8, NOW(), NOW()),
    ('1efe21c0-0e0d-613d-3e00-7e7655d40f10', '1efe21c0-0e05-6c21-740e-5b0e14d07757', 'Gegrond', 'option', 9, NOW(), NOW()),
    ('1efe21c0-0e0d-613e-49ac-78eec9e8ffbc', '1efe21c0-0e05-6c21-740e-5b0e14d07757', 'Kennelijk gegrond', 'option', 10, NOW(), NOW()),
    ('1efe21c0-0e0d-613f-70ac-bc0d001b39d9', '1efe21c0-0e05-6c21-740e-5b0e14d07757', 'Ongegrond', 'option', 11, NOW(), NOW()),
    ('1efe21c0-0e0d-6140-e849-aa7ee5f5212f', '1efe21c0-0e05-6c21-740e-5b0e14d07757', 'Kennelijk ongegrond', 'option', 12, NOW(), NOW()),
    ('1efe21c0-0e0d-6141-6c06-2809115992b0', '1efe21c0-0e05-6c21-740e-5b0e14d07757', 'Niet-ontvankelijk', 'option', 13, NOW(), NOW()),
    ('1efe21c0-0e0d-6142-679a-0b01d68f4c15', '1efe21c0-0e05-6c21-740e-5b0e14d07757', 'Kennelijk niet-ontvankelijk', 'option', 14, NOW(), NOW()),
    ('1efe21c0-0e0d-6143-6d6d-bfeb45dde403', '1efe21c0-0e05-6c21-740e-5b0e14d07757', 'Doorzending', 'subtitle', 15, NOW(), NOW()),
    ('1efe21c0-0e0d-6144-a2d3-689ea41db1b3', '1efe21c0-0e05-6c21-740e-5b0e14d07757', 'Doorzending', 'option', 16, NOW(), NOW()),
    ('1efe21c0-0e0d-6145-9da5-4c757c5ac4bb', '1efe21c0-0e05-6c21-740e-5b0e14d07757', 'Intrekking bezwaar', 'subtitle', 17, NOW(), NOW()),
    ('1efe21c0-0e0d-6146-3da8-b38152ef7fae', '1efe21c0-0e05-6c21-740e-5b0e14d07757', 'Herziening – herstel bezwaar', 'option', 18, NOW(), NOW()),
    ('1efe21c0-0e0d-6147-dae6-57711d574465', '1efe21c0-0e05-6c21-740e-5b0e14d07757', 'Herziening – herstel primair besluit', 'option', 19, NOW(), NOW()),
    ('1efe21c0-0e0d-6148-dc54-1709801bb889', '1efe21c0-0e05-6c21-740e-5b0e14d07757', 'Informeel', 'option', 20, NOW(), NOW()),
    ('1efe21c0-0e0d-6149-b2c0-978635529d8a', '1efe21c0-0e05-6c21-740e-5b0e14d07757', 'Overig', 'option', 21, NOW(), NOW()),
    ('1efe21c0-0e0d-614a-6a9f-ab5b5a996233', '1efe21c0-0e05-6c21-740e-5b0e14d07757', 'Zwaarte doorgeven', 'title', 22, NOW(), NOW()),
    ('1efe21c0-0e0d-614b-aa58-6e79744706d9', '1efe21c0-0e05-6c21-740e-5b0e14d07757', 'Zwaarte', 'subtitle', 23, NOW(), NOW()),
    ('1efe21c0-0e0d-614c-8229-0df309ca90fa', '1efe21c0-0e05-6c21-740e-5b0e14d07757', 'A', 'option', 24, NOW(), NOW()),
    ('1efe21c0-0e0d-614d-04a6-00851d1ccb3e', '1efe21c0-0e05-6c21-740e-5b0e14d07757', 'B', 'option', 25, NOW(), NOW()),
    ('1efe21c0-0e0d-614e-e355-0b3e9167fedd', '1efe21c0-0e05-6c21-740e-5b0e14d07757', 'C', 'option', 26, NOW(), NOW()),
    ('1efe21c0-0e0d-614f-2f72-628d3bca48f7', '1efe21c0-0e05-6c21-740e-5b0e14d07757', 'D', 'option', 27, NOW(), NOW()),
    ('1efe21c0-0e0d-6150-a0af-39d836bb0b6a', '1efe21c0-0e05-6c21-740e-5b0e14d07757', 'E', 'option', 28, NOW(), NOW()),
    ('1efe2210-2568-6f01-68f7-9081b04f9d3b', '1efe21c0-0e05-6c22-0d63-53f9b2e8bca4','Uitkomst', 'name', 1, NOW(), NOW()),
    ('1efe2210-2568-6f02-e4b3-60eecebab78f', '1efe21c0-0e05-6c22-0d63-53f9b2e8bca4','Uitkomst doorgeven', 'title', 2, NOW(), NOW()),
    ('1efe2210-2568-6f03-0293-53b6455152c0', '1efe21c0-0e05-6c22-0d63-53f9b2e8bca4','Uitspraak', 'subtitle', 3, NOW(), NOW()),
    ('1efe2210-2568-6f04-9f32-d813c4c3979f', '1efe21c0-0e05-6c22-0d63-53f9b2e8bca4','Gegrond', 'option', 4, NOW(), NOW()),
    ('1efe2210-2568-6f05-5714-fe5cebea8cd7', '1efe21c0-0e05-6c22-0d63-53f9b2e8bca4','Ongegrond', 'option', 5, NOW(), NOW()),
    ('1efe2210-2568-6f06-b501-2b7c7c89ffa2', '1efe21c0-0e05-6c22-0d63-53f9b2e8bca4','Intrekking', 'option', 6, NOW(), NOW()),
    ('1efe2210-2568-6f07-573e-60091e20040a', '1efe21c0-0e05-6c22-0d63-53f9b2e8bca4','Niet-ontvankelijk', 'option', 7, NOW(), NOW()),
    ('1efe2210-2568-6f08-2ec5-4fac07be2cba', '1efe21c0-0e05-6c22-0d63-53f9b2e8bca4','Kennelijk niet-ontvankelijk', 'option', 8, NOW(), NOW()),
    ('1efe2210-2568-6f09-8ac5-f2157f370bbe', '1efe21c0-0e05-6c23-d703-956dfba84fc9', 'Uitkomst', 'name', 1, NOW(), NOW()),
    ('1efe2210-2568-6f0a-6275-880f6f1ba6f4', '1efe21c0-0e05-6c23-d703-956dfba84fc9', 'Uitkomst doorgeven', 'title', 2, NOW(), NOW()),
    ('1efe2210-2568-6f0b-cf0b-d613236d068b', '1efe21c0-0e05-6c23-d703-956dfba84fc9', 'Uitspraak', 'subtitle', 3, NOW(), NOW()),
    ('1efe2210-2568-6f0c-bc7f-d03ffe6490fb', '1efe21c0-0e05-6c23-d703-956dfba84fc9', 'Gegrond', 'option', 4, NOW(), NOW()),
    ('1efe2210-2568-6f0d-6e2a-8533f8aa412d', '1efe21c0-0e05-6c23-d703-956dfba84fc9', 'Ongegrond', 'option', 5, NOW(), NOW()),
    ('1efe2210-2568-6f0e-2746-b6f0c4d2e0d4', '1efe21c0-0e05-6c23-d703-956dfba84fc9', 'Intrekking', 'option', 6, NOW(), NOW()),
    ('1efe2210-2568-6f0f-2f72-73e8f74db059', '1efe21c0-0e05-6c23-d703-956dfba84fc9', 'Niet-ontvankelijk', 'option', 7, NOW(), NOW()),
    ('1efe2210-2568-6f10-0756-b9af7de5bed0', '1efe21c0-0e05-6c23-d703-956dfba84fc9', 'Kennelijk niet-ontvankelijk', 'option', 8, NOW(), NOW()),
    ('1efe2210-2568-6f11-5920-481b5aca81c9', '1efe21c0-0e05-6c24-a96a-16604abee71d', 'Uitkomst', 'name', 1, NOW(), NOW()),
    ('1efe2210-2568-6f12-83ed-72c1238a475b', '1efe21c0-0e05-6c24-a96a-16604abee71d', 'Uitkomst doorgeven', 'title', 2, NOW(), NOW()),
    ('1efe2210-2568-6f13-9dc3-bd5c6dddabd7', '1efe21c0-0e05-6c24-a96a-16604abee71d', 'Uitspraak', 'subtitle', 3, NOW(), NOW()),
    ('1efe2210-2568-6f14-1532-a1fa4ac31468', '1efe21c0-0e05-6c24-a96a-16604abee71d', 'Gegrond', 'option', 4, NOW(), NOW()),
    ('1efe2210-2568-6f15-0630-b0e2683412cc', '1efe21c0-0e05-6c24-a96a-16604abee71d', 'Ongegrond', 'option', 5, NOW(), NOW()),
    ('1efe2210-2568-6f16-0200-e987d3d3d433', '1efe21c0-0e05-6c24-a96a-16604abee71d', 'Intrekking', 'option', 6, NOW(), NOW()),
    ('1efe2210-2568-6f17-46e2-f7bc2a88131b', '1efe21c0-0e05-6c24-a96a-16604abee71d', 'Niet-ontvankelijk', 'option', 7, NOW(), NOW()),
    ('1efe2210-2568-6f18-5444-8a290837c706', '1efe21c0-0e05-6c24-a96a-16604abee71d', 'Kennelijk niet-ontvankelijk', 'option', 8, NOW(), NOW()),
    ('1efe2210-2568-6f19-dd9f-9558da7f195c', '1efe21c0-0e05-6c25-49bc-30e3c3f69b94', 'Uitkomst', 'name', 1, NOW(), NOW()),
    ('1efe2210-2568-6f1a-6ca5-d0d7cf9f46a4', '1efe21c0-0e05-6c25-49bc-30e3c3f69b94', 'Uitkomst doorgeven', 'title', 2, NOW(), NOW()),
    ('1efe2210-2568-6f1b-b518-20079dc0585c', '1efe21c0-0e05-6c25-49bc-30e3c3f69b94', 'Uitspraak', 'subtitle', 3, NOW(), NOW()),
    ('1efe2210-2568-6f1c-65dc-4b845b4d0f25', '1efe21c0-0e05-6c25-49bc-30e3c3f69b94', 'Gegrond', 'option', 4, NOW(), NOW()),
    ('1efe2210-2568-6f1d-d396-6b7cbb8ac6ae', '1efe21c0-0e05-6c25-49bc-30e3c3f69b94', 'Ongegrond', 'option', 5, NOW(), NOW()),
    ('1efe2210-2568-6f1e-766c-a2177bfef475', '1efe21c0-0e05-6c25-49bc-30e3c3f69b94', 'Intrekking', 'option', 6, NOW(), NOW()),
    ('1efe2210-256b-6610-35ee-7e721221e907', '1efe21c0-0e05-6c25-49bc-30e3c3f69b94', 'Niet-ontvankelijk', 'option', 7, NOW(), NOW()),
    ('1efe2210-256b-6611-2bda-4fb606f21b65', '1efe21c0-0e05-6c25-49bc-30e3c3f69b94', 'Kennelijk niet-ontvankelijk', 'option', 8, NOW(), NOW()),
    ('1efe2210-256b-6612-a7e9-22d0846de9da', '1efe21c0-0e05-6c26-3b9f-f69e7ef42ec5', 'Uitkomst', 'name', 1, NOW(), NOW()),
    ('1efe2210-256b-6613-0dd8-0576d4337dad', '1efe21c0-0e05-6c26-3b9f-f69e7ef42ec5', 'Binnen/buiten termijn doorgeven', 'title', 2, NOW(), NOW()),
    ('1efe2210-256b-6614-cad8-9cc3cd3710a0', '1efe21c0-0e05-6c26-3b9f-f69e7ef42ec5', 'Binnen/buiten termijn', 'subtitle', 3, NOW(), NOW()),
    ('1efe2210-256b-6615-98b7-9e906f9274bb', '1efe21c0-0e05-6c26-3b9f-f69e7ef42ec5', 'Binnen wettelijke termijn', 'option', 4, NOW(), NOW()),
    ('1efe2210-256b-6616-f903-685ef1450b04', '1efe21c0-0e05-6c26-3b9f-f69e7ef42ec5', 'Binnen afgesproken termijn', 'option', 5, NOW(), NOW()),
    ('1efe2210-256b-6617-65df-b20f665e14c3', '1efe21c0-0e05-6c26-3b9f-f69e7ef42ec5', 'Buiten wettelijke/afgesproken termijn', 'option', 6, NOW(), NOW()),
    ('1efe2210-256b-6618-3164-c00cbaeea4b9', '1efe21c0-0e05-6c26-3b9f-f69e7ef42ec5', 'Uitkomst doorgeven', 'title', 7, NOW(), NOW()),
    ('1efe2210-256b-6619-aab1-b9bef4681af3', '1efe21c0-0e05-6c26-3b9f-f69e7ef42ec5', 'Dictum', 'subtitle', 8, NOW(), NOW()),
    ('1efe2210-256b-661a-dd81-bddfc559419e', '1efe21c0-0e05-6c26-3b9f-f69e7ef42ec5', 'Gegrond', 'option', 9, NOW(), NOW()),
    ('1efe2210-256b-661b-9791-595a347f13ba', '1efe21c0-0e05-6c26-3b9f-f69e7ef42ec5', 'Kennelijk gegrond', 'option', 10, NOW(), NOW()),
    ('1efe2210-256b-661c-1991-b51e81b1ec65', '1efe21c0-0e05-6c26-3b9f-f69e7ef42ec5', 'Ongegrond', 'option', 11, NOW(), NOW()),
    ('1efe2210-256b-661d-d60c-0df89d0c9974', '1efe21c0-0e05-6c26-3b9f-f69e7ef42ec5', 'Kennelijk ongegrond', 'option', 12, NOW(), NOW()),
    ('1efe2210-256b-661e-ae09-dce07cc0ba52', '1efe21c0-0e05-6c26-3b9f-f69e7ef42ec5', 'Niet-ontvankelijk', 'option', 13, NOW(), NOW()),
    ('1efe2210-256b-661f-f52b-e6103b68dbfd', '1efe21c0-0e05-6c26-3b9f-f69e7ef42ec5', 'Kennelijk niet-ontvankelijk', 'option', 14, NOW(), NOW()),
    ('1efe2210-256b-6620-0856-3b2c22404a4e', '1efe21c0-0e05-6c26-3b9f-f69e7ef42ec5', 'Doorzending', 'subtitle', 15, NOW(), NOW()),
    ('1efe2210-256b-6621-66f2-a758c02e8d3f', '1efe21c0-0e05-6c26-3b9f-f69e7ef42ec5', 'Doorzending', 'option', 16, NOW(), NOW()),
    ('1efe2210-256b-6622-86e9-b3806ff5cdfc', '1efe21c0-0e05-6c26-3b9f-f69e7ef42ec5', 'Intrekking bezwaar', 'subtitle', 17, NOW(), NOW()),
    ('1efe2210-256b-6623-ae27-a308e1c3f40d', '1efe21c0-0e05-6c26-3b9f-f69e7ef42ec5', 'Herziening – herstel bezwaar', 'option', 18, NOW(), NOW()),
    ('1efe2210-256b-6624-a1e9-548e3e160193', '1efe21c0-0e05-6c26-3b9f-f69e7ef42ec5', 'Herziening – herstel primair besluit', 'option', 19, NOW(), NOW()),
    ('1efe2210-256b-6625-f03e-77dc74ae0f9b', '1efe21c0-0e05-6c26-3b9f-f69e7ef42ec5', 'Informeel', 'option', 20, NOW(), NOW()),
    ('1efe2210-256b-6626-7335-6de6676de7ce', '1efe21c0-0e05-6c26-3b9f-f69e7ef42ec5', 'Overig', 'option', 21, NOW(), NOW()),
    ('1efe2210-256b-6627-b90b-ef415f44d098', '1efe21c0-0e05-6c26-3b9f-f69e7ef42ec5', 'Zwaarte doorgeven', 'title', 22, NOW(), NOW()),
    ('1efe2210-256b-6628-5683-4aede9bc2462', '1efe21c0-0e05-6c26-3b9f-f69e7ef42ec5', 'Zwaarte', 'subtitle', 23, NOW(), NOW()),
    ('1efe2210-256d-6d20-3f8b-34afdb15dc0b', '1efe21c0-0e05-6c26-3b9f-f69e7ef42ec5', 'A', 'option', 24, NOW(), NOW()),
    ('1efe2210-256d-6d21-3a5f-52413d2af039', '1efe21c0-0e05-6c26-3b9f-f69e7ef42ec5', 'B', 'option', 25, NOW(), NOW()),
    ('1efe2210-256d-6d22-d76b-14a599ffd06b', '1efe21c0-0e05-6c26-3b9f-f69e7ef42ec5', 'C', 'option', 26, NOW(), NOW()),
    ('1efe2210-256d-6d23-1ff5-25b6cd9102f2', '1efe21c0-0e05-6c26-3b9f-f69e7ef42ec5', 'D', 'option', 27, NOW(), NOW()),
    ('1efe2210-256d-6d24-3e18-f31407773154', '1efe21c0-0e05-6c26-3b9f-f69e7ef42ec5', 'E', 'option', 28, NOW(), NOW());

END $$
