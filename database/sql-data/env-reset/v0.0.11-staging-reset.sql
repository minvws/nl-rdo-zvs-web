-- A cleanup file for the Zaakvolgsysteem Test and Staging environments
-- Version: Fri 4 Oct 2024 13:38
-- DB version: v0.0.11

-- IMPORTANT! All entries and their properties are fake and generated!
-- This file does not and will never contain references to real people or actual petitions.

TRUNCATE  "active_phase_delete_occurences";
TRUNCATE  "active_phase_occurrences";
TRUNCATE  "active_phase_rollback_occurences";
TRUNCATE  "active_phases";
TRUNCATE  "active_phases_deadline_shifts";
TRUNCATE  "assignment_occurrences";
TRUNCATE  "attachments";
TRUNCATE  "contact_petition";
TRUNCATE  "contacts";
TRUNCATE  "notes";
TRUNCATE  "petition_statuses";
TRUNCATE  "petition_type_phase";
TRUNCATE  "petition_types";
TRUNCATE  "petitions";
TRUNCATE  "phases";
TRUNCATE  "public_holidays";
TRUNCATE  "status_occurrences";
TRUNCATE  "term_adjustment_occurrences";
TRUNCATE  "term_adjustments";
TRUNCATE  "timeline_items";

INSERT INTO "contact_petition" ("contact_id", "petition_id", "type") VALUES
('0192573b-1a38-711d-be28-767e64703414', '0192573b-1726-71b5-a0e4-572235558173', 'applicant'),
('0192573b-1a3c-718a-9c51-7e500f199be3', '0192573b-1726-71b5-a0e4-572235558173', 'representative');

INSERT INTO "contacts" ("id", "initials", "last_name", "organisation_name", "email_address", "phone_number", "street", "house_number", "postal_code", "city", "is_journalist", "created_at", "updated_at") VALUES
('0192573b-1a38-711d-be28-767e64703414', 'C.', 'de Vries', '', 'c.devries@rdobeheer.nl', '054 2542155', NULL, NULL, '1531CZ', 'Achtmaal', 'f', '2024-10-04 11:12:24+00', '2024-10-04 11:12:24+00'),
('0192573b-1a3a-7220-823f-8e58efd3b515', 'S.', 'Hendriks', 'VNO-NCW', 's.hendriks@rdobeheer.nl', NULL, 'van den Eerenbeemtdreef', NULL, '9354VD', NULL, 'f', '2024-10-04 11:12:24+00', '2024-10-04 11:12:24+00'),
('0192573b-1a3c-718a-9c51-7e500f199be3', 'T.', 'Lemmens', 'AGA Juristen', 't.lemmens@rdobeheer.nl', NULL, NULL, NULL, '4382JC', NULL, 'f', '2024-10-04 11:12:24+00', '2024-10-04 11:12:24+00'),
('0192573b-1a3d-73df-9ba2-45212bb73d67', 'J.', 'Maes', 'NRC', 'j.maes@rdobeheer.nl', '0900 671596', 'Schipperhof', '18-71', NULL, 'Tuil', 'f', '2024-10-04 11:12:24+00', '2024-10-04 11:12:24+00');

INSERT INTO "petition_statuses" ("id", "status", "created_at", "updated_at", "badge", "default_status") VALUES
('0192573b-145c-73c8-963f-e24176312090', 'Opgeschort', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00', 'danger', 'f'),
('0192573b-1475-7052-8b34-c314d33e0bcf', 'In behandeling', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00', 'warning', 'f'),
('0192573b-1477-7100-949f-50be988ce068', 'Afgehandeld', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00', 'success', 'f'),
('0192573b-148f-7075-b0f2-76c268c0d61a', 'Nieuw', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00', 'info', 't');

INSERT INTO "petition_type_phase" ("phase_id", "petition_type_id") VALUES
('0192573b-14b2-7144-88d2-f290b95a4a67', '0192573b-1496-70c1-abf7-793da7ffdb87'),
('0192573b-14b2-7144-88d2-f290b95a4a67', '0192573b-1498-70bb-ab19-4565cc461ef6'),
('0192573b-14b2-7144-88d2-f290b95a4a67', '0192573b-149a-70ab-a9f8-5db6171f6f1c');

INSERT INTO "petition_types" ("id", "name", "created_at", "updated_at") VALUES
('0192573b-1496-70c1-abf7-793da7ffdb87', 'Beroep', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00'),
('0192573b-1498-70bb-ab19-4565cc461ef6', 'Bezwaar', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00'),
('0192573b-149a-70ab-a9f8-5db6171f6f1c', 'Woo verzoek', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00');

INSERT INTO "petitions" ("id", "created_at", "updated_at", "petition_type_id", "number", "name", "description", "date_of_entry", "assigned_to", "petition_status_id", "deadline_at") VALUES
('0192573b-1726-71b5-a0e4-572235558173', '2024-10-04 11:12:23+00', '2024-10-04 11:12:23+00', '0192573b-149a-70ab-a9f8-5db6171f6f1c', '0192573b-1726-71b5-a0e4-572235558173', 'Covid, Vaccinaties, RIVM, Coronamelder', 'Verzoek voor inzage communicatie omtrent Coronamelder en persoonsgegevens.', '2024-09-01', NULL, '0192573b-1475-7052-8b34-c314d33e0bcf', '2024-11-01'),
('0192573b-1a36-7031-88eb-36684bf13935', '2024-10-04 11:12:24+00', '2024-10-04 11:12:24+00', '0192573b-149a-70ab-a9f8-5db6171f6f1c', '0192573b-1a36-7031-88eb-36684bf13935', 'Halix, mondkapjes en hulptroepen.nu', 'Verzoek inzage communicatie mondkapjes.', '2024-09-01', NULL, '0192573b-148f-7075-b0f2-76c268c0d61a', '2024-11-01'),
('0192573b-1a36-7031-88eb-4565cc461ef6', '2024-10-04 11:12:24+00', '2024-10-04 11:12:24+00', '0192573b-149a-70ab-a9f8-5db6171f6f1c', '0192573b-1a36-7031-88eb-4565cc461ef6', 'De totstandkoming van de Baangerelateerde Investeringskorting (BIK)', '', '2024-09-01', NULL, '0192573b-148f-7075-b0f2-76c268c0d61a', '2024-11-01');

INSERT INTO "phases" ("id", "name", "status_label", "type", "period_in_days", "start_date_label", "has_end_date", "end_date_label", "created_at", "updated_at") VALUES
('0192573b-14b2-7144-88d2-f290b95a4a67', 'Beroep niet tijdig beslissen', 'BNT', 'end_required', 14, 'Datum uitspraak rechter', 't', 'Nieuwe deadline', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00');

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

INSERT INTO "term_adjustments" ("id", "name", "period_in_days", "can_enter_date_manually", "created_at", "updated_at") VALUES
('0192573b-14a5-7004-9d65-6eaf0835bdf7', 'Verdaging', 14, 't', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00'),
('0192573b-14a9-7392-a2f5-b9c371d02c04', 'Afspraak met verzoeker', 0, 't', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00'),
('0192573b-14ab-7196-b308-676b04bb1383', 'Anders, namelijk:', 0, 't', '2024-10-04 11:12:22+00', '2024-10-04 11:12:22+00');
