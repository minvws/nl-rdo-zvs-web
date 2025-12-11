ALTER TABLE
    "petition_exports"
    ADD COLUMN "type" VARCHAR(50) NOT NULL DEFAULT 'dashboard',
    ALTER COLUMN "filters" SET DATA TYPE JSONB USING '{}'::JSONB
;