-- A file for seeding Departments in the Zaakvolgsysteem environments
-- Version: Thu 10 Oct 2024 17:18
-- DB version: v0.0.13

INSERT INTO "departments" ("id", "name", "slug", "abbreviation", "created_at", "updated_at") VALUES
('019275e3-45ac-73d8-9dcd-db2dab32952f', 'Cluster 1', 'c1', 'C1', NOW(), NOW()),
('019275e3-4597-71c7-b9dd-78ae49e144f8', 'Cluster 4', 'c4', 'C4', NOW(), NOW()),
('019275e3-4598-70ec-8b9f-c6ace68ccded', 'Programmadirectie Openbaarheid', 'pdo', 'PDO', NOW(), NOW());
