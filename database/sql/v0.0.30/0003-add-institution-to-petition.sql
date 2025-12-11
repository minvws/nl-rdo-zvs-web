ALTER TABLE "petitions"
     ADD COLUMN "institution_id" uuid NULL REFERENCES "contacts" (id);