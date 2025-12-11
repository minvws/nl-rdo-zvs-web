-- 0016-migrate-petition-contact-ids-to-pivot.sql
-- Migreert bestaande applicant_id, representative_id en institution_id relaties naar contact_petition pivot table

-- Applicant migratie
INSERT INTO contact_petition (contact_id, petition_id, role)
SELECT applicant_id, id, 'applicant'
FROM petitions
WHERE applicant_id IS NOT NULL;

-- Representative migratie
INSERT INTO contact_petition (contact_id, petition_id, role)
SELECT representative_id, id, 'representative'
FROM petitions
WHERE representative_id IS NOT NULL;

-- Institution migratie
INSERT INTO contact_petition (contact_id, petition_id, role)
SELECT institution_id, id, 'institution'
FROM petitions
WHERE institution_id IS NOT NULL;
