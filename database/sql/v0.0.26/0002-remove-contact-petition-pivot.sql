DROP TABLE "contact_petition";

ALTER TABLE "petitions"
    ADD COLUMN "applicant_id" uuid NULL REFERENCES "contacts" (id) ON DELETE SET NULL,
    ADD COLUMN "representative_id" uuid NULL REFERENCES "contacts" (id) ON DELETE SET NULL
;