-- A file for seeding PolicyDepartments in the Zaakvolgsysteem environments
-- Version: Thu 31 Nov 2024 13:25
-- DB version: v0.0.13

TRUNCATE  "policy_departments";

INSERT INTO "policy_departments" ("id", "name", "created_at", "updated_at") VALUES
('0193257c-15f7-702b-a84e-166c951445bf', 'RIVM', NOW(), NOW()),
('0193257c-15f8-708c-9498-0a840a8c1b13', 'CIBG', NOW(), NOW());
